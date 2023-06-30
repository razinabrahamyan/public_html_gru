<?php 

/**
 * Name:    Модель для работы с BTC
 *
 * Created:  03.04.2020
 *
 * Description:  
 *
 * Requirements: PHP 7.2 or above
 *
 * @author     Krotov Roman <tg: @KrotovRoman>
 */
namespace Btc\Models;

use CodeIgniter\Model;
use \CodeIgniter\Database\ConnectionInterface;

/**
 * Class BlockchainModel
 */
class BlockchainModel
{
	/**
	 * Database object
	 *
	 * @var \CodeIgniter\Database\BaseConnection
	 */
	protected $db;
	protected $config;
    protected $id_pay = 4; //id способа оплаты BitCoin

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->db = \Config\Database::connect();
        $this->SettingsModel = new \Admin\Models\SettingsModel();
	}

	/**
	 * Getter to the DB connection used by Ion Auth
	 * May prove useful for debugging
	 *
	 * @return object
	 */
	public function db() {
		return $this->db;
	}

	/*
    Проверяем все не оплаченные заказы
    в которых указан txid на оплату
    берем не старше недели заказы
     */
    public function check_orders($days = 7) {
        $this->PayModel = new \Pays\Models\PayModel();

        $address = $this->PayModel->get($this->id_pay, "address");
        if (empty($address)) {
            return FALSE;
        }

        $blockio_key = $this->PayModel->get($this->id_pay, "blockio_key");
        if (!empty($blockio_key)) {
            return FALSE;
        }

        $days = $this->PayModel->get($this->id_pay, "days");

        $confirmations = $this->PayModel->get($this->id_pay, "confirmations");
        $confirmations = $confirmations <= 0 ? 1 : $confirmations;

        //получаем все заказы в которых указан txid
        $db = $this->db->table('orders');
        $db->where('NOT txid IS NULL', NULL, FALSE); //указан txid
        $db->where('status', 0); //не оплачен
        $db->where('id_pay', $this->id_pay); //статус оплат BitCoin
        if ($days > 0) {
        	$db->where('updated>=', date("Y-m-d H:i:s", time() - 3600 * 24 * $days));
        }
        $orders = $db->get()->getResultArray();

        $this->OrderModel = new \Orders\Models\OrderModel();
        foreach ($orders as $order) {
            //если оплата пригла
            if ($this->is_payed($address, $order['txid'], $order['sum_pay'], $confirmations)) {
                //помечаем оплаченным этот заказ
                $this->OrderModel->status($order['id']);
            }
        }

        echo "ok";
    }

    /*
    * Оплачен заказ или нет
    *
    * @return bool TRUE - заказ оплачен
    * 
    * @param string $address - адрес владельца бота на который смотрим поступлениы
    * @param string $txid - хеш транзакции клиента которую проверяем
    * @param float $sum_order - сумма которая указана в заказе, которая должна была поступить
    * @paeam int $only_approved - количество подтвеждений которое учитывать
    *
    * @example 
    *
    * $this->BlockchainModel = new \Btc\Models\BlockchainModel();
    * $is_payed = $this->BlockchainModel->is_payed($address, $txtid, $sum_pay);
     */
    public function is_payed(string $address, string $txid, $sum_order, $only_approved = 1): bool {
        $transactions = $this->transactions($address, $only_approved);
        foreach ($transactions as $item) {
            if ($item['hash'] == $txid AND $item['value'] >= $sum_order) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /*
     * Транзакции на адрес
     * @dosc https://btc.com/api-doc#Address
     */

    public function transactions(string $address, $only_approved = 1, $only_in = TRUE) {
        $return = json_decode(file_get_contents("https://chain.api.btc.com/v3/address/" . $address . "/tx"));
        if (!isset($return->data->total_count) OR $return->data->total_count <= 0) {
            return [];
        }

        $res = [];
        foreach ($return->data->list as $item) {
            if ($only_approved > 0 AND $item->confirmations <= $only_approved) {
                continue; //только подтвержденные
            }

            if ($only_in AND $item->balance_diff < 0) {
                continue; //только поступления
            }

            $data = [];
            $data['is_out'] = $item->balance_diff < 0; //это вывод (если отрицательный)
            $data['hash'] = $item->hash;    //хеш транзакций
            $data['value'] = $item->balance_diff / 100000000; //сумма поступления в BTC
            $data['fee'] = $item->fee / 100000000; //коимссия сети
            $data['confirmations'] = $item->confirmations; //количество подтвеждений
            $data['created'] = isset($item->created_at) ? date("Y-m-d H:i:s", $item->created_at) : date("Y-m-d H:i:s", $item->block_time);
            $res[] = $data;
        }

        return $res;
    }

	/*
     * Средняя стоимость транзакции
     * @docs https://bitcoinfees.earn.com/api
     * @return в сатоши на байт
     */
    public function fastestfee() {
        $avgtxvalue = json_decode(file_get_contents('https://bitcoinfees.earn.com/api/v1/fees/recommended'));
        return floatval($avgtxvalue->fastestFee);
    }
    
	/*
     * Существует такой адрес или нет
     */
    public function is_good($address) {
    	$return = floatval(file_get_contents("https://blockchain.info/rawaddr/".$address));
        return !$return ? FALSE : TRUE;
    }

    /*
    * USD => BTC
     */
    public function tobtc($value, $currency = FALSE) {
        try {
            $currency OR $currency = $this->SettingsModel->currency_cod;
            $url = "https://blockchain.com/tobtc?currency=".$currency."&value=".$value;
            return floatval(file_get_contents($url));
        } catch (\Exception $e) {
            // log_message('error', print_r($e->getMessage(), TRUE));
            return 0;
        }
    }

    /*
     * BTC => USD
     */

    public function torub($value, $currency = FALSE) {
        $currency OR $currency = $this->SettingsModel->currency_cod;
        if (!$one = $this->tobtc(1, $currency)) {
            return 0;
        }
        return $value / $one;
    }

}
