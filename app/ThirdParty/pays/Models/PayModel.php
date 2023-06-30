<?php 

/**
 * Name:    Модель для работы с партнерской программой
 *
 * Created:  03.04.2020
 *
 * Description:  
 *
 * Requirements: PHP 7.2 or above
 *
 * @author     Krotov Roman <tg: @KrotovRoman>
 */
namespace Pays\Models;

use CodeIgniter\Model;
use \CodeIgniter\Database\ConnectionInterface;

/**
 * Class ButtonsModel
 */
class PayModel
{
	/**
	 * Database object
	 *
	 * @var \CodeIgniter\Database\BaseConnection
	 */
	protected $db;
	protected $config;
    protected $count_levels;
    protected $aff_links;

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
    Удалить запись способа оплаты
     */
    public function delete(int $id) {
        //ставим всем заказам способ оплаты - 0
        $this->db()->table('orders')->where('id_pay', $id)->update(['id_pay' => 0]);

        //удаляем настройки способа оплаты
        $this->db()->table('pay_settings')->delete(['id_pay' => $id]);

        //удаляем способ оплаты
        return $this->db()->table('pay_methods')->delete(['id' => $id]);
    }

    /*
    Добавить способ оплаты
    ручной
     */
    public function add(array $data){

        $data = $this->to_json($data);
        $data['id'] = rand();
        $id_pay = $data['id'];
        if ($this->db->table('pay_methods')->insert($data)) {
            //добавляем настройки
            $data_settings = [];
            $data_settings['id_pay'] = $id_pay;
            $data_settings['name'] = 'number';
            $data_settings['comment'] = 'Реквизиты';
            $this->db()->table('pay_settings')->insert($data_settings);

            return $id_pay;
        }

        return FALSE;
    }

    /*
    Сконвертировать в JSON названия
     */
    public function to_json($data, $field_ = "name") {
        foreach($data as $field => $value) {
            if (mb_stripos($field, $field_) !== FALSE) {
                $data[$field] = json_encode($value);
            }
        }
        return $data;
    }

	/*
    Сохранение настроек
     */
    public function pay_settings_set(int $id_pay, $post) {

        $arr_checkbox = [];
        foreach ($post as $name => $value) {
            $data = $this->get_data($id_pay, $name);
            if (isset($data['type']) AND $data['type'] == "checkbox") {
                $value = $value == "on" ? 1 : 0;
                $arr_checkbox[] = $name;
            }

            $this->set($id_pay, $name, $value);
        }

        //помечаем отсутствующие чекбоксы
        $db = $this->db->table('pay_settings');
        $db->where('type', "checkbox");
        $db->where('id_pay', $id_pay);
        $settings = $db->get();
        foreach ($settings->getResultArray() as $item) {
            if (!in_array($item['name'], $arr_checkbox)) {
                $this->set($id_pay, $item['name'], 0);
            }
        }
        

        return TRUE;
    }

    /*
    Получить id способа оплаты включенного
     */
    public function get_pay_one() {
        $items = $this->items(TRUE);
        return count($items) <= 0 ? FALSE : $items[0]['id'];
    }

	/*
     * Записать настройку
     */

    public function set(int $id_pay, string $name, $value) {
        //если поле зашифровано
        $data_set = $this->get_data($id_pay, $name);

        if (isset($data_set['type']) AND !empty($value) AND $data_set['type'] == "encrypted") { //шифруем защищенное поле
            $value = base64_encode($this->encryption->encrypt($value));
        }

        $db = $this->db->table('pay_settings');
        $db->where('name', $name);
        $db->where('id_pay', $id_pay);
        if (!$db->update(['value' => $value])) {
            return FALSE;
        }

        return TRUE;
    }

	/*
     * Получить настройки по названию
     */
    
    public function get(int $id_pay, string $name){  
        $db = $this->db->table('pay_settings');
        $db->where('name', $name);
        $db->where('id_pay', $id_pay);
        $settings = $db->get(1);

        if (!isset($settings->getRow()->value)) {
            return "";
        }
        
        $value = $settings->getRow()->value;
        if (!empty($value) AND $settings->getRow()->type == "encrypted") { //расшифровываем настройку
            $value = $this->encryption->decrypt(base64_decode($value));
        }
        
        return $value;
    }

    /*
    Сохранить данные способа оплаты
     */
    public function pay_set($data){  
    	$db = $this->db->table('pay_methods');

    	if (isset($data['name'])) {
    		$data['name'] = json_encode($data['name']);
    	}

    	//удаляем поля которых нет в этой таблице
    	foreach ($data as $field => $value) {
    		if (!$this->db->fieldExists($field, 'pay_methods')) {
    			unset($data[$field]);
    		}
    	}
    	
    	$db = $this->db->table('pay_methods');
        $db->where('id', $data['id']);
        if (!$db->update($data)) {
            return FALSE;
        }

        if (isset($data['currency'])) {
            $this->CourseModel->set_cash($this->currency_cod, $data['currency']);
        }

        return TRUE;
    }

    /*
    Получить данные способа оплаты
     */
    public function pay(int $id_pay){  
    	$db = $this->db->table('pay_methods');
        $db->where('id', $id_pay);
        $pay_methods = $db->get(1)->getRowArray();
        if (!isset($pay_methods['id'])) {
            return FALSE;
        }
        $pay_methods['name'] = json_decode($pay_methods['name']);
        return $pay_methods;
    }

	/*
    Получить данные настройки
     */
    public function get_data(int $id_pay, string $name){ 
        $db = $this->db->table('pay_settings');
        $db->where('name', $name);
        $db->where('id_pay', $id_pay);
        $result = $db->get(1);
        return $db->countAll() <= 0 ? FALSE : $result->getRowArray();
    }

    /*
    Получить список способов оплаты
     */
    public function items($active = FALSE):array {
        $db = $this->db->table('pay_methods');
        $db->where('hidden', 0);
        if ($active) {
            $db->where('active', 1);
        }
        $db->orderBy('priority', "DESC");
        $items = $db->get();

        $return = [];
        foreach ($items->getResultArray() as $item) {
            $item['name'] = json_decode($item['name']);
            $return[]=$item;
        }
        return $return;
    }

    /*
	Получить все настройки способа оплаты
	 */
	public function settings(int $id_pay) {
		$db = $this->db->table('pay_settings');
        $db->where('pay_settings.id_pay', $id_pay);
        $return = [];
        foreach ($db->get()->getResultArray() as $item) {
            if (!empty($item['value']) AND $item['type'] == "encrypted") {
                $item['value'] = $this->encryption->decrypt(base64_decode($item['value']));
            }
            if ($item['type'] == "blockquote") {
            	$item['comment'] = $this->fill($item['comment'], ['url' => base_url(), 'ip' => $_SERVER['SERVER_ADDR']]);
            }
            $return[]=$item;
        }
        return $return;
    }

    /*
    Заполнить массивом

    @param $text - шаблон
    @param $fill - массив параметров
     */
    public function fill(string $text, array $fill = []): string {

        //заполнить массивом
        foreach ($fill as $field => $value) {
            $text = str_ireplace("{" . $field . "}", $value, $text);
        }

        return $text;
    }
    
}
