<?php 

/**
 * Name:    Модель для работы с Wayforpay
 *
 * Created:  03.04.2020
 *
 * Description:  
 *
 * Requirements: PHP 7.2 or above
 *
 * @author     Krotov Roman <tg: @KrotovRoman>
 * @src https://github.com/wayforpay/php-sdk
 * @docs https://wiki.wayforpay.com/
 */
namespace Wayforpay\Models;

use CodeIgniter\Model;
use \CodeIgniter\Database\ConnectionInterface;

require_once realpath(APPPATH.'/ThirdParty/wayforpay/Libraries/vendor/autoload.php');
use WayForPay\SDK\Collection\ProductCollection;
use WayForPay\SDK\Credential\AccountSecretCredential;
use WayForPay\SDK\Domain\Client;
use WayForPay\SDK\Domain\Product;
use WayForPay\SDK\Wizard\PurchaseWizard;
use WayForPay\SDK\Exception\WayForPaySDKException;
use WayForPay\SDK\Handler\ServiceUrlHandler;

/**
 * Class YandexModel
 */
class WayforpayModel
{
	protected $db;
    protected $id_wayforpay, $key_wayforpay;
    protected $id_pay = 10;

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
        $this->ionAuth = new \IonAuth\Libraries\IonAuth();

        $settings = $this->SettingsModel->all(TRUE);
        foreach ($settings as $settings_) {
            $this->{$settings_['name']} = trim($settings_['value']);
        }
	}

    public function id_wayforpay() {
        if (!empty($this->id_wayforpay)) {
            return $this->id_wayforpay;
        }
        $this->id_wayforpay = $this->PayModel->get($this->id_pay, 'id_wayforpay');
        return $this->id_wayforpay;
    }

    public function key_wayforpay() {
        if (!empty($this->key_wayforpay)) {
            return $this->key_wayforpay;
        }
        $this->key_wayforpay = $this->PayModel->get($this->id_pay, 'key_wayforpay');
        return $this->key_wayforpay;
    }

    /*
    Проверка поступления платежа
     */
    public function check(int $id_order) {
        if (!$data_order = $this->OrderModel->get($id_order)) {
            log_message('error', "Нет такого заказа №" . $id_order);
            return FALSE;
        }
        if ($data_order['status'] == 1) {
            return FALSE;
        }
        
        try {
            $credential = new AccountSecretCredential($this->id_wayforpay(), $this->key_wayforpay());
            $handler = new ServiceUrlHandler($credential);
            $response = $handler->parseRequestFromPostRaw();

            $return = $handler->getSuccessResponse($response->getTransaction());
            echo $return;

            $return = (array) json_decode($return);
            log_message('error','Пришло уведомление об оплате WayForPay id_order='.$id_order);
            log_message('error', print_r($return, TRUE));

            if (isset($return['status']) AND $return['status'] == "accept") {
                // $this->OrderModel->status($id_order); //помечаем заказ оплаченным
            }

        } catch (WayForPaySDKException $e) {
            echo "WayForPay SDK exception: " . $e->getMessage();
            log_message('error', "WayForPay SDK exception: " . $e->getMessage());
        }
    }

    /*
    После поступления оплаты
     */
    public function returnurl(int $id_order) {
        if (!$data_order = $this->OrderModel->get($id_order)) {
            log_message('error', "Нет такого заказа №" . $id_order);
            return FALSE;
        }

        if ($data_order['status'] == 1) {
            return FALSE;
        }

        try {
            $credential = new AccountSecretCredential($this->id_wayforpay(), $this->key_wayforpay());
            
            $handler = new ServiceUrlHandler($credential);
            $response = $handler->parseRequestFromGlobals();

            if ($response->getReason()->isOK()) {
                $this->OrderModel->status($id_order);
            } else {
                echo "Error: " . $response->getReason()->getMessage();
                log_message('error', "Error: " . $response->getReason()->getMessage());
            }

        } catch (WayForPaySDKException $e) {
            echo "WayForPay SDK exception: " . $e->getMessage();
            log_message('error', "WayForPay SDK exception: " . $e->getMessage());
        }
    }

    /*
    Получить код формы оплаты
     */
    public function btn(int $id_order) {
        if (!$data_order = $this->OrderModel->get($id_order)) {
            return FALSE;
        }
        $pay = $this->PayModel->pay($this->id_pay);
        $user = $this->ionAuth->user($data_order['chat_id'])->getRowArray();
        $products = $this->OrderModel->products($id_order);
        $bot = $this->TelegramModel->getMe()->result;

        try {
            $credential = new AccountSecretCredential($this->id_wayforpay(), $this->key_wayforpay());

            $products_order = [];
            foreach ($products as $product) {
                $products_order[]=new Product($product['name'], $product['price'], 1);
            }
            $form = PurchaseWizard::get($credential)
                ->setOrderReference(sha1(microtime(true)))
                ->setAmount($data_order['sum_pay'])
                ->setCurrency($pay['currency'])
                ->setOrderDate(new \DateTime())
                ->setMerchantDomainName($bot->first_name)
                ->setClient(new Client(
                    $user['first_name'],
                    $user['last_name'],
                    '',
                    $user['phone']
                ))
                ->setProducts(new ProductCollection($products_order))
                ->setReturnUrl(base_url('wayforpay/returnurl/'.$id_order))
                ->setServiceUrl(base_url('wayforpay/check/'.$id_order))
                ->getForm()
                ->getAsString();

            return $form;
        } catch (WayForPaySDKException $e) {
            echo "WayForPay SDK exception: " . $e->getMessage();
        }
    }
}
