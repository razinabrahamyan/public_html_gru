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
namespace Products\Models;
use CodeIgniter\Model;

use \CodeIgniter\Database\ConnectionInterface;
use \EditorJS\EditorJS;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

//для чтения файлов
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

use voku\helper\UTF8;
use DiDom\Document;

ini_set('memory_limit', '-1');

/**
 * Class UsersModel
 */
class ProductModel
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
        $this->ionAuth = new \IonAuth\Libraries\IonAuth();
        $this->SettingsModel = new \Admin\Models\SettingsModel();
        $this->LangModel = new \Admin\Models\LangModel();
        $this->TelegramModel = new \App\Models\TelegramModel();

        $settings = $this->SettingsModel->all(TRUE);
        foreach ($settings as $settings_) {
            $this->{$settings_['name']} = trim($settings_['value']);
        }
    }

    public function set_kg($data) {
        return $this->db->table('products_kg')->update($data, ['id' => $data['id']]);
    }

    public function delete_kg(int $id) {
        return $this->db->table('products_kg')->delete(['id' => $id]);
    }

    public function add_kg($data) {
        return $this->db->table('products_kg')->insert($data);
    }

    public function item_kg(int $id): array {
        return $this->db->table('products_kg')->where("id", $id)->get(1)->getRowArray();
    }

    public function product_items_kg(int $id_product): array {
        return $this->db->table('products_kg')->where("id_product", $id_product)->get()->getResultArray();
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
    Получить артикул из id
     */
    public function sku(int $id) {
        return str_pad($id, 8, '0', STR_PAD_LEFT);
    }

    /*
    Извлечь данные из файла CSV с 1С
     */
    public function extract_csv($src) {
        if (!$src) {
            return FALSE;
        }
        try {
            $reader = IOFactory::createReaderForFile($src);
            $reader->setInputEncoding('Windows-1251');
            $spreadsheet = $reader->load($src);

            $return = $spreadsheet->getActiveSheet()->toArray(NULL, TRUE, TRUE, TRUE);
            
            if (isset($return[1])) {
                unset($return[1]);
            }

            return $return;
        } catch (\Exception $e) {
            return [];
        }
    }

    /*
    Удаление продукта - из xls файла пришло
     */
    public function delete_xls($item) {
        $name = json_encode($item['A']);
        
        //находим товар с таким названием
        $product = $this->db->table('products')
        ->where('name', $name)
        ->get(1)
        ->getRowArray();

        if (!isset($product['id'])) {
            return FALSE;
        }

        //удаляем из базы
        return $this->delete($product['id']);
    }

    /*
    Копирование единицы товаров в другие товары
     */
    public function copy_item(array $data) {
        if (!isset($data['id_products'])) {
            return FALSE;
        }

        $this->ModModel = new \Mods\Models\ModModel();

        $i = 0;
        foreach ($data['id_products'] as $id_product) {
            $data_item = $this->get_item($data['id']);

            $mods = $this->ModModel->mods_item($data_item['id']);
            $data_item['id_mods'] = [];
            foreach ($mods as $mod) {
                $data_item['id_mods'][]=$mod['id_mod'];
            }
            
            unset($data_item['id']);

            if ($id_item_new = $this->add_item($id_product, $data_item)) {
                $i++;
            }

            $photos = $this->photos($data['id']);
            foreach ($photos as $photo) {
                unset($photo['id']);
                $photo['id_item'] = $id_item_new;
                $this->add_photo($photo);
            }
        }
        return $i;
    }

    public function find_product(string $text) {
        $text_ = json_encode($text);

        $products = $this->db->table('products')
        ->groupBy('products.id')
        ->orderBy('products.priority', "DESC")
        ->where('products.price>', 0)
        ->groupStart()
        ->where('products.id', $text)
        ->orLike('products.name', $text_)
        ->orLike('products.name_', $text)
        ->groupEnd()
        ->select('products.*')
        ->select('products.name')
        ->get()
        ->getResultArray();
        return count($products) <= 0 ? FALSE : $products;
    }

    /*
    Поиск по названию
     */
    public function find(string $text) {
        $text_ = json_encode($text);

        $products = $this->db->table('products')
        ->join('products_items', 'products.id = products_items.id_product')
        ->groupBy('products_items.id')
        ->orderBy('products_items.priority', "DESC")
        ->like('products_items.articul', $text)
        ->orLike('products.name', $text_)
        ->orLike('products.name_', $text)
        ->select('products_items.*')
        ->select('products.name')
        ->get()
        ->getResultArray();
        return count($products) <= 0 ? FALSE : $products;
    }

    public function set_photo(array $data): bool{
        return $this->db->table('products_items_photo')->where('id', $data['id'])->update($data);
    }

    /*
    Получить данные 
     */
    public function get_photo(int $id) {
        return $this->db->table('products_items_photo')->where('id', $id)->get()->getRowArray();
    }

    /*
    Удалить запись настройки уровня комиссионных
     */
    public function delete_photo(int $id) {
        return $this->db()->table('products_items_photo')->delete(['id' => $id]);
    }


    /*
    Добавить фото
     */
    public function add_photo(array $data) {
        return $this->db->table('products_items_photo')->insert($data);
    }

    /*
    Получить массив фото для отправки альбом
    до 10 штук
     // $files = [
        //     0 => [
        //     'type' => 'photo',
        //     'media' => 'fileid1',
        //     'caption' => 'test1'
        //     ],

        //     2 => [
        //     'type' => 'photo',
        //     'media' => 'fileid2',
        //     'caption' => 'test1'
        //     ],
        // ];
     */
    public function albom(int $id_item) : array {
        return $this->db->table('products_items_photo')
        ->where('id_item', $id_item)
        ->select('type, media, caption')
        ->get(10)
        ->getResultArray();
    }

    /*
    Список всех фото
     */
    public function photos(int $id_item) : array {
        return $this->db->table('products_items_photo')
        ->where('id_item', $id_item)
        ->get()
        ->getResultArray();
    }

    /*
    Есть ли у категории родители
     */
    public function is_root_category(int $id_category): bool {
        return $this->db->table('products_category_parent')
        ->where('id_category', $id_category)
        ->where('id_parent', 0)
        ->countAllResults() > 0;
    }

    /*
    Продукты в выбранной категории
    С учетом хлебных крошек
     */
    public function products_in_menu(int $id_category, int $chat_id, int $offset = 0, $is_count_all = FALSE) {
        $path = $this->path($chat_id);
        $path[]=$id_category;

        if (count($path) <= 0) {
            return $is_count_all ? 0 : [];
        }

        // log_message('error', print_r($id_category, TRUE));
        // log_message('error', print_r($path, TRUE));

        //проверяем находится ли пользователь щас в корневой папке (нет крошек)
        $in_root = $this->user_in_root($chat_id);

        //получаем id товаров которые надо вывести
        $ids_need = $this->products_need($path, $in_root);
        
        var_dump($ids_need);
        echo "<hr>";
        
        if (count($ids_need) <= 0) {
            return $is_count_all ? 0 : [];
        }

        $db = $this->db->table('products');
        $db->where('products.active', 1);
        $db->where('products.id>', 0);
        $db->where('products.price>', 0);
        $db->select('products.*');
        $db->groupBy('products.id');
        $db->orderBy('products.priority', "DESC");
        $db->orderBy('products.name', "ASC");
        $db->whereIn('products.id', $ids_need);
        if ($is_count_all) {
            $result = $db->get()->getResultArray();
        } else {
            $result = $db->get($this->limit_products, $offset)->getResultArray();
        }

        if ($is_count_all) {
            return count($result);
        }

        return $result;
    }

    /*
    Пользователь находится в корневой папке
     */
    public function user_in_root(int $chat_id): bool {
        return $this->db->table('products_category_path')->where('chat_id', $chat_id)->countAllResults() <= 0;
    }

    /*
    Получаем продукты которые могут быть видны в конкретной категории с учетом
    хлебных крошек
     */
    public function products_need(array $path = [], bool $in_root): array {

        $db = $this->db->table('products');
        $db->where('products.active', 1);
        $db->where('products.id>', 0);
        $db->where('products.price>', 0);
        $db->select('products.*');
        $db->groupBy('products.id');
        $db->orderBy('products.priority', "DESC");
        $db->orderBy('products.name', "ASC");
        $products = $db->get()->getResultArray();

        $result = [];
        foreach ($products as $product) {
            if ($product['id'] <= 0) {
                continue;
            }

            //получить id родительских категорий
            $parents_product = $this->parents_product($product['id']);
            $no_need = FALSE;

            if ($in_root) { //если смотрим в корневой
                foreach ($path as $id_category_path) {
                    if (!in_array($id_category_path, $parents_product)) {
                        $no_need = TRUE;
                    }
                }
            } else {

                if (!$this->is_parent_product($product['id'], end($path))) {
                   continue; 
                }
                // foreach ($parents_product as $id_category) {
                //     if (!in_array($id_category, $path)) {
                //         $no_need = TRUE;
                //     }
                // }
            }

            if ($no_need) {
                continue;
            }
            $result[]=$product['id'];
        }

        return $result;
    }

    /*
    Эта категория является родительской
     */
    public function is_parent_product(int $id_product, int $id_category) {
        return $this->db->table('products_category_link')
        ->where('id_product', $id_product)
        ->where('id_category', $id_category)
        ->countAllResults() > 0;
    }

    /*
    Получить id родительских категорий продукта
     */
    public function parents_product(int $id_product): array{
        $products_category_link = $this->db->table('products_category_link')->groupBy('id_category')->where('id_product', $id_product)->select('id_category')->get()->getResultArray();
        $ids = [];
        foreach ($products_category_link as $item) {
            $ids[]=$item['id_category'];
        }
        return $ids;
    }

    /*
    Получит хлебные крошки
     */
    public function path(int $chat_id, $is_reverse = FALSE): array{
        $products_category_path = $this->db->table('products_category_path')->groupBy('id_category')->where('chat_id', $chat_id)->select('id_category')->orderBy('id', 'ASC')->get()->getResultArray();
        $ids = [];
        foreach ($products_category_path as $item) {
            $ids[]=$item['id_category'];
        }
        if ($is_reverse) {
            krsort($ids);
        }
        return $ids;
    }

    /*
    Очистить хлебные крошки
     */
    public function clear_path(int $chat_id){
        return $this->db->table('products_category_path')->delete(['chat_id' => $chat_id]);
    }

    /*
    Движение пользователя по категориям
    Хлебные крошки
     */
    public function add_path(int $chat_id, int $id_category){
        if ($id_category <= 0) {
            return FALSE;
        }
        return $this->db->table('products_category_path')->insert(['chat_id' => $chat_id, 'id_category' => $id_category]);
    }

    /*
    Список категорий с постраничным перелистованием
     */
    public function categoryes_offset(int $id_parent, int $offset = 0, $is_count_all = FALSE){
        $db = $this->db->table('products_category');
        $db->where('products_category_parent.id_parent', $id_parent);
        $db->orderBy('products_category.priority', "DESC");
        $db->orderBy('products_category.name', "ASC");
        $db->join('products_category_parent', 'products_category.id = products_category_parent.id_category');
        $db->groupBy('products_category.id');
        $db->select('products_category.*');
        
        if ($is_count_all) {
            return count($db->get()->getResultArray());
        }
        return $db->get($this->limit_menu, $offset)->getResultArray();
    }

    /*
    Выдаем ссылки для входа через минуту после создания
     */
    public function generated(){
        $channels_tasks = $this->db->table('channels_tasks')->limit(10)->get()->getResultArray();
        foreach ($channels_tasks as $task) {
            $this->TelegramModel->generated($task['id']);
            $this->db->table('channels_tasks')->delete(['id' => $task['id']]);
        }
        return TRUE;
    }

    /*
    Добавить
     */
    public function add(array $data){

        $data = $this->to_json($data);

        if (isset($data['price'])) {
            $data['price'] = floatval(str_ireplace(",", ".", $data['price']));
        }

        if (!isset($data['created'])) {
            $data['created'] = date("Y-m-d H:i:s");
        }

        $data['name_'] = json_decode($data['name']);

        $categoryes = [];
        if (isset($data['categoryes'])) {
            $categoryes = $data['categoryes'];
            unset($data['categoryes']);
        }

        if ($this->db->table('products')->insert($data)) {
            $id = $this->db->insertID();

            foreach ($categoryes as $id_category) {
                $data = [];
                $data['id_product'] = $id;
                $data['id_category'] = $id_category;
                $this->db->table('products_category_link')->insert($data);
            }

            return $id;
        }

        return FALSE;
    }

    /*
    Обновить все старые ссылки всех продуктов
    Запускаем по крону каждую минуту
     */
    public function update_links() {
        if ($this->time_invitelink <= 0) {
            return FALSE; //если отключено автоматическое обновление ссылок
        }

        $db = $this->db->table('channels');
        //берем только каналы ссылки на которые были обновлены старше $this->time_invitelink минут
        $db->where('channels.updated<=', date("Y-m-d H:i:s", time() - 60 * $this->time_invitelink));
        $db->select('channels.*');
        $db->join('channels_products', 'channels.channel_id = channels_products.channel_id');
        $db->limit(10);
        $items = $db->get();
        $return = [];
        foreach ($items->getResultArray() as $channel) {

            if (!$url = $this->TelegramModel->exportChatInviteLink($channel['channel_id'])) {
                continue; //если не удалось получить ссылку
            }

            if (!is_string($url) AND isset($url->description)) {
                continue;
            }

            if ($this->db->table('channels')->where('id', $channel['id'])->update(['updated' => date("Y-m-d H:i:s"), 'url' => $url])) {
                $channel['url'] = $url;
                $return[]=$channel;
            }
        }

        return $return;
    }

    /*
    Обновить ссылки у каналов в продукте
    Без учета того когда были ссылки сделаны.
    При получении ссылок у пользователя используется
     */
    public function update_urls(int $id_product): array {
        $channels_ = $this->channels($id_product);
        $return = [];
        foreach ($channels_ as $channel) {

            //если время жизни ссылки еще не закончилось
            if (!empty($channel['url']) AND $channel['updated'] >= date("Y-m-d H:i:s", time() - 60 * $this->time_invitelink)) {
                $url = $channel['url']; //берем старую ссылку
            } else { //если время жизни ссылки закончилось

                //генерим новую ссылку
                if (!$url = $this->TelegramModel->exportChatInviteLink($channel['channel_id'])) {
                    continue; //если не удалось создать пригласительную ссылку
                }
            }

            if (empty($url)) {
                continue;
            }
            
            //сохраняем новую дату жизни ссылки
            if ($this->db->table('channels')->where('id', $channel['id'])->update(['updated' => date("Y-m-d H:i:s"), 'url' => $url])) {
                $channel['url'] = $url;
                $return[]=$channel;
            }
        }

        return $return;
    }

    /*
    Получить список каналов добавленных в бот
     */
    public function channels($id_product = FALSE) {
        $db = $this->db->table('channels');

        if ($id_product !== FALSE) {
            $db->select('channels.*');
            $db->where('channels_products.id_product', $id_product);
            $db->join('channels_products', 'channels.channel_id = channels_products.channel_id');
            $db->groupBy('channels.channel_id');
        }

        $db->where('channels.active', 1);
        $items = $db->get();
        $return = [];
        foreach ($items->getResultArray() as $item) {
            $item['title'] = json_decode($item['title']);
            $return[]=$item;
        }
        return $return;
    }


    /*
     *  Получить все родительские категори этой категории
     */

    public function tree() {
        $category = $this->db->table('products_category')->orderBy('priority', 'desc')->orderBy('name', 'asc')->get()->getResultArray();
        $res = [];
        foreach ($category as $item) {
            $res[] = $this->from_json($item);
        }
        $category = $res;

        $cats = [];
        foreach ($category as $cat) {
            $cats_ID[$cat['id']][] = $cat;
            
            // $cat['parents'] = $this->parents($cat['id']);

            $id_parent =  $this->parent($cat['id']);
            $cats[$id_parent][$cat['id']] = $cat;
        }
        return $this->build_tree($cats, 0);
    }

    /*
     * Построить дерево категорий
     */

    public function build_tree($cats, $parent_id, $only_parent = FALSE) {
        if (is_array($cats) and isset($cats[$parent_id])) {
            $tree = '<ul>';
            if (!$only_parent) {
                foreach ($cats[$parent_id] as $cat) {
                    $tree .= '<li><a href="'.base_url('category/edit/'.$cat['id']).'">' . $cat['name'] . '</a> (ID ' . $cat['id'].")";
                    $count_in_cat = $this->count_in_cat($cat['id']);
                    if ($count_in_cat > 0) {
                        $tree .= " [товаров - " . $count_in_cat . " шт.]";
                    }
                    // $tree .= $this->build_tree($cats, $cat['id']);
                    $tree .= '</li>';

                    //добавляем дочерние
                    $tree.= '<ul>';
                    $childs = $this->childs($cat['id']);
                    foreach ($childs as $item) {
                        $tree .= '<li><a href="'.base_url('category/edit/'.$item['id']).'">' . $item['name'] . '</a> (ID' . $item['id'].")";
                        $count_in_cat = $this->count_in_cat($item['id']);
                        if ($count_in_cat > 0) {
                            $tree .= " [товаров - " . $count_in_cat . " шт.]";
                        }
                        $tree .= $this->build_tree($cats, $item['id']);
                        $tree .= '</li>';
                    }
                    $tree .= '</ul>';

                }
            } else if (is_numeric($only_parent)) {
                $cat = $cats[$parent_id][$only_parent];
                $tree .= '<li><a href="'.base_url('category/edit/'.$cat['id']).'">' . $cat['name'] . '</a> (ID' . $cat['id'].")";
                $tree .= $this->build_tree($cats, $cat['id']);
                $tree .= '</li>';
            }
            $tree .= '</ul>';
        } else {
            return NULL;
        }
        return $tree;
    }

    /*
     * Получить родительские
     * @docs https://phpdes.com/php/postroenie-dereva-kategorijj-na-php-rekursiya/
     */

    public function find_parent($tmp, $cur_id) {
        if ($tmp[$cur_id][0]['id_parent'] != 0) {
            return $this->find_parent($tmp, $tmp[$cur_id][0]['id_parent']);
        }
        return (int) $tmp[$cur_id][0]['id'];
    }

    /*
    Посчитать количество товаров в категории
     */
    public function count_in_cat(int $id_category) {
        $return = $this->db->table('products_category_link')
        ->where('id_category', $id_category)
        ->groupBy('id_product')
        ->get()->getResultArray();
        return count($return);
    }

    /*
    Добавить категорию
     */
    public function add_category(array $data){

        $categoryes = [];
        if (isset($data['id_parents'])) {
            $categoryes = $data['id_parents'];
            unset($data['id_parents']);
        }

        $data = $this->to_json($data);
        $data['name_'] = json_decode($data['name']);

        if ($this->db->table('products_category')->insert($data)) {
            $id = $this->db->insertID();

            if (count($categoryes) <= 0) {
                $data_insert = [];
                $data_insert['id_category'] = $id;
                $data_insert['id_parent'] = 0;
                $this->db->table('products_category_parent')->insert($data_insert);
            } else {
                foreach ($categoryes as $id_parent) {
                    $data = [];
                    $data['id_category'] = $id;
                    $data['id_parent'] = $id_parent;
                    $this->db->table('products_category_parent')->insert($data);
                }
            }

            return $id;
        }

        return FALSE;
    }

    /*
    Сохранить данные настройки
     */
    public function set_category(array $data): bool{

        $this->db()->table('products_category_parent')->delete(['id_category' => $data['id']]);
        if (isset($data['id_parents'])) {
            $categoryes = $data['id_parents'];
            unset($data['id_parents']);
            foreach ($categoryes as $id_parent) {
                $data_insert = [];
                $data_insert['id_category'] = $data['id'];
                $data_insert['id_parent'] = $id_parent;
                $this->db->table('products_category_parent')->insert($data_insert);
            }
        } else {
            $data_update = [];
            $data_update['id_category'] = $data['id'];
            $data_update['id_parent'] = 0;
            $this->db->table('products_category_parent')->insert($data_update);
        }

        $data = $this->to_json($data);
        $data['name_'] = json_decode($data['name']);

        if (!$this->db->table('products_category')->update($data, ['id' => $data['id']])) {
            return FALSE;
        }

        return $data['id'];
    }

    /*
    Удалить категорию
     */
    public function delete_category(int $id) {
        $this->db()->table('products_category_link')->delete(['id_category' => $id]);
        $this->db()->table('products_category_parent')->delete(['id_category' => $id]);
        return $this->db()->table('products_category')->delete(['id' => $id]);
    }

    /*
    Получить категорию по названию
     */
    public function category_name(string $name) {
        $name = json_encode($name);

        $db = $this->db->table('products_category');
        $db->where('name', $name);
        $data = $db->get(1)->getRowArray();
        if (!isset($data['id'])) {
            return FALSE;
        }
        $data = $this->from_json($data);
        return $data;
    }

    /*
    Получить данные 
     */
    public function category($id) {
        $db = $this->db->table('products_category');
        $db->where('id', $id);
        $data = $db->get(1)->getRowArray();
        $data = $this->from_json($data);
        return $data;
    }

    /*
    Получить свободный товар из категории
     */
    public function category_product(int $id_category, int $id_mod = 0, int $id_mod2 = 0) {
        $products_in_cat = $this->products_in_cat($id_category, FALSE);
        if (count($products_in_cat) <= 0) {
            return FALSE;
        }

        //если выбраны модификаторы то надо взять те продукты которые с этим модификатором
        if ($id_mod > 0 OR $id_mod2 > 0) {
            $this->ModModel = new \Mods\Models\ModModel();
            foreach ($products_in_cat as $product) {
                $product_items = $this->product_items($product['id'], TRUE);
                foreach ($product_items as $product_item) {
                    if ($id_mod > 0) {
                        $have_mod = $this->ModModel->have_mod($product_item['id'], $id_mod);
                    }
                    if ($id_mod2 > 0) {
                        $have_mod2 = $this->ModModel->have_mod($product_item['id'], $id_mod2);
                    }

                    if ($id_mod > 0 AND $id_mod2 > 0) {
                        if ($have_mod AND $have_mod2) {
                            $product = $this->get($product['id']);
                            return [
                                'product' => $product,
                                'product_item' => $product_item
                            ];
                        }
                    } else { //если не оба сразу выбраны
                        if ($id_mod > 0 AND $have_mod) {
                            $product = $this->get($product['id']);
                            return [
                                'product' => $product,
                                'product_item' => $product_item
                            ];
                        } else if ($id_mod2 > 0 AND $have_mod2) {
                            $product = $this->get($product['id']);
                            return [
                                'product' => $product,
                                'product_item' => $product_item
                            ];
                        }
                    } 
                }
            }
            return FALSE;
        }

        $product = $this->get($products_in_cat[0]['id']);
        return [
            'product' => $product,
            'product_item' => FALSE
        ];
    }

    /*
    Получить список
     */
    public function categoryes($id_product = FALSE, $id_exclude = FALSE): array {
        $db = $this->db->table('products_category');

        if ($id_product !== FALSE) {
            $db->where('products_category_link.id_product', $id_product);
            $db->join('products_category_link', 'products_category.id = products_category_link.id_category');
            $db->groupBy('products_category.id');
            $db->select('products_category.*');
        }
        if ($id_exclude !== FALSE) {
            $db->where('products_category.id<>', $id_exclude);
        }

        $db->orderBy('products_category.priority', 'DESC');
        $items = $db->get();
        $return = [];
        foreach ($items->getResultArray() as $item) {
            $item['name'] = json_decode($item['name']);
            $item['parents'] = $this->parents($item['id']);
            $return[]=$item;
        }
        return $return;
    }

    /*
    Дочерние категории этой категории
     */
    public function childs(int $id_category): array {
        $db = $this->db->table('products_category');
        $db->where('products_category_parent.id_parent', $id_category);
        $db->where('products_category_parent.id_category<>', $id_category);
        $db->join('products_category_parent', 'products_category.id = products_category_parent.id_category');
        $db->groupBy('products_category.id');
        $db->select('products_category.*');
        $db->orderBy('products_category.priority', 'DESC');
        $items = $db->get();
        $return = [];
        foreach ($items->getResultArray() as $item) {
            $item['name'] = json_decode($item['name']);
            $return[]=$item;
        }
        return $return;
    }

    /*
    Родительские категории этой категории
     */
    public function parents(int $id_category): array {
        $db = $this->db->table('products_category');
        $db->where('products_category_parent.id_category', $id_category);
        $db->join('products_category_parent', 'products_category.id = products_category_parent.id_parent');
        $db->groupBy('products_category.id');
        $db->select('products_category.*');
        $db->orderBy('products_category.priority', 'DESC');
        $items = $db->get();
        $return = [];
        foreach ($items->getResultArray() as $item) {
            $item['name'] = json_decode($item['name']);
            $return[]=$item;
        }
        return $return;
    }

    /*
    Удалить запись настройки уровня комиссионных
     */
    public function delete_item(int $id) {
        $this->ModModel = new \Mods\Models\ModModel();
        $this->ModModel->delete_mod_items($id);
        return $this->db()->table('products_items')->delete(['id' => $id]);
    }

    /*
    Поиск по артикулу
     */
    public function get_item_articul(string $articul): array {
        return $this->db->table('products_items')->like('articul', $articul)->get(1)->getRowArray();
    }

    /*
    Получить данные 
     */
    public function get_item(int $id) {
        return $this->db->table('products_items')->where('id', $id)->get(1)->getRowArray();
    }

    /*
    Добавляем из файла единицы товара
     */
    public function add_item(int $id_product, $post): array {
        $count = isset($post['count']) ? (int) $post['count'] : 1;
        $res = [];
        while ($count > 0) {
            $data_item = [];
            $data_item['id_product'] = $id_product;
            $data_item['created'] = date("Y-m-d H:i:s");
            $data_item['price'] = $post['price'];
            $data_item['articul'] = (!empty($post['articul']) AND 1 == $count) ? $post['articul'] : rand();
            if (isset($post['file_id'])) {
                $data_item['file_id'] = $post['file_id'];
            }
            if (!$this->db->table('products_items')->insert($data_item)) {
                continue;
            }
            $id_item = $this->db->insertID();

            if (isset($post['id_mods'])) {
                $this->ModModel = new \Mods\Models\ModModel();
                if (!$this->ModModel->set_item_mods($id_item, $post['id_mods'])) {
                    continue;
                }
            }
            $res[]=$id_item;
            $count--;
        }

        return $res;
    }

    /*
    Получить текстовый файл с данными
    @return string - путь к файлу
     */
    public function assets_items_file(int $id_order) {
        $assets_items = $this->assets_items($id_order);
        if (count($assets_items) <= 0) {
            log_message('error','нет файлов назначенных по заказу '.$id_order);
            return FALSE;
        }
        $text = "";
        $i = 0;
        foreach ($assets_items as $item) {
            if (empty(trim($item['text']))) {
                continue;
            }
            if ($i > 0) {
                $text.="\n";
            }
            $text.=$item['text'];
            $i++;
        }

        //генерим текстовый файл
        $path = realpath(APPPATH."/../writable/cache").'/'.$id_order.'.txt';
        helper('filesystem');
        if (!write_file($path, $text)) {
            return FALSE;
        }

        return $path;
    }

    /*
     Получить купленные единицы товара по номеру заказа
     */
     public function assets_items(int $id_order):array {
        return $this->db->table('products_items')->where('id_order', $id_order)->get()->getResultArray();
    }

    /*
    Прилинковать единицу товара к заказу
     */
    public function asset_item(int $id_order, int $chat_id, int $id_product, int $count): bool {
        $i = 1;
        while ($i <= $count) {
            //получаем свободную единицу товара
            $product_items = $this->product_items($id_product, TRUE);
            if (count($product_items) <= 0) {
                return FALSE;
            }

            $id = $product_items[0]['id'];

            $this->set_item(['id' => $id, 'id_order' => $id_order, 'chat_id' => $chat_id]);

            $i++;
        }

        return TRUE;
    }

    /*
    Количество единиц товара в наличии в определенной категории
     */
    public function cat_items_count(int $id_category): int {
        $count = $this->product_items_count_in_cat($id_category);

        //смотрим количество товаров в дочерних категориях
        $products_category = $this->db->table('products_category')->where('id_parent', $id_category)->get()->getResultArray();
        foreach ($products_category as $cat) {
            $count+=$this->product_items_count_in_cat($cat['id']);
        }
        
        return $count;
    }

    /*
    Количество единиц товара в категории
    без учета подкатегорий
     */
    public function product_items_count_in_cat(int $id_category): int {
        $products = $this->products_in_cat($id_category);
        $count = 0;
        foreach ($products as $product) {
            $count+= $this->product_items_count($product['id'], TRUE);
        }
        return $count;
    }

    /*
    Количество единиц товара
     */
    public function product_items_count(int $id_product, $only_free = FALSE):int {
        $db = $this->db->table('products_items');
        $db->where('id_product', $id_product);
        if ($only_free) {
            $db->where('id_order', 0);
        }
        return $db->countAllResults();
    }

    /*
    Получить свободную единицу товара которую можно добавить к заказу
     */
    public function get_free_item(int $id_product) {
        //если не нужно проверять наличие товара
        if ($this->need_check_empty <= 0) {
            $product = $this->get($id_product);

            //генерим единицы товара автоматически 
            $this->db->table('products_items')->insert(['created' => date("Y-m-d H:i:s"), 'articul' => rand(), 'id_product' => $id_product, 'price' =>  $product['price']]);
        }

        $db = $this->db->table('products_items');
        $db->where('id_product', $id_product);
        $db->where('id_order', 0);
        $db->orderBy('priority', "DESC");
        $product_items = $db->get()->getResultArray();

        foreach ($product_items as $product_item) {
            
            if (
                $this->db->table('orders_items') 
                ->where('id_product', $id_product)
                ->where('id_item', $product_item['id'])
                ->countAllResults() <= 0
            ) {
                return $product_item['id'];
            }
            
        }
        return FALSE;
    }

    /*
    Отобразить список единиц товара
     */
    public function product_items(int $id_product, $only_free = FALSE, int $chat_id = 0, $id_mod_color = FALSE):array {
        $this->ModModel = new \Mods\Models\ModModel();

        $id_mod = FALSE;
        if ($chat_id > 0) {
            $id_mod = $this->ModModel->size($chat_id);
        }

        $db = $this->db->table('products_items');
        $db->where('id_product', $id_product);
        if ($only_free) {
            $db->where('id_order', 0);
        }
        $db->orderBy('priority', "DESC");
        $items = $db->get();

        $return = [];
        foreach ($items->getResultArray() as $item) {

            if ($id_mod !== FALSE AND !$this->ModModel->have_mod($item['id'], $id_mod)) {
                //нет такого модификатора у единицы товара
                continue;
            }

            if ($id_mod_color !== FALSE AND !$this->ModModel->have_mod($item['id'], $id_mod_color)) {
                continue; //нет такого цвета
            }

            $item['mods'] = '';
            $mods = $this->ModModel->mods_item($item['id']);
            $i = 0;
            foreach ($mods as $mod) {
                if ($i > 0) {
                    $item['mods'].=', ';
                }
                $item['mods'].=$mod['name'];
                $i++;
            }
            $return[]=$item;
        }
        return $return;

    }

    /*
    id категории продукта в которой находится продукт
     */
    public function id_category_product(int $id_product) {
        $categoryes = $this->categoryes($id_product);
        return count($categoryes) <= 0 ? FALSE : $categoryes[0]['id'];
    }

    /*
    Получить родителя продукта
     */
    public function parent_product(int $id_product) {
        $categoryes = $this->categoryes($id_product);
        return count($categoryes) <= 0 ? FALSE : $categoryes[0]['id_parent'];
    }


    /*
    Получить родителя категории
     */
    public function parent(int $id_category) {
        if ($id_category <= 0) {
            return FALSE;
        }

        $parent = $this->db->table('products_category_parent')
        ->where('id_category', $id_category)
        ->groupBy('id_parent')
        ->orderBy('id', 'ASC')
        ->get(1)
        ->getRowArray();
        if (!isset($parent['id_parent'])) {
            return FALSE;
        }
        return $parent['id_parent'] <= 0 ? FALSE : $parent['id_parent'];
    }

    /*
    Получить продукты которые в корневой категории
     */
    public function products_in_cat(int $id_category, $priority = TRUE): array {
        $db = $this->db->table('products');
        $db->where('products_category_link.id_category', $id_category);
        $db->where('products.active', 1);
        $db->join('products_category_link', 'products.id = products_category_link.id_product');
        $db->select('products.*');
        $db->groupBy('products.id');
        if ($priority) {
            $db->orderBy('products.priority', "DESC");
        } else {
            $db->orderBy('products.file_id', "DESC");
        }

        return $db->get()->getResultArray();
    }

    /*
    Получить продукты которые в корневой категории
     */
    public function products_in_root(): array {
        //получить id продуктов без указания категорий
      $array_id = $this->id_products_in_cats();
      if (count($array_id) <= 0) {
        return [];
    } 

    $db = $this->db->table('products');
    $db->whereNotIn('products.id', $array_id);
    $db->select('products.*');
    $db->groupBy('products.id');
    $db->orderBy('products.priority', "DESC");
    $db->where('products.active', 1);
    return  $db->get()->getResultArray();
}

    /*
     Получить id продуктов которые есть хотя бы в одной категории
     */
     public function id_products_in_cats(): array {
        $array_id = [];

        $ids = $this->db->table('products_category_link')
        ->groupBy('id_product')
        ->select('id_product')
        ->get()
        ->getResultArray();
        foreach ($ids as $item) {
            $array_id[]=$item['id_product'];
        }

        return $array_id;
    }

     /*
     * Получить список с помощью ajax
     *   
     * @docs https://datatables.net/manual/server-side#Sent-parameters
     * @docs https://datatables.net/examples/server_side/simple.html
     * @docs https://datatables.net/examples/data_sources/server_side.html
     */

     public function products_($params, $filter = TRUE) {
        // $limit = 10;
        
        $db = $this->db->table('products');

        //поисковой фильтр
        if (!empty($params['search']['value'])) {
            $db->groupStart();
            $id = (int) trim($params['search']['value']);
            $time = human_to_unix(trim($params['search']['value']));
            if ($id > 0) {//если это число
                $db->where('products.id', $id);
                $db->orWhere('products.price', $id);
            } else if ($time) {//если это дата                   
                $db->where('products.created', trim($params['search']['value']));
                $db->orWhere('products.updated', trim($params['search']['value']));
            } else {//если это текст        
                $db->orWhere('products.name', json_encode(trim($params['search']['value'])));
                $db->orLike('products.name_', trim($params['search']['value']));
                //ищем по категории
                $db->orWhere('products_category.name', json_encode(trim($params['search']['value'])));
                $db->orLike('products_category.name_', trim($params['search']['value']));
            }
            $db->groupEnd();
        }

        //список полей которые будут в таблице                
        $need_fields = array(
            'priority',
            'id',
            'name',
            'price',
            'id_category',
            'count',
            'id'
        );

        $db->select('products.*');

        //сортировка 
        $order_column = (int) $params['order'][0]['column'];
        $order_direction = $params['order'][0]['dir'];
        $dir = empty($order_direction) ? "desc" : $order_direction;
        $db->orderBy($need_fields[$order_column], $dir);

        $db->join('products_category_link', 'products.id = products_category_link.id_product', 'left');
        $db->join('products_category', 'products_category_link.id_category = products_category.id', 'left');

        $db->groupBy('products.id');

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

            $data_item = "<a href='".base_url("products/edit/" . $item['id'])."'>". $item['priority']."</a>";
            $data[] = $data_item;
            
            $data_item = "<a href='".base_url("products/edit/" . $item['id'])."'>". $item['id']."</a>";
            $data[] = $data_item;

            $data[] = "<a href='".base_url("products/edit/" . $item['id'])."'>". json_decode($item['name'])."</a>";
            
            $data_item = "<a href='".base_url("products/edit/" . $item['id'])."'>".number_format($item['price'], $this->decimals, ',', ' ')."</a>";
            $data[] = $data_item;
            
            $data_item = "";
            $i = 0;
            $item['categoryes'] = $this->categoryes($item['id']);
            foreach ($item['categoryes'] as $category) {
                if ($i > 0) {
                    $data_item.= ", ";
                }
                $data_item.= "<a href='".base_url("category/edit/" . $category['id'])."'>". $category['name']."</a>";
                $i++;
            }
            $data[] = $data_item;
            
            $product_items = $this->product_items($item['id'], TRUE);
            $count = count($product_items);
            // $count =$item['count'];
            $data[] = $count;

            $product_items_kg = $this->product_items_kg($item['id']);

            $data_item = "";
            // $data_item .= "<a title='Скидки за вес' class='btn btn-secondary btn-flat' href='" . base_url("products/items_kg/" . $item['id']) . "'><i class='fas fa-carrot'></i> ".count($product_items_kg)."</a>"; 
            $data_item .= "<a title='Единицы товара' class='btn btn-primary btn-flat' href='" . base_url("products/items/" . $item['id']) . "'><i class='fas fa-file-alt'></i></a>";
            $data_item .= "<a title='Спасибо за покупку' class='btn btn-default btn-flat' href='" . base_url("products/thankyou/" . $item['id']) . "'><i class='fas fa-comment-dollar'></i></a>";
            $data_item .= "<a title='Изменить' class='btn btn-success btn-flat' href='" . base_url("products/edit/" . $item['id']) . "'><i class='fa fa-pencil'></i></a>";
            $data_item .= "<a title='Удалить' class='btn btn-danger btn-flat' href='" . base_url("products/delete/" . $item['id']) . "'><i class='fa fa-trash'></i></a>";
            $data[] = $data_item;

            $return[] = $data;
        }

        return $return;
    }

    /*
    Получить список
     */
    public function items($all = TRUE) {
        $this->SubscribeModel = new \Orders\Models\SubscribeModel();

        $db = $this->db->table('products');
        if (!$all) {
            $db->where('products.active', 1);
        }
        $db->where('products.id>', 0);
        $db->select('products.*');
        $db->orderBy('priority', 'DESC');
        $items = $db->get();
        $return = [];
        foreach ($items->getResultArray() as $item) {
            $item['name'] = json_decode($item['name']);
            if ($item['end_month'] > 0) {
                $item['days_end_month'] = $this->SubscribeModel->days_end_month();
            }
            $item['categoryes'] = $this->categoryes($item['id']);
            $product_items = $this->product_items($item['id'], TRUE);
            $item['count'] = count($product_items);
            $return[]=$item;
        }
        return $return;
    }

    /*
    Получить данные 
     */
    public function get(int $id) {
        $db = $this->db->table('products');
        $db->where('id', $id);
        $data = $db->get()->getRowArray();
        $data = $this->from_json($data);

        $data['description'] = empty($data['description']) ? json_encode([]) : $data['description'];
        $data['thankyou'] = empty($data['thankyou']) ? json_encode([]) : $data['thankyou'];
        $data['sku'] = $this->sku($id);
        
        return $data;
    }

    /*
    Получить текст описания или спасибо за покупку
     продукта для отправки в телеграм
     */
     public function text($id = FALSE, $field = "description", array $fill = []): string {

        //получаем текст сообщения с учетом языка
        if (!$data_product = $this->get($id)) {
            return '';
        }
        $text = $data_product[$field];

        //декодируем в обычную строку
        $this->PagesModel = new \Admin\Models\PagesModel();
        $text = $this->PagesModel->json_to_txt($text);
        $text = $this->PagesModel->fill($text, $fill);

        return $text;
    }

    /*
    Сконвертировать из JSON названия
     */
    public function from_json($data = [], $field_ = "name"):array {
        if (empty($data) OR !is_array($data)) {
            return [];
        }
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

    public function set_item(array $data, $change_mods = TRUE): bool{

        if ($change_mods) {
            $this->ModModel = new \Mods\Models\ModModel();
            $this->ModModel->delete_mod_items($data['id']);
            if (isset($data['id_mods'])) {
                $id_mods = $data['id_mods'];
                unset($data['id_mods']);
                $this->ModModel->set_item_mods($data['id'], $id_mods);
            }
        }

        return $this->db->table('products_items')->where('id', $data['id'])->update($data);
    }

    /*
    Сохранить данные настройки
     */
    public function set(array $data, $no_link_cat = FALSE): bool{
        $this->db->transBegin();

        if (!$no_link_cat) {
            $this->ModModel = new \Mods\Models\ModModel();
            $this->ModModel->delete_group_product($data['id']);
            if (isset($data['product_groups'])) {
                $this->ModModel->set_group_product($data['id'], $data['product_groups']);
                unset($data['product_groups']);
            }
            
            $this->db()->table('products_category_link')->delete(['id_product' => $data['id']]);

            if (isset($data['categoryes'])) {
                $categoryes = $data['categoryes'];
                unset($data['categoryes']);

                foreach ($categoryes as $id_category) {
                    $data_insert = [];
                    $data_insert['id_product'] = $data['id'];
                    $data_insert['id_category'] = $id_category;
                    $this->db->table('products_category_link')->insert($data_insert);
                }
            }
        }

        if (isset($data['end_month'])) {
            $data['end_month'] = $data['end_month'] == "on";
        }
        $data = $this->to_json($data);
        if (isset($data['name'])) {
            $data['name_'] = json_decode($data['name']);
        }

        if (isset($data['price'])) {
            $data['price'] = floatval(str_ireplace(",", ".", $data['price']));
        }

        $db = $this->db->table('products');
        $db->where('id', $data['id']);
        $db->update($data);


        $this->db->transComplete();
        if ($this->db->transStatus() === FALSE) {
            $this->db->transRollback(); //откатить изменения
            return FALSE;
        }

        $this->db->transCommit(); //зафиксировать изменения в БД

        if (isset($data['price']) AND $data['price'] > 0) {
            $product_items = $this->product_items($data['id'], TRUE);
            foreach ($product_items as $item) {
                $this->set_item(['id' => $item['id'], 'price' => $data['price']], FALSE);
            }
        }

        return $data['id'];
    }

    /*
    Удалить запись настройки уровня комиссионных
     */
    public function delete(int $id) {
        $this->db->transBegin();
        
        $this->db()->table('products_items')->delete(['id_product' => $id]);
        $this->db()->table('products_category_link')->delete(['id_product' => $id]);
        $this->db()->table('products')->delete(['id' => $id]);

        $this->ModModel = new \Mods\Models\ModModel();
        $this->ModModel->delete_group_product($id);

        $this->db->transComplete();
        if ($this->db->transStatus() === FALSE) {
            $this->db->transRollback(); //откатить изменения
            return FALSE;
        }
        return $this->db->transCommit(); //зафиксировать изменения в БД
    }

    /*
    Сохраняем данные с редактора сообщений

    @docs https://editorjs.io/base-concepts
    @src https://github.com/editor-js/editorjs-php
     */
    public function save_thankyou($data = NULL, $id, $short = "ru") {
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

            //если сохраняем перевод
            if ($short <> "ru") {
                $this->LangModel = new \Admin\Models\LangModel();
                return $this->LangModel->trans_page_set($id, $short, $blocks);
            }

            $db = $this->db->table('products');
            $db->where('id', $id);
            return $db->update(['thankyou' => $blocks]);
        } catch (\EditorJSException $e) {
            log_message('error', print_r($e->getMessage(), TRUE));
            return FALSE;
        }
    }

    /*
    Сохраняем данные с редактора сообщений

    @docs https://editorjs.io/base-concepts
    @src https://github.com/editor-js/editorjs-php
     */
    public function save_description($data = NULL, $id, $short = "ru") {
        if (empty($data) OR !isset($data['blocks'])) {
            $db = $this->db->table('products');
            $db->where('id', $id);
            return $db->update(['description' => NULL]);
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

            //если сохраняем перевод
            if ($short <> "ru") {
                $this->LangModel = new \Admin\Models\LangModel();
                return $this->LangModel->trans_page_set($id, $short, $blocks);
            }

            $db = $this->db->table('products');
            $db->where('id', $id);
            return $db->update(['description' => $blocks]);
        } catch (\EditorJSException $e) {
            log_message('error', print_r($e->getMessage(), TRUE));
            return FALSE;
        }
    }
}
