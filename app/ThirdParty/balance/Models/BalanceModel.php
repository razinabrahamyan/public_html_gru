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
namespace Balance\Models;

use CodeIgniter\Model;
use \CodeIgniter\Database\ConnectionInterface;

/**
 * Class ButtonsModel
 */
class BalanceModel
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
    Получить список баланса
     */
    public function items($chat_id = FALSE) {
        $db = $this->db->table('balance');
        if ($chat_id !== FALSE) {
            $db->where('chat_id', $chat_id);
        }
        return $db->get()->getResultArray();
    }

    /*
    Всего выплачено всем/партнеру
     */
    public function total_payed($chat_id_parent = FALSE) {
        $db = $this->db->table('balance');
        $db->where('value<', 0);
        if ($chat_id_parent !== FALSE) {
            $db->where('chat_id', $chat_id_parent);
        }
        $db->selectSum('value');
        return abs($db->get()->getRow()->value);
    }

    /*
    Проверить было ли начисление такое уже по уникальному параметру
     */
    public function have_balance($chat_id, string $field = "chat_id_aff", $value, $type = FALSE): bool {
        $db = $this->db->table('balance');
        $db->where('chat_id', $chat_id);
        $db->where($field , $value);
        if ($type) {
            $db->where('type', $type);
        }
        return $db->countAllResults() > 0;
    }

    /*
    Проверяет есть ли не завершенные транзакции у пользователя
     */
    public function have_no_finish($chat_id): bool {
        return $this->db->table('balance')
                    ->where('finish', 0)
                    ->where('chat_id', $chat_id)
                    ->limit(1)
                    ->countAllResults() > 0;
    }

    /*
     * Добавить пополнение/списание баланса
     */

    public function add($data) {
        if ($data['value'] == 0) {
            return FALSE;
        }
        if (isset($data['value'])) {
            $data['value'] = floatval(str_ireplace(",", ".", $data['value']));
        }

        if (isset($data['finish']) AND $data['finish'] <= 0) {
            //если это добавление не завершенной транзакции
            //тогда проверяем есть ли не завершенные у него еще 
            //не даем создать транзакцию пока не завершит старые
            if ($this->have_no_finish($data['chat_id'])){
                return FALSE;
            }
        }

        if ($data['value'] < 0) {//защита от излишнего списания
            $type = isset($data['type']) ? $data['type'] : FALSE;
            $dif = $this->dif($data['chat_id'], $data['value']);
            if ($dif > 0) { //если не хватает на балансе
                //то урованиваем сумму чтобы не было отрицательного баланса
                $data['value'] = $data['value'] - $dif;
            }
        }

        if (!isset($data['created'])) {
            $data['created'] = date("Y-m-d H:i:s");
        }

        //открываем транзакцию
        $this->db->transBegin();

        //заносим транзакцию
        $this->db->table('balance')->insert($data);
        $id = $this->db->insertID();
        
        //закрываем транзакцию
        $this->db->transComplete();
        if ($this->db->transStatus() === FALSE) {
            $this->db->transRollback(); //откатить изменения
            return FALSE;
        }

        $this->db->transCommit(); //зафиксировать изменения в БД
        return $id;
    }

    /*
     * Сколько не хватает на балансе
     */

    public function dif($chat_id, $sum, $type = FALSE) {
        $balance = $this->get($chat_id, $type);
        $dif = $balance - abs($sum);
        return $dif < 0 ? abs($dif) : 0;
    }

    /*
    Сохранить данные баланса
     */
    public function set(array $data): bool{
        if (isset($data['value'])) {
            $data['value'] = floatval(str_ireplace(",", ".", $data['value']));
        }
        $db = $this->db->table('balance');
        $db->where('id', $data['id']);
        return $db->update($data);
    }

    /*
    Получить данные баланса
     */
    public function get_data(int $id) {
        $db = $this->db->table('balance');
        $db->where('id', $id);
        return $db->get()->getRowArray();
    }

    /*
    Удалить данные пользователя
     */
    public function delete_user(int $chat_id) {
        $this->db()->table('balance')->delete(['chat_id_aff' => $chat_id]);
        return $this->db()->table('balance')->delete(['chat_id' => $chat_id]);
    }

    /*
    Удалить запись баланса
     */
    public function delete(int $id) {
        return $this->db()->table('balance')->delete(['id' => $id]);
    }

    /*
    Доход
     */
    public function in($chat_id = FALSE, $type = FALSE, $currency = FALSE) {
        $db = $this->db->table('balance');
        if ($chat_id !== FALSE) {
            $db->where('chat_id', $chat_id);
        }
        if ($type !== FALSE) {
            $db->where('type', $type);
        }
        if ($currency !== FALSE) {
            $db->where('currency', $currency);
        }
        $db->where('value>', 0);
        $db->where('finish', 1);
        $db->selectSum('value');
        $in = $db->get()->getRow()->value;
        if ($in <= 0) {
            return 0;
        }
        return $in;
    }

    /*
    Получить баланс

    @param $chat_id - id пользователя
    @param $type - тип баланса
    @param $currency - определенная валюта (баланс может быть в разных валютах)
     */
    public function get($chat_id = FALSE, $type = FALSE, $currency = FALSE) {
        $db = $this->db->table('balance');
        if ($chat_id !== FALSE) {
            $db->where('chat_id', $chat_id);
        }
        if ($type !== FALSE) {
            $db->where('type', $type);
        }
        if ($currency !== FALSE) {
            $db->where('currency', $currency);
        }
        $db->where('value>', 0);
        $db->where('finish', 1);
        $db->selectSum('value');
        $in = $db->get()->getRow()->value;
        if ($in <= 0) {
            return 0;
        }

        $db = $this->db->table('balance');
        if ($chat_id !== FALSE) {
            $db->where('chat_id', $chat_id);
        }
        if ($type !== FALSE) {
            $db->where('type', $type);
        }
        if ($currency !== FALSE) {
            $db->where('currency', $currency);
        }
        $db->where('value<', 0);
        $db->where('finish', 1);
        $db->selectSum('value');
        $out = $db->get()->getRow()->value;

        return ($in - abs($out));
    }

}
