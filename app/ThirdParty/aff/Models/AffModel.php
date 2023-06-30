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
namespace Aff\Models;

use CodeIgniter\Model;
use \CodeIgniter\Database\ConnectionInterface;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Class ButtonsModel
 */
class AffModel
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
        $this->ionAuth = new \IonAuth\Libraries\IonAuth();

        $this->SettingsModel = new \Admin\Models\SettingsModel();

        $settings = $this->SettingsModel->all(TRUE);
        foreach ($settings as $settings_) {
            $this->{$settings_['name']} = $settings_['value'];
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
    * Экспорировать в эскель
    *
    * @param bool $download - TRUE - скачать щас, иначе вернет путь к файлу на сервере
    * @docs https://phpspreadsheet.readthedocs.io/en/latest/
     */
    public function export($chat_id, $download = FALSE) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Уровень');
        $sheet->setCellValue('B1', 'Имя');
        $sheet->setCellValue('C1', 'Фамилия');
        $sheet->setCellValue('D1', 'Username');
        $sheet->setCellValue('E1', 'Доход');
        $sheet->setCellValue('F1', 'id');

        //задаем автоширину колонок
        $sheet->getColumnDimension('A')->setAutoSize(TRUE);
        $sheet->getColumnDimension('B')->setAutoSize(TRUE);
        $sheet->getColumnDimension('C')->setAutoSize(TRUE);
        $sheet->getColumnDimension('D')->setAutoSize(TRUE);
        $sheet->getColumnDimension('E')->setAutoSize(TRUE);

        if (!$tree_invited = $this->tree_invited($chat_id)) {
            return FALSE;
        }

        $i = 1;
        foreach ($tree_invited as $user) {
            $i++;
            $profit = $this->profit_invited($user['chat_id'], $chat_id);
            $sheet->setCellValue('A'.$i, $user['level']);
            $sheet->setCellValue('B'.$i, json_decode($user['first_name']));
            $sheet->setCellValue('C'.$i, json_decode($user['last_name']));
            $sheet->setCellValue('D'.$i, $user['username']);
            $sheet->setCellValue('E'.$i, number_format($profit, $this->decimals, ',', ' '));
            $sheet->setCellValue('F'.$i, $user['chat_id']);
        }

        $writer = new Xlsx($spreadsheet);
        
        $filename = date("dmY").'.Xlsx';
        
        if ($download) {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"{$filename}\"");
            $writer->save('php://output');
        } else {
            $path = ROOTPATH.'/writable/uploads/'.$filename;
            $writer->save($path);
            return $path;
        }
    }

    /*
    Сколько заработал с конкретного уровня
     */
    public function profit_invited_level(int $chat_id, $level) {
        $profit_levels = $this->profit_invited_levels($chat_id);
        foreach ($profit_levels as $level_ => $sum) {
            if ($level_ == $level) {
                return $sum;
            }
        }
        return 0;
    }

    /*
    Получить сколько заработал с каждого уровня
     */
    public function profit_invited_levels(int $chat_id): array {
        if (!$tree_invited = $this->tree_invited($chat_id)) {
            return [];
        }

        $res = [];
        foreach ($tree_invited as $user) {
            if (empty($res[$user['level']])) {
                $res[$user['level']] = 0;
            }
            $res[$user['level']]+=$this->profit_invited($user['chat_id'], $chat_id);
        }

        return $res;
    }

    /*
    Прибыль с приглашенного партнера
    смотрим все заказы этого пользователя
    и считаем балансы в которых есть этот id_order
     */
    public function profit_invited(int $chat_id_aff, int $chat_id_parent) {
        return $this->db->table('balance')
            ->where('value>', 0)
            ->where('chat_id_aff', $chat_id_aff)
            ->where('chat_id', $chat_id_parent)
            ->selectSum('value')
            ->get()
            ->getRow()
            ->value;
    }

    /*
    Получить массив дерева партнеров
     */
    public function aff_links($required = FALSE) {
        if ($required) { //если принудительно
            $this->aff_links = $this->db->table('aff_links')->get()->getResultArray();
            return $this->aff_links;
        }
        if (empty($this->aff_links)) {
            $this->aff_links = $this->db->table('aff_links')->get()->getResultArray();
        }
        return $this->aff_links;
    }

    /*
    Получаем количество уровней в партнерке
     */
    public function count_levels() {
        if (empty($this->count_levels)) {
            $res = $this->db->table('aff_settings')
            ->groupBy('level')
            ->get()
            ->getResultArray();
            
            $this->count_levels = count($res);
        }
        return $this->count_levels;
    }

    /*
    Определяем количество приглашенных
     */
    public function count_invite($chat_id) {
        $db = $this->db->table('aff_tree');
        $db->where('chat_id_parent', $chat_id);
        $db->groupBy('chat_id_invited');
        return $db->countAllResults();
    }

    /*
     * Получить массив комиссионных которые будут начислены родителям
     *
     * @param $chat_id - id пользователя который совершил целевое действие
     * @param $price - цена действия (сумма заказа например который оплатил) от него будет считаться процент
     * @param $id_order - id действия - например номер заказа (для защиты повторного начисления за действие)
     */
    public function array_comissions($chat_id_invited, $price = 0){

        //получаем дерево партнеров
        $mas_aff = $this->get_mas_parent($chat_id_invited);
        
        //получаем количество уровней в партнерской программе из настроек комиссионных для каждого уровня
        $count_levels = $this->count_levels();
        if ($count_levels < 1 OR count($mas_aff) < 1){
            return [];
        }
        
        $created = date("Y-m-d H:i:s");
        $level = 0;
        $arr_return = [];
        while ($count_levels >= 1) {
            $count_levels--;
            if (array_key_exists($level, $mas_aff)) {
                $chat_id = $mas_aff[$level];
                $level++; //не передвигать
                if ($chat_id_invited == $chat_id){
                    continue; //самому себе не надо начислять
                }

                //получаем % комиссионных за нужны уровень партнера
                if (!$comission_data = $this->get_settings_comissions($level)){
                    continue; //если настройки комиссионных удалены для этого уровня
                }
                
                $data_com['created'] = $created;
                $data_com['chat_id'] = $chat_id;
                
                //начисляем комиссионные от прибыли
                $data_com['sum'] = $comission_data['sum'] > 0 ? $comission_data['sum'] : ($price / 100) * $comission_data['percent'];
                
                if ($data_com['sum'] <= 0) {
                    continue;
                }

                $arr_return[] = $data_com;

            }// if (array_key_exists($level, $mas_aff)) { 
        } //while ($count_levels>=1) {  

        return $arr_return; //возвращаем массив начисленных комиссионных
    }

    /*
    Удалить связь между партнерами
     */
    public function delete_link($chat_id_parent, $chat_id_invited) { 
        $this->db->table('aff_tree')->delete(['chat_id_invited' => $chat_id_invited]);
        $this->db->table('aff_tree')->delete(['chat_id_parent' => $chat_id_parent]);

        $this->db->table('aff_links')->delete(['chat_id_parent' => $chat_id_parent, 'chat_id_invited' => $chat_id_invited]);

        return $this->db->table('aff_links')
        ->where('chat_id_parent',  $chat_id_parent)
        ->where('chat_id_invited',  $chat_id_invited)
        ->update(['chat_id_invited' => 0]);
    }

    /*
    Получить массив тех кого пригласил
     */
    public function tree_invited(int $chat_id_parent) {
        $db = $this->db->table('aff_tree');
        $db->where('aff_tree.chat_id_parent', $chat_id_parent);
        $db->groupBy('aff_tree.chat_id_invited');
        $db->join('users', 'aff_tree.chat_id_invited = users.chat_id');
        if ($db->countAllResults() <= 0) {
            return FALSE;
        }

        $db = $this->db->table('aff_tree');
        $db->where('aff_tree.chat_id_parent', $chat_id_parent);
        $db->groupBy('aff_tree.chat_id_invited');
        $db->orderBy('aff_tree.level', "ASC");
        $db->join('users', 'aff_tree.chat_id_invited = users.chat_id');
        $db->select('aff_tree.*');
        $db->select('users.first_name, users.last_name, users.username, users.chat_id');
        return $db->get()->getResultArray();
    }

    /*
    Количество приглашенных в моей структуре или вообще в боте
     */
    public function total_aff($chat_id_parent = FALSE) {
        if (!$chat_id_parent) {
            return $this->db->table('users')->where('active', 1)->countAllResults();
        }

        $db = $this->db->table('aff_tree');
        $db->where('chat_id_parent', $chat_id_parent);
        $db->select('id');
        $return = $db->countAllResults();

        return $return;
    }

    /*
    Получить массив уровней и количество партнеров в них
     */
    public function count_aff_by_levels($chat_id_parent = FALSE) {
        $settings = $this->items();
        $result = [];
        foreach($settings as $set) {
            $result[$set['level']] = $this->total_aff_by_levels($set['level'], $chat_id_parent);
        }
        return $result;
    }

    /*
    Количество партнеров на определенном уровне
    Для всей системы или для конкретного партнера
     */
    public function total_aff_by_levels($level = 1, $chat_id_parent = FALSE) {
        $db = $this->db->table('aff_tree');
        $db->where('level', $level);
        if ($chat_id_parent !== FALSE) {
            $db->where('chat_id_parent', $chat_id_parent);
        }
        return $db->countAllResults();
    }

    /*
    Получить настройки бонусов
     */
    public function bonuses() {
        $db = $this->db->table('aff_bonuses');
        $db->join('products', 'aff_bonuses.id_product = products.id');
        $db->groupBy('aff_bonuses.id');
        $db->select('aff_bonuses.*');
        $db->select('products.name');
        $db->orderBy('aff_bonuses.count_pays', 'ASC');
        $return = $db->get()->getResultArray();
        $res = [];
        foreach ($return as $item) {
            $item['name'] = json_decode($item['name']);
            $res[]=$item;
        }
        return $res;
    }

    /*
    Дарим бонус родителю за приведенную продажу
     */
    public function gift_bonus(int $id_order) {
        $bonuses = $this->bonuses();
        if (count($bonuses) <= 0) {
            return FALSE; //если нет настроек бонусов
        }

        //смотрим продукты в заказе
        $this->OrderModel = new \Orders\Models\OrderModel();

        //получаем родителя этого клиента
        $data_order = $this->OrderModel->get($id_order);
        if (!$chat_id_parent = $this->chat_id_parent($data_order['chat_id'])) {
            return FALSE; //если у клиента нет родителя
        }

        $this->BalanceModel = new \Balance\Models\BalanceModel();
        $this->TelegramModel = new \App\Models\TelegramModel();

        //получаем все продукты в заказе
        $products_in = $this->OrderModel->products($id_order);
        foreach ($bonuses as $bonus) {
            foreach ($products_in as $product_in) {
                if (
                    //если в заказе нашли продукт который указан в бонусе
                    $bonus['id_product'] == $product_in['id_product'] 
                    AND  //и совершено покупок приглашенными нужное количество
                    $bonus['count_pays'] == $this->count_payed_invited($chat_id_parent)
                    AND //и не начисляли еще за такую настройку бонус
                    !$this->have_bonus($chat_id_parent, $bonus['id'])
                ) {
                    //начисляем бонус
                    $data = [];
                    $data['chat_id'] = $chat_id_parent;
                    $data['id_order'] = $id_order;
                    $data['value'] = $bonus['sum'];
                    $data['comment'] = "Бонус за ".$bonus['count_pays']." оплат";
                    $data['type'] = "bonus";
                    $data['currency'] = $this->currency_cod;
                    if (!$this->BalanceModel->add($data)) {
                        continue;
                    }

                    //уведомляем что начислены комиссионные
                    $this->TelegramModel->notify_new_bonus($data, $data_order);
                }
            }
        }
    }

    /*
    начисляем комиссионные за оплату заказа
     */
    public function set_comission(int $id_order): bool {
        $this->BalanceModel = new \Balance\Models\BalanceModel();
        $this->TelegramModel = new \App\Models\TelegramModel();
        $this->OrderModel = new \Orders\Models\OrderModel();

        $data_order = $this->OrderModel->get($id_order);
        
        //получаем массив комиссионных которые нужно начислить текущему клиенту
        $comissions = $this->array_comissions($data_order['chat_id'], $data_order['sum']);

        //обходим и начисляем на баланс
        foreach ($comissions as $comission) {
            //проверяем было ли такое начисление уже
            if ($this->BalanceModel->have_balance($comission['chat_id'], 'id_order', $data_order['id'])) {
                continue;
            }

            //начисляем комиссионные
            $data = [];
            $data['chat_id'] = $comission['chat_id'];
            $data['id_order'] = $data_order['id'];
            $data['chat_id_aff'] = $data_order['chat_id'];
            $data['value'] = $comission['sum'];
            $data['comment'] = "№".$data_order['id'];
            $data['type'] = "aff";
            $data['currency'] = $this->currency_cod;
            if (!$this->BalanceModel->add($data)) {
                continue;
            }

            //уведомляем что начислены комиссионные
            $this->TelegramModel->notify_new_comission($comission, $data_order);
        }

        //начисляем бонус тому кто привел клиента
        $this->gift_bonus($id_order);

        return TRUE;
    }

    /*
    Получаем родителя этого пользователя
     */
    public function chat_id_parent(int $chat_id_invited) {
        if ($this->db->table('aff_links')
                    ->where('chat_id_invited', $chat_id_invited)
                    ->countAllResults() <= 0) {
            return FALSE;
        }

        return $this->db->table('aff_links')
                    ->where('chat_id_invited', $chat_id_invited)
                    ->select('chat_id_parent')
                    ->get()
                    ->getRow()->chat_id_parent;
    }

    /*
     Количество оплат приглашенных пользователей
     */
    public function count_payed_invited(int $chat_id_parent): int {
        $count = 0;
        $chat_id_clients = $this->chat_id_clients($chat_id_parent);
        foreach ($chat_id_clients as $item) {
            //смотрим количество оплаченных заказов этого клиента
            $count+= $this->count_payed_orders($item['chat_id']);
        }
        return $count;
    }

    /*
    Количество оплаченных заказов у этого пользователя
     */
    public function count_payed_orders(int $chat_id): int {
        return $this->db->table('orders')
            ->where('chat_id', $chat_id)
            ->where('status', 1)
            ->countAllResults();
    }

    /*
    Массив приведенных пользователей этим родителем
     */
    public function chat_id_clients(int $chat_id_parent): array {
        return $this->db->table('aff_links')
                    ->where('chat_id_parent', $chat_id_parent)
                    ->select('chat_id_invited as chat_id')
                    ->get()
                    ->getResultArray();
    }

    /*
    Начисляли или нет пользователю за эту настроку бонусов на баланс
     */
    public function have_bonus(int $chat_id, int $id_bonus_set): bool {
        return $this->db->table('balance')
                    ->where('id_bonus', $id_bonus_set)
                    ->where('chat_id', $chat_id)
                    ->limit(1)
                    ->countAllResults() > 0;
    }

    /*
    Добавить бонус
     */
    public function add_bonus(array $data){
        if (isset($data['sum'])) {
            $data['sum'] = floatval(str_ireplace(",", ".", $data['sum']));
        }

        if ($this->db->table('aff_bonuses')->insert($data)) {
            return $this->db->insertID();
        }

        return FALSE;
    }

    /*
    Получить уровни комиссионных
     */
    public function items() {
        return $this->db->table('aff_settings')->orderBy('level', 'ASC')->get()->getResultArray();
    }

    /*
    Добавить настройки уровня
     */
    public function add(array $data){
        if (isset($data['sum'])) {
            $data['sum'] = floatval(str_ireplace(",", ".", $data['sum']));
        }

        if ($this->db->table('aff_settings')->insert($data)) {
            return $this->db->insertID();
        }

        return FALSE;
    }

    /*
    Сохраняем настройки бонуса
     */
    public function set_bonus(array $data): bool{

        if (isset($data['sum'])) {
            $data['sum'] = floatval(str_ireplace(",", ".", $data['sum']));
        }

        $db = $this->db->table('aff_bonuses');
        $db->where('id', $data['id']);
        return $db->update($data);
    }

    /*
    Сохранить данные настройки
     */
    public function set(array $data): bool{

        if (isset($data['sum'])) {
            $data['sum'] = floatval(str_ireplace(",", ".", $data['sum']));
        }
        if (isset($data['percent'])) {
            $data['percent'] = floatval(str_ireplace(",", ".", $data['percent']));
        }

        $db = $this->db->table('aff_settings');
        $db->where('id', $data['id']);
        return $db->update($data);
    }

    /*
    Удалить данные пользователя
     */
    public function delete_user(int $chat_id) {
        $this->db()->table('aff_tree')->delete(['chat_id_invited' => $chat_id]);
        $this->db()->table('aff_tree')->delete(['chat_id_parent' => $chat_id]);

        $this->db()->table('aff_links')->delete(['chat_id_invited' => $chat_id]);
        return $this->db()->table('aff_links')->delete(['chat_id_parent' => $chat_id]);
    }

    /*
    Удалить запись настройки бонуса
     */
    public function delete_bonus(int $id) {
        return $this->db()->table('aff_bonuses')->delete(['id' => $id]);
    }

    /*
    Удалить запись настройки уровня комиссионных
     */
    public function delete(int $id) {
        return $this->db()->table('aff_settings')->delete(['id' => $id]);
    }

    /*
    Получить данные настройки комиссионных
     */
    public function get(int $id) {
        $db = $this->db->table('aff_settings');
        $db->where('id', $id);
        return $db->get()->getRowArray();
    }

    /*
    Получить данные бонуса
     */
    public function get_bonus(int $id) {
        $db = $this->db->table('aff_bonuses');
        $db->where('id', $id);
        return $db->get()->getRowArray();
    }

    /*
     * Получаем процент комиссионных
     */
    public function get_settings_comissions(int $level, $chat_id = FALSE){
        $db = $this->db->table('aff_settings');
        $db->where('level', $level);
        $data = $db->get()->getRowArray();
        return empty($data['id']) ? FALSE : $data;
    }

    /*
    Очистить дерево партнерской программы
     */
    public function truncate() {
        $this->db->table('aff_links')->truncate();
        return $this->db->table('aff_tree')->truncate();
    }

	/*
    массив партнеров в памяти
     */
    private function item_aff_link($chat_id){
        foreach($this->aff_links() as $item) {
            if ($item['chat_id_invited'] == $chat_id) {
                return $item;
            }
        }
        return FALSE;
    }

    /*
    Получить цепочку пригласителей в порядке 
    с самого старшего родителя к младшему - кто кого пригласил
     */
    public function get_mas_parent_full($chat_id_invited) {
        //обходим дерево партнеров и определяем кто на какой уровень пригласил этого партнера
        if (!$mas_aff = $this->get_mas_parent($chat_id_invited)) {
            return FALSE;
        }

        array_unshift($mas_aff, $chat_id_invited); //добавляем в начало массива
        $mas_aff = array_reverse($mas_aff); //сортируем в обратном порядке
        $mas_aff = array_unique($mas_aff); //удаляем дубли если есть

        $result = [];
        foreach($mas_aff as $level => $chat_id_parent_) {
            if (!$chat_id_invited_ = next($mas_aff) OR $chat_id_invited_ == $chat_id_parent_) {
                continue;
            }

            $data = [];
            $data['level'] = $level+1;
            $data['chat_id_parent'] = $chat_id_parent_;
            $data['chat_id_invited'] = $chat_id_invited_;
            $result[] = $data;
        }

        return $result;
    }
    
    /*
     *  поиск дерева приглашателей текущего партнера для 
     * партнерки с любым количеством уровней - рекурсивная функция
     */
    public function get_mas_parent($chat_id) {
        static $mas_wood_aff = []; // при следующем вызове функции данные с прошлого вызова сохранятся в массиве
        static $level = 0;
        
        if ($level > $this->count_levels()){//смотрим не глубже количества уровней в партнерке
            if (count($mas_wood_aff) > 0) {
                return $mas_wood_aff; 
            }
            return FALSE;
        }

        $level++;
        if (!is_null($chat_id) AND $chat_id > 0) {//если задан id_user пользователя
            $data_aff = $this->item_aff_link($chat_id);
            if ($data_aff !== FALSE) {
                $chat_id = $data_aff['chat_id_parent'];
            }
            if (!is_null($chat_id) AND $chat_id > 0) {   //если есть родитель у искомого партнера
                $mas_wood_aff[] = $chat_id;
                $data_aff = $this->item_aff_link($chat_id);
                //проверяем - а  есть ли он среди приглашенных
                if ($data_aff !== FALSE) {
                    return $this->get_mas_parent($chat_id); //если он есть среди приглашенных - значит и у него нужно получить родителя - той же функцией.
                } else {//если родителей больше нет, то возвращаем массив партнеров
                    $return = $mas_wood_aff;
                    $mas_wood_aff = [];
                    $level = 0;
                    unset($level);
                    unset($mas_wood_aff); //и уничтожаем статический массив
                    return $return; //array_unique(
                }
            } else {
                return FALSE;
            }
        } else {
            return FALSE;
        }
    }

    /*
     * Регистрация в дереве партнеров
     * 
     * @param $chat_id_invited -  тот кто пришел по реферальной ссылке
     * @param $chat_id_parent - тот чей реферальный id в ссылке (родитель)
     *
     * @return bool TRUE - если кого то зарегали под родителем
     * FALSE - если никого не прилинковали
     */
    public function set_aff($chat_id_invited, $chat_id_parent = FALSE) {
        if (!$chat_id_parent OR $chat_id_invited == $chat_id_parent OR $chat_id_parent <= 0 OR $chat_id_invited <= 0) {
            return FALSE;
        }

        $count = $this->db->table('aff_links')
        ->where('chat_id_parent', $chat_id_invited)
        ->orWhere('chat_id_invited', $chat_id_invited)
        ->countAllResults();
        if ($count > 0) {
            return FALSE;
        } else {
            //добавляем связь
            if (!$this->db->table('aff_links')->insert(['chat_id_parent' => $chat_id_parent, 'chat_id_invited' => $chat_id_invited])) {
                return FALSE;
            }
        }

        $this->aff_links(TRUE);

        //обходим дерево партнеров и определяем кто на какой уровень пригласил этого партнера
        if (!$mas_aff = $this->get_mas_parent_full($chat_id_invited)) {
            return FALSE;
        }

        foreach ($mas_aff as $data_level) {
            $level = 1;
            foreach ($mas_aff as $data_invited) {
                if ($data_level['chat_id_parent'] == $data_invited['chat_id_invited']) {
                    continue;
                }
                $this->add_tree_level($data_level['chat_id_parent'], $data_invited['chat_id_invited'], $level);
                $level++; //<- не перемещать
            }

            array_shift($mas_aff); //<- не перемещать - удаляем первый элемент массива чтобы не заносил родителя
        }

        
        return TRUE;
    }

    /*
    Добавляем уровень в дерево
     */
    public function add_tree_level($chat_id_parent, $chat_id_invited, $level) {
        if ($chat_id_parent == $chat_id_invited OR $level <= 0) {
            return FALSE;
        }

        $count = $this->db->table('aff_tree')
        ->where('chat_id_parent', $chat_id_parent)
        ->where('chat_id_invited', $chat_id_invited)
        ->where('level', $level)
        ->countAllResults();

        if ($count <= 0){
            $data = [];
            $data['chat_id_parent'] = $chat_id_parent;
            $data['chat_id_invited'] = $chat_id_invited;
            $data['level'] = $level;
            $data['created'] = date("Y-m-d H:i:s");
            return $this->db->table('aff_tree')->insert($data);
        }

        return FALSE;
    }

}
