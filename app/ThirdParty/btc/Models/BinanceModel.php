<?php 

/**
 * Name:    Модель для работы с Binance
 *
 * Created:  03.04.2020
 *
 * Description:  
 *
 * Requirements: PHP 7.2 or above
 *
 * @author     Krotov Roman <tg: @KrotovRoman>
 * @src https://github.com/binance-exchange/php-binance-api
 */
namespace Btc\Models;

use CodeIgniter\Model;
use \CodeIgniter\Database\ConnectionInterface;

/**
 * Class BinanceModel
 */
class BinanceModel
{
	/**
	 * Database object
	 *
	 * @var \CodeIgniter\Database\BaseConnection
	 */
	protected $db;
	protected $config;
	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->db = \Config\Database::connect();
        $this->encryption = \Config\Services::encrypter();
        $this->SettingsModel = new \Admin\Models\SettingsModel();
        $this->CourseModel = new \Course\Models\CourseModel();

        $settings = $this->SettingsModel->all(TRUE);
        foreach ($settings as $settings_) {
            $this->{$settings_['name']} = trim($settings_['value']);
        }
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
     * Последняя цена по паре
     * @docs https://bablofil.com/binance-api/
     * @src https://github.com/binance-exchange/php-binance-api
     */

    public function price(string $pair = "BTCRUB"){
        try {
            $res = json_decode(file_get_contents('https://api.binance.com/api/v3/ticker/price?symbol='.$pair));
            return floatval($res->price);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /*
    Получить цену из БД 
     */
    public function price_db(string $pair, $online = FALSE) {
        if ($online AND $price = $this->price($pair)) {
            return $price; //если принудительно взять цену онлайн
        }
        $pair = strtoupper($pair);
        $courses_binance = $this->db->table('courses_binance')->where('pair', $pair)->get(1)->getRowArray();
        return isset($courses_binance['price']) ? $courses_binance['price'] : FALSE;
    }

    /*
     * Цены по всем парам
     * @docs https://bablofil.com/binance-api/
     * @src https://github.com/binance-exchange/php-binance-api
     */

    public function prices($online = TRUE){
        if (!$online) {
            return $this->db->table('courses_binance')->get()->getResultArray();
        }
        try {
            return json_decode(file_get_contents('https://api.binance.com/api/v3/ticker/price'));
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /*
    Обновляем цены в БД
     */
    public function cron(){
        $prices = $this->prices();
        if (isset($prices['error'])) {
            echo $prices['error'];
            return FALSE;
        }


        foreach ($prices as $pair => $price) {
            $pair = $price->symbol;
            $price = floatval($price->price);
            echo $pair.' '.$price;
            echo "<hr>";
            if ($this->db->table('courses_binance')->where('pair', $pair)->countAllResults() > 0) {
                $this->db->table('courses_binance')->where('pair', $pair)->update(['price' => $price, 'updated' => date("Y-m-d H:i:s")]);
            } else {
                $this->db->table('courses_binance')->insert(['price' => $price, 'pair' => $pair]);
            }
        }
    }

    /*
    Конвертируем валюты по курсу Binance
    
    * RUB => BTC или BTC => RUB
     */
    public function convert($from, $value, $to, $online = FALSE) {
        $to = strtoupper($to);
        $from = strtoupper($from);

        if ($to == "USD") {
            $to.="T";
        }
        if ($from == "USD") {
            $from.="T";
        }

        if ($from == $to) {
            return $value;
        }

        if (!$price_one = $this->price_db($from.$to, $online)) {
            if (!$price_one_ = $this->price_db($to.$from, $online)) {
                return FALSE;
            }
            $price_one = 1 / $price_one_;
        }
        return $value * $price_one;
    }
}
