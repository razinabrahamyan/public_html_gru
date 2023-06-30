<?php namespace Admin\Models;

/**
 * Name:    Модель для работы с кнопками бота
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
 * Class ButtonsModel
 */
class ButtonsModel
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
	Получить кнопки
	 */
	public function buttons() {
		$db = $this->db->table('menu_buttons');
        $db->where('menu_buttons.active', 1);
        $db->select('menu_buttons.*');
        $db->select('menus.name as name_menu');
		$db->join('menus', 'menu_buttons.id_menu = menus.id', 'left');
        $pages = $db->get();
        $return = [];
        foreach ($pages->getResultArray() as $page) {
            $page['name'] = json_decode($page['name']);
        	$return[]=$page;
        }
        return $return;
	}

	/*
	Получить данные кнопки
	 */
	public function get($id) {
		$db = $this->db->table('menu_buttons');
        $db->where('id', $id);
        $data = $db->get()->getRowArray();
        $data = $this->from_json($data);
        return $data;
	}

    /*
    Сконвертировать из JSON названия
     */
    public function from_json($data, $field_ = "name") {
        foreach($data as $field => $value) {
            if (mb_stripos($field, $field_) !== FALSE) {
                $data[$field] = json_decode($value);
            }
        }
        return $data;
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
    Сохраняем данные кнопки
     */
    public function set($data) {
        $id = (int) $data['id'];

        $this->LangModel = new \Admin\Models\LangModel();

        foreach($data as $field => $value) {
            if (mb_stripos($field, "nameru") !== FALSE) {
                //сохраняем название кнопки
                $db = $this->db->table('menu_buttons');
                $db->where('id', $id);
                $db->update(['name' => json_encode(trim($data['nameru']))]);
                continue;
            } else {
                if (mb_stripos($field, "name") !== FALSE) {
                    //сохраняем переводы для языков
                    $short = trim(str_ireplace("name", "", $field));
                    $this->LangModel->trans_btn_set($id, $short, $value);
                }
            }
        }

        return TRUE;
    }
}
