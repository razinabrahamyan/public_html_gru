<?php namespace Telegram\Models;

/**
 * Name: Модель для работы меню ботов
 * 
 * Created:  03.04.2020
 *
 * Description: Базовые функции для построения всех видов меню в telegram
 *
 * Requirements: PHP 7.2 or above
 *
 * @author Krotov Roman <tg: @KrotovRoman>
 */
use CodeIgniter\Model;
use \CodeIgniter\Database\ConnectionInterface;

/**
 * Class TgModel
 */
class MenusModel
{   
	protected $db;
    protected $id;
    protected $lang;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->db = \Config\Database::connect();
        $this->SettingsModel = new \Admin\Models\SettingsModel();
        $this->LangModel = new \Admin\Models\LangModel();
        $this->ionAuth = new \IonAuth\Libraries\IonAuth();

        $settings = $this->SettingsModel->all(TRUE);
        foreach ($settings as $settings_) {
            $this->{$settings_['name']} = trim($settings_['value']);
        }
    }


    /*
    Определяем язык пользователя текущий
     */
    public function lang() {
        if (empty($this->lang)) {
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
            return $this->id($message['message']['from']['id']);
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
    Меню выбора языка
     */
    public function language($message) {
        //можно вытащить язык из самого телеграм
        $short = isset($message['message']['from']['language_code']) ? $message['message']['from']['language_code'] : NULL;

        $keyboard = [];

        $languages = $this->LangModel->languages();

        $buttons = [];
        foreach ($languages as $language) {
            $button = [];
            $button['text'] = $language['name'];
            $button['callback_data'] = '/language '.$language['short'];
            $buttons[]= $button;
        }

        $buttons = array_chunk($buttons, 3); //по 3 кнопки в строке
        foreach($buttons as $item) {
            $keyboard[] = $item;
        }

        return $this->inline_keyboard($keyboard);
    }

    /*
    Получить текст кнопки с учетом языка
     */
    public function btn($message, $id = FALSE) {
        if (!$id) {
            return FALSE;
        }
        $this->set_id($message);
        $this->lang();
        return stripcslashes($this->LangModel->trans_btn($id, $this->lang));
    }

    /*
    Получить меню бота
     */
    public function get($message, $id = FALSE) {
        $menus = $this->db()->table('menus');
        if (!$id) {
            $menus = $menus->where('is_main', 1)->limit(1)->get()->getRowArray();
        } else {
            $menus = $menus->where('id', $id)->limit(1)->get()->getRowArray();
        }   

        if (!isset($menus['id'])) {
            return [];
        }

        //получаем строки меню
        $menu_items = $this->db
        ->table('menu_items')
        ->where('id_menu', $menus['id'])
        ->orderBy('priority', 'desc')
        ->select('id')
        ->get()
        ->getResultArray();
        
        $this->set_id($message);
        $this->lang();

        $keyboard = [];
        foreach ($menu_items as $menu_item) {
            $menu_buttons = $this->db()
            ->table('menu_buttons')
            ->where('id_item', $menu_item['id'])
            ->where('id_menu', $menus['id'])
            ->where('active', 1)
            ->orderBy('priority', 'desc')
            ->select('comand, url, name, id')
            ->get()
            ->getResultArray();

            //получаем кнопки в строке меню
            $row = [];
            $settings = $this->SettingsModel->all(TRUE);
            foreach ($menu_buttons as $menu_button) {
                $name = $this->LangModel->trans_btn($menu_button['id'], $this->lang);
                $name = empty($name) ? json_decode($menu_button['name']) : $name;

                //заменить теги в названии кнопки
                foreach ($settings as $settings_) {
                    $name = str_ireplace("{" . $settings_['name'] . "}", $settings_['value'], $name);
                }

                if ($menus['inline']) {
                    $data_button = [];
                    $data_button['text'] = stripcslashes($name);
                    if (!empty($menu_button['url'])) {

                        //заменить теги в URL кнопки
                        foreach ($settings as $settings_) {
                            $menu_button['url'] = str_ireplace("{" . $settings_['name'] . "}", $settings_['value'], $menu_button['url']);
                        }
                        $data_button['url'] = $menu_button['url'];

                    } else 
                    if (!empty($menu_button['comand'])) {
                        //заменить теги в команде
                        foreach ($settings as $settings_) {
                            $menu_button['comand'] = str_ireplace("{" . $settings_['name'] . "}", $settings_['value'], $menu_button['comand']);
                        }
                        // unset($data_button['url']);

                        $data_button['callback_data'] = '/' . $menu_button['comand'];
                    } else {
                        $data_button['callback_data'] = '/button ' . $menu_button['id'];
                    }

                } else {
                    $data_button = stripcslashes($name);
                }
                $row[] = $data_button;
            }

            $keyboard[] = $row;
        }

        if ($menus['inline']) {
            return $this->inline_keyboard($keyboard);
        }
        
        return $this->keyboard($keyboard);
    }

    /*
     * Удалить клавиатуру
     */

    public function keyboard_remove() {
        return [
            'reply_markup' => json_encode([
                'remove_keyboard' => TRUE
            ])
        ];
    }
    /*
     * Создает массив клавиатуры Inline
     */

    public function inline_keyboard($btns = NULL) {
        if (empty($btns)) {
            return [];
        }
        return [
            'reply_markup' => json_encode([
                'inline_keyboard' => $btns
            ])
        ];
    }


    /*
     * Создает массив клавиатуры
     * @example 
     * $this->keyboard(array('Контакты', 'Помощь', 'О тарифе'));
     * или 
     * $this->keyboard("Да");
     * @param bool $once - FALSE - не скрывать после использования
     */

    public function keyboard($btns = NULL, $once = FALSE) {
        if (empty($btns)) {
            return [];
        }
        $replyMarkup = [
            'keyboard' => $btns, //массивы кнопкок. чтобы кнопки были в новой строке - нужно в отдельный массив
            'resize_keyboard' => TRUE, //Указывает клиенту подогнать высоту клавиатуры под количество кнопок (сделать её меньше, если кнопок мало). По умолчанию False, то есть клавиатура всегда такого же размера, как и стандартная клавиатура устройства.
            'one_time_keyboard' => $once //Указывает клиенту скрыть клавиатуру после использования (после нажатия на кнопку). Её по-прежнему можно будет открыть через иконку в поле ввода сообщения. По умолчанию False.
        ];
        
        return [
            'reply_markup' => json_encode($replyMarkup)
        ];
    }

}
