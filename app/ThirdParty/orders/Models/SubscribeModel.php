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
namespace Orders\Models;

use CodeIgniter\Model;
use \CodeIgniter\Database\ConnectionInterface;

/**
 * Class ButtonsModel
 */
class SubscribeModel
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

        $this->ProductModel = new \Products\Models\ProductModel();
        $this->SettingsModel = new \Admin\Models\SettingsModel();
        $this->TelegramModel = new \App\Models\TelegramModel();

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
    Есть ли активные подпсики у пользователя
     */
    public function have_active(int $chat_id): bool {
        $db = $this->db->table('orders_products');
        $db->select('orders_products.*');
        $db->where('orders_products.date_finish>', date("Y-m-d H:i:s"));
        $db->where('orders.status', 1);
        $db->where('orders.chat_id', $chat_id);
        $db->groupBy('orders_products.id_product');
        $db->orderBy('orders_products.date_finish', "DESC");
        $db->join('orders', 'orders_products.id_order = orders.id');
        return $db->countAllResults() > 0;
    }

    /*
    Получить список активных подписок пользователя
     */
    public function active_subscribes(int $chat_id, $is_text = TRUE) {
        $db = $this->db->table('orders_products');
        $db->select('orders_products.*');
        $db->where('orders_products.date_finish>', date("Y-m-d H:i:s"));
        $db->where('orders.status', 1);
        $db->where('orders.chat_id', $chat_id);
        $db->groupBy('orders_products.id_product');
        $db->orderBy('orders_products.date_finish', "DESC");
        $db->join('orders', 'orders_products.id_order = orders.id');
        $orders_products = $db->get()->getResultArray();
        if (!$is_text) {
            return $orders_products;
        }

        $this->ProductModel = new \Products\Models\ProductModel();
        $text = ""; 
        $i = 0;
        helper("date");
        foreach ($orders_products as $orders_product) {
            if ($i > 0) {
                $text.="\n\n";
            }
            $data_product = $this->ProductModel->get($orders_product['id_product']);
            if (!isset($data_product['name'])) {
                continue;
            }
            $text.= $data_product['name'];
            $text.=" - ".date("d.m.Y H:i", human_to_unix($orders_product['date_finish']));
            // $text.=" (".timespan($orders_product['date_finish']).")";

            $i++;
        }
        return $text;
    }

    /*
    Проверка истечения подписки на каналы всех пользователей

    //смотрим купленные продукты
    //если подписка закончилась
    //если человек не выкинут то выкидываем
    //помечаем что человек выкинут чтобы в следующий раз не смотреть его
     */
    public function check_subscribers() {
        $db = $this->db->table('orders_products');
        $db->select('orders_products.*');
        $db->groupBy('orders_products.id');
        $db->orderBy('orders_products.date_finish', 'DESC');
        $db->where('orders.status', 1); //смотрим только тех кто оплачивал заказ
        $db->where('orders_products.kicked', 0); //еще не выкинут из канала
        $db->where('orders_products.count_days>', 0);
        $db->where('orders_products.date_finish<=', date("Y-m-d H:i:s")); //дата окончания подписки в прошлом
        $db->join('orders', 'orders_products.id_order = orders.id');
        $db->join('products', 'orders_products.id_product = products.id');
        $items = $db->get()->getResultArray();

        //обходим такие продукты
        foreach ($items as $item) {

            //отписываем от каналов
            $channels = $this->unsubscribe_product($item['id']);
            if (count($channels) <= 0) {
                continue;
            }

            //уведомляем что подписка закончилась
            $this->TelegramModel->notify_unsubscribe($item, $channels);
        }
        return TRUE;
    }

    /*
    Получить массив пользователей у которых нет покупок
    оплаченных заказов любых
     */
    public function no_have_subscribes(): array {

        $users = $this->db->table('users')
        ->select('chat_id')
        ->where('chat_id>', 0)
        ->where('active', 1)
        ->get()
        ->getResultArray();

        $result = [];
        foreach ($users as $user) {
            $db = $this->db->table('orders_products');
            $db->limit(1);
            $db->where('orders.status', 1); //заказ оплачен
            $db->where('orders_products.date_finish>', date("Y-m-d H:i:s")); //дата подписки не закончилась
            $db->where('orders.chat_id', $user['chat_id']);
            $db->join('orders', 'orders_products.id_order = orders.id');
            $db->groupBy('orders_products.id');
            $db->select('orders.id');
            if ($db->countAllResults() > 0) { //если есть хотя бы один активный оплаченный заказ
                continue; //тогда такой не подходит
            }

            $result[]= $user['chat_id'];
        }

        return $result;
    }

    /*
    Проверяем закончилась ли подписка на продукт
    @return bool TRUE - возвращаем TRUE если подписка еще не закончилась
     */
    public function have_subscribe_product($chat_id, $id_product, $id_product_in = FALSE) {
        $db = $this->db->table('orders_products');
        $db->limit(1);
        $db->groupBy('orders_products.id');
        if ($id_product_in !== FALSE) { //исключая этот продукт
            $db->where('orders_products.id<>', $id_product_in);
        }
        $db->where('orders_products.chat_id', $chat_id);
        $db->where('orders.status', 1);
        $db->where('orders_products.id_product', $id_product);
        $db->where('orders_products.date_finish>', date("Y-m-d H:i:s")); //дата окончания подписки еще не наступила
        $db->join('orders', 'orders_products.id_order = orders.id');

        return $db->countAllResults() > 0;
    }

    /*
    Выдаем доступы к каналам в продукте
    @return array массив каналов на которые подписан
     */
    public function subscribe_product($id_product_in):array {
        $db = $this->db->table('orders_products');
        $db->where('id', $id_product_in);
        $poduct = $db->get(1)->getRowArray();

        //помечаем что не выкинули
        $this->db->table('orders_products')->where('id', $id_product_in)->update(['kicked' => 0]);

        //получаем все каналы в продукте
        $channels = $this->ProductModel->channels($poduct['id_product']);
        $result = [];
        foreach ($channels as $channel) {
            if ($this->TelegramModel->isChatMember($channel['channel_id'], $poduct['chat_id'])) {
                //если уже является подписчиком канала то просто добавляем в массив разблокировать не нужно
                //чтобы повторно потом не вступал
                $result[]=$channel;
            } else { //если не является подписчиком канала

                if ($this->TelegramModel->unbanChatMember($channel['channel_id'], $poduct['chat_id'])) {
                    $result[]=$channel;
                }
            }
        }
        return $result;
    }

    /*
    Отписываем от каналов в продукте
    @return array массив каналов от которых отписан
     */
    public function unsubscribe_product($id_product_in):array {
        $db = $this->db->table('orders_products');
        $db->where('id', $id_product_in);
        $poduct = $db->get(1)->getRowArray();

        //получаем все каналы в продукте
        $channels = $this->ProductModel->channels($poduct['id_product']);
        $result = [];
        foreach ($channels as $channel) {
            if ($this->TelegramModel->kickChatMember($channel['channel_id'], $poduct['chat_id'])) {

                //помечаем что выкинули
                $this->db->table('orders_products')->where('id', $id_product_in)->update(['kicked' => 1]);

                $result[]=$channel;
            }
        }
        return $result;
    }

    /*
    Получить колчество дней до конца месяца
     */
    public function days_end_month($time = 0){
        $time OR $time = time();
        return date("t", $time) - date("d", $time);
    }

    /*
    Получить дату окончания подписки этого пользователя на этот продукт
     */
    public function date_finish(int $chat_id, int $id_product, int $days = 0) {

        $data_product = $this->ProductModel->get($id_product);

        if ($data_product['end_month'] > 0) {
            return date("Y-m-d H:i:s", strtotime("first day of next month 00:00")); //если это продукт "до конца месяца" - то получаем дату окончания текущего месяца
        }

        //смотрим оплаченные заказы с этим продуктом у него
        $db = $this->db->table('orders');
        $db->select('orders_products.date_finish');
        $db->groupBy('orders.id');
        $db->orderBy('orders_products.date_finish', 'DESC');
        $db->where('orders.chat_id', $chat_id);
        $db->where('orders.status', 1);
        $db->where('orders_products.chat_id', $chat_id);
        $db->where('orders_products.id_product', $id_product);
        $db->join('orders_products', 'orders.id = orders_products.id_order');
        if ($db->countAllResults() <= 0) {
            //если оплаченных заказов с этим продуктом то прибавляем к текущей дате количество дней
            return date("Y-m-d H:i:s", time() + 3600* 24*$days);
        }
        
        $db = $this->db->table('orders');
        $db->select('orders_products.date_finish');
        $db->groupBy('orders.id');
        $db->orderBy('orders_products.date_finish', 'DESC');
        $db->where('orders.chat_id', $chat_id);
        $db->where('orders.status', 1);
        $db->where('orders_products.chat_id', $chat_id);
        $db->where('orders_products.id_product', $id_product);
        $db->join('orders_products', 'orders.id = orders_products.id_order');
        $data = $db->get()->getRowArray();
        $date_finish = $data['date_finish'];

        //если закончилась - то к текущей дате
        if ($date_finish < date("Y-m-d H:i:s")) {
            return date("Y-m-d H:i:s", time() + 3600* 24*$days);
        }

        //иначе если дата еще не закончилась  - прибавляем к ней
        $last = human_to_unix($date_finish);
        return date("Y-m-d H:i:s", $last + 3600* 24*$days);
    }
}
