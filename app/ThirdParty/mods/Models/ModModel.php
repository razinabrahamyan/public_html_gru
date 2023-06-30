<?php 
/**
 * Name:    Модель для работы с модификаторами
 *
 * Created:  13.10.2020
 *
 * Description:  
 *
 * Requirements: PHP 7.2 or above
 *
 * @author     Krotov Roman <tg: @KrotovRoman>
 */
namespace Mods\Models;
use CodeIgniter\Model;
use \CodeIgniter\Database\ConnectionInterface;

/**
 * Class UsersModel
 */
class ModModel
{
    /**
     * Database object
     *
     * @var \CodeIgniter\Database\BaseConnection
     */
    protected $db;
    public $id_group_color = 1;
    public $id_group_size = 2;

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
    Вывести список модификаторов которые есть в этой категории
    причем уникализаированне

    $id_mod > 0 - тогда выбираем только те модификаторы которые есть совместно с этим в единицах товара
     */
    public function mods_category(int $id_category, $id_mod = 0) {
        $this->ProductModel = new \Products\Models\ProductModel();
        $products_in_cat = $this->ProductModel->products_in_cat($id_category);
        $return = [];
        foreach ($products_in_cat as $product) {

            //получаем все свободные единицы товаров
            $product_items = $this->ProductModel->product_items($product['id'], TRUE);
            foreach ($product_items as $product_item) {
                $mods = $this->mods_item($product_item['id']);
                $return = array_merge($return, $mods);
            }
        }

        $res = [];
        foreach ($return as $mod_item) {
            $res[]=$mod_item['id_mod'];
        }
        $res = array_unique($res);
        $return = [];
        foreach ($res as $id_mod) {
            $return[]=$this->mod($id_mod);
        }

        //по убыванию
        usort($return, function($a, $b){
            return ($b['id_group'] - $a['id_group']);
        });

        return $return;
    }

    /*
    Обновляем свойство у товара
     */
    public function update_product_mode(string $name_mode, int $id_mod_group) {
        $mod = $this->db->table('mod_items')
        ->where('name', $name_mode)
        ->get(1)
        ->getRowArray();

        if (!isset($mod['id'])) {
            $id_mod = $this->add_mod(['name' => $name_mode, 'id_group' => $id_mod_group]);
        } else {
            $this->set_mod(['id' => $mod['id'], 'id_group' => $id_mod_group]);
            $id_mod = $mod['id'];
        }

        return $id_mod;
    }

    /*
    Модификаторы размеров основных
     */
    public function colors(): array {
        return $this->mods($this->id_group_color);
    }

    /*
    Модификаторы размеров основных
     */
    public function sizes(int $id_group = 2): array {
        return $this->mods($id_group);
    }

    /*
    Группы модификаторов
     */
    public function groups() {
        return $this->db->table('mod_groups')->get()->getResultArray();
    }

    /*
    Добавить группу модификаторов
     */
    public function add_group(array $data) {
        return $this->db->table('mod_groups')->insert($data);
    }

    /*
    Удалить группу модификаторов
     */
    public function delete_group(int $id) {
        return $this->db->table('mod_groups')->delete(['id' => $id]);
    }

    /*
    Получить данные группы модификаторов
     */
    public function group(int $id) {
        return $this->db->table('mod_groups')->getWhere(['id' => $id], 1)->getRowArray();
    }

    /*
    Редактирование группы модификаторов
     */
    public function set_group(array $data) {
        return $this->db->table('mod_groups')->update($data, ['id' => $data['id']]);
    }

    /*
    Есть ли у такой единицы товара этот модификатор
     */
    public function have_mod($id_product_item, $id_mod): bool {
        if (!$id_mod) {
            return FALSE;
        }
        $mods_item = $this->mods_item($id_product_item);
        $have = FALSE;
        foreach ($mods_item as $item) {
            if ($item['id_mod'] == $id_mod) {
                return TRUE;
            }
        }
        return $have;
    }

    /*
    Получить id модификатора размера пользователя
     */
    public function size(int $chat_id, $id_group = 2) {
        $this->ionAuth = new \IonAuth\Libraries\IonAuth();
        $user = $this->ionAuth->user($chat_id)->getRowArray();

        if (empty($user['size']) OR $user['size'] <= 0) {
            return FALSE;
        }

        $mod_items = $this->db->table('mod_items')
        ->where('name', $user['size'])
        ->where('id_group', $id_group);

        if ($mod_items->countAllResults(FALSE) <= 0) {
            return FALSE;
        }
        return $mod_items->get(1)->getRow()->id;
    }

    /*
    Модификаторы в группе
     */
    public function mods(int $id_group): array {
        return $this->db->table('mod_items')
        ->join('mod_groups', 'mod_items.id_group = mod_groups.id')
        ->select('mod_items.*')
        ->select('mod_groups.name as name_group')
        ->groupBy('mod_items.id')
        ->orderBy('mod_groups.priority, mod_items.priority', 'DESC')
        ->getWhere(['mod_items.id_group' => $id_group])
        ->getResultArray();
    }

    /*
    Добавить модификатор в группу
     */
    public function add_mod(array $data) {
        if (!$this->db->table('mod_items')->insert($data)) {
            return FALSE;
        }
        return $this->db->insertID();
    }

    /*
    Получить данные модификатора
     */
    public function mod(int $id) {
        return $this->db->table('mod_items')->getWhere(['id' => $id], 1)->getRowArray();
    }

    /*
    Удалить модификатор
     */
    public function delete_mod(int $id) {
        return $this->db->table('mod_items')->delete(['id' => $id]);
    }

    /*
    Изменить модификатор
     */
    public function set_mod(array $data) {
        return $this->db->table('mod_items')->update($data, ['id' => $data['id']]);
    }

    /*
    Группы модификаторов к продукту
     */
    public function product_groups(int $id_product, $only_mod_group = FALSE) {
        $db = $this->db->table('mod_group_product');
        if ($only_mod_group !== FALSE) {
            $db->where('id_group', $only_mod_group);
        }
        $db->where('id_product', $id_product);
        return $db->get()->getResultArray();
    }

    /*
    Удалить все группы из продукта
     */
    public function delete_group_product(int $id_product) {
        $this->db->table('mod_group_product')->delete(['id_product' => $id_product]);
    }

    /*
    Прилинковать группы модификаторов к продукту
     */
    public function set_group_product(int $id_product, array $product_groups = []) {
        if (count($product_groups) <= 0) {
            return TRUE;
        }

        $array = [];
        $created = date("Y-m-d H:i:s");
        foreach ($product_groups as $id_group) {
            $data = [];
            $data['created'] = $created;
            $data['id_product'] = $id_product;
            $data['id_group'] = $id_group;
            $array[] = $data;
        }

        return $this->db->table('mod_group_product')->insertBatch($array);
    }

    /*
    Получить продукты с этим модификатором
     */
    public function products_with_mod(int $id_mod, int $offset = 0, $is_count_all = FALSE){
        $db = $this->db->table('products');
        $db->where('mod_product_items.id_mod', $id_mod);
        $db->join('products_items', 'products.id = products_items.id_product');
        $db->join('mod_product_items', 'products_items.id = mod_product_items.id_item');
        $db->groupBy('products.id');
        $db->select('products.*');
        if ($is_count_all) {
            return count($db->get()->getResultArray());
        }
        return $db->get($this->limit_products, $offset)->getResultArray();
    }

    /*
    Модификаторы из групп подключенных к продукту
     */
    public function mods_product(int $id_product, $only_mod_group = FALSE): array {
        $product_groups = $this->product_groups($id_product, $only_mod_group);
        $mods = [];
        foreach ($product_groups as $item) {
            $mod_items = $this->mods($item['id_group']);
            $mods = array_merge($mods, $mod_items);
        }
        return $mods;
    }

    /*
    Удалить цену модификатора
     */
    public function delete_mod_items(int $id_item) {
        return $this->db->table('mod_product_items')->delete(['id_item' => $id_item]);
    }

    /*
     Сохраняем цены на единицы товара на модификаторы
     */
     public function set_item_mods(int $id_item, array $id_mods = []) {
        if (count($id_mods) <= 0) {
            return TRUE;
        }

        $array = [];
        $created = date("Y-m-d H:i:s");
        foreach ($id_mods as $id_mod) {
            $data = [];
            $data['created'] = $created;
            $data['id_item'] = $id_item;
            $data['id_mod'] = $id_mod;
            $array[] = $data;
        }

        return $this->db->table('mod_product_items')->insertBatch($array);
    }

    /*
    Свойства единицы товара через запятую
     */
    public function mods_item_string(int $id_item): string {
        $text = "";
        $mods_item = $this->mods_item($id_item);
        $i = 0;
        foreach ($mods_item as $item) {
            if ($i > 0) {
                $text.=", ";
            }
            $text.=$item['name'];
            $i++;
        }
        return $text;
    }

    /*
    Получить модификаторы единицы товара
     */
    public function mods_item(int $id_item): array {
        return $this->db->table('mod_product_items')
        ->join('mod_items', 'mod_product_items.id_mod = mod_items.id')
        ->groupBy('mod_product_items.id')
        ->select('mod_product_items.*')
        ->select('mod_items.name')
        ->getWhere(['mod_product_items.id_item' => $id_item])
        ->getResultArray();
    }
}
