<?php 
/**
 * Name:    Модель для работы со статистикой. Аналог Яндекс Метрики
 *
 * Created:  29.07.2020
 *
 * Description:  
 *
 * Requirements: PHP 7.2 or above
 *
 * @author     Krotov Roman <tg: @KrotovRoman>
 */
namespace Stat\Models;
use CodeIgniter\Model;
use \CodeIgniter\Database\ConnectionInterface;

/**
 * Class StatModel
 */
class StatModel
{
	/**
	 * Database object
	 *
	 * @var \CodeIgniter\Database\BaseConnection
	 */
	protected $db;
	protected $config;
    protected $months = array('', 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь');
    protected $days = array('', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье');

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
    Количество человек которые заблокировали бот
     */
    public function count_block(): int {
        return $this->db->table('users')->where('block', 1)->countAllResults();
    }

    /*
    Статистика за периоды
     */
    public function periods(string $date_start, string $date_finish, array $products = []): array {
        $return = [];

        $days = 1;
        $data = $this->period($days, $date_start, $date_finish, $products);
        $return[$days]['new'] = $data['new'];
        $return[$days]['sales'] = $data['sales'];
        $return[$days]['cr'] = $data['cr'];

        $days = 7;
        $data = $this->period($days, $date_start, $date_finish, $products);
        $return[$days]['new'] = $data['new'];
        $return[$days]['sales'] = $data['sales'];
        $return[$days]['cr'] = $data['cr'];

        $days = 14;
        $data = $this->period($days, $date_start, $date_finish, $products);
        $return[$days]['new'] = $data['new'];
        $return[$days]['sales'] = $data['sales'];
        $return[$days]['cr'] = $data['cr'];


        return $return;
    }

    public function period(int $days, string $date_start, string $date_finish, array $products = []): array {
        $date_finish_ = $date_finish;
        $date_start_ = date("Y-m-d H:i:s", human_to_unix($date_finish_) - 3600*$days * 24);

        $return = [];
        $return['new'] = $this->users($date_start_, $date_finish_);
        $return['sales'] = $this->payed($date_start_, $date_finish_, $products);
        $return['cr'] = $return['new'] <= 0 ? 0  : (($return['sales'] / $return['new']) * 100);
        $return['cr'] = round($return['cr'], 2);
        return $return;
    }

    /*
    Свечи
    
    нажатия «старт»
    заказы
    оплаты

     */
    public function candles(string $date_start, string $date_finish, array $products = []): array {
        $return = [];

        helper('date');
        $date_range = date_range($date_start, $date_finish);
        foreach ($date_range as $date) {
            $data = new \stdClass();
            $data->day = date("d.m", human_to_unix($date." 00:00:00"));
            $date_start_= date("Y-m-d H:i:s", human_to_unix($date." 00:00:00"));
            $date_finish_= date("Y-m-d H:i:s", human_to_unix($date." 23:59:59"));

            $data->start = $this->users($date_start_, $date_finish_);
            $data->order = $this->users_orders($date_start_, $date_finish_, $products);
            $data->pay = $this->users_orders($date_start_, $date_finish_, $products, 1);
            $return[] = $data;
        }

        return $return;
    }

    private function _random_html_color() {
        return sprintf('#%02X%02X%02X', rand(0, 255), rand(0, 255), rand(0, 255));
    }

    /*
     * Получить дату начала отчетов по умолчанию
     * берется дата первого заказа
     */

    public function default_range($table = 'orders') {
        $first_order = $this->db->table('orders')
        ->select('created')
        ->limit(1)
        ->orderBy('id', 'asc') //по возрастанию
        ->get()
        ->getRowArray();

        if (!isset($first_order['id'])) {
            return date("Y-m-d H:i", now()) . " / " . date("Y-m-d H:i", now()); //текущий день тогда если вообще нет
        }
        return date("Y-m-d H:i", human_to_unix($first_order['created'])) . " / " . date("Y-m-d H:i");
    }

    /*
     * Извлечь начальное и конечное время
     */

    public function extract_range($text) {
        $arr = explode(" / ", $text);

        $date_time_start = human_to_unix(trim($arr[0]) . ":00");
        $date_time_end = human_to_unix(trim($arr[1]) . ":00");

        return [
            'date_start' => date("Y-m-d H:i:s", $date_time_start),
            'date_finish' => date("Y-m-d H:i:s", $date_time_end)
        ];
    }

    /*
    Сколько прошло времени между интервалами времени
    @example 
    $mins = $this->time($created);
     */
    public function time($start = FALSE, $finish = FALSE, $interval = "i") {
        $start OR $start = date("Y-m-d H:i:s", now());
        $finish OR $finish = date("Y-m-d H:i:s", now());
        $startTime = new \Datetime($start);
        $endTime = new \DateTime($finish);
        $diff = $endTime->diff($startTime);
        return (int) $diff->{$interval};
    }

    /*
    Количество использованных промокодов которые делают скидку
     */
    public function promocodes_pay($date_start = FALSE, $date_finish = FALSE, array $products = []): int {
        $db = $this->db->table('orders');
        $db->where('orders.is_hand', 0);
        if ($date_start !== FALSE) {
            $db->where('orders.created>=', $date_start);
        }
        if ($date_finish !== FALSE) {
            $db->where('orders.created<=', $date_finish);
        }
        if (count($products) > 0) {
            $db->whereIn('orders_products.id_product', $products);
        }

        $db->join('orders_products', 'orders.id = orders_products.id_order');
        
        $db->where('promocode.id_product', 0);
        $db->where('promocode.count_days', 0);
        $db->join('promocode', 'orders.id = promocode.id_order');
        $db->groupBy('promocode.id');
        return count($db->get()->getResultArray());
    }

    /*
    Количество использованных промокодов которые дарят продукт бесплатно полностью
     */
    public function promocodes_free($date_start = FALSE, $date_finish = FALSE, array $products = []): int {
        $db = $this->db->table('orders');
        $db->where('orders.is_hand', 0);
        if ($date_start !== FALSE) {
            $db->where('orders.created>=', $date_start);
        }
        if ($date_finish !== FALSE) {
            $db->where('orders.created<=', $date_finish);
        }
        if (count($products) > 0) {
            $db->whereIn('orders_products.id_product', $products);
        }

        $db->join('orders_products', 'orders.id = orders_products.id_order');
        
        $db->where('promocode.id_product>', 0);
        $db->where('promocode.count_days>', 0);
        $db->join('promocode', 'orders.id = promocode.id_order');
        $db->groupBy('promocode.id');
        return count($db->get()->getResultArray());
    }

    /*
    Количество использованных всего промокодов
     */
    public function promocodes($date_start = FALSE, $date_finish = FALSE, array $products = []): int {
        $db = $this->db->table('orders');
        $db->where('orders.is_hand', 0);
        if ($date_start !== FALSE) {
            $db->where('orders.created>=', $date_start);
        }
        if ($date_finish !== FALSE) {
            $db->where('orders.created<=', $date_finish);
        }
        if (count($products) > 0) {
            $db->whereIn('orders_products.id_product', $products);
        }

        $db->join('orders_products', 'orders.id = orders_products.id_order');
        
        $db->join('promocode', 'orders.id = promocode.id_order');
        $db->groupBy('promocode.id');
        return count($db->get()->getResultArray());
    }

    /*
    Количество пользователей у которых подписка закончилась в этот период
    и которые при этом купили подписку
     */
    public function users_no_prolong($date_start = FALSE, $date_finish = FALSE, array $products = [], bool $is_count = TRUE) {
        $db = $this->db->table('orders');
        $db->where('orders.is_hand', 0);
        $db->where('orders.status', 1);
        if ($date_start !== FALSE) {
            $db->where('orders_products.date_finish>=', $date_start);
        }
        if ($date_finish !== FALSE) {
            $db->where('orders_products.date_finish<=', $date_finish);
        }
        if (count($products) > 0) {
            $db->whereIn('orders_products.id_product', $products);
        }

        $db->join('orders_products', 'orders.id = orders_products.id_order', 'left');
        
        $db->join('users', 'orders.chat_id = users.chat_id', 'left');
        $db->groupBy('orders.chat_id');
        $db->select('orders.chat_id');
        $items = $db->get()->getResultArray();

        if (count($items) <= 0) {
            return $is_count ? 0 : [];
        }

        //список тех кто продлил подписку
        $users_prolong = $this->users_prolong($date_start, $date_finish, $products, FALSE);

        //считаем сколько из них оплатило заказ в этот же период
        $return = [];
        foreach ($items as $item) {
            if (!in_array($item['chat_id'], $users_prolong)) {
                $return[]=$item['chat_id'];
            }
        }

        return $is_count ? count($return) : $return;
    }

    /*
    Количество пользователей у которых подписка закончилась в этот период
    и которые при этом купили подписку
     */
    public function users_prolong($date_start = FALSE, $date_finish = FALSE, array $products = [], bool $is_count = TRUE) {
        
        //смотрим у кого закончилась подписка в этот период
        $db = $this->db->table('orders');
        $db->where('orders.is_hand', 0);
        if ($date_start !== FALSE) {
            $db->where('orders_products.date_finish>=', $date_start);
        }
        if ($date_finish !== FALSE) {
            $db->where('orders_products.date_finish<=', $date_finish);
        }
        if (count($products) > 0) {
            $db->whereIn('orders_products.id_product', $products);
        }
        $db->join('orders_products', 'orders.id = orders_products.id_order');
        
        $db->join('users', 'orders.chat_id = users.chat_id');
        $db->groupBy('orders.chat_id');
        $db->select('orders.chat_id');
        $items = $db->get()->getResultArray();

        if (count($items) <= 0) {
            return $is_count ? 0 : [];
        }

        //считаем сколько из них оплатило заказ в этот же период
        $return = [];
        foreach ($items as $item) {

            //смотрим сколько из этих людей создали и оплатили заказ новый в этот период
            $db = $this->db->table('orders');
            $db->where('orders.status', 1); //заказ оплаченный
            $db->where('orders.is_hand', 0);
            $db->where('orders.chat_id', $item['chat_id']);
            $db->where('orders_products.date_finish>', $date_finish);//у кого из этих пользователей был при этом активный заказ был в этот период
            if ($date_start !== FALSE) {
                $db->where('orders.created>=', $date_start);
            }
            if ($date_finish !== FALSE) {
                $db->where('orders.created<=', $date_finish);
            }
            if (count($products) > 0) {
                $db->whereIn('orders_products.id_product', $products);
            }
            $db->join('orders_products', 'orders.id = orders_products.id_order');

            //при этом заказы не на бесплатные промокоды
            // $db->where('promocode.id_product', 0);
            // $db->where('promocode.count_days', 0);
            // $db->join('promocode', 'orders.id = promocode.id_order', 'left');

            $db->groupBy('orders.id');
            $db->select('orders.id');
            $items_=$db->get()->getResultArray();
            $count = count($items_);

            if ($count <= 0) {
                continue; //если нет новых заказов оплаченных при этом - не берем
            }

            $return[]=$item['chat_id'];
        }

        return $is_count ? count($return) : $return;
    }

    /*
    Количество пользователей у которых подписка закончилась в этот период
     */
    public function users_finish($date_start = FALSE, $date_finish = FALSE, array $products = []): int {
        $db = $this->db->table('orders');
        $db->where('orders.is_hand', 0); //не добавлены вручную
        $db->where('orders.status', 1); //были оплачены

        if ($date_start !== FALSE) {
            $db->where('orders_products.date_finish>=', $date_start);
        }
        if ($date_finish !== FALSE) {
            $db->where('orders_products.date_finish<=', $date_finish);
        }
        if (count($products) > 0) {
            $db->whereIn('orders_products.id_product', $products);
        }

        $db->join('orders_products', 'orders.id = orders_products.id_order');
        
        $db->join('users', 'orders.chat_id = users.chat_id');
        $db->groupBy('orders.chat_id');
        $return = count($db->get()->getResultArray());
        return $return;
    }

    /*
    Количество пользователей сделавших заказы
     */
    public function users_orders($date_start = FALSE, $date_finish = FALSE, array $products = [], $status = FALSE): int {
        
        $db = $this->db->table('orders');
        $db->where('orders.is_hand', 0);
        if ($status !== FALSE) {
            $db->where('orders.status', $status);
        }
        if ($date_start !== FALSE) {
            $db->where('users.created_on>=', human_to_unix($date_start));
        }
        if ($date_finish !== FALSE) {
            $db->where('users.created_on<=', human_to_unix($date_finish));
        }
        if (count($products) > 0) {
            $db->whereIn('orders_products.id_product', $products);
            $db->join('orders_products', 'orders.id = orders_products.id_order');
        }

        $db->join('users', 'orders.chat_id = users.chat_id');
        $db->groupBy('orders.chat_id');
        $return = count($db->get()->getResultArray());

        //вычитаем те которые оплачены по бесплатному промокоду
        $promocodes_free = $this->promocodes_free($date_start, $date_finish, $products);

        //вычитаем использованные бесплатные промокоды
        $return-=$promocodes_free;

        return $return;
    }

    /*
    Количество пользователей зарегистрированных в этот период
     */
    public function users($date_start = FALSE, $date_finish = FALSE): int {
        $db = $this->db->table('users');
        if ($date_start !== FALSE) {
            $db->where('users.created_on>=', human_to_unix($date_start));
        }
        if ($date_finish !== FALSE) {
            $db->where('users.created_on<=', human_to_unix($date_finish));
        }
        $return = count($db->get()->getResultArray());
        return $return;
    }

    /*
    Получить сумму заказов полученных промокодом
     */
    public function sum_promo($date_start = FALSE, $date_finish = FALSE, array $products = [], $chat_id = FALSE): int {

        $db = $this->db->table('orders');
        $db->where('orders.status', 1);
        $db->where('orders.is_hand', 0);

        if ($chat_id !== FALSE) {
            $db->where('orders.chat_id', $chat_id);
        }
        if ($date_start !== FALSE) {
            $db->where('orders.created>=', $date_start);
        }
        if ($date_finish !== FALSE) {
            $db->where('orders.created<=', $date_finish);
        }
        if (count($products) > 0) {
            $db->whereIn('orders_products.id_product', $products);
            $db->join('orders_products', 'orders.id = orders_products.id_order');
            $db->groupBy('orders.id');
        }
        $orders = $db->get()->getResultArray();

        $sum = 0;
        foreach ($orders as $order) {
            $promocode = $this->db->table('promocode')->where('id_order', $order['id'])->get()->getRowArray();
            if (isset($promocode['id'])) {
                if ($promocode['percent'] == 100 OR $promocode['count_days'] > 0  OR ($promocode['id_product'] > 0 AND $promocode['count_days'] > 0)) {
                    continue; //если это в подарок - то вообще сумму не учитываем
                }

                if ($promocode['value'] <= 0 AND $promocode['percent'] > 0) {
                    $promocode['value'] = ($order['sum'] / 100) * $promocode['percent'];
                }
            }
            $promocode['value'] = isset($promocode['value']) ? $promocode['value'] : 0;

            $sum+=$order['sum'] - $promocode['value'];
        }

        return $sum;
    }

    /*
    Количество оплаченных заказов
     */
    public function payed($date_start = FALSE, $date_finish = FALSE, array $products = [], $chat_id = FALSE, $field = 'payed'): int {
        $db = $this->db->table('orders');
        $db->where('orders.status', 1);
        $db->where('orders.is_hand', 0);

        if ($chat_id !== FALSE) {
            $db->where('orders.chat_id', $chat_id);
        }
        if ($date_start !== FALSE) {
            $db->where('orders.'.$field .'>=', $date_start);
        }
        if ($date_finish !== FALSE) {
            $db->where('orders.'.$field .'<=', $date_finish);
        }
        if (count($products) > 0) {
            $db->whereIn('orders_products.id_product', $products);
            $db->join('orders_products', 'orders.id = orders_products.id_order');
            $db->groupBy('orders.id');
        }
        return count($db->get()->getResultArray());
    }

    /*
    Количество заказов
     */
    public function orders($date_start = FALSE, $date_finish = FALSE, array $products = [], $chat_id = FALSE): int {
        $db = $this->db->table('orders');
        $db->where('orders.is_hand', 0);
        if ($chat_id !== FALSE) {
            $db->where('orders.chat_id', $chat_id);
        }
        if ($date_start !== FALSE) {
            $db->where('orders.created>=', $date_start);
        }
        if ($date_finish !== FALSE) {
            $db->where('orders.created<=', $date_finish);
        }
        if (count($products) > 0) {
            $db->whereIn('orders_products.id_product', $products);
            $db->join('orders_products', 'orders.id = orders_products.id_order');
            $db->groupBy('orders.id');
        }
        return count($db->get()->getResultArray());
    }

     /*
     * История команд по часам
     */

     public function hours($chat_id = FALSE) {
        $db = $this->db->table('stat_history');

        if ($chat_id !== FALSE) {
            $db->where('chat_id', $chat_id);
        }
        $stat_history = $db->get()->getResultArray();
        if (count($stat_history) <= 0) {
            return [];
        }

        foreach ($stat_history as $item) {
            $m[] = date("H", human_to_unix($item['created']));
        }

        $m = array_count_values($m);
        ksort($m);

        $res = [];
        $hour = 0;
        while ($hour <= 23) {
            $data['hour'] = $hour;
            $data['count'] = isset($m[$hour]) ? $m[$hour] : 0;
            $res[] = $data;
            $hour++;
        }

        return $res;
    }

    /*
     * История команд по дням недели
     */

    public function days($chat_id = FALSE) {
        $db = $this->db->table('stat_history');

        if ($chat_id !== FALSE) {
            $db->where('chat_id', $chat_id);
        }

        $stat_history = $db->get()->getResultArray();
        if (count($stat_history) <= 0) {
            return [];
        }

        foreach ($stat_history as $item) {
            $m[] = date("N", human_to_unix($item['created']));
        }

        $m = array_count_values($m);
        ksort($m);

        $res = [];
        $day = 1;
        while ($day <= 7) {   
            $data['count'] = (isset($m[$day]) ? $m[$day] : 0);
            $data['label'] = $this->days[$day];
            $res[] = $data;
            $day++;
        }

        return $res;
    }

    /*
     * Активность по датам
     */
    public function history($chat_id = FALSE) {
        
        $res = [];
        $bullet = ["round", "square", "triangleUp", "triangleDown", "bubble"];
        //"none", "round", "square", "triangleUp", "triangleDown", "bubble", "custom"
        $res['graphs'] = [];
        
        //добавляем линию
        $data = new \stdClass();
        $data->valueAxis = "v1";
        $data->lineColor = "#000000";//$this->_random_html_color(); //цвета надо случайно
        $data->bullet = "round";//$bullet[array_rand($bullet)]; //форма точки случайно
        $data->bulletBorderThickness = 1;
        $data->hideBulletsCount = 30;
        $data->title = "Вся активность";
        $data->valueField = 'countid';
        $data->fillAlphas = 0;
        $res['graphs'][] = $data;

        
        $res['data'] = [];
        $history_all = $this->history_all($chat_id);
        foreach($history_all as $item){
            $data = new \stdClass();
            $data->date = $item['mydate'];
            $data->countid = $item['countid'];
            $res['data'][] = $data;
        }
        return $res;
    }

    /*
     * Активность по датам
     */
    public function history_all($chat_id = FALSE) {
        $db = $this->db->table('stat_history');
        if ($chat_id !== FALSE) {
            $db->where('chat_id', $chat_id);
        }
        $db->select('COUNT(id) AS countid, DATE(created) as mydate');
        $db->groupBy('mydate');
        return $db->get()->getResultArray();
    }

    /*
     * Добавить запись в историю событий
     */

    public function add(int $chat_id, string $text, $id_button = FALSE, $file_id = FALSE, $is_button = FALSE) {
        if (!isset($this->recordstat) OR $this->recordstat <= 0) {
            return FALSE;
        }
        if ($file_id !== FALSE) {
            $data['file_id'] = $file_id;
        }
        if ($id_button !== FALSE) {
            $data['id_button'] = $id_button;
        } else if ($is_button) { //если не указана кнопка пытаемся найти ее id по команде
            $btn = $this->db->table('menu_buttons')->where('comand', $text)->get()->getRowArray();
            if (isset($btn['id'])) {
                $data['id_button'] = $btn['id'];
            }
        }
        $data['chat_id'] = $chat_id;
        $data['text'] = $text;

        if (empty($text) AND !$file_id AND !$id_button) {
            return FALSE;
        }
        return $this->db->table('stat_history')->insert($data);
    }

    /*
     * Участники рулетки
     * Получить список с помощью ajax
     *   
     * @docs https://datatables.net/manual/server-side#Sent-parameters
     * @docs https://datatables.net/examples/server_side/simple.html
     * @docs https://datatables.net/examples/data_sources/server_side.html
     */

    public function items_($params, $filter = TRUE) {

        $db = $this->db->table('stat_history');

        //поисковой фильтр
        if (!empty($params['search']['value'])) {
            $db->groupStart();
            $id = (int) trim($params['search']['value']);
            $time = human_to_unix(trim($params['search']['value']));
            if ($id > 0) {//если это число
                $db->where('stat_history.id_button', $id);
                $db->orLike('stat_history.chat_id', $id);
            } else if ($time) {//если это дата                   
                $db->where('stat_history.created', trim($params['search']['value']));
            } else {//если это текст        
                $db->orLike('users.first_name', json_encode(trim($params['search']['value'])));
                $db->orLike('users.last_name', json_encode(trim($params['search']['value'])));
                $db->orLike('users.email', trim($params['search']['value']));
                $db->orLike('users.username', trim($params['search']['value']));
            }
            $db->groupEnd();
        }

        //список полей которые будут в таблице                
        $need_fields = array(
            'created',
            'text',
            'chat_id'
        );

        $db->select('stat_history.*');
        $db->select('menu_buttons.name');
        $db->select('users.first_name, users.last_name, users.username, users.email, users.phone');
        
        //сортировка 
        $order_column = (int) $params['order'][0]['column'];
        $order_direction = $params['order'][0]['dir'];
        $dir = empty($order_direction) ? "desc" : $order_direction;
        $db->orderBy($need_fields[$order_column], $dir);

        $db->join('users', 'stat_history.chat_id = users.chat_id');
        $db->join('menu_buttons', 'stat_history.id_button = menu_buttons.id', 'left');

        $db->groupBy('stat_history.id');

        if ($filter) {
            $limit = (int) $params['length'];
            $offset = (int) $params['start'];
            $items = $db->get($limit, $offset);
        } else {//если без фильтра - то общее количество записей
            return count($db->get()->getResultArray());
        }

        $return = [];
        foreach ($items->getResultArray() as $item) {
            $data = [];

            $data[] = $item['created'];

            $data_item = "";
            if (!empty($item['name'])) {
                $data_item.= "<a href='".base_url('admin/buttons/edit/'.$item['id_button'])."'>".json_decode($item['name'])."</a>";
            } else if (!empty($item['file_id'])) {
                $data_item.= '<i title="Файл" class="fas fa-file"></i> '.$item['text'];
            } else {
                $data_item.= $item['text'];
            }
            
            $data[] = $data_item;

            $username = empty($item['username']) ? "" : "@".$item['username'];
            $data_item = "<a href='".base_url("admin/users/edit/" . $item['chat_id'])."'>".json_decode($item['first_name'])." ".json_decode($item['last_name'])." ".$username."</a>";
            $data_item.="<br>(ID ".$item['chat_id'].")";
            $data[] = $data_item;
            
            $return[] = $data;
        }

        return $return;
    }


}
