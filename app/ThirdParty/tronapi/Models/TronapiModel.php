<?php 
  /**
   * Name:    Модель для работы с Tronapi
   *
   * Created:  11.03.2022
   *
   * Description:  
   *
   * Requirements: PHP 7.2 or above
   *
   * @author     Krotov Roman <tg: @KrotovRoman>
   * @docs https://tronapi.net/docs
   */
  namespace Tronapi\Models;

  use CodeIgniter\Model;
  use \CodeIgniter\Database\ConnectionInterface;

  /**
   * Class TronapiModel
   */
  class TronapiModel
  { 
  	protected $db;
    protected $id_pay = 8;

  	public function __construct() {
  		$this->db = \Config\Database::connect();

      $this->OrderModel = new \Orders\Models\OrderModel();
      $this->SettingsModel = new \Admin\Models\SettingsModel();
      $this->TelegramModel = new \App\Models\TelegramModel();
      $this->PayModel = new \Pays\Models\PayModel();

      $settings = $this->SettingsModel->all(TRUE);
      foreach ($settings as $settings_) {
        $this->{$settings_['name']} = trim($settings_['value']);
      }
    }

    /*
    Получить ID страницы оплаты
     */
    public function id_page($currency): int {
      switch ($currency) {
        case 'DASH':
          return 141;
        
        case 'LTC':
        default:
          return 140;
      }
    }

    /*
    Получаем параметр запроса в зависимости от апи 
     */
    public function currency_request_param($currency): string {
      switch (mb_strtolower($currency)) {
          case 'bch':
          case 'dash':
          case 'ltc':
          case 'btc':
            return '&currency='.$currency;

          case 'eth': return "";

          default:
            return '&token='.$currency;
      }
    }

    public function test() {
      $res = $this->balance();
      var_dump($res);
    }

    /*
    Сгенерить QR код
     */
    public function qrcode(string $text, bool $cache = FALSE) {
        $save_name = APPPATH."/../writable/cache/" . md5($text) . ".png";
        if ($cache AND realpath($save_name)) {
            return realpath($save_name); //если уже сгенерен второй раз не генерим
        }
        require_once realpath(APPPATH."/ThirdParty/tronapi/Libraries/phpqrcode/qrlib.php");
        \QRcode::png($text, $save_name, 'H', 5, 2); 
        return realpath($save_name);
    }

    /*
    Получить настройки сспособа оплаты
     */
    public function setting(string $name) {
      if (empty($this->{$name})) {
        $this->{$name} = $this->PayModel->get($this->id_pay, $name);
      }
      return $this->{$name};
    }

    /*
    Получить апи ключ
     */
    public function api_key($currency) {
      $currency = mb_strtoupper($currency);

      switch ($currency) {
        case 'BNIX':
          $domain_api_url = 'bnbapi.net';
          break;
        case 'USDT':
          $domain_api_url = 'tronapi.net';
          break;
        default:
          $domain_api_url = 'cryptocurrencyapi.net';
          break;
      }

      return [
        'domain_api_url' => $domain_api_url,
        'apikey' => $this->setting('apikey')
      ];
    }

    /*
    Получить адрес заказа
     */
    public function get_address_user(int $chat_id, $currency = 'USDT') {
      $user = $this->db->table('users')
      ->where('chat_id', $chat_id)
      ->get(1)
      ->getRowArray();
      if (!empty($user['usdt_address'])) {
        return $user['usdt_address'];
      }

      $usdt_address = $this->new_address($chat_id, $currency);

      if (is_array($usdt_address)) {
        return $usdt_address['error'];
      }

      $this->db->table('users')->update(['usdt_address' => $usdt_address], ['chat_id' => $chat_id]);

      return $usdt_address;
    }

    /*
    Получить адрес заказа
     */
    public function get_address(int $id, $currency = 'USDT') {
      $order = $this->db->table('orders')
      ->where('id', $id)
      ->get(1)
      ->getRowArray();

      switch ($currency) {
        case 'DASH':
          $field = 'dash_address';
          break;
        
        default:
          $field = 'crypto_address';
          
          break;
      }
      if (!empty($order[$field])) {
        return $order[$field];
      }

      $usdt_address = $this->new_address($id, $currency);

      log_message('error', print_r('получаем адрес',TRUE));
      log_message('error', print_r($usdt_address,TRUE));

      if (isset($return['error'])) {
        return $return['error'];
      }
      
      $this->db->table('orders')->update([$field => $usdt_address], ['id' => $id]);

      return $usdt_address;
    }

  /*
  Получить новый адрес для пользователя
  Array
  (
      [result] => stdClass Object
          (
              [address] => TMakZLLddaMPJj7zdkzYKwdEXuS9iGTUCV
              [token] => USDT
          )

  )
   */
  public function new_address(int $chat_id, $currency = 'USDT') {

      if (!$data_api_key = $this->api_key($currency)) {
          return ['error' => 'Не указан API ключ '.$currency.'!'];
      }

      try{
          $returnurl = base_url('tronapi/hook');
          $url_request = 'https://'.$data_api_key['domain_api_url'].'/api/.give?key='.$data_api_key['apikey'].'&token='.$currency.'&tag='.$chat_id.'&statusURL='.$returnurl;
          $url_request.=$this->currency_request_param($currency);

          $curl = curl_init();
          curl_setopt_array($curl, array(
            CURLOPT_URL => $url_request,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
          ));

          $response = curl_exec($curl);

          curl_close($curl);

          $response = (array) json_decode($response);

          if (isset($response['result']->address)) {
            return $response['result']->address;
          } else if (isset($response['result'])) {
            return $response['result'];
          }

          if (isset($response['error'])) {
            if ($response['error'] == 'credits_low') {
              $response['error'] = 'Не достаточно кредитов на балансе API!';
            }
            return [
              'error' => $response['error']
            ];
          }

          return [
            'error' => 'Не удалось получить адрес '.$currency
          ];

      } catch (Exception $e) {
          return ['error' => $e->getMessage()];
      }
  }

  /*
    Получаем уведомления
    { 
       tronapi.net: version 
       type: message type = (in-payment / track-track / out-sending) 
       date: date and time in UNIX format 
       from: address-sender 
       to: address-receiver 
       token: token (empty for TRX tx) 
       amount: amount 
       fee: network fee 
       txid: transaction txid (hash)
       confirmations: number of confirmations
       tag: tag 
       sign: signature 
    } 

    Уведомление о том что сделан вывод
    поступление tronapi USDT
ERROR - 2022-09-06 00:15:56 --> Array
(
    [tronapi.net] => 1
    [type] => in
    [date] => 1662412550
    [from] => TPaBgLuMh2euCUaVetHRNbkn8NpZZTWaHN
    [to] => TPZMiF1Hontou7tFHiKoZgVnaKg4mUcmy6
    [token] => USDT
    [amount] => 50.000000
    [fee] => 8.647000
    [txid] => e61b23d9bb3be0e1da5494d774ccd6fc2a9593613aa7909a73ad56ec12425488
    [confirmations] => 1
    [tag] => 166099143
    [sign] => 815782cf73dcf9f43a349a172d1b5780e3b91e52
)

уведомление от currencyapi
Array
(
    [cryptocurrencyapi.net] => 2
    [currency] => DASH
    [type] => in
    [date] => 1669279884
    [address] => XfHRk4WzsZ9D2Aymo1xHsmPjfn4kTthdCd
    [amount] => 0.47225500
    [txid] => 1501d1ff668e59fd2f4eb138e5cea4253b01df45cc8e251aca50d2c53226b84a
    [pos] => 0
    [confirmations] => 6
    [tag] => 2134038040
    [transferid] => 15295
    [sign] => bbb77d3da2f6e5d65e226d350ce3315b19d51304
    [sign2] => 7f53c585ac16a0be7ff891a520f6b2077bc50eb4
)
   */
  
  public function check($currency = 'USDT') {
    $data = @json_decode(file_get_contents('php://input'), true);

    //example
    // $data = [
    //     'confirmations' => 2,
    //     'currency' => 'DASH',
    //     'to' => 'XksUcJUsZ5PiSyb59MQh1t4KTP883CGAhX',
    //     'tag' => 1313149451,
    //     'type' => 'in',
    //     'amount' => 0.23529400,
    //     'txid' => 'f111d9c01df13f8b65b861607387aa81bed17fd68c8c015d0be1431c1095d042'
    // ];

    log_message('error', print_r('уведомление', TRUE));
    log_message('error', print_r($data, TRUE));

    if (!isset($data['confirmations']) OR $data['confirmations'] < 1) {
      return FALSE;
    }

    if ($data['type'] == 'in') {

      $data_invoice = [];
      $data_invoice['txid'] = $data['txid'];
      $data_invoice['value'] = floatval($data['amount']);
      $data_invoice['id_order'] = (int) trim($data['tag']);
      if (!empty($data['currency'])) {
        $data_invoice['currency'] = mb_strtoupper($data['currency']);
      } else {
        $data_invoice['currency'] = empty($data['token']) ? mb_strtoupper($currency) : mb_strtoupper($data['token']);
      }
      
      log_message('error', print_r('поступление оплаты',TRUE));
      log_message('error', print_r($data_invoice, TRUE));

      $order = $this->OrderModel->get($data_invoice['id_order']);
      if (!isset($order['id'])) {
        log_message('error', print_r('Не найден такой заказ',TRUE));
        return FALSE;
      }

      $pay = $this->PayModel->pay($order['id_pay']);
      if ($pay['currency'] <> $data_invoice['currency']) {
        log_message('error', print_r($data_invoice['currency'].' пришло а надо '.$data_invoice['currency'].' для заказа №'.$data_invoice['id_order'],TRUE));
        return FALSE;
      }

      if ($order['status'] > 0) {
        log_message('error', print_r('Заказ уже оплачен №'.$order['id'],TRUE));
        return FALSE;
      }

      if ($order['sum_pay'] > $data_invoice['value']) {
        log_message('error', print_r('Пришло не достаточно по заказу №'.$order['id'].' надо '.$order['sum_pay'],TRUE));
        return FALSE;
      }

      $this->OrderModel->set(['id' => $data_invoice['id_order'], 'finish' => 1, 'txid' => $data_invoice['txid']]);

      log_message('error', print_r('помечаем оплаченным заказ №'.$data_invoice['id_order'],TRUE));
      return $this->OrderModel->status($data_invoice['id_order']);

    } else if ($data['type'] == 'out') {//если уведомление о выводе
      log_message('error', print_r('сделан вывод',TRUE));
    }
    
    return TRUE;
  }

  /*
  Получить баланс сервиса
   */
  public function balance($currency = 'USDT') {
    if (!$data_api_key = $this->api_key($currency)) {
        return ['error' => 'Не указан API ключ '.$currency.'!'];
    }

    try{
      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://'.$data_api_key['domain_api_url'].'/api/.balance?key='.$data_api_key['apikey'].'&token='.$currency,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
      ));

      $response = curl_exec($curl);

      curl_close($curl);
      $response = (array) json_decode($response);
      
      return isset($response['result']) ? floatval($response['result']) : 0;

    } catch (Exception $e) {
      return ['error' => $e->getMessage()];
    }
  }

  /*
  Получаем IP адрес внутренний
   */
  public function ip() {
    try{
      $res = json_decode(file_get_contents("https://block.io/ip_echo"));
      return $res->ip;
    } catch (Exception $e) {
      return "";
      // return ['error' => $e->getMessage()];
    }
  }

  /*
  Запрос к апи на вывод
  array (
  'value',
  'currency',
  'address',
  'id' - id запроса на вывод
  )

  @return - id заявки на вывод в системе tronapi
   */
  public function send($data) {
      $limit_out = $this->setting('limit_out');
      if ($limit_out <= 0) {
        return ['error' => 'Автовывод отключен!'];
      }

      if (!isset($data['currency'])) {
        return ['error' => 'Не указана монета которую выводим!'];
      }
      if (!isset($data['id'])) {
        return ['error' => 'Не указан id запроса на вывод!'];
      }
      if (!isset($data['value'])) {
        return ['error' => 'Не указана сумма вывода!'];
      }
      if (!isset($data['address'])) {
        return ['error' => 'Не указан адрес вывода!'];
      }

      if (!$data_api_key = $this->api_key($data['currency'])) {
          return ['error' => 'Не указан API ключ!'];
      }

      try{
        $returnurl = base_url('tronapi/hook');
        $url = 'https://'.$data_api_key['domain_api_url'].'/api/.send?key='.$data_api_key['apikey'].'&token='.$data['currency'].'&address='.$data['address'].'&statusURL='.$returnurl.'&amount='.$data['value'].'&tag='.$data['id'];
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $return = (array) json_decode($response);

        log_message('error', print_r($return,TRUE));
        if (!isset($return['result'])) {
          return ['error' => 'Не удалось создать задачу на вывод по API: '.$return['error']];
        }

        return $return['result'];
      } catch (Exception $e) {
          return ['error' => $e->getMessage()];
      }
  }

  /*
  Автоматический вывод по апи
   */
  public function autoout() {
    $limit_out = $this->setting('limit_out');

    if ($limit_out <= 0) {
      echo "отключен автоывод";
      return FALSE;
    }

    $balance = $this->db->table('balance')
    ->where('value<', 0)
    ->where('finish', 0)
    ->where('currency', 'USDT')
    ->get($limit_out)
    ->getResultArray();

    foreach ($balances as $item) {
      $data = [];
      $data['id'] = $item['id'];
      $data['address'] = $item['comment'];
      $data['value'] = abs($item['value']);
      $result = $this->send($data);
      if (isset($result['error'])) {
        //не удалось вывести
      } else {
        //помечаем выплаченной
        $this->db->table('balance')->update(['finish' => 1], ['id' => $item['id']]);
      }
    }
    
    return TRUE;
  }

}//class
