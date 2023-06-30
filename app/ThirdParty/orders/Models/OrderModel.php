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

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

//для чтения файлов
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * Class ButtonsModel
 */
class OrderModel
{
	/**
	 * Database object
	 *
	 * @var \CodeIgniter\Database\BaseConnection
	 */
	protected $db;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->db = \Config\Database::connect();
        $this->PayModel = new \Pays\Models\PayModel();
        $this->SettingsModel = new \Admin\Models\SettingsModel();
        $this->CourseModel = new \Course\Models\CourseModel();

        $settings = $this->SettingsModel->all(TRUE);
        foreach ($settings as $settings_) {
            $this->{$settings_['name']} = $settings_['value'];
        }
    }

    public function history($chat_id, int $offset, bool $count = FALSE) {
        if ($count) {
            return $this->db->table('orders')
            ->where('chat_id', $chat_id)
            ->countAllResults();
        }
        
        return $this->db->table('orders')
            ->where('chat_id', $chat_id)
            ->orderBy('created', 'desc')
            ->get($this->limit_menu, $offset)
            ->getResultArray();
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
    Парсим .xlsx файл
    Колонки в файле: 
        ID Telegram | Имя | Телефон | Email | Дата окончания | Тариф
    @param $src - путь к файлу который парсим
    @return 
        count int - количество записей которое добавлено
    
    @docs https://phpspreadsheet.readthedocs.io/en/latest/topics/reading-files/
     */
    public function parsing(string $src): int {
        if (!realpath($src)) {
            return 0;
        }
        $this->ProductModel = new \Products\Models\ProductModel();

        helper('date');
        $reader = IOFactory::createReader("Xlsx");
        $spreadsheet = $reader->load($src);
        $items = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        //первая строка с заголовками нам не нужна
        unset($items[1]);

        $i = 0;
        foreach ($items as $item) {

            $id_order = (int) $item['A']; //данные заказа
            if ($id_order <= 0) {
                continue;
            }
            $articul = $item['B']; //артикул
            $status_text = $item['C']; //статус посылки
            $date_finish = nice_date($item['D'], "Y-m-d H:i:s");//дата получения 01/09/20
            $name_target = $item['E']; //имя клиента
            
            $order = $this->get($id_order);
            if (!isset($order['id'])) {
                log_message('error','не нашли заказ №'.$id_order);
                continue;
            }

            $product_item = $this->ProductModel->get_item_articul($articul);
            if (!isset($product_item['id'])) {
                log_message('error','не продукт с артикулом '.$articul);
                continue;
            }

            //меняем данные в заказе
            $this->set(['id' => $id_order, 'date_finish' => $date_finish, 'name_target' => $name_target, 'status_text' => $status_text]);

            // log_message('error', $id_order);
            // log_message('error', $articul);
            // log_message('error', $status_text);
            // log_message('error', $date_finish);
            // log_message('error', $name_target);

            $i++;
        }
        
        return $i;
    }

    /*
    Если щас тихое время и нельзя уведомлять
     */
    public function is_quet_time(): bool {
        if (!empty($this->time_no_send)) {
            $arr = explode("-", $this->time_no_send);
            $start = $arr[0];
            $start = date("Y-m-d ".$start.':00');

            $finish = $arr[1];
            $finish = date("Y-m-d ".$finish.':00');

            if (date("Y-m-d H:i:s") >= $start AND date("Y-m-d H:i:s") <= $finish) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /*
    Уведомление если не дооформил заказ
     */
    public function notify_no_finish() {

        if ($this->hours_no_finish <= 0 OR $this->is_quet_time()) {
            echo "<h1>нельзя щас уведомлять</h1>";
            return FALSE;
        }

        $this->TelegramModel = new \App\Models\TelegramModel();

        $orders = $this->db->table("orders")
        ->where('status', 0)
        ->where('finish', 0)
        ->where('created<=', date("Y-m-d H:i:s", time() - 3600 * $this->hours_no_finish))
        ->get()
        ->getResultArray();

        foreach ($orders as $order) {
            if ($this->db->table('notify_sended')
            ->where('chat_id', $order['chat_id'])
            ->where('id_order', $order['id'])
            ->countAllResults() > 0) {
                continue; //такое уведолмение уже отправляли
            }
            $this->db->table('notify_sended')->insert(['id_order' => $order['id'], 'chat_id' => $order['chat_id'], 'created' => date("Y-m-d H:i:s")]);

            $left = $this->time($order['created']);
            $left_need = $this->hours_no_finish + 1;
            
            $id_page = $left > $left_need ? 116 : 115;
            $this->TelegramModel->notify_no_finish($order);

        }
    }

    /*
    Сколько прошло часов
    @example 
    $mins = $this->time($created);
     */
    public function time($start = FALSE, $finish = FALSE) {
        $start OR $start = date("Y-m-d H:i:s");
        $finish OR $finish = date("Y-m-d H:i:s");
        $start = human_to_unix($start);
        $finish = human_to_unix($finish);
        $datediff = $finish - $start;
        return floor($datediff/(60*60));
    }

    /*
    Корректируем указатель листания
     */
    public function offset_cart(array $message): int {
        $offset = isset($message['params'][1]) ? (int) $message['params'][1] : 0;

        $offset = $offset < 0 ? 0 : $offset;
        $items_in_cart = $this->items_in_cart($message['message']['chat']['id']);
        if ($offset >= $items_in_cart) {
            $offset = 0;
        }

        return $offset;
    }

    /*
    Получить список заказов текстом
     */
    public function items_in_order_text($id_order): string {
        $this->ModModel = new \Mods\Models\ModModel();
        $text = ""; $i = 0;
        $items_in_order = $this->items_in_order($id_order);
        foreach ($items_in_order as $item) {
            if ($i > 0) {
                $text.="\n";
            }
            $text.= json_decode('"\u2b55\ufe0f"').' ';
            $text.=json_decode($item['name_product'])." (".$item['articul'].")";
            $text.=" за ".number_format($item['price'], $this->decimals, ',', ' ');
            $text.=' '.$this->currency_name;
            
            $mods_item_string = $this->ModModel->mods_item_string($item['id_item']);
            if (!empty($mods_item_string)) {
                $text.= " (".$mods_item_string.")";
            }
            $i++;
        }
        return $text;
    }

    /*
    Единицы товаров в заказе
     */
    public function items_in_order($id_order): array {
        return $this->db->table('orders_items')
        ->join('products', 'orders_items.id_product = products.id')
        ->join('products_items', 'orders_items.id_item = products_items.id')
        ->join('orders_products', 'orders_items.id_order_product = orders_products.id')
        ->select('products_items.id as id_product_item')
        ->select('orders_products.price, orders_items.id_item, orders_items.id_product, orders_items.id')
        ->select('products.name as name_product')
        ->select('products_items.articul, products_items.file_id')
        ->groupBy('orders_items.id')
        ->where('orders_items.id_order', $id_order)
        ->get()
        ->getResultArray();
    }

    /*
    Количество единиц товара в корзине
     */
    public function items_in_cart(int $chat_id):int {
        if (!$id_order = $this->active($chat_id)) {
            return 0;
        }
        return count($this->db->table('orders_items')
        ->join('products', 'orders_items.id_product = products.id')
        ->join('products_items', 'orders_items.id_item = products_items.id')
        ->select('orders_items.price, orders_items.id_item')
        ->select('products.name as name_product')
        ->select('products_items.articul, products_items.file_id')
        ->groupBy('orders_items.id')
        ->where('orders_items.id_order', $id_order)
        ->get()
        ->getResultArray());
    }

    /*
    Количество единиц товара в корзине
     */
    public function count_items_in_cart(int $chat_id, int $id_product):int {
        return count($this->db->table('orders_items')
        ->join('products', 'orders_items.id_product = products.id')
        ->join('products_items', 'orders_items.id_item = products_items.id')
        ->join('orders', 'orders_items.id_order = orders.id')
        ->select('orders_items.price, orders_items.id_item')
        ->select('products.name as name_product')
        ->select('products_items.articul, products_items.file_id')
        ->groupBy('orders_items.id')
        ->where('orders.finish', 0)
        ->where('orders_items.chat_id', $chat_id)
        ->where('orders_items.id_product', $id_product)
        ->where('orders.status', 0)
        ->get()
        ->getResultArray());
    }

    /*
    Листание страниц корзины
     */
    public function cart(int $chat_id, int $offset = 0) {
        if (!$id_order = $this->active($chat_id)) {
            return FALSE;
        }
        $offset = $offset < 0 ? 0 : $offset;

        return $this->db->table('orders_items')
        ->join('products', 'orders_items.id_product = products.id')
        ->join('products_items', 'orders_items.id_item = products_items.id')
        ->select('orders_items.price, orders_items.id_item, orders_items.id')
        ->select('products.name as name_product')
        ->select('products_items.articul, products_items.file_id')
        ->groupBy('orders_items.id')
        ->where('orders_items.id_order', $id_order)
        ->get(1, $offset)
        ->getRowArray();
    }

    /*
    Автоматически удалять старые заказы
     */
    public function autodel() {
        if ($this->time_autodel_order <= 0) {
            return FALSE;
        }

        $this->TelegramModel = new \App\Models\TelegramModel();

        //ищем все старые заказы
        $orders = $this->db->table('orders')
        ->where('created<=', date("Y-m-d H:i:s", time() - 60 * $this->time_autodel_order))
        ->where('status', 0)
        ->get()
        ->getResultArray();

        foreach ($orders as $order) {
            
            $this->TelegramModel->notify_deleted($order);
            $this->delete($order['id']);
        }
    }

    /*
    Изменение статуса
     */
    public function status(int $id_order, int $status = 1, $need_resend_coin = TRUE) {
        
        //меняем статус заказа
        if (!$this->set(['id' => $id_order, 'status' => $status])) {
            return FALSE;
        }

        if ($status == 1) {
            $this->TelegramModel = new \App\Models\TelegramModel();

            //финализируем заказ
            $this->set(['id' => $id_order, 'finish' => 1]);

            //если это был заказ на пополнение баланса
            if ($this->is_balance($id_order)) {
                
                $this->BalanceModel = new \Balance\Models\BalanceModel();
                
                //начисляем на баланс
                $order = $this->get($id_order);

                //если такой баланс уже начисляли
                if ($this->BalanceModel->have_balance($order['chat_id'], "id_order", $id_order)) {
                    return TRUE;
                }

                //создаем транзакцию
                $data = [];
                $data['chat_id'] = $order['chat_id'];
                $data['value'] = $order['sum'];
                $data['finish'] = 1;
                $data['id_order'] = $id_order;
                $data['comment'] = "Пополнение баланса, заказ №".$id_order;
                $data['type'] = "balance";
                $data['currency'] = $this->currency_cod;

                
                if (!$id_trans = $this->BalanceModel->add($data)) {
                    return FALSE;
                }
            } else { //если обычная покупка

                //помечаем единицы товара использованными
                $this->used_items($id_order);

                //начислить бонус за покупку товара из категории с бонусами
                if ($bonus = $this->add_bonus_cat($id_order)) {
                    $this->TelegramModel->notify_bonus_cat($bonus, $id_order);
                }
            }

            //уведомляем что выдали права и на какие каналы
            $this->TelegramModel->notify_payed($id_order);
            
            //начисляем комиссионные
            $this->AffModel = new \Aff\Models\AffModel();
            $this->AffModel->set_comission($id_order);

            return TRUE;
        }

        return TRUE;
    }

    /*
    Использовать единицы товаров
     */
    public function used_items(int $id_order) {
        $orders_products = $this->db->table('orders_items')
        ->where('id_order', $id_order)
        ->get()
        ->getResultArray();

        foreach ($orders_products as $item) {
            $this->db->table('products_items')->update(['id_order' => $id_order], ['id' => $item['id_item']]);
        }

        return TRUE;
    }

    /*
    Начислить бонус за покупку товара из категории с бонусами
     */
    public function add_bonus_cat(int $id_order) {
        $this->ProductModel = new \Products\Models\ProductModel();

        $sum_bonus = 0;
        $products = $this->products($id_order);
        $cat_text = ""; $i = 0;
        foreach ($products as $product_item) {

            if (!$id_category = $this->ProductModel->id_category_product($product_item['id_product'])) {
                continue;
            }

            $category = $this->ProductModel->category($id_category);
            if ($category['bonus'] <= 0) {
                continue;
            }
            $sum_bonus+= $category['bonus'];

            if ($i > 0) {
                $cat_text.=", ";
            }
            $cat_text.=$category['name'];
            $i++;
        }

        if ($sum_bonus <= 0) {
            echo "Нет бонуса для начисления!";
            return FALSE;
        }

        $order = $this->get($id_order);

        $this->BalanceModel = new \Balance\Models\BalanceModel();

        //если такой баланс уже начисляли
        if ($this->BalanceModel->have_balance($order['chat_id'], "id_order", $id_order, 'bonus')) {
            return TRUE;
        }

        //создаем транзакцию пополнения бонусом из категории
        $data = [];
        $data['chat_id'] = $order['chat_id'];
        $data['value'] = $sum_bonus;
        $data['finish'] = 1;
        $data['id_order'] = $id_order;
        $data['comment'] = "Начисление бонуса за покупку из категорий (".$cat_text."), заказ №".$id_order;
        $data['type'] = "bonus";
        $data['currency'] = $this->currency_cod;
        if (!$this->BalanceModel->add($data)) {
            return FALSE;
        }
        return $data;
    }

    /*
    Это заказ на пополнение баланса
     */
    public function is_balance(int $id_order): bool {
        $db = $this->db->table('orders_products');
        $db->where('orders_products.id_product', 0);
        $db->where('orders_products.id_order', $id_order);
        return $db->countAllResults() > 0;
    }

    /*
    Список покупок
     */
    public function buyed_products(int $chat_id, $offset = 0, $limit = 20) {  
        $db = $this->db->table('orders_products');
        $db->where('orders_products.chat_id', $chat_id);
        $db->where('orders.status', 1);
        $db->where('orders_products.id_product>', 0);
        $db->join('orders', 'orders_products.id_order = orders.id');
        $db->join('products', 'orders_products.id_product = products.id');
        $db->groupBy('orders_products.id_order');
        $db->orderBy('orders.created','DESC');
        $db->select('orders_products.*');
        $db->select('products.name');
        if ($offset === FALSE) {
            $items = $db->get()->getResultArray();
            return count($items);
        }
        return $db->get($limit, $offset)->getResultArray();
    }

    /*
    Покупал ли пользователь такой продукт
     */
    public function buyed_product(int $id_product, int $chat_id): bool{  
        $db = $this->db->table('orders_products');
        $db->where('orders_products.id_product', $id_product);
        $db->where('orders_products.chat_id', $chat_id);
        $db->where('orders.status', 1);
        $db->join('orders', 'orders_products.id_order = orders.id', 'left');
        return $db->countAllResults() > 0;
    }

    /*
    Сохраняем заказ
     */
    public function set($data) {
        return $this->db->table('orders')->where('id', $data['id'])->update($data);
    }

    /*
    Получить недооформленный заказ
     */
    public function active($chat_id) {
        $db = $this->db->table('orders');
        $db->where('finish', 0);
        $db->where('status', 0);
        $db->where('chat_id', $chat_id);
        if ($db->countAllResults() <= 0) {
            return FALSE;
        }

        $db = $this->db->table('orders');
        $db->where('finish', 0);
        $db->where('status', 0);
        $db->where('chat_id', $chat_id);
        $db->limit(1);
        $db->orderBy('updated', 'ASC');
        return $db->get()->getRow()->id;
    }
    
    /*
    Единица товара в корзине или нет
     */
    public function item_in_cart($id_order, int $id_item): bool {
        if ($id_order === FALSE) {
            return FALSE;
        }
        return $this->db->table('orders_items')
        ->where('id_order', $id_order)
        ->where('id_item', $id_item)
        ->countAllResults() > 0;
    }

    /*
    Получить последнюю единицу товара из корзины для этого продукта
    чтобы можно было удалить
     */
    public function get_last_item(int $chat_id, int $id_product) {
        $id_order = $this->active($chat_id);

        $data = $this->db->table('orders_items')
        ->where('id_order', $id_order)
        ->where('chat_id', $chat_id)
        ->where('id_product', $id_product)
        ->orderBy('created', 'DESC')
        ->get(1)
        ->getRowArray();

        return isset($data['id_item']) ? $data['id_item'] : FALSE;
    }

    /*
    Удалить единицу товара из корзины
    если удалена последняя единица то удаляется и заказ
     */
    public function delete_cart(array $data) {
        if (!isset($data['chat_id']) OR !isset($data['id_product'])) {
            return FALSE;
        }

        //открываем транзакцию
        $this->db->transBegin();

        if (isset($data['id_order'])) {
            $id_order = $data['id_order'];
        } else {
            //если нет активного заказа
            if (!$id_order = $this->active($data['chat_id'])) {
                return FALSE;
            }
        }

        if (!isset($data['id_item']) OR $data['id_item'] <= 0) {
            //значит берем любую единицу товара которая есть в заказе
            $order_item = $this->db->table('orders_items')
            ->where('id_order', $id_order)
            ->where('id_product', $data['id_product'])
            ->get(1)
            ->getRowArray();
            if (!isset($order_item['id_item'])) {
                return TRUE;
            }
            $data['id_item'] = $order_item['id_item'];
        }

        $orders_products = $this->db->table('orders_products')
        ->where('id_order', $id_order)
        ->where('id_product', $data['id_product']);

        if ($orders_products->countAllResults(FALSE) <= 0) {
            return FALSE; //если нет такого продукта в корзине
        }

        $id_order_product = $orders_products->get(1)->getRow()->id;

        $orders_items = $this->db->table('orders_items')
        ->where('id_order', $id_order)
        ->where('id_product', $data['id_product'])
        ->where('id_item', $data['id_item']);

        if ($orders_items->countAllResults(FALSE) <= 0) {
            return FALSE; //если нет такой единицы товара в корзине
        }
        $id_order_item = $orders_items->get(1)->getRow()->id;

        $this->ProductModel = new \Products\Models\ProductModel();
        $data_item = $this->ProductModel->get_item($data['id_item']);

        //увеличиваем счетчик количества единиц товара
        $order_product = $this->get_product($id_order_product);
        $count = $order_product['count'] - 1;
        $price = $order_product['price'] - $data_item['price'];
        $price_pay = $order_product['price_pay'] - $data_item['price'];
        $this->set_product_order($id_order, $data['id_product'], ['count' => $count, 'price' => $price, 'price_pay' => $price_pay]);
        
        //удаляем единицу товара
        $this->db->table('orders_items')->delete(['id_order' => $id_order, 'id_item' => $data['id_item']]);

        $order_product = $this->get_product($id_order_product);
        if ($order_product['count'] <= 0) {
            //удаляем товар из заказа
            $this->db->table('orders_products')->delete(['id' => $id_order_product]);
        }

        //пересчитать цену с учетом скидок на кол-во товаров
        $this->recount_sum_order_count($id_order, $data['id_product']);
        
        //Пересчитать сумму товаров в заказе
        $this->recount_sum_order($id_order, $data['id_product']);

        $products = $this->products($id_order);
        if (count($products) <= 0) {
            //если в продукте нет товаров - то удаляем заказ
            $this->delete($id_order);
            $this->db->transCommit();
            return TRUE;
        }

        //закрываем транзакцию
        $this->db->transComplete();
        if ($this->db->transStatus() === FALSE) {
            $this->db->transRollback(); //откатить изменения
            return FALSE;
        }
        $this->db->transCommit(); //зафиксировать изменения в БД

        return $id_order;
    }

    /*
    Этот товар уже у кого то в корзине его нельзя добавить
     */
    public function is_busy(int $chat_id, int $id_item):bool {
        $orders_items = $this->db->table('orders_items')
        ->where('chat_id<>', $chat_id)
        ->where('id_item', $id_item);
        return $orders_items->countAllResults() > 0;
    }



    /*
    Получаем скидку с учетом количества единиц товара в корзине
     */
    public function actual_pice(int $id_order, int $id_product)  {
        $value = $this->count_in_cart($id_order, $id_product);

        $value++; //столько будет после добавления

        return $this->actual_price_count($id_product, $value);
    }

    /*
    Актуальная цена за единицу товара в зависимости от скидки по кол-ву в корзине
     */
    public function actual_price_count(int $id_product, int $value)  {
        //ищем в таблице скидок от кол-ва
        $products_kg = $this->db->table('products_kg')
        ->where('id_product', $id_product)
        ->where('value', $value)
        ->get(1)
        ->getRowArray();

        return isset($products_kg['price']) ? floatval($products_kg['price']) : FALSE;
    }

    /*
    Количество в корзине
     */
    public function count_in_cart(int $id_order, int $id_product): int {
        return $this->db->table('orders_items')
        ->where('id_order', $id_order)
        ->where('id_product', $id_product)
        ->countAllResults();
    }

    /*
    Добавить единицу товара в корзину
     */
    public function add_cart(array $data) {
        if (!isset($data['chat_id']) OR !isset($data['id_product'])) {
            return FALSE;
        }

        if (!isset($data['id_item']) OR $data['id_item'] <= 0) {
            log_message('error', print_r('add_cart не указана id_item',TRUE));
            return FALSE;
        }

        $data['created'] = date("Y-m-d H:i:s");

        //открываем транзакцию
        $this->db->transBegin();
        
        if (!$id_order = $this->active($data['chat_id'])) { //добавляем заказ
            $data_insert = [];
            $data_insert['id'] = rand();
            $data_insert['created'] = $data['created'];
            $data_insert['chat_id'] = $data['chat_id'];
            $this->db->table('orders')->insert($data_insert);

            $id_order = $this->db->insertID();
        }

        //добавляем товар в корзину
        $orders_products = $this->db->table('orders_products')
        ->where('id_order', $id_order)
        ->where('id_product', $data['id_product']);

        if ($orders_products->countAllResults(FALSE) <= 0) {
            $data_insert = [];
            $data_insert['created'] = $data['created'];
            $data_insert['chat_id'] = $data['chat_id'];
            $data_insert['id_product'] = $data['id_product'];
            $data_insert['id_order'] = $id_order;
            $this->db->table('orders_products')->insert($data_insert);

            $id_order_product = $this->db->insertID();
        } else {
            $id_order_product = $orders_products->get(1)->getRow()->id;
        }

        $this->ProductModel = new \Products\Models\ProductModel();
        $data_item = $this->ProductModel->get_item($data['id_item']);

        if (!isset($data_item['id'])) {
            $this->db->transRollback(); //откатить изменения
            log_message('error','нет id_item при добавлении в корзину OrderModel->add_cart');
            log_message('error', print_r($data,TRUE));
            return FALSE;
        }

        //добавляем единицу товара к товару в корзине
        $orders_items = $this->db->table('orders_items')
        ->where('id_order', $id_order)
        ->where('id_product', $data['id_product'])
        ->where('chat_id', $data['chat_id'])
        ->where('id_item', $data['id_item']);
        
        if ($orders_items->countAllResults(FALSE) <= 0) {

            //добавляем единицу товара к заказу
            $data_insert = [];
            $data_insert['created'] = $data['created'];
            $data_insert['chat_id'] = $data['chat_id'];
            $data_insert['id_product'] = $data['id_product'];
            $data_insert['id_order'] = $id_order;
            $data_insert['id_order_product'] = $id_order_product;
            $data_insert['id_item'] = $data['id_item'];

            //получаем цену с учетом купленного кол-ва в корзине
            if ($price_discount = $this->actual_pice($id_order, $data['id_product'])) {
                $data_insert['price'] = $price_discount;
            } else { //иначе берем обучную цену
                $data_insert['price'] = $data_item['price'];
            }

            $this->db->table('orders_items')->insert($data_insert);
            $id_order_item = $this->db->insertID();

            //увеличиваем счетчик количества единиц товара
            $order_product = $this->get_product($id_order_product);
            $count = $order_product['count'] + 1;
            $price = $order_product['price'] + $data_item['price'];
            $price_pay = $order_product['price_pay'] + $data_item['price'];
            $this->set_product_order($id_order, $data['id_product'], ['count' => $count, 'price' => $price, 'price_pay' => $price_pay]);
            
        } else {
            $id_order_item = $orders_items->get(1)->getRow()->id;
        }

        //пересчитать цену с учетом скидок на кол-во товаров
        $this->recount_sum_order_count($id_order, $data['id_product']);
        
        //Пересчитать сумму товаров в заказе
        $this->recount_sum_order($id_order, $data['id_product']);


        //закрываем транзакцию
        $this->db->transComplete();
        if ($this->db->transStatus() === FALSE) {
            $this->db->transRollback(); //откатить изменения
            return FALSE;
        }
        $this->db->transCommit(); //зафиксировать изменения в БД

        return $id_order;
    }

    /*
    пересчитать цену с учетом скидок на кол-во товаров
     */
    public function recount_sum_order_count(int $id_order, int $id_product) {
        //получаем цену за единицу в зависимости от кол-ва в корзине
        $value = $this->count_in_cart($id_order, $id_product);
        if ($actual_price_count = $this->actual_price_count($id_product, $value)) {
            
            $this->db->table('orders_items')
            ->where('id_order', $id_order)
            ->where('id_product', $id_product)
            ->update(['price' => $actual_price_count]);

            $price = $value * $actual_price_count;

            $this->db->table('orders_products')
            ->where('id_order', $id_order)
            ->where('id_product', $id_product)
            ->update(['price' => $price, 'price_pay' => $price]);

            return TRUE;
        }

        return FALSE;
    }

    /*
    Пересчитать сумму товаров в заказе
     */
    public function recount_sum_order($id_order, $id_product = FALSE, $sum_discount = FALSE) {
        $order = $this->get($id_order);
        $products = $this->products($id_order);
        $data_pay = $this->PayModel->pay($order['id_pay']);

        //если надо применить скидку по промокоду
        $sum_discount_need = 0;
        if ($sum_discount > 0) {
            $count = count($products);
            $sum_discount_need  = $sum_discount / $count; //применяем скидку к каждому товару
        }

        $sum = 0; $sum_pay = 0;
        foreach ($products as $product_in) {

            //применяем сидку по промокоду
            $price = $product_in['price'] - $sum_discount_need;
            $price_pay = $product_in['price_pay'] - $sum_discount_need;
            
            //если валюта платежной системы не совпадает
            if ($data_pay['currency'] <> $this->currency_cod) {
                //тогда конвертим в валюту платежки
                $price_pay = $this->CourseModel->convert($this->currency_cod, $price_pay, $data_pay['currency']);
            }

            //если цена изменилась
            if ($product_in['price'] <> $price OR $product_in['price_pay'] <> $price_pay) {
                $this->set_product(['id' => $product_in['id'], 'price' => $price, 'price_pay' => $price_pay]);
            }
            
            $sum+=$price;
            $sum_pay+=$price_pay;
        }

        //увеличиваем сумму в заказе
        return $this->set(['id' => $id_order, 'sum' => $sum, 'sum_pay' => $sum_pay]);
    }

    /*
    Добавление заказа
     */
    public function add($data, $requirenew = FALSE) {

        if (!isset($data['chat_id'])) {
            return FALSE;
        }

        //открываем транзакцию
        $this->db->transBegin();

        if (isset($data['count'])) {
            $count = $data['count'];
            unset($data['count']);
        } else {
            $count = 1;
        }

        if (isset($data['sum'])) {
            $sum_balance = $data['sum'];
            unset($data['sum']);
        } else {
            $sum_balance = 0;
        }

        if (isset($data['products'])) {
            $products = $data['products'];
            if (count($products) <= 0) {
                return FALSE;
            }
            unset($data['products']);
        }

        //если есть не завершенный заказ
        if (!$requirenew AND $id_order = $this->active($data['chat_id'])) {

            //удаляем из заказа все продукты чтобы ниже добавить заново
            $this->db->table('orders_products')->delete(['id_order' => $id_order]);

            $data['created'] = date("Y-m-d H:i:s");
        } else {
            //добавляем заказ
            $db = $this->db->table('orders');
            $data['id'] = rand();
            $data['created'] = date("Y-m-d H:i:s");
            $db->insert($data);
            $id_order = $this->db->insertID();
        }

        $this->PayModel = new \Pays\Models\PayModel();
        $data_pay = $this->PayModel->pay($data['id_pay']);

        $this->SubscribeModel = new \Orders\Models\SubscribeModel();
        $this->ProductModel = new \Products\Models\ProductModel();

        //добавляем товары к заказу
        $sum = 0;
        $sum_pay = 0;
        foreach ($products as $id_product) {

            $data_product = $this->ProductModel->get($id_product);

            $data_insert = [];
            $data_insert['created'] = $data['created'];
            $data_insert['chat_id'] = $data['chat_id'];
            $data_insert['id_product'] = $id_product;
            $data_insert['price'] = $data_product['price'] * $count;

            if ($sum_balance > 0) {
                $data_insert['price'] = $sum_balance;
                $data_product['price'] = $data_insert['price'];
            }

            $data_insert['count_days'] = $count;

            //если валюта платежной системы не совпадает
            if ($data_pay['currency'] <> $this->currency_cod) {
                //тогда конвертим в валюту платежки
                $data_insert['price_pay'] = $this->CourseModel->convert($this->currency_cod, $data_insert['price'], $data_pay['currency']);
            } else {
                $data_insert['price_pay'] = $data_product['price'];
            }

            $data_insert['id_order'] = $id_order;
            if ($this->db->table('orders_products')->insert($data_insert)) {
                $sum+=$data_insert['price'];
                $sum_pay+=$data_insert['price_pay'];

                //прилинковываем свободные единицы товара к заказу
                $this->ProductModel->asset_item($id_order, $data['chat_id'], $id_product, $count);
            }
        }

        //обновляем сумму в заказе
        $this->set(['id' => $id_order, 'id_pay' => $data['id_pay'], 'sum' => $sum, 'sum_pay' => $sum_pay, 'status_text' => $this->status_text_default]);

        //если нулевая сумма заказа сразу помечаем оплаченным
        if ($sum <= 0) {
            $this->status($id_order);
        }

        //закрываем транзакцию
        $this->db->transComplete();
        if ($this->db->transStatus() === FALSE) {
            $this->db->transRollback(); //откатить изменения
            return FALSE;
        }
        $this->db->transCommit(); //зафиксировать изменения в БД
        return $id_order;
    }

    /*
    Удалить единицу товара из заказа
     */
    public function delete_item(int $id) {
        if (!$this->db()->table('orders_items')->delete(['id' => $id])) {
            return FALSE;
        }
    }

    /*
    Удаляем продукт из заказа
     */
    public function delete_product(int $id) {
        $product = $this->get_product($id);

        //удаляем из базы этот продукт
        $this->db()->table('orders_products')->delete(['id' => $id]);
        $this->db()->table('orders_items')->delete(['id_order_product' => $id]);

        $products = $this->products($product['id_order']);
        if (count($products) <= 0) { //если больше нет продуктов в заказе то удаляем сам заказ
            return $this->db()->table('orders')->delete(['id' => $product['id_order']]);
        }
        return TRUE;
    }

    /*
    Удалить пользователя со всеми заказами
     */
    public function delete_user(int $chat_id) {
        $orders = $this->db->table('orders')->where('chat_id', $chat_id)->get()->getResultArray();
        foreach ($orders as $order) {
            $this->delete($order['id']);
        }
    }

    /*
    Удалить заказ со всеми продуктами
     */
    public function delete(int $id_order) {
        $products = $this->products($id_order);
        foreach ($products as $product) {
            $this->delete_product($product['id']);
        }

        $this->db()->table('balance')->delete(['id_order' => $id_order]);
        $this->db()->table('promocode')->delete(['id_order' => $id_order]);

        return $this->db()->table('orders')->delete(['id' => $id_order]);
    }

    /*
    Получить данные заказа по адресу bitcoin
     */
    public function btc_address(string $address){  
        return $this->db->table('orders')->where('btc_address', $address)->get(1)->getRowArray();
    }

    public function get_order_item(int $id){
        return $this->db->table('orders_items')->where('id', $id)->get(1)->getRowArray();
    }

    /*
    Удалить единицу товара из заказа
     */
    public function delete_order_item(int $id){
        $order_item = $this->get_order_item($id);
        $data = [];
        $data['id_product'] = $order_item['id_product'];
        $data['id_item'] = $order_item['id_item'];
        $data['chat_id'] = $order_item['chat_id'];
        $data['id_order'] = $order_item['id_order'];
        return $this->delete_cart($data);
    }

    /*
    Получить данные заказа
     */
    public function get(int $id){
        return $this->db->table('orders')->where('id', $id)->get(1)->getRowArray();
    }

    public function set_order_item(array $data) {
        return $this->db->table('orders_items')
        ->where('id', $data['id'])
        ->update($data);
    }

    /*
    Изменить данные продукта в заказе
     */
    public function set_product_order(int $id_order, int $id_product, array $data) {
        return $this->db->table('orders_products')
        ->where('id_order', $id_order)
        ->where('id_product', $id_product)
        ->update($data);
    }

    /*
    Сохраняем данные продукта
     */
    public function set_product($data) {
        return $this->db->table('orders_products')->where('id', $data['id'])->update($data);
    }

    /*
    Получить данные продукта в заказе
     */
    public function get_product(int $id){  
        $db = $this->db->table('orders_products');
        $db->join('products', 'orders_products.id_product = products.id', 'left');
        $db->select('orders_products.*');
        $db->select('products.name');
        $db->where('orders_products.id', $id);
        return $db->get(1)->getRowArray();
    }

    /*
    Получить товары в заказе
     */
    public function products(int $id_order){  
        $db = $this->db->table('orders');
        $db->select('orders_products.*');
        $db->select('products.name');
        $db->groupBy('orders_products.id_product');
        $db->orderBy('orders_products.created', 'DESC');
        $db->where('orders.id', $id_order);
        $db->join('orders_products', 'orders.id = orders_products.id_order');
        $db->join('products', 'orders_products.id_product = products.id');
        $items = $db->get()->getResultArray();
        $result = [];
        foreach ($items as $item) {

            $orders_items = $this->db->table('orders_items')
            ->where('id_order_product', $item['id'])
            ->where('id_order', $item['id_order'])
            ->selectSum('price')
            ->get()
            ->getRowArray();

            if (isset($orders_items['price'])) {
                $item['price'] = $orders_items['price'];
                $item['price_pay'] = $orders_items['price'];
            }

            $item['name'] = json_decode($item['name']);
            $result[]=$item;
        }
        return $result;
    }

     /*
     * Получить список с помощью ajax
     *   
     * @docs https://datatables.net/manual/server-side#Sent-parameters
     * @docs https://datatables.net/examples/server_side/simple.html
     * @docs https://datatables.net/examples/data_sources/server_side.html
     */

     public function orders_($params, $filter = TRUE) {
        if (realpath(APPPATH."/ThirdParty/promo")) {
            $this->PromoModel = new \Promo\Models\PromoModel();
        }

        $db = $this->db->table('orders');

        //поисковой фильтр
        if (!empty($params['search']['value'])) {
            $db->groupStart();
            $id = (int) trim($params['search']['value']);
            $time = human_to_unix(trim($params['search']['value']));
            if (trim($params['search']['value']) == "не оплачен") {
                $db->where('orders.status', 0);
            } else if (trim($params['search']['value']) == "оплачен") {
                $db->where('orders.status', 1);
            } else if ($id > 0) {//если это число
                $db->where('orders.id', $id);
                $db->orWhere('orders.chat_id', $id);
                $db->orLike('orders.btc_address', trim($params['search']['value']));
            } else if ($time) {//если это дата                   
                $db->where('orders.created', trim($params['search']['value']));
                $db->orWhere('orders.updated', trim($params['search']['value']));
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
            'id',
            'sum',
            'date_finish',
            'status',
            'chat_id',
            'id'
        );

        $db->select('orders.*');
        $db->select('users.first_name, users.last_name, users.username, users.email, users.phone as phone_user');
        $db->select('pay_methods.name as name_pay, pay_methods.currency');

        //сортировка 
        $order_column = (int) $params['order'][0]['column'];
        $order_direction = $params['order'][0]['dir'];
        $dir = empty($order_direction) ? "desc" : $order_direction;
        $db->orderBy($need_fields[$order_column], $dir);

        $db->join('users', 'orders.chat_id = users.chat_id');
        $db->join('pay_methods', 'orders.id_pay = pay_methods.id');

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

            $data_item = "<a href='".base_url("orders/edit/" . $item['id'])."'>". $item['created']."</a>";
            $data[] = $data_item;
            
            $data[] = "<a href='".base_url("orders/edit/" . $item['id'])."'>". $item['id']."</a>";
            
            $data_item = "<a href='".base_url("orders/edit/" . $item['id'])."'>".number_format($item['sum'], $this->decimals, ',', ' ')."</a>";
            
            if (realpath(APPPATH."/ThirdParty/promo")) {
                if (!$promocode = $this->PromoModel->order($item['id'])) {
                    $promocode = $this->PromoModel->find_no_once($item['chat_id']);
                }
                if ($promocode) {
                    $data_item.="<br>Применен промокод <a href='".base_url("promo/edit/" . $promocode['id'])."'>".$promocode['name']."</a> ";
                    if ($promocode['percent'] > 0) {
                        $data_item.=" на ".$promocode['percent']." %";
                    } else {
                        $data_item.=" на ".$promocode['count_days']." ".$this->currency_name;
                    }
                }
            }
            $data_item.="<br>(".number_format($item['sum_pay'], 8, ',', ' ')." ".$item['currency'].")";
            if (!empty($item['txid'])) {
                $data_item.="<br>TXID: ".$item['txid'];
            }

            if ($item['id_pay'] == 4 AND $item['sum_pay'] <> $item['sum']) {
                
                if (!empty($item['btc_in']) AND $item['btc_in'] > 0) {
                    $data_item.="<br>Пришло: ".number_format($item['btc_in'], 8, ',', ' ')." ".$item['currency'];
                }
            }

            $data[] = $data_item;
            

            $data_item = ($item['status'] > 0) ?
            anchor('orders/status/' . $item['id'], '<span class="badge badge-success">оплачен</span>') :
            anchor('orders/status/' . $item['id'], 'не оплачен');

            if (!empty($item['status_text'])) {
                $data_item.="<br>".$item['status_text'];
            }
            $data[] = $data_item;

            $data_item =  "<a href='".base_url("admin/users/edit/" . $item['chat_id'])."'>".json_decode($item['first_name'])." ".json_decode($item['last_name'])."</a>";
            if (!empty($item['phone'])) {
                $data_item.="<br>Телефон: ".$item['phone'];
            }
            if (!empty($item['city'])) {
                $data_item.="<br>Город: ".$item['city'];
            }
            if (!empty($item['delivery_time'])) {
                $data_item.="<br>Время доставки: ".$item['delivery_time'];
            }
            $data[] = $data_item;
            
            //продукты в заказе
            $data[] = $this->order_products($item['id']);

            $data_item = "";
            $data_item .= "<a title='Изменить' class='btn btn-success btn-flat' href='" . base_url("orders/edit/" . $item['id']) . "'><i class='fa fa-pencil'></i></a>";
            $data_item .= "<a title='Удалить' class='btn btn-danger btn-flat' href='" . base_url("orders/delete/" . $item['id']) . "'><i class='fa fa-trash'></i></a>";
            $data[] = $data_item;

            $return[] = $data;
        }

        return $return;
    }

    /*
    Список единиц товара в заказе, строкой
     */
    public function order_products(int $id_order, $delim = "<br>"): string {
        $this->ModModel = new \Mods\Models\ModModel();
        $this->ProductModel = new \Products\Models\ProductModel();

        $products_items = $this->db->table('orders_products')
        ->where('id_order', $id_order)
        ->groupBy('id_product')
        ->get()
        ->getResultArray();

        $text = ""; $i = 0;
        foreach ($products_items as $item) {
            $mods_item_string = $this->ModModel->mods_item_string($item['id']);
            $product = $this->ProductModel->get($item['id_product']);
            if ($i > 0) {
                $text.=$delim;
            }
            $text.="<a href='".base_url('products/edit/'.$item['id_product'])."'>".$product['name'].'</a>';
            if (!empty($mods_item_string)) {
                $text.=' ('.$mods_item_string.')';
            }
            $i++;
        }

        return $text;
    }

    /*
    Список единиц товара в заказе, строкой
     */
    public function order_product_items(int $id_order, $delim = "<br>"): string {
        $this->ModModel = new \Mods\Models\ModModel();
        $this->ProductModel = new \Products\Models\ProductModel();

        $products_items = $this->db->table('products_items')
        ->where('id_order', $id_order)
        ->get()
        ->getResultArray();

        $text = ""; $i = 0;
        foreach ($products_items as $item) {
            $mods_item_string = $this->ModModel->mods_item_string($item['id']);
            $product = $this->ProductModel->get($item['id_product']);
            if ($i > 0) {
                $text.=$delim;
            }
            $text.=$product['name'];
            if (!empty($mods_item_string)) {
                $text.=' ('.$mods_item_string.')';
            }
            $i++;
        }

        return $text;
    }

}
