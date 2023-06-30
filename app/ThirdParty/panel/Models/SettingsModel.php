<?php namespace Admin\Models;

/**
 * Name:    Модель для работы с страницами бота
 *
 * Created:  03.04.2020
 *
 * Description:  
 *
 * Requirements: PHP 7.2 or above
 *
 * @author     Krotov Roman <tg: @KrotovRoman>
 */
use CodeIgniter\Model;
use \CodeIgniter\Database\ConnectionInterface;

/**
 * Class UsersModel
 */
class SettingsModel
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

        $all_settings = $this->all();
        foreach ($all_settings as $name_group => $settings) {
            foreach ($settings as $item) {
                $this->{$item['name']} = $item['value'];
            }
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
    Сохранение настроек
     */
    public function save($post) {
        $arr_checkbox = [];
        foreach ($post as $name => $value) {
            $data = $this->get_data($name);
            if ($data['type'] == "checkbox") {
                $value = $value == "on" ? 1 : 0;
                $arr_checkbox[] = $name;
            }

            $this->set($name, $value);
        }

        //помечаем отсутствующие чекбоксы
        if (count($arr_checkbox) > 0) {
            $settings = $this->db->table('settings')->getWhere(['type' => "checkbox"]);
            foreach ($settings->getResultArray() as $item) {
                if (!in_array($item['name'], $arr_checkbox)) {
                    $this->set($item['name'], 0);
                }
            }
        } else {
            $settings = $this->db->table('settings')->getWhere(['type' => "checkbox"]);
            foreach ($settings->getResultArray() as $item) {
                $this->set($item['name'], 0);
            }
        }
        

        return TRUE;
    }

    /*
     * Записать настройку
     */

    public function set($name, $value) {
        //если поле зашифровано
        $data_set = $this->get_data($name);

        if (!empty($value) AND $data_set['type'] == "encrypted") { //шифруем защищенное поле
            $value = base64_encode($this->encryption->encrypt($value));
        }

        $db = $this->db->table('settings');
        $db->where('name', $name);
        return $db->update(['value' => $value]);
    }

    /*
    Получить данные настройки
     */
    public function get_data($name){ 
        $db = $this->db->table('settings');
        $db->where('name', $name);
        $result = $db->get(1);
        return $db->countAll() <= 0 ? FALSE : $result->getRowArray();
    }

    /*
     * Получить настройки по названию
     */
    
    public function get($name){  
        $db = $this->db->table('settings');
        $db->where('name', $name);
        $settings = $db->get(1);

        $value = $settings->getRow()->value;
        if (!empty($value) AND $settings->getRow()->type == "encrypted") { //расшифровываем настройку
            $value = $this->encryption->decrypt(base64_decode($value));
        }
        
        return $value;
    }

	/*
	Получить страницы
	 */
	public function all($is_array = FALSE) {
		$db = $this->db->table('settings');
        $db->where('settings.active', 1);
        $db->where('settings_groups.active', 1);
        $db->select('settings.*');
        $db->select('settings_groups.name as name_group');
        $db->orderBy('settings_groups.priority, settings.priority', 'DESC');
        $db->join('settings_groups', 'settings.id_group = settings_groups.id');
        $return = [];
        foreach ($db->get()->getResultArray() as $item) {
            if (!empty($item['value']) AND $item['type'] == "encrypted") {
                $item['value'] = $this->encryption->decrypt(base64_decode($item['value']));
            }
            if ($is_array) {
                $return[]=$item;
            } else {
                $return[$item['name_group']][]= $item;
            }
        }
        return $return;
    }
}
