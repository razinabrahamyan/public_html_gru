<?php 
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
namespace Sender\Models;
use CodeIgniter\Model;
use \CodeIgniter\Database\ConnectionInterface;
use \EditorJS\EditorJS;

/**
 * Class UsersModel
 */
class PostsModel
{
	/**
	 * Database object
	 *
	 * @var \CodeIgniter\Database\BaseConnection
	 */
	protected $db;
	protected $config;
    protected $id;
    protected $lang;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->db = \Config\Database::connect();
        $this->ionAuth = new \IonAuth\Libraries\IonAuth();
        $this->SettingsModel = new \Admin\Models\SettingsModel();
        $this->LangModel = new \Admin\Models\LangModel();

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

    public function segments_send() {
        $this->ProductModel = new \Products\Models\ProductModel();
        $data['products'] = [];

        $segments = $this->segments();
        foreach ($segments as $segment) {
            $item['id'] = $segment['id_product'];
            $item['name'] = $segment['name'];
            $data['products'][]=$item;
        }

        $products = $this->ProductModel->items();
        $data['products'] = array_merge($data['products'], $products);

        return  $data['products'];
    }
    /*
    Сохранить настройки получения новостей
     */
    public function set_subscribe(int $chat_id, array $id_products = []) {
        $this->db->table('posts_access')->delete(['chat_id' => $chat_id]);
        $items = [];
        foreach ($id_products as $id_product) {
            $item = [];
            $item['chat_id'] = $chat_id;
            $item['id_product'] = $id_product;
            $items[]=$item;
        }
        if (count($items) > 0) {
            return $this->db->table('posts_access')->insertBatch($items);
        }
        return FALSE;
    }

    /*
     * Статистика в виде круговой диаграммы
     * по статусам доставки
     */

    public function pie(int $id_post) {
        $db = $this->db->table('posts_sended');
        $db->groupBy('result');
        $db->select('result, count(*) as count');
        $db->where('id_post', $id_post);
        $items = $db->get()->getResultArray();

        $datapie = [];
        foreach ($items as $item) {
            $obj = new \stdClass();
            $obj->label = $item['result'];
            $obj->value = $item['count'];
            $datapie[] = $obj;
        }
        return json_encode($datapie);
    }

    /*
     * Получить список с помощью ajax
     *   
     * @docs https://datatables.net/manual/server-side#Sent-parameters
     * @docs https://datatables.net/examples/server_side/simple.html
     * @docs https://datatables.net/examples/data_sources/server_side.html
     */

    public function stat_($id, $params, $filter = TRUE) {
        $db = $this->db->table('posts_sended');

        //поисковой фильтр
        if (!empty($params['search']['value'])) {
            $db->groupStart();
            $id = (int) trim($params['search']['value']);
            $time = human_to_unix(trim($params['search']['value']));
            if ($id > 0) {//если это число
                $db->where('posts_sended.chat_id', $id);
            } else if ($time) {//если это дата                   
                $db->where('posts_sended.created', trim($params['search']['value']));
            } else {//если это текст
                $db->orLike('posts_sended.result', trim($params['search']['value']));
                $db->orLike('users.first_name', trim($params['search']['value']));
                $db->orLike('users.last_name', trim($params['search']['value']));
                $db->orLike('users.email', trim($params['search']['value']));
                $db->orLike('users.username', trim($params['search']['value']));
            }
            $db->groupEnd();
        }

        //список полей которые будут в таблице                
        $need_fields = [
            'created',
            'chat_id',
            'result'
        ];

        //сортировка 
        $order_column = (int) $params['order'][0]['column'];
        $order_direction = $params['order'][0]['dir'];
        $dir = empty($order_direction) ? "desc" : $order_direction;
        $db->orderBy($need_fields[$order_column], $dir);

        $db->where('posts_sended.id_post', $id);

        $db->select('posts_sended.*');
        $db->select('users.first_name, users.last_name, users.username, users.email, users.phone');
        
        $db->join('users', 'posts_sended.chat_id = users.chat_id');

        if ($filter) {
            $limit = (int) $params['length'];
            $offset = (int) $params['start'];
            $items = $db->get($limit, $offset);
        } else {//если без фильтра - то общее количество записей
            return $db->countAll();
        }

        $return = [];
        foreach ($items->getResultArray() as $item) {
            $data = [];

            $data[] = date("d.m.Y H:i", human_to_unix($item['created']));

            $data[] = "<a href='".base_url("admin/users/edit/" . $item['chat_id'])."'>".json_decode($item['first_name'])." ".json_decode($item['last_name'])."</a>";
            
            $data[] = $item['result'];

            $return[] = $data;
        }

        return $return;
    }

    /*
    Завершение редактирования черновика
    Генерация списка получателей
    Отправка в очередь
     */
    public function finish(int $id) {
        $count_users = 0;

        //получаем сегменты
        $products_to_posting = $this->segment_posting($id);
        if (count($products_to_posting) <= 0 OR in_array(0, $products_to_posting)) {
            $count_users = $this->generate_segment_all($id);
        } else {//если указаны сегменты и не указан "всем"
            if (in_array(-1, $products_to_posting)) { //если есть сегмент "без покупок"
                $count_users = $this->generate_segment_nobuyed($id, $products_to_posting);
            } elseif (in_array(-2, $products_to_posting)){ //покупал любой продукт
                $count_users = $this->generate_segment_buyed_any($id, $products_to_posting);
            } else {//если указаны конкретные продукты и надо смотреть куплен или нет у клиента продукт
                $count_users = $this->generate_segment_buyed($id, $products_to_posting);
            }//else
        }//else 

        //если не кому отправлять то сохранять черновик нет смысла
        if ($count_users <= 0) {
            return FALSE;
        }

        $data_upd = [];
        $data_upd['finished'] = 1;
        $data_upd['count_users'] = $count_users;
        if ($this->set($id, $data_upd)) {
            return $count_users;
        }
        return FALSE;
    }

    /*
    Мои настройки уведомлений в виде текста
     */
    public function my_subscribe_string(int $chat_id): string {
        $text = ""; $i= 0;
        $my_subscribe = $this->my_subscribe($chat_id);
        foreach ($my_subscribe as $subscribe) {
            if ($subscribe['id_product'] == 0) {
                return "отключены";
            }
            if ($i > 0) {
                $text.=", ";
            }
            $text.=$subscribe['name'];
            $i++;
        }
        return $text;
    }

    /*
    Мои настройки уведомлений
     */
    public function my_subscribe(int $chat_id): array {
        return $this->db->table('posts_access')
        ->join('posts_segments', 'posts_access.id_product = posts_segments.id_product')
        ->where('posts_access.chat_id', $chat_id)
        ->select('posts_access.id_product')
        ->select('posts_segments.name')
        ->groupBy('posts_access.id')
        ->get()
        ->getResultArray();
    }

    /*
    Все сегменты в системе
     */
    public function segments(): array {
        return $this->db->table('posts_segments')
        ->where('active', 1)
        ->get()
        ->getResultArray();
    }

    /*
    Этот подписчик отписался от уведомлений
     */
    public function is_unsubscribe(int $chat_id): bool {
        return $this->db->table('posts_access')
        ->where('chat_id', $chat_id)
        ->where('id_product', 0)
        ->countAllResults() > 0;
    }

    /*
    Генерим сегмент "всем"
     */
    public function generate_segment_all(int $id): int {
        $count_users = 0;
        $created = date("Y-m-d H:i:s");
        $items = [];

        //добавляем всех в получаетелей
        $users = $this->db->table('users')
        ->select('chat_id')
        ->where('chat_id>', 0)
        ->where('active', 1)
        ->get()
        ->getResultArray();
        foreach ($users as $user) {
            if ($this->is_unsubscribe($user['chat_id'])) {
                continue;
            }
            $data = [];
            $data['chat_id'] = $user['chat_id'];
            $data['id_post'] = $id;
            $data['created'] = $created;
            $items[]=$data;
            $count_users++;
        }

        if (count($items) > 0) {
            $this->db->table('posts_to')->insertBatch($items);
        }

        return $count_users;
    }

    /*
    Генерим сегмент "покупал любой продукт"
     */
    public function generate_segment_buyed_any(int $id, $products_to_posting): int {
        $items = [];
        $count_users = 0;
        $created = date("Y-m-d H:i:s");
        $this->OrderModel = new \Orders\Models\OrderModel();

        //добавляем всех в получаетелей
        $users = $this->db->table('users')
        ->select('chat_id')
        ->where('chat_id>', 0)
        ->where('active', 1)
        ->get()
        ->getResultArray();
        foreach ($users as $user) {
            $count_buyed = $this->OrderModel->buyed_products($user['chat_id'], FALSE);
            if ($count_buyed <= 0) {
                continue; //если нет покупок
            }
            
            $data = [];
            $data['chat_id'] = $user['chat_id'];
            $data['id_post'] = $id;
            $data['created'] = $created;
            $items[]=$data;
            $count_users++;
        }

        if (count($items) > 0) {
            $this->db->table('posts_to')->insertBatch($items);
        }

        return $count_users;
    }

    /*
    Генерим сегмент "без покупок"
     */
    public function generate_segment_nobuyed(int $id, $products_to_posting): int {
        $items = [];
        $count_users = 0;
        $created = date("Y-m-d H:i:s");
        $this->OrderModel = new \Orders\Models\OrderModel();

        //добавляем всех в получаетелей
        $users = $this->db->table('users')
        ->select('chat_id')
        ->where('chat_id>', 0)
        ->where('active', 1)
        ->get()
        ->getResultArray();
        foreach ($users as $user) {
            if ($this->is_unsubscribe($user['chat_id'])) {
                continue;
            }
            $count_buyed = $this->OrderModel->buyed_products($user['chat_id'], FALSE);
            if ($count_buyed > 0) {
                continue; //если у него есть хотя бы одна покупка то пропускаем
            }
            
            $data = [];
            $data['chat_id'] = $user['chat_id'];
            $data['id_post'] = $id;
            $data['created'] = $created;
            $items[]=$data;
            $count_users++;
        }

        if (count($items) > 0) {
            $this->db->table('posts_to')->insertBatch($items);
        }

        return $count_users;
    }

    /*
    Генерим сегмент - купили определенные продукты
     */
    public function generate_segment_buyed(int $id, $products_to_posting): int {
        $count_users = 0;
        $created = date("Y-m-d H:i:s");
        $this->OrderModel = new \Orders\Models\OrderModel();
        $users = $this->db->table('users')
            ->select('chat_id')
            ->where('chat_id>', 0)
            ->where('active', 1)
            ->get()
            ->getResultArray();

        $items = [];
        foreach ($users as $user) {
            if ($this->is_unsubscribe($user['chat_id'])) {
                continue;
            }
            $need = FALSE;
            foreach ($products_to_posting as $id_product) {
                //если у пользователя есть хотя бы на один продукт купленный
                if ($this->OrderModel->buyed_product($id_product, $user['chat_id'])) {
                    $need = TRUE; //тогда берем его
                }
            }

            if (!$need) {
                continue;
            }

            $data = [];
            $data['chat_id'] = $user['chat_id'];
            $data['id_post'] = $id;
            $data['created'] = $created;
            $items[]=$data;
            $count_users++;

        }//foreach

        if (count($items) > 0) {
            $this->db->table('posts_to')->insertBatch($items);
        }

        return $count_users;
    }

    /*
    Генерим сегмент - "акции"
     */
    public function generate_segment_actions(int $id, $products_to_posting): int {
        $count_users = 0;
        $created = date("Y-m-d H:i:s");

        //тем кто подписался на акции
        $users = $this->db->table('users')
        ->select('chat_id')
        ->where('chat_id>', 0)
        ->where('active', 1)
        ->get()
        ->getResultArray();

        $items = [];
        foreach ($users as $user) {
            if ($this->is_unsubscribe($user['chat_id'])) {
                continue;
            }

            $need = FALSE;
            foreach ($products_to_posting as $id_product) {
                if ($this->db->table('posts_access')
                ->where('chat_id', $user['chat_id'])
                ->where('id_product', $id_product)
                ->countAllResults() > 0) {
                    $need = TRUE;
                }

                if (!$need) {
                    continue;
                }
                $item = [];
                $item['chat_id'] = $user['chat_id'];
                $item['id_post'] = $id;
                $item['created'] = $created;
                $items[]=$item;
                $count_users++;
            }
        }

        if (count($items) > 0) {
            $this->db->table('posts_to')->insertBatch($items);
        }

        return $count_users;
    }

    /*
    Получить текущий для отправки пост
     */
    public function post_now() {
        //берем пост для отправки
        $post = $this->db->table('posts')
            ->orderBy('datestart', 'ASC')
            ->where('finished', 1)
            ->where('sended', 0)
            ->where('datestart<=', date("Y-m-d H:i:s"))
            ->get(1)
            ->getRowArray();

        return empty($post['id']) ? FALSE : $post;
    }

    /*
    Отправка очередями
     */
    public function send() {
        
        if (!$post = $this->post_now()) {
            return print_r("Нет поста в очереди!"); //нет поста на очереди
        }

        //берем получаетелей
        $users = $this->db->table('posts_to')
            ->orderBy('created', 'ASC')
            ->select('chat_id')
            ->where('sended', 0)
            ->where('id_post', $post['id'])
            ->get($this->limit_send)
            ->getResultArray();

        //если всем отправили
        if (count($users) <= 0) {
            //помечаем пост разосланным
            return $this->set($post['id'], ['sended' => 1]);
        }

        //получаем текст для отправки
        $text = $this->text($post['id']);

        //если нет ни текста сообщения ни файла - финализируем пост
        if (empty($post['file_id']) AND empty($text)) {
            //удаляем из очереди рассылки
            $this->db->table('posts_to')->delete(['id_post' => $post['id']]);

            return $this->set($post['id'], ['sended' => 1]);
        }

        $this->TelegramModel = new \App\Models\TelegramModel();
        $this->MenuModel = new \App\Models\MenuModel();


        $k = 0;
        //обходим порцию получаетелей
        foreach ($users as $user) {

            //помечаем отправленным
            $this->db->table('posts_to')
            ->where('id_post', $post['id'])
            ->where('chat_id', $user['chat_id'])
            ->update(['sended' => 1]);

            //если результат отправки уже есть
            $count = $this->db->table('posts_sended')
                ->select('id')
                ->limit(1)
                ->where('chat_id', $user['chat_id'])
                ->where('id_post', $post['id'])
                ->countAllResults();

            if ($count > 0) {
                continue; //не отправляем повторно
            }

            
            $message['message']['chat']['id'] = $user['chat_id'];

            if ($post['sum_bonus'] > 0) {
                $params = $this->MenuModel->post_bonus($message, $post['id']);
            } else {
                $params = $this->MenuModel->get($message);
            }
            
            if (empty($post['file_id'])) {
                $result = $this->TelegramModel->sendMessage($user['chat_id'], $text, $params);
            } else { //отправляем файл
                if (!empty($text)) {
                    $params['caption'] = $text;
                }
                $result = $this->TelegramModel->sendFile($user['chat_id'], $post['file_id'], $params);
            }

            $k++;
            if ($k >= 30) {
                $k = 0;
                sleep(1);
            }

            //пишем результат отправки
            $data = [];
            if ($result->ok) {
                $data['delivered'] = 1;
                $data['message_id'] = $result->result->message_id;
                $data['result'] = "Доставлено";
            } else {
                $data['result'] = $this->translate_error($result->description);
                $data['delivered'] = 0;
                $data['message_id'] = 0;
            }
            $data['chat_id'] = $user['chat_id'];
            $data['id_post'] = $post['id'];
            $this->db->table('posts_sended')->insert($data);

            //удаляем из очереди отправки
            $this->db->table('posts_to')->delete(['id_post' => $post['id'], 'chat_id' => $user['chat_id']]);
        } //foreach

    }

    /*
    Получить текст страницы для отправки в телеграм 
     */
    public function text($id = FALSE, array $fill = []): string {
        
        //получаем текст сообщения с учетом языка
        if (!$post = $this->get($id, TRUE) OR empty($post['text'])) {
            return '';
        }

        $this->PagesModel = new \Admin\Models\PagesModel();

        //декодируем в обычную строку
        $text = $this->PagesModel->json_to_txt($post['text']);

        //заполнить массивом
        return $this->PagesModel->fill($text, $fill);
    }

    /*
    Сохранить список сегментов
     */
    public function set_products(int $id_post, $products) {
        $this->db()->table('posts_tarifs')->delete(['id_post' => $id_post]);
        foreach ($products as $id_product) {
            $data = [];
            $data['created'] = date("Y-m-d H:i:s");
            $data['id_product'] = $id_product;
            $data['id_post'] = $id_post;
            $this->db->table('posts_tarifs')->insert($data);
        }
        return TRUE;
    }

    /*
    Сохранить данные сообщения
     */
    public function set($id, $data) {
        return $this->db->table('posts')->where('id', $id)->update($data);
    }

    /*
    Удалить пост с историей
     */
    public function delete(int $id_post) {
        $this->db->transBegin();

        $this->db()->table('posts_to')->delete(['id_post' => $id_post]);
        $this->db()->table('posts_tarifs')->delete(['id_post' => $id_post]);
        $this->db()->table('posts_sended')->delete(['id_post' => $id_post]);
        $this->db()->table('posts')->delete(['id' => $id_post]);

        //закрываем транзакцию
        $this->db->transComplete();
        if ($this->db->transStatus() === FALSE) {
            $this->db->transRollback(); //откатить изменения
            return FALSE;
        } else {
            $this->db->transCommit(); //зафиксировать изменения в БД
            return TRUE;
        }
    }

    /*
    Получить данные поста
     */
    public function get($id, $full = FALSE) {
        $data = $this->db
        ->table('posts')
        ->where('id', $id)
        ->limit(1)
        ->get()
        ->getRowArray();

        if (empty($data['text'])) {
            $data['text'] = json_encode([]);
        }

        return $full ? $data : $data['text'];
    }

    

    /*
    Создать черновик или получить не законченный
     */
    public function create() {
        $db = $this->db->table('posts');
        $db->where('finished', 0);
        $db->limit(1);
        if ($db->countAllResults() <= 0) {
            $db = $this->db->table('posts');
            $data = [];
            $data['created'] = date("Y-m-d H:i:s");
            $data['datestart'] = $data['created'];
            $db->insert($data);
            $id_post = $this->db->insertID();
            return $id_post;
        }

        $db = $this->db->table('posts');
        $db->where('finished', 0);
        $db->limit(1);
        return $db->get()->getRow()->id;
    }

    /*
     * Получить список с помощью ajax
     *   
     * @docs https://datatables.net/manual/server-side#Sent-parameters
     * @docs https://datatables.net/examples/server_side/simple.html
     * @docs https://datatables.net/examples/data_sources/server_side.html
     */

    public function items_($params, $filter = TRUE) {
        $db = $this->db->table('posts');

        //поисковой фильтр
        if (!empty($params['search']['value'])) {
            $db->groupStart();
            $id = (int) trim($params['search']['value']);
            $time = human_to_unix(trim($params['search']['value']));
            if ($id > 0) {//если это число
                $db->where('posts.id', $id);
            } else if ($time) {//если это дата                   
                $db->where('posts.created', trim($params['search']['value']));
                $db->orWhere('posts.datestart', trim($params['search']['value']));
            } else {//если это текст
                $db->orLike('posts.text', trim($params['search']['value']));
            }
            $db->groupEnd();
        }

        //список полей которые будут в таблице                
        $need_fields = [
            'id',
            'text',
            'created',
            'sended'
        ];

        //сортировка 
        $order_column = (int) $params['order'][0]['column'];
        $order_direction = $params['order'][0]['dir'];
        $dir = empty($order_direction) ? "desc" : $order_direction;
        $db->orderBy($need_fields[$order_column], $dir);

        if ($filter) {
            $limit = (int) $params['length'];
            $offset = (int) $params['start'];
            $items = $db->get($limit, $offset);
        } else {//если без фильтра - то общее количество записей
            return $db->countAll();
        }

        $this->ProductModel = new \Products\Models\ProductModel();

        $return = [];
        foreach ($items->getResultArray() as $item) {
            $data = [];
            $data[] = $item['id'];

            $data_item = "";
            if ($item['sended'] <= 0) {//если не отправлено еще - то можно исправить
                $data_item .= "<a href='" . base_url('sender/edit/' . $item['id']) . "'>";
            }

            //конвертим JSON в строку
            $data_item .= empty($item['text']) ? "" : $this->json_to_short($item['text']);

            if (isset($item['file_id']) AND ! empty($item['file_id'])) {
                $data_item .= ' <i title="С файлом" class="far fa-file-image"></i>';
            }

            if ($item['sended'] <= 0) {
                $data_item .= "</a>";
            }

            $data[] = $data_item;

            //дата создания
            $data[] = date("d.m.Y H:i", human_to_unix($item['created']));


            $count_no_delivered = $this->count_no_delivered($item);

            $percent= $this->stat_delivery($item);
            $percent_delivered = $percent['percent'];
            $count_delivered = $percent['delivered'];
            $count_users = $item['count_users'];

            if ($item['sended'] > 0) {
                $data_item = "Готово \xE2\x9C\x85";
            } else {
                $post_now = $this->post_now();
                if ($post_now !== FALSE AND $post_now['id'] == $item['id']) {
                    $data_item = "Отправляется...";
                    $data_item .= "<br>(" . $item['count_users'] . ")";
                } else {
                    if ($item['finished'] <= 0) { 
                        $data_item = "черновик";
                    } else {
                        $data_item = "В очереди \xE2\x8C\x9B";
                        if (!empty($item['datestart'])) {
                            $data_item.="<br>Отправка в ".date("d.m.Y H:i", human_to_unix($item['datestart']));
                        }
                    }
                }
            }

            if ($item['sended']) {
                $data_item .= '<div class="progress">
                <div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar" aria-valuenow="' . $percent['percent'] . '" aria-valuemin="0" aria-valuemax="100" style="width: ' . $percent['percent'] . '%">
                <span class="sr-only">' . $percent['percent'] . '% доставлено</span>
                ' . $percent['delivered'] . ' из ' . $item['count_users']. ' (' . $percent['percent'] . '%)
                </div>
                </div>';
            } else {
                $data_item .= '<div class="progress">
                <div class="progress-bar progress-bar-primary progress-bar-striped" role="progressbar" aria-valuenow="' . $percent['percent'] . '" aria-valuemin="0" aria-valuemax="100" style="width: ' . $percent['percent'] . '%">
                <span class="sr-only">' . $percent['percent'] . '% доставлено</span>
                </div>
                </div>';
            }

            if ($count_no_delivered > 0) {
                $data_item .= "Не доставлено " . $count_no_delivered;
            }
            if ($percent['delivered'] > 0) {
                $data_item .= "<br>Доставлено " . $percent['delivered'];
            }

            $data[] = $data_item;

            $data_item = "всем";
            $segment_posting = $this->segment_posting($item['id']);
            if (in_array(0, $segment_posting)) {
                $data_item = "всем";
            } else {
                $data_item = "";
                $i = 0;
                foreach ($segment_posting as $id_product) {
                    if ($i > 1) {
                        $data_item .= ", ";
                    }
                    if ($id_product == -1) {
                        $data_item .= "Ничего не покупал";
                    } else if ($id_product == -2){
                        $data_item .= "Покупал любой продукт";
                    } else {
                        $data_product = $this->ProductModel->get($id_product);
                        $data_item .= "<a href='".base_url('products/edit/'.$id_product)."'>".$data_product['name']."</a>";
                    }
                    $i++;
                }
            }
            $data[] = $data_item;
            
            $data_item = "";
            if ($item['sended'] <= 0) {
                $data_item.="<a title='Изменить' class='btn btn-success btn-flat' href='" . base_url("sender/edit/" . $item['id']) . "'><i class='fa fa-pencil'></i></a>";
            }
            $data_item .= "<a title='Статистика' class='btn btn-primary btn-flat' href='" . base_url("sender/stat/" . $item['id']) . "'><i class='fas fa-signal'></i></a>";
            $data_item .= "<a title='Удалить' class='btn btn-danger btn-flat' href='" . base_url("sender/delete/" . $item['id']) . "'><i class='fa fa-trash'></i></a>";
            $data[] = $data_item;

            $return[] = $data;
        }

        return $return;
    }

    /*
    Конвертируем JSON текст в короткое превью для таблицы
     */
    public function json_to_short(string $text, int $limit = 25): string {
        helper(['text']);
        $text_ = "";
        $arr = json_decode($text);
        if (is_array($arr) AND count($arr) > 0) {
            foreach($arr as $item) {
                if (!isset($item->data->text)) {
                    continue;
                }
                $text_.= $item->data->text;
                $text_.= "\n";
            }
        }
        return htmlspecialchars(word_limiter(strip_tags($text_), $limit,"..."), ENT_QUOTES,'UTF-8');
    }

    /*
     * Получить id тарифов для публикации поста
     */

    public function segment_posting(int $id_post): array {
        $db = $this->db->table('posts_tarifs');
        $db->where('id_post', $id_post);
        $db->select('id_product');
        $posts_tarifs = $db->get()->getResultArray();

        $id_selected = [];
        foreach ($posts_tarifs as $item) {
            $id_selected[] = $item['id_product'];
        }
        return $id_selected;
    }

    /*
    Получить процент доставленных 
     */
    public function stat_delivery($data_post) {
        $count_no_delivered = $this->count_no_delivered($data_post);
        $count_delivered = $this->count_delivered($data_post);

        //получаем процент доставляемсоти
        $percent_delivered = ($count_delivered > 0 AND $data_post['count_users'] > 0) ? round(100 / ($data_post['count_users'] / $count_delivered)) : 0;

        return [
            'percent' => $percent_delivered,
            'delivered' => $count_delivered,
            'no_delivered' => $count_no_delivered
        ];
    }

    

    /*
     * Количество пользователей которым должно было прийти
     */

    public function count_subscribers() {
        $db = $this->db->table('users');
        return $db->countAllResults();
    }

    /*
     * Количество не доставленных
     */

    public function count_no_delivered($data_post) {
        $db = $this->db->table('posts_sended');
        $db->where('delivered', 0);
        $db->where('id_post',  $data_post['id']);
        return $db->countAllResults();
    }

    /*
     * Количество доставленных
     */

    public function count_delivered($data_post) {
        $db = $this->db->table('posts_sended');
        $db->where('delivered', 1);
        $db->where('id_post',  $data_post['id']);
        return $db->countAllResults();
    }

    /*
     * Перевести ошибку доставки
     */

    public function translate_error($text) {
        $data_item = $text;

        if (stripos($data_item, "Bad Request: can't parse entities") !== FALSE) {
            $data_item = "Лишние теги в сообщении, помотрите <a target='_blank' href='https://help.botcreator.ru/editor'>видеоурок по работе с редактором</a> еще раз медленно и внимательно!";
            $data_item .= " \xE2\x9D\x8C";
        } else {
            switch ($text) {
                case "Forbidden: bot was blocked by the user":
                $data_item = "Пользователь заблокировал бота";
                $data_item .= " \xE2\x9D\x8C";
                break;
                case "Bad Request: chat not found":
                case "Forbidden: user is deactivated":
                $data_item = "Пользователь удален";
                $data_item .= " \xE2\x9D\x8C";
                break;
                case "Forbidden: bot can't initiate conversation with a user":
                $data_item = "Пользователь не писал боту ни разу";
                $data_item .= " \xE2\x9D\x8C";
                break;
                case "Forbidden: bot can't send messages to bots":
                $data_item = "Не могу писать другому боту";
                $data_item .= " \xE2\x9D\x8C";
                break;
                default:
                if (stripos($data_item, "Forbidden") === FALSE AND stripos($data_item, "Bad Request") === FALSE) {
                        $data_item .= " \xE2\x9C\x85"; //ok
                    } else {
                        $data_item .= " \xE2\x9D\x8C"; //bad
                    }
                    break;
                }
            }

            return $data_item;
        }

    

    /*
    Сохраняем данные с редактора сообщений

    @docs https://editorjs.io/base-concepts
    @src https://github.com/editor-js/editorjs-php
     */
    public function save($data = NULL, $id, $short = "ru") {
        if (empty($data)) {
            return FALSE;
        }
        
        $data_ = []; 
        foreach($data['blocks'] as $item) {
            if ($item['type'] == "delimiter") {
                $item['data']['text'] = '<i>***</i>';
            }
            $data_[]=$item;
        }
        $data['blocks'] = $data_;

        try {
            //настройки фильтрации от лишних тегов
            $config = file_get_contents(APPPATH."/ThirdParty/panel/Config/editorjs.json");

            $data = json_encode($data);
            $editor = new EditorJS($data, $config);
            
            $blocks = $editor->getBlocks();

            $blocks = json_encode($blocks);

            $db = $this->db->table('posts');
            $db->where('id', $id);
            return $db->update(['text' => $blocks]);
        } catch (\EditorJSException $e) {
            log_message('error', print_r($e->getMessage(), TRUE));
            return FALSE;
        }
    }
}
