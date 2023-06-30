<?php 

/**
 * Name:    Модель для работы с YooKassa
 *
 * Created:  22.05.2021
 *
 * Description:  
 *
 * Requirements: PHP 7.2 or above
 *
 * @author     Krotov Roman <tg: @KrotovRoman>
 * @docs https://yookassa.ru/developers/api?lang=php#create_payment
 * @src https://github.com/yoomoney/yookassa-sdk-php/blob/master/docs/examples/02-payments.md#%D0%97%D0%B0%D0%BF%D1%80%D0%BE%D1%81-%D0%BD%D0%B0-%D1%81%D0%BE%D0%B7%D0%B4%D0%B0%D0%BD%D0%B8%D0%B5-%D0%BF%D0%BB%D0%B0%D1%82%D0%B5%D0%B6%D0%B0
 */
namespace Yookassa\Models;

use CodeIgniter\Model;
use \CodeIgniter\Database\ConnectionInterface;

use YooKassa\Client;

/**
 * Class YookassaModel
 */
class YookassaModel
{
	/**
	 * Database object
	 *
	 * @var \CodeIgniter\Database\BaseConnection
	 */
	protected $db, $client;
    public $id_pay = 9;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->db = \Config\Database::connect();

        $this->OrderModel = new \Orders\Models\OrderModel();
        $this->SettingsModel = new \Admin\Models\SettingsModel();
        $this->TelegramModel = new \App\Models\TelegramModel();
        $this->PayModel = new \Pays\Models\PayModel();
        $this->ProductModel = new \Products\Models\ProductModel();
        $this->ionAuth = new \IonAuth\Libraries\IonAuth();

        $settings = $this->SettingsModel->all(TRUE);
        foreach ($settings as $settings_) {
            $this->{$settings_['name']} = trim($settings_['value']);
        }
	}

    public function shopid() {
        return (int) $this->PayModel->get($this->id_pay, 'shopid');
    }

    public function sekretkey() {
        return $this->PayModel->get($this->id_pay, 'sekretkey');
    }

    public function provider_token() {
        return $this->PayModel->get($this->id_pay, 'provider_token');
    }

	public function init() {
        if (empty($this->sekretkey()) OR empty($this->shopid())) {
            return FALSE;
        }
		require_once realpath(APPPATH.'ThirdParty/yookassa/Libraries/vendor/autoload.php');
        $this->client = new Client();
        return $this->client->setAuth($this->shopid(), $this->sekretkey());
	}

    /*
    Получить доступные курсы валют
    которые поддерживают платежка телеграм
    @docs https://core.telegram.org/bots/payments#supported-currencies
     */
    public function get_currency($currency_need = "RUB") {
        try {
            $items = json_decode(file_get_contents("https://core.telegram.org/bots/payments/currencies.json"));
            foreach($items as $currency => $data) {
                if ($data->symbol == $currency_need) {
                    $res = [];
                    foreach($data as $name => $val) {
                        $res[$name] = $val;
                    }
                    $res['min_amount'] = $res['min_amount']/100;
                    $res['max_amount'] = $res['max_amount']/100;
                    return $res;
                }
            }

            return FALSE;
        } catch (\Exception $e) {
            $response = $e->getMessage();

            if (!empty($response)) {
                print_r($response);
            }
        }

        return FALSE;
    }

    /*
    При успешном проведении оплаты
    @docs https://core.telegram.org/bots/api#successfulpayment
     */
    public function successful_payment($message) {
        $chat_id = $message['message']['chat']['id'];
        $id_order = (int) $message['message']['successful_payment']['invoice_payload'];
        
        if (!$order = $this->OrderModel->get($id_order)) {
            log_message('error', "Заказа с №".$id_order." не существует!");
            return FALSE;
        }

        //сравниваем сумму покупки и того что пришло на наш сервер
        if (isset($order['delivery_price'])) {
            $order['sum_pay']+=$order['delivery_price'];
        }

        $pay = $this->PayModel->pay($order['id_pay']);
        if ($message['message']['successful_payment']['currency'] <> $pay['currency']) {
            //тут по идее надо конвертировать в основную валюту по курсу
            log_message('error','Пришло в валюте '.$message['message']['successful_payment']['currency'].' а надо в '.$pay['currency']);
            return FALSE;
        }

        $message['message']['successful_payment']['total_amount'] = $message['message']['successful_payment']['total_amount'] / 100;

        if ($message['message']['successful_payment']['total_amount'] < $order['sum_pay']) {
            log_message('error', "Пришло ".$message['message']['successful_payment']['total_amount'].", а надо ".$order['sum_pay']."!");
            return FALSE;
        }

        //меняем статус заказа
        return $this->OrderModel->status($id_order);
    }

    /*
    Предоплатная проверка в pre_checkout_query
    @docs https://core.telegram.org/bots/api#answerprecheckoutquery
     */
    public function pre_checkout($message): bool {
        $chat_id = $message['pre_checkout_query']['from']['id'];
        $id_order = (int) $message['pre_checkout_query']['invoice_payload'];

        if (!$order = $this->OrderModel->get($id_order)) {
            log_message('error', "Заказа с №".$id_order." не существует!");
            return FALSE;
        }

        //сравниваем сумму покупки и того что пришло на наш сервер
        if (isset($order['delivery_price'])) {
            $order['sum_pay']+=$order['delivery_price'];
        }

        $message['pre_checkout_query']['total_amount'] = $message['pre_checkout_query']['total_amount'] / 100;

        $pay = $this->PayModel->pay($order['id_pay']);
        if ($message['pre_checkout_query']['currency'] <> $pay['currency']) {
            //тут по идее надо конвертировать в основную валюту по курсу
            log_message('error','Пришло в валюте '.$message['pre_checkout_query']['currency'].' а надо в '.$pay['currency']);
            return FALSE;
        }

        if ($message['pre_checkout_query']['total_amount'] < $order['sum_pay']) {
            log_message('error', "Пришло ".$message['pre_checkout_query']['total_amount'].", а надо ".$order['sum_pay']."!");
            return FALSE;
        }

        return TRUE;
    }

    /*
    Сгенерировать счет для оплаты внутри телеграм 
     */
    public function generate_check(int $id_order): array {

        $order = $this->OrderModel->get($id_order);
        if (!isset($order['id'])) {
            return 'Не найден в базе заказ №'.$id_order;
        }
        
        if (!$currency_data = $this->get_currency()) {
            return 'не удалось получить валюту';
        }

        $pay = $this->PayModel->pay($order['id_pay']);
        $data_user = $this->ionAuth->user($order['chat_id'])->getRowArray();

        $params = [];
        $params['payload'] = $id_order;
        $params['title'] = "Оплата заказа №".$id_order;
        $params['description'] = $params['title'];
        $params['provider_token'] = $this->provider_token();
        $params['start_parameter'] = $id_order;
        $params['currency'] = $pay['currency']; //код валюты

        // $params['need_phone_number'] = $this->need_phone > 0;
        // $params['need_shipping_address'] = $this->need_delivery > 0;
        // $params['is_flexible'] = $this->is_flexible > 0;
        $params['disable_notification'] = FALSE;
        $params['need_name'] = $data_user['first_name'];
        
        $receipt = (object) [];
        $receipt->items = [];
        $params['prices'] = [];

        $i = 0;
        $sum_total = 0;

        //обходим все продукты в корзине и добавляем в чек
        $products = $this->OrderModel->products($id_order);

        //добавляем стоимость доставки
        if (isset($order['delivery_price']) AND $order['delivery_price'] > 0) {
            $product_data = [];
            $product_data['price_in_order'] = $order['delivery_price'];
            $product_data['count'] = 1;
            $product_data['name'] = 'Доставка';
            $products[]=$product_data;
        }

        foreach ($products as $product) {
            $product['count'] = 1;//!!! уже посчитано

            $price = (object) [];
            $price->label = $product['name'];

            $price->amount = $product['price_pay'] * 100;
            $price->amount = $price->amount * $product['count']; //

            $amount = $product['price_pay'];
            $amount = $amount * $product['count']; //

            if ($amount <= 0 OR $amount < $currency_data['min_amount']) {
                //слишком маленькая сумма заказа
                continue;
            }

            if ($amount  >= $currency_data['max_amount']) {
                continue; //слишком большая сумма
            }

            $sum_total+= $amount;

            $params['prices'][]= $price;

            $product_data = (object) [];
            $product_data->description = $product['name'];
            $product_data->quantity = $product['count'] <= 0 ? 1 : $product['count'];

            $amount = (object) [];
            $amount->value = $price->amount;
            $amount->currency = "RUB";
            $product_data->amount = $amount;
            $product_data->vat_code = 1;
            $receipt->items[]=$product_data;
        }
        
        if ($sum_total < $currency_data['min_amount']) {
            return "Сумма оплаты ".$sum_total." не должна быть меньше ".$currency_data['min_amount'];
        }
        if ($sum_total >= $currency_data['max_amount']) {
            return "Сумма оплаты ".$sum_total." не должна быть больше ".$currency_data['max_amount'];
        }

        $params['prices'] = json_encode($params['prices']);
        $params['provider_data'] = json_encode($receipt);

        return $params;
    }

    /*
    Получаем поступившие данные об оплате
     */
    public function checkpay() {
        $data = json_decode(file_get_contents('php://input'), TRUE);


        log_message('error','платеж пришел');
        log_message('error', print_r($data, TRUE));

        if (!isset($data['event'])) {
            echo "URL надо указать в YooKassa";
            return FALSE;
        }
        if ($data['event'] == "payment.canceled") {
            log_message('error','платеж отменен');
            return FALSE;
        }

        if ($data['event'] <> "payment.succeeded" OR $data['object']['status'] <> "succeeded" OR $data['object']['paid'] <> 1) {
            log_message('error','платеж НЕ успешен');
            return FALSE;
        }

        $id_order = (int) $data['object']['metadata']['orderNumber'];
        
        //сумма к оплате
        $amount = $data['object']['amount']['value'];
        $currency = $data['object']['amount']['currency'];

        //назначение платежа
        $description = $data['object']['description'];

        //поступило в итоге с учетом комиссий
        $income_amount = $data['object']['income_amount']['value'];
        $income_currency = $data['object']['income_amount']['currency'];

        //способ оплаты который выбрал
        $payment_method = $data['object']['payment_method']['type'];
        $bank_card = isset($data['object']['payment_method']['card']['issuer_name']) ? $data['object']['payment_method']['card']['issuer_name'] : '';

        log_message('error','платеж успешен');

        // log_message('error','id_order='.$id_order);
        // log_message('error', $amount.' '.$currency);
        // log_message('error', $income_amount.' '.$income_currency);
        // log_message('error', $description);

        // log_message('error', $payment_method);
        // log_message('error', $bank_card);
        
        //уведомляем в канал что пришла оплата
        return $this->OrderModel->status($id_order);
    }

    /*
    Создать счет
    @docs https://yookassa.ru/developers/api?lang=php#create_payment
    @src https://github.com/yoomoney/yookassa-sdk-php/blob/master/docs/examples/02-payments.md#%D0%97%D0%B0%D0%BF%D1%80%D0%BE%D1%81-%D0%BD%D0%B0-%D1%81%D0%BE%D0%B7%D0%B4%D0%B0%D0%BD%D0%B8%D0%B5-%D0%BF%D0%BB%D0%B0%D1%82%D0%B5%D0%B6%D0%B0
    @return string url - URl на страницу оплаты со способами оплаты
     */
    public function createbill(int $id_order = 0) {
        if (!$this->init()) {
            log_message('error','не удалось получить апи ключ yooKassa');
            return FALSE;
        }

        if (!$order = $this->OrderModel->get($id_order)) {
            log_message('error','нет такой операции №'.$id_order);
            return FALSE;
        }
        
        $botusername = $this->TelegramModel->getMe()->result->username; 
        $description = "Оплата в боте @".$botusername; 
        $description = character_limiter($description, 127, ' ');//128 символов максимум
        $description = trim($description);

        $this->ionAuth = new \IonAuth\Libraries\IonAuth();
        $data_user = $this->ionAuth->user($order['chat_id'])->getRowArray();
        $full_name = empty($data_user['fname']) ? json_decode($data_user['first_name']).' '.json_decode($data_user['last_name']) : $data_user['fname'];


        $data = array(
                    'amount' => array(
                        'value' => $order['sum_pay'],
                        'currency' => $this->currency_cod,
                    ),
                    'confirmation' => array(
                        'type' => 'redirect',
                        'locale' => 'ru_RU',
                        'return_url' => "https://t.me/".$botusername,
                    ),
                    'capture' => TRUE,
                    'description' => (string) $description,
                    'metadata' => array(
                        'orderNumber' => $id_order
                    ),
                    'receipt' => array(
                        'customer' => array(
                            'full_name' => $full_name,
                            'email' => $data_user['email'],
                            'phone' => $data_user['phone'],
                            // 'inn' => $this->inn_admin()
                        ),
                        'items' => array(
                            array(
                                'description' => (string) $description,
                                'quantity' => '1.00',
                                'amount' => array(
                                    'value' => $order['sum_pay'],
                                    'currency' => $this->currency_cod
                                ),
                                'vat_code' => '1', //без НДС
                                'payment_mode' => 'full_prepayment',  //тип платежа - аванс и тп @docs https://yookassa.ru/developers/54fz/parameters-values#payment-mode
                                'payment_subject' => 'service', //услуга @docs https://yookassa.ru/developers/54fz/parameters-values#payment-subject
                            ),
                        )
                    )
                );
        var_dump($data);
        try {
            $idempotenceKey = uniqid('', true);
            $response = $this->client->createPayment($data, $idempotenceKey);
            
            //получаем confirmationUrl для дальнейшего редиректа
            return $response->getConfirmation()->getConfirmationUrl();
        } catch (\Exception $e) {
            $response = $e->getMessage();

            if (!empty($response)) {
                echo "<p>ОШИБКА ЮМАНИ: ".$response."</p>";
                log_message('error','Ошибка yookassa: ' . $response);
            }
        }

        return FALSE;
    }
}
