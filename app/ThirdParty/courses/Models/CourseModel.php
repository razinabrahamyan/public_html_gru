<?php 

/**
 * Name:    Модель для работы с курсами валют
 *
 * Created:  03.04.2020
 *
 * Description:  
 *
 * Requirements: PHP 7.2 or above
 *
 * @author     Krotov Roman <tg: @KrotovRoman>
 * @example 
$this->CourseModel = new \Course\Models\CourseModel();
$sum = $this->CourseModel->convert("RUB", 100, "KZT");
 */
namespace Course\Models;

use CodeIgniter\Model;
use \CodeIgniter\Database\ConnectionInterface;

/**
 * Class CourseModel
 */
class CourseModel
{   

    /**
     * Database object
     *
     * @var \CodeIgniter\Database\BaseConnection
     */
    protected $db;
    protected $config;
    /*
     * Короткие имена валют для отображения в интерфейсе
     */

    public $shortname = array('AUD' => '$', 'AMD' => 'др.', 'CAD' => '$', 'CNY' => 'юан.', 'CZK' => 'крон', 'DKK' => 'крон', 'HUF' => 'фор.', 'INR' => 'руп.', 'JPY' => '¥', 'KZT' => 'тенге', 'KRW' => 'вон', 'KGS' => 'сом', 'MDL' => 'леев', 'NOK' => 'крон', 'SGD' => '$', 'ZAR' => 'рэнд', 'SEK' => 'крон', 'CHF' => 'фр.', 'GBP' => '£', 'USD' => '$', 'UZS' => 'сум', 'BYN' => 'руб.', 'RUB' => 'руб.', 'TMT' => 'ман.', 'AZN' => 'ман.', 'RON' => 'лей', 'TRY' => 'лир', 'XDR' => 'сдр', 'TJS' => 'сом.', 'BGN' => 'лев', 'EUR' => '€', 'UAH' => 'гр.', 'PLN' => 'злот.', 'BRL' => 'реал');
    public $shortnamesmall = array('AUD' => '$', 'AMD' => 'д', 'CAD' => '$', 'CNY' => 'ю', 'CZK' => 'к', 'DKK' => 'к', 'HUF' => 'ф', 'INR' => 'р', 'JPY' => '¥', 'KZT' => 'т', 'KRW' => 'в', 'KGS' => 'с', 'MDL' => 'л', 'NOK' => 'к', 'SGD' => '$', 'ZAR' => 'р', 'SEK' => 'к', 'CHF' => 'ф', 'GBP' => '£', 'USD' => '$', 'UZS' => 'с', 'BYN' => 'р', 'RUB' => 'р', 'TMT' => 'м', 'AZN' => 'м', 'RON' => 'л', 'TRY' => 'л', 'XDR' => 'с', 'TJS' => 'с', 'BGN' => 'л', 'EUR' => '€', 'UAH' => 'г', 'PLN' => 'з', 'BRL' => 'р');

    /*
     * Символьные коды влют, которые поддерживает PayPal
     */
    public $paypal_currency = array('AUD', 'CAD', 'EUR', 'GBP', 'JPY', 'USD', 'NZD', 'CHF', 'HKD', 'SGD', 'SEK', 'DKK', 'PLN', 'NOK', 'HUF', 'CZK', 'ILS', 'MXN', 'BRL', 'MYR', 'PHP', 'TWD', 'THB', 'TRY', 'RUB');

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct() {
        $this->db = \Config\Database::connect();
        $this->SettingsModel = new \Admin\Models\SettingsModel();

        $settings = $this->SettingsModel->all(TRUE);
        foreach ($settings as $settings_) {
            $this->{$settings_['name']} = $settings_['value'];
        }
    }

    /*
    Заменяем тегик курсо валют если есть
     */
    public function replace_tags(string $text): string {
        $courses_cash = $this->db->table('courses_cash')->get()->getResultArray();

        //заполнить массивом
        foreach ($courses_cash as $item) {
            $decimals = $this->is_fiat($item['currency2']) ? 2 : 8;
            $item['value'] = number_format($item['value'], $decimals, ',', ' ');
            $text = str_ireplace("{" . $item['currency1'].'_'.$item['currency2'] . "}", $item['value'], $text);
        }

        return $text;
    }

    /*
    Это фиат
     */
    public function is_fiat(string $currency): bool {
        return $this->db->table('courses_currency')
            ->where('charcode', $currency)
            ->countAllResults() > 0;
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
     * Конвертировать валюты между собой по центробанку РФ
     * 
     * @from - код валюты из которой переводим
     * @to - код валюты в которую переводим, если это не указать то сконвертится в рубли
     * @summa - сумма которую нужно конвертировать
     * 
     * @exmaple Сколько будет 150$ в рублях?
     * $res = $this->Currency_model->convert("USD", 150, "RUB");
     */

    public function convert(string $from, $summa, $to = FALSE) {
        if ($summa == 0 OR ($from == $to AND $to !== FALSE)) {
            return $summa;
        }

        //сначала пытаемся взять из кеша уже сконвертированное
        //для криптовалют с разных бирж например
        if (
            $this->db->table('courses_cash')
                ->where('currency1', $from)
                ->where('currency2', $to)
                ->countAllResults() > 0
        ) {

            $data = $this->db->table('courses_cash')
                ->where('currency1', $from)
                ->where('currency2', $to)
                ->get(1)
                ->getRowArray();

            $result = $summa * $data['value'];
            
            return $result;
        }

        //если не нашли в кеше битокин - то конвертим с blockchain.com
        if ($from == "BTC" OR $to == "BTC") {
            $this->BlockchainModel = new \Btc\Models\BlockchainModel();
            if ($to == "BTC") {
                $return = $this->BlockchainModel->tobtc($summa, $from);
                if ($return > 0) {
                    return $return;
                }
            } else {
                $return = $this->BlockchainModel->torub($summa, $to);
                if ($return > 0) {
                    return $return;
                }
            }
        }

        //переводим from в рубли, если это не рубли
        //полученную сумму по курсу переводим в to
        if ($from == "RUB") {
            $from_nominal = 1;
            //если источник валюты - рубли
            $from_summa = $summa;
        } else {
            $from_curs = $this->get($from);
            if (!$from_curs OR !isset($from_curs['value'])) {
                return FALSE;
            }
            $from_summa = $from_curs['value'] * $summa; //получили сумму в рублях
            $from_nominal = $from_curs['nominal'];
        }

        //если переводим просто в рубли
        if ($to == "RUB" OR ! $to) {
            //если у валюты источника спец номинал
            if ($from_nominal > 1) {
                return ($from_summa / $from_nominal);
            }

            return $from_summa;
        }

        //получаем курс целевой
        $to_curs = $this->get($to);
        if (!$to_curs) {
            return FALSE;
        }
        //если номинал источника специальный
        if ($from_nominal > 1) {
            //получаем стоимость одной единицы валюты
            $from_summa = ($from_summa / $from_nominal);
        }
        //теперь полученное количество рублей делим на количество рублей в единице to
        //и получим сколько будет единиц to в этой сумме
        return ($from_summa / $to_curs['value'] * $to_curs['nominal']);
    }

    /*
    Конвертим через coinmarketcap.com
    @docs https://coinmarketcap.com/api/documentation/v1/#section/Authentication
    $time = 5 - время кеша
     */
    public function convert_coinmarketcap($from_currency, $summa, $to_currency, $time = FALSE) {
        $time OR $time = $this->cache_coinmarketcap;
        
        $from_currency = mb_strtoupper(trim($from_currency));
        $to_currency = mb_strtoupper(trim($to_currency));

        if ($from_currency == $to_currency OR $summa == 0) {
            return $summa;
        }

        $data = $this->db->table('courses_coinmarketcap')
        ->where('from_currency', $from_currency)
        ->where('to_currency', $to_currency)
        ->where('updated>=', date("Y-m-d H:i:s", time() - $time))
        ->get(1)
        ->getRowArray();
        
        if (isset($data['value'])) {
            return $summa * $data['value'];
        }

        if ($value = $this->coinmarketcap($from_currency, $summa, $to_currency)) {
            if (
                $this->db->table('courses_coinmarketcap')
                ->where('from_currency', $from_currency)
                ->where('to_currency', $to_currency)
                ->countAllResults() > 0
            ) {
                $this->db->table('courses_coinmarketcap')
                ->where('from_currency', $from_currency)
                ->where('to_currency', $to_currency)
                ->update(['value' => $value, 'updated' => date("Y-m-d H:i:s")]);
            } else {
                $data = [];
                $data['from_currency'] = $from_currency;
                $data['to_currency'] = $to_currency;
                $data['value'] = $value;
                $this->db->table('courses_coinmarketcap')->insert($data);
            }

            return ($value * $summa);
        } else { //иначе если не получили берем из кеша последнее значение
            
            $data = $this->db->table('courses_coinmarketcap')
            ->where('from_currency', $from_currency)
            ->where('to_currency', $to_currency)
            ->get(1)
            ->getRowArray();

            if (isset($data['value'])) {
                return $summa * $data['value'];
            }
        }

        return FALSE;
    }

    /*
    Конвертим через coinmarketcap
     */
    public function coinmarketcap($from_currency, $summa, $to_currency) {
        if ($from_currency == $to_currency) {
            return $summa;
        }
        if ($summa == 0) {
            return 0;
        }
        try {
            $curl = curl_init();

            curl_setopt_array($curl, array(
              CURLOPT_URL => 'https://pro-api.coinmarketcap.com/v1/tools/price-conversion?symbol='.$from_currency.'&amount=1&convert='.$to_currency,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'GET',
              CURLOPT_HTTPHEADER => array(
                'X-CMC_PRO_API_KEY: '.$this->api_coinmarketcap
              ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $response = json_decode($response);

            if (!isset($response->data->quote)) {
                return FALSE;
            }

            foreach ($response->data->quote as $code => $item) {
                if ($code == mb_strtoupper($to_currency)) {
                    return $item->price;
                }
            }

            return FALSE;
            
        } catch (\Exception $e) {
            log_message('error', print_r($e->getMessage(), TRUE));
            return FALSE;
        }
    }

    /*
    Сохраняем направление обмена в кеше
     */
    public function set_cash(string $from, string $to) {
        if ($this->db->table('courses_cash')
        ->where('currency1', $from)
        ->where('currency2', $to)
        ->countAllResults() > 0) {
            return FALSE;
        }

        $data = [];
        $data['currency1'] = mb_strtoupper($from);
        $data['currency2'] = mb_strtoupper($to);
        if ($data['currency2'] == $data['currency1']) {
            return FALSE;
        }
        if (!$this->db->table('courses_cash')->insert($data)) {
            return FALSE;
        }

        //обновляем курс валют
        return $this->update_cash();
    }

    /*
     * Обновляем кеш курсов конкретных пар обменника
     */

    public function update_cash() {
        $this->BinanceModel = new \Btc\Models\BinanceModel();

        $courses_cash = $this->db->table('courses_cash')->get()->getResultArray();
        foreach ($courses_cash as $item) {
            if ($item['currency1'] == $item['currency2']) {
                continue;
            }

            //смотрим в bybitexchange если не нашли
            // $value = $this->bybitexchange(1, $item['currency1'], $item['currency2']);
            // if ($value > 0) {
            //     $this->db->table('courses_cash')->where('id', $item['id'])->update(['value' => $value]);
            //     continue;
            // }
            
            //смотрим в криптонаторе если не нашли
            $value = $this->BinanceModel->convert($item['currency1'], 1, $item['currency2']);
            echo $item['currency1'].' => '.$item['currency2'].' '.$value;
            echo "<hr>";
            if ($value > 0) {
                $this->db->table('courses_cash')->where('id', $item['id'])->update(['value' => $value, 'updated' => date("Y-m-d H:i:s")]);
                continue;
            }
            
            if ($item['currency1'] == "UZS" OR $item['currency2'] == "UZS") {
                $value = $this->uzs($item['currency1'], $item['currency2']);
                if ($value > 0) {
                    $this->db->table('courses_cash')->where('id', $item['id'])->update(['value' => $value, 'updated' => date("Y-m-d H:i:s")]);
                    continue;
                }
            }

            if ($item['currency1'] == "XMR" OR $item['currency2'] == "RUB") { //XMR->USDT, USDT->RUB
                $value = $this->BinanceModel->convert($item['currency1'], 1, 'USDT');
                $value = $this->BinanceModel->convert('USDT', $value, $item['currency2']);
                if ($value > 0) {
                    $this->db->table('courses_cash')->where('id', $item['id'])->update(['value' => $value, 'updated' => date("Y-m-d H:i:s")]);
                    continue;
                }
            }

            if ($item['currency1'] == "RUB" OR $item['currency2'] == "XMR") { //XMR->USDT, USDT->RUB
                $value = $this->BinanceModel->convert($item['currency1'], 1, 'USDT');
                $value = $this->BinanceModel->convert('USDT', $value, $item['currency2']);
                if ($value > 0) {
                    $this->db->table('courses_cash')->where('id', $item['id'])->update(['value' => $value, 'updated' => date("Y-m-d H:i:s")]);
                    continue;
                }
            }
            
            //смотрим в криптонаторе если не нашли
            $value = $this->cryptonator(1, $item['currency1'], $item['currency2']);
            echo $item['currency1'].' => '.$item['currency2'].' '.$value;
            echo "<hr>";
            if ($value > 0) {
                $this->db->table('courses_cash')->where('id', $item['id'])->update(['value' => $value, 'updated' => date("Y-m-d H:i:s")]);
                continue;
            }

            //пытаемся сконвертить отсюда
            $value = $this->convert_coinmarketcap($item['currency1'], 1, $item['currency2']);
            if ($value !== FALSE) {
                $this->db->table('courses_cash')->where('id', $item['id'])->update(['value' => $value]);
                continue;
            }
        }
    }

    /*
    Получаем курс узбекский сум к BTC
     */
    public function uzs($currency1, $currency2, $nominal = 100) {
        $this->BlockchainModel = new \Btc\Models\BlockchainModel();

        if ($currency1 == "UZS" AND $currency2 == "BTC") {

            //получаем сколько стоит один узбекский сум в рублях
            $one_sum_in_rub = $this->convert($currency1, $nominal, "RUB");
            
            return $this->BlockchainModel->tobtc($one_sum_in_rub, "RUB");
        }

        if ($currency1 == "BTC" AND $currency2 == "UZS") {

            $one_btc = $this->BlockchainModel->torub(1, "RUB");

            return $this->convert("RUB", $one_btc, "UZS");
        }

        return 0;
    }

    /*
     * Конвертация через криптонатор
     * @docs https://ru.cryptonator.com/api
     */

    public function cryptonator($sum, string $currency1, string $currency2) {
        try {
            $url = "https://api.cryptonator.com/api/full/" . mb_strtolower($currency1) . "-" . mb_strtolower($currency2);
            $result = json_decode(file_get_contents($url));
            return isset($result->ticker->price) ? $sum * $result->ticker->price : 0;
        } catch (\Exception $e) {
            // log_message('error', print_r($e->getMessage(), TRUE));
            return 0;
        }
    }

    /*
    Конвертация через 
    @docs https://bybit-exchange.github.io/docs/inverse/#t-latestsymbolinfo
     */
    public function bybitexchange($sum, string $currency1, string $currency2) {
        try {
            $url = "https://api.bybit.com/v2/public/tickers?" . mb_strtoupper($currency1) . mb_strtoupper($currency2);
            $result = json_decode(file_get_contents($url));
            
            if (isset($result->result[0]->index_price)) {
                return $sum * $result->result[0]->index_price;
            }
            return 0;
        } catch (\Exception $e) {
            // log_message('error', print_r($e->getMessage(), TRUE));
            return 0;
        }
    }

    /*
     * Получить курсы валют ЦБ РФ и обновить в БД
     * запускается кроном
     * 
     * @docs http://www.cbr.ru/scripts/Root.asp?PrtId=SXML
     * @docs http://www.cbr.ru/scripts/Root.asp?PrtId=DWS
     * 
     * Если не удается получить курсы смотри тут
     * http://www.cbr-xml-daily.ru/
     */

    public function update() {
        try {
            $date = date("d/m/Y"); // Сегодняшняя дата в необходимом формате
            $link = "http://www.cbr-xml-daily.ru/daily.xml";
            //напрямую с ЦБ РФ - блокирует!
            //"http://www.cbr.ru/scripts/XML_daily.asp?date_req=$date"; // Ссылка на XML-файл с курсами валют
            $content = file_get_contents($link); // Скачиваем содержимое страницы
            $xml = new \SimpleXMLElement($content);
            if (count($xml) <= 0) {
                return FALSE;
            }
        } catch (\Exception $e) {
            // log_message('error', print_r($e->getMessage(), TRUE));
            return FALSE;
        }
        foreach ($xml as $k => $val) {
            $valute['updated'] = date("Y-m-d H:i:s");
            $valute['numcode'] = (string) $val->NumCode;
            $valute['charcode'] = (string) $val->CharCode;
            $valute['nominal'] = (int) $val->Nominal;
            $valute['name'] = (string) $val->Name;
            $valute['value'] = $this->tofloat((string) $val->Value);
            if ($this->db->table('courses_currency')->where('numcode', $valute['numcode'])->countAllResults() > 0) {
                $this->db->table('courses_currency')
                ->where('numcode', $valute['numcode'])
                ->update($valute);
            } else {
                $this->db->table('courses_currency')
                ->insert($valute);
            }
        }

        return TRUE;
    }

    /*
     * Получить курс валют по коду. 
     * Цифровому или буквенному
     * или получить все курсы валют
     */

    public function get($code = FALSE) {
        $db = $this->db->table('courses_currency');
        if ($code) {
            $db->where('numcode', $code);
            $db->orWhere('charcode', $code);
        }
        if ($db->countAllResults() <= 0) {
            return FALSE;
        }

        $db = $this->db->table('courses_currency');
        if ($code) {
            $db->where('numcode', $code);
            $db->orWhere('charcode', $code);
        }
        return $code ? $db->get()->getRowArray() : $db->get()->getResultArray();
    }

    /*
     * Получить курсы валют ЦБ РФ из БД 
     * в формате - код буквенный => значение
     */

    public function get_course_sbrf() {
        $currency_courses = $this->db->table('courses_currency')->get()->getResultArray();
        $res = [];
        foreach ($currency_courses as $item) {
            $res[$item['charcode']] = $item['value'];
        }
        return $res;
    }

    /*
     * Преобразует строку в дробное число
     * @link http://php.net/manual/ru/function.floatval.php
     */

    private function tofloat($num) {
        $dotPos = strrpos($num, '.');
        $commaPos = strrpos($num, ',');
        $sep = (($dotPos > $commaPos) AND $dotPos) ? $dotPos :
        ((($commaPos > $dotPos) AND $commaPos) ? $commaPos : FALSE);

        if (!$sep) {
            return floatval(preg_replace("/[^0-9]/", "", $num));
        }

        return floatval(
            preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
            preg_replace("/[^0-9]/", "", substr($num, $sep + 1, strlen($num)))
        );
    }

}
