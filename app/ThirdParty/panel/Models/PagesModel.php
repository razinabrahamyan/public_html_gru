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
use \EditorJS\EditorJS;

/**
 * Class UsersModel
 */
class PagesModel
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
	}

    /*
    Определяем язык пользователя текущий
     */
    public function lang() {
        if (empty($this->lang)) {
            $this->LangModel = new \Admin\Models\LangModel();
            $this->lang = $this->LangModel->lang($this->id);
        }

        return $this->lang;
    }

    /*
    Задаем id пользователя
     */
    private function set_id($message) {
        //определяем chat_id пользователя
        if ($message['message']['chat']['id'] > 0) { //если пишет в личку
            return $this->id($message['message']['chat']['id']);
        } else { //если в чате пишет
            $chat_id = isset($message['message']['from']['id']) ? $message['message']['from']['id'] : $message['message']['chat']['id'];
            return $this->id($chat_id);
        }
    }

    /*
    Получаем/задаем chat_id пользователя
     */
    public function id($chat_id = FALSE) {
        if ($chat_id) {
            $this->id = $chat_id;
        }
        return $this->id;
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
	Получить страницы
	 */
	public function pages() {
		$db = $this->db->table('pages');
        $db->where('pages.active', 1);
        $db->where('pages_group.active', 1);
        $db->select('pages.*');
        $db->select('pages_group.name as name_group');
		$db->join('pages_group', 'pages.id_group = pages_group.id');
        $pages = $db->get();
        $return = [];
        helper(['text']);
        foreach ($pages->getResultArray() as $page) {
        	if (!empty($page['text'])) {
        		$text = "";
        		$arr = json_decode($page['text']);
        		if (is_array($arr) AND count($arr) > 0) {
        			foreach($arr as $item) {
                        if (!isset($item->data->text)) {
                            continue;
                        }
        				$text.= $item->data->text;
        				$text.= "\n";
        			}
        		}
        		$page['text'] = htmlspecialchars(word_limiter(strip_tags($text), 25,"..."),ENT_QUOTES,'UTF-8');
        	}
        	$return[]=$page;
        }
        return $return;
	}

    /*
    Получить текст страницы для отправки в телеграм 
     */
    public function page($id = FALSE, array $message = [], $fill = [], $text_ = FALSE): array {
        
        if (!$text_) {
            //определяем id пользователя
            $this->set_id($message);
            //определяем язык
            $this->lang();

            //получаем текст сообщения с учетом языка
            if (!$data_page = $this->get($id, TRUE, $this->lang)) {
                return FALSE;
            }
            $text = $data_page['text'];
            $disable_web_page_preview = $data_page['disable_web_page_preview'];
            $file_id = $data_page['file_id'];

            //декодируем в обычную строку
            $text = $this->json_to_txt($text);

        } else {
            $disable_web_page_preview = FALSE;
            $text = $text_;
            $file_id = NULL;
        }

        //заполнить массивом
        $text = $this->fill($text, $fill);

        return [
            'text' => $text,
            'disable_web_page_preview' => $disable_web_page_preview,
            'file_id' => $file_id
        ];
    }

    /*
    Заполнить массивом

    @param $text - шаблон
    @param $fill - массив параметров
     */
    public function fill(string $text, $fill = []): string {
        if (!is_array($fill)) {
            return $text;
        }
        //заполнить массивом
        foreach ($fill as $field => $value) {
            $value = json($value); //если это json
            if (is_array($value)) {
                continue;
            }
            $text = str_ireplace("{" . $field . "}", $value, $text);
        }

        //заполнить переменными из настроек
        $this->SettingsModel = new \Admin\Models\SettingsModel();
        $all = $this->SettingsModel->all();
        foreach($all as $name_group => $settings) {
            foreach($settings as $setting) {
                $text = str_ireplace("{" . $setting['name'] . "}", $setting['value'], $text);
            }
        }

        return $text;
    }

    /*
    Закодировать текст в формат editorjs
     */
    public function txt_to_json($text) {
        $result = [];
        foreach (explode("\n", $text) as $item) {

            if (empty($item)) {
                $data = [];
                $data['type'] = "delimiter";
                $data['data'] = [];
                $data['data'] = (object) $data['data'];
                $data['data']->text = "***";
            } else {
                $data = [];
                $data['type'] = "paragraph";
                $data['data'] = [];
                $data['data'] = (object) $data['data'];
                $data['data']->text = $item;
            }

            $result[]= (object) $data;
        }
        
        return json_encode($result);
    }
    
    /*
    Декодировать текст сообщения в обычный текст с HTML тегами
     */
    public function json_to_txt($text): string {
        $items = (array) json_decode($text);
        
        $result = "";
        foreach($items as $item) {
            switch($item->type) {
                case "delimiter":
                    $result.="\n";
                break;
                case "paragraph":
                default:
                    $result.= $item->data->text;
                    $result.="\n";
                break;
            }
        }
        return $result;
    }

	/*
	Получить данные сообщения
	 */
	public function get($id, $full = FALSE, $short = "ru") {
		$data = $this->db
        ->table('pages')
        ->where('id', $id)
        ->limit(1)
        ->get()
        ->getRowArray();

        if ($short <> "ru") {
            $this->LangModel = new \Admin\Models\LangModel();
            $text_tr = $this->LangModel->trans_page($id, $short);
            $data['text'] = count(json_decode($text_tr)) > 0 ? $text_tr : $data['text'];

        }

        if (empty($data['text'])) {
            $data['text'] = json_encode([]);
        }

        return $full ? $data : $data['text'];
	}

    /*
    Сохранить данные сообщения
     */
    public function set($id, $data) {
        return $this->db->table('pages')->where('id', $id)->update($data);
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
        if (isset($data['blocks'])) {
            foreach($data['blocks'] as $item) {
                if ($item['type'] == "delimiter") {
                    $item['data']['text'] = '<i>***</i>';
                }
                $data_[]=$item;
            }
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

            $db = $this->db->table('pages');
            $db->where('id', $id);
            return $db->update(['text' => $blocks]);
        } catch (\EditorJSException $e) {
            log_message('error', print_r($e->getMessage(), TRUE));
            return FALSE;
        }
    }
}
