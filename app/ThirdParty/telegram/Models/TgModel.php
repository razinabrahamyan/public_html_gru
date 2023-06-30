<?php namespace Telegram\Models;

/**
 * Name: Модель для работы с API Telegram. 
 * 
 * Created:  03.04.2020
 *
 * Description: Все базовые функции используется во всех ботов. 
 * Универсальный родительский класс
 *
 * Requirements: PHP 7.2 or above
 *
 * @author     Krotov Roman <tg: @KrotovRoman>
 */
use CodeIgniter\Model;
use \CodeIgniter\Database\ConnectionInterface;

/**
 * Class TgModel
 */
class TgModel
{   
	protected $db;
    private $api_key;
    private $hook_url;
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
        $this->PagesModel = new \Admin\Models\PagesModel();
        $this->MenuModel = new \App\Models\MenuModel();
        $this->ionAuth = new \IonAuth\Libraries\IonAuth();
        helper(['text', 'url']);

        $settings = $this->SettingsModel->all(TRUE);
        foreach ($settings as $settings_) {
            $this->{$settings_['name']} = $settings_['value'];
        }
	}

    /*
    Получить адрес вебхука
     */
    private function hook_url() {
        if (empty($this->hook_url)) {
            $this->hook_url =  base_url('telegram/hook');
        }
        return $this->hook_url;
    }

    /*
    Определяем язык пользователя текущий
     */
    public function lang() {
        if (empty($this->lang) AND !empty($this->id)) {
            $this->lang = $this->LangModel->lang($this->id);
        }

        return $this->lang;
    }

    /*
    Задаем id пользователя
     */
    private function set_id($message) {
        if (!isset($message['message'])) {
            return FALSE;
        }
        
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
    Получить токен бота из настроек БД
     */
    private function api_key() {
        if (empty($this->api_key)) {
            $this->api_key = $this->SettingsModel->get('api_key');
        }
        return $this->api_key;
    }

    /**
     * @param String $file_path - Путь к файлу
     * @return string - Софрмированный URL для отправки запроса
     */
    private function buildUrlDownload($file_path) {
        return 'https://api.telegram.org/file/bot' . $this->api_key() . '/' . $file_path;
    }

    /**
     * @param String $methodName - имя метода в API, который вызываем
     * @return string - Софрмированный URL для отправки запроса
     */
    private function buildUrl($methodName) {
        return 'https://api.telegram.org/bot' . $this->api_key() . '/' . $methodName;
    }

    /*
    Обновить данные пользователя в БД
     */
    public function update_userdata($message) {
        if (!isset($message['message'])) {
            return FALSE;
        }

        $data = [];

        $field = $message['message']['chat']['id'] > 0 ? "chat" : "from";
        if (isset($message['message'][$field]['first_name'])) {
            $data['first_name'] = $message['message'][$field]['first_name'];
        }
        if (isset($message['message'][$field]['last_name'])) {
            $data['last_name'] = $message['message'][$field]['last_name'];
        }
        if (isset($message['message'][$field]['username'])) {
            $data['username'] = $message['message'][$field]['username'];
        }

        if (!$this->ionAuth->identityCheck($message['message'][$field]['id'])) {
            return TRUE; //если такого пользователя нет
        }
        return $this->ionAuth->update($this->id, $data);
    }

    /*
    Запрос к API Telegram
    @docs https://codeigniter4.github.io/CodeIgniter4/libraries/curlrequest.html?highlight=curl
     */
    private function request(string $methodName, string $type = "POST", array $params = []) {
        try {
            $url = $this->buildUrl($methodName);
            $client = \Config\Services::curlrequest();

            $response = $client->request($type, $url, 
                [
                    'json' => $params, 
                    'http_errors' => FALSE
                ]
            );

            if (trim($response->getReason()) <> "OK" AND $response->getStatusCode() <> 200) {
                log_message('error',$response->getStatusCode().' Telegram API '.$methodName. ' '.$response->getReason());
                log_message('error', print_r($params, TRUE));
                // if (isset($params['chat_id'])) {
                //     $this->request("sendMessage", "POST", ['chat_id' => $params['chat_id'], 'text' => "ОШИБКА: ".$response->getStatusCode().' Telegram API '.$methodName. ' '.$response->getReason()]);
                // }
                return (object) [
                    'ok' => 0,
                    'description' => $response->getReason()
                ];
            }

            $body = $response->getBody();
            if (strpos($response->getHeader('content-type'), 'application/json') !== FALSE) {
                $body = json_decode($body);
            }

            return $body;
        } catch (\Exception $e) {
            log_message('error', print_r($methodName, TRUE));
            log_message('error', print_r($params, TRUE));
            log_message('error', print_r($e->getMessage(), TRUE));
            return FALSE;
        }
    }

	/*
    Задать вебхук   
    @docs https://core.telegram.org/bots/api#setwebhook
     */
	public function setWebHook($hook_url = FALSE) {
        $hook_url OR $hook_url = $this->hook_url();
        return $this->request("setWebHook", "POST", ['url' => $hook_url]);
	}

    /*
    Получить информацию об установленном вебхуке
    @docs https://core.telegram.org/bots/api#getwebhookinfo
     */
    public function getWebhookInfo() {
        return $this->request("getWebhookInfo", "GET");
    }

    /*
     * Получить данные пользователя
     * @https://core.telegram.org/bots/api#getme
     */
    public function getMe($chat_id = FALSE) {
        $data = [];
        if ($chat_id !== FALSE) {
            $data = ['chat_id' => $chat_id];
        }
        return $this->request("getMe", "POST", $data);
    }

    /**
     * Отправляемт событие что что то делает бот
     * @param string $type - photo,video,audio,document -для разных типов данных. ПО умолчанию - печать текста
     * @param array $chat_id - id чата
     * @return mixed|null
     * @docs https://tlgrm.ru/docs/bots/api#sendchataction
     * 
     * @example   $this->sendChatAction($message['message']['chat']['id']);
     */
    public function sendChatAction($chat_id, $type = FALSE) {
        if (!$type) {
            $data['action'] = "typing";
        } else {
            $data['action'] = "upload_" . $type;
        }
        $data['chat_id'] = $chat_id;
        if (!$res = $this->request("sendChatAction", "POST", $data)) {
            return FALSE;
        }

        return $res;
    }

    /*
     * Получить тип файла
     * @return FALSE - если не файл, иначе название типа
     */

    private function get_type_file($array) {
        $arr_types = array('photo', 'audio', 'document', 'voice', 'video', 'sticker');
        $res = FALSE;
        if (is_array($array)) {
            foreach ($arr_types as $type) {
                if (isset($array[$type])) {
                    $res = $type;
                }
            }
        } else {
            foreach ($arr_types as $type) {
                if (stripos($array, $type) !== FALSE) {
                    $res = $type;
                }
            }
        }
        return $res;
    }

    /*
     * ЗАмена file_get_contents
     */

    private function download_file_from_www($url, $connect_timeout = 10, $timeout = 120) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connect_timeout);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /*
    Отправка файла с URL
    @param $url - путь к файлу на сервере 
    @param $type - тип отправляемого файла
    @example path $path = ROOTPATH."/writable/uploads/1.txt";
     */
    public function sendUrl($chatId, string $url, array $params = [], string $type = "photo") {
        if (empty($url) OR filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
            log_message('error','Не URL к файлу: '.$url);
            return FALSE;
        }

        //отправляем прелоадер
        $this->sendChatAction($chatId, $type);
        
        //получаем картинку по URl
        $save_dir = realpath(ROOTPATH."/writable/uploads");
        $info = pathinfo($url);
        $save_patch = $save_dir . '/' . $info['basename'];
        if (!realpath($save_patch)) {//если такую картинку еще не скачивали            
            file_put_contents($save_patch, $this->download_file_from_www($url)); //скачиваем
        }
        if (!$path = realpath($save_patch)) {
            return FALSE;
        }

        //теперь отправляем как обычно
        return $this->sendSrc($chatId, $path, $params, $type);
    }


    /*
    Извлечь id файла
    @example 
    $result = $this->sendSrc($message['message']['chat']['id'], $src);
    $file_id = $this->extract_file_id($result);
     */
    public function extract_file_id($result) {
        $file_id = FALSE;
        if (!isset($result->result)) {
            return FALSE;
        }
        $result = $result->result;

        if (isset($result['photo'])) {
            foreach ($result['photo'] as $data_photo) {
                $file_id = $data_photo['file_id'];
            }
            return $file_id;
        } else if (isset($result['document'])) {
            return $result['document']['file_id'];
        } else if (isset($result['video'])) {
            return $result['video']['file_id'];
        } else if (isset($result['audio'])) {
            return $result['audio']['file_id'];
        } else if (isset($result['voice'])) {
            return $result['voice']['file_id'];
        } else if (isset($message['message']['video_note'])) {
            return $result['video_note']['file_id'];
        }

        return FALSE;
    }
    
    /*
    Отправка файла с нашего сервера
    @param $path - путь к файлу на сервере 
    @param $type - тип отправляемого файла
    @example path $path = ROOTPATH."/writable/uploads/1.txt";
     */
    public function sendSrc($chatId, string $path, array $params = [], string $type = "photo") {
        if (empty($path) OR !realpath($path)) {
            log_message('error','Не верный путь к файлу: '.$path);
            return FALSE;
        }

        //отправляем прелоадер
        $this->sendChatAction($chatId, $type);
        
        if (!empty($params['caption'])) {
            helper('text');
            $params['parse_mode'] = 'HTML';
            $params['caption'] = character_limiter($params['caption'], 1024);
        }

        $params['chat_id'] = $chatId;
        $params[$type] = new \CURLFile(realpath($path));

        //определяем метод отправки файла
        $method = $this->methodSendFile($type);

        //делаем запрос по старинке
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:multipart/form-data"]);
        curl_setopt($ch, CURLOPT_URL, $this->buildUrl($method));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $data = curl_exec($ch);
        $res = (object) json_decode($data, TRUE);
        
        if (isset($res->error_code) AND $res->error_code > 0) { //и не отсутствие поста
            return FALSE;
        }

        return $res;
    }

    /*
     * Диалоговое окно
     * 
     * $show_alert - если TRUE - тогда с кнопкой ОК
     * $text - текст в диалоговом окне
     * $callback_query_id - $message['id] -которое возвращается при нажатии кнопки 
     * $params['url'] = ссылка
     * 
     * @example 
     * $this->answerCallbackQuery($message['id'], "Привет");
     * @docs https://core.telegram.org/bots/api#answercallbackquery
     */

    public function answerCallbackQuery($callback_query_id, string $text = NULL, bool $show_alert = FALSE, array $params = []) {

        $params['show_alert'] = $show_alert;
        $params['callback_query_id'] = $callback_query_id;
        if (!empty($text)) {
            $params['text'] = strip_tags($text);
        }

        if (!$res = $this->request("answerCallbackQuery", "POST", $params)) {
            return FALSE;
        }

        if (isset($res->error_code) AND $res->error_code > 0) {
            $this->request("sendMessage", "POST", ['chat_id' => $chatId, 'text' => $res->description]);
            return FALSE;
        }
        return $res;
    }

    /*
    Ответ в процессе оплаты

    @docs https://core.telegram.org/bots/api#answerprecheckoutquery
    @docs https://yandex.ru/support/checkout/instructions/telegram.html
     */
    public function answerPreCheckoutQuery($pre_checkout_query_id, $text = FALSE, $ok = TRUE) {
        $params['ok'] = $ok;
        $params['pre_checkout_query_id'] = $pre_checkout_query_id;
        
        if ($text !== FALSE) {
            $params['error_message'] = strip_tags($text);
        }

        if (!$res = $this->request("answerPreCheckoutQuery", "POST", $params)) {
            return FALSE;
        }

        if (isset($res->error_code) AND $res->error_code > 0) {
            return FALSE;
        }
        return $res;
    }
    
    /*
     * Переслать сообщение
     * $this->forwardMessage($message['message']['chat']['id'], $this->support_chat_id, $message['message']['message_id']);
     */
    public function forwardMessage($from_chat_id, $chat_id, $message_id) {
        $params['message_id'] = $message_id;
        $params['chat_id'] = $chat_id;
        $params['from_chat_id'] = $from_chat_id;
        if (!$res = $this->request("forwardMessage", "POST", $params)) {
            return FALSE;
        }

        if (isset($res->error_code) AND $res->error_code > 0) {
            $this->request("sendMessage", "POST", ['chat_id' => $chat_id, 'text' => $res->description]);
            return FALSE;
        }
        return $res;
    }

    /*
    Получить фотографии пользователя
    @param $chat_id - id чата
    @param $user_id - id пользователя
    @docs https://core.telegram.org/bots/api#unbanchatmember
     */
    public function getUserProfilePhotos($user_id, $limit = 100, $offset = 0) {
        $params['user_id'] = $user_id;
        $params['offset'] = $offset;
        $params['limit'] = $limit;
        if (!$res = $this->request("getUserProfilePhotos", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            return FALSE;
        }
        return $res;
    }

    /*
    Отправить контакт
    @param $chat_id - id чата
    @docs https://core.telegram.org/bots/api#sendpoll
     */
    public function sendContact($chat_id, string $phone_number, string $first_name, $params) {
        if ($chat_id >= 0) {
            log_message('error','chat_id это id группового чата или канала и он должен быть отрицательным а не '.$chat_id);
            return FALSE;
        }
        $params['chat_id'] = $chat_id;
        $params['phone_number'] = $phone_number;
        $params['first_name'] = $first_name;
        if (!$res = $this->request("sendContact", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            return FALSE;
        }
        return $res;
    }

    /**
     * Остановить голосование
     * 
     * @param int $chatId - ID чата, в который отправляем сообщение
     * @param String $message - текст сообщения
     * @param array $params - дом.параметры (опционально)
     * @return mixed результаты голосования
     * 
     * @docs https://core.telegram.org/bots/api#stoppoll
     */
    public function stopPoll($chatId, $message_id, array $params = []) {
        $params['chat_id'] = $chatId;
        $params['message_id'] = $message_id;

        if (!$res = $this->request("stopPoll", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) { //и не отсутствие поста
            $this->request("sendMessage", "POST", ['chat_id' => $chatId, 'text' => $res->description]);
            return FALSE;
        }
        return $res;
    }

    /*
    Отправить голосование
    @param $chat_id - id чата
    @docs https://core.telegram.org/bots/api#sendpoll
     */
    public function sendPoll($chat_id, string $question, array $options = [], $params) {
        if ($chat_id >= 0) {
            log_message('error','chat_id это id группового чата или канала и он должен быть отрицательным а не '.$chat_id);
            return FALSE;
        }
        $params['chat_id'] = $chat_id;
        $params['question'] = json_encode($question);
        if (!$res = $this->request("sendPoll", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            return FALSE;
        }
        return $res;
    }


    /*
    Выкинуть пользователя из чата

    @param $chat_id - id чата
    @param $user_id - id пользователя
    @param $until_date - до какой даты unix
    @docs https://core.telegram.org/bots/api#kickchatmember
     */
    public function kickChatMember($chat_id, $user_id, $until_date = 0): bool {
        if ($chat_id >= 0) {
            log_message('error','chat_id это id группового чата или канала и он должен быть отрицательным а не '.$chat_id);
            return FALSE;
        }
        $params['chat_id'] = $chat_id;
        $params['user_id'] = $user_id;
        $params['until_date'] = $until_date;
        if (!$res = $this->request("kickChatMember", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            return FALSE;
        }
        if (!isset($res->ok) OR !$res->ok) {
            return FALSE;
        }
        return TRUE;
    }

    /*
    Разбанить пользователя в чате

    @param $chat_id - id чата
    @param $user_id - id пользователя
    @docs https://core.telegram.org/bots/api#unbanchatmember
     */
    public function unbanChatMember($chat_id, $user_id):bool {
        if ($chat_id >= 0) {
            log_message('error','chat_id это id группового чата или канала и он должен быть отрицательным а не '.$chat_id);
            return FALSE;
        }
        $params['chat_id'] = $chat_id;
        $params['user_id'] = $user_id;
        if (!$res = $this->request("unbanChatMember", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            return FALSE;
        }
        if (!isset($res->ok) OR !$res->ok) {
            return FALSE;
        }
        return TRUE;
    }

    /*
    Забрать права пользователя в чате
    @param $chat_id - id чата
    @param $user_id - id пользователя
    @param $permissions - массив прав и их значение
    @docs https://core.telegram.org/bots/api#promotechatmember
  
    array $permissions = [] могут быть:
    can_change_info - if the administrator can change chat title, photo and other settings
    can_post_messages - if the administrator can create channel posts, channels only
    can_edit_messages  - if the administrator can edit messages of other users and can pin messages, channels only
    can_delete_messages  - if the administrator can delete messages of other users
    can_invite_users - if the administrator can invite new users to the chat
    can_restrict_members   - if the administrator can restrict, ban or unban chat members
    can_pin_messages   - if the administrator can pin messages, supergroups only
    can_promote_members - if the administrator can add new administrators with a subset of his own privileges or demote administrators that he has promoted, directly or indirectly (promoted by administrators that were appointed by him)
    
     */
    public function restrictChatMember($chat_id, $user_id, array $permissions = [], $until_date = 0) {
        if ($chat_id >= 0) {
            log_message('error','chat_id это id группового чата или канала и он должен быть отрицательным а не '.$chat_id);
            return FALSE;
        }
        $params['chat_id'] = $chat_id;
        $params['user_id'] = $user_id;
        $params['permissions'] = $permissions;
        $params['until_date'] = $until_date;
        if (!$res = $this->request("restrictChatMember", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            return FALSE;
        }
        return $res;
    }

    /*
    Выдать права пользователя в чате
    @param $chat_id - id чата
    @param $user_id - id пользователя
    @param $permissions - массив прав и их значение
    @docs https://core.telegram.org/bots/api#promotechatmember
  
    array $permissions = [] могут быть:
    can_change_info - if the administrator can change chat title, photo and other settings
    can_post_messages - if the administrator can create channel posts, channels only
    can_edit_messages  - if the administrator can edit messages of other users and can pin messages, channels only
    can_delete_messages  - if the administrator can delete messages of other users
    can_invite_users - if the administrator can invite new users to the chat
    can_restrict_members   - if the administrator can restrict, ban or unban chat members
    can_pin_messages   - if the administrator can pin messages, supergroups only
    can_promote_members - if the administrator can add new administrators with a subset of his own privileges or demote administrators that he has promoted, directly or indirectly (promoted by administrators that were appointed by him)
     */
    public function promoteChatMember($chat_id, $user_id, array $permissions = []) {
        if ($chat_id >= 0) {
            log_message('error','chat_id это id группового чата или канала и он должен быть отрицательным а не '.$chat_id);
            return FALSE;
        }
        $params['chat_id'] = $chat_id;
        $params['user_id'] = $user_id;
        $params = array_merge($params, $permissions);
        if (!$res = $this->request("promoteChatMember", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            return FALSE;
        }
        return $res;
    }

    /*
    Задать название статуса пользователя в чате
    @param $chat_id - id чата
    @param $user_id - id пользователя
    @param $title - например Админ
     */
    public function setChatAdministratorCustomTitle($chat_id, $user_id, string $title) {
        if ($chat_id >= 0) {
            log_message('error','chat_id это id группового чата или канала и он должен быть отрицательным а не '.$chat_id);
            return FALSE;
        }
        $params['chat_id'] = $chat_id;
        if (!$res = $this->request("setChatAdministratorCustomTitle", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            return FALSE;
        }
        return $res;
    }

    /*
    Задать разрешения по умолчанию для всех участников
    @param $chat_id - id чата
    @return array
    @docs https://core.telegram.org/bots/api#setchatpermissions
     */
    public function setChatPermissions($chat_id, $permissions = []) {
        if ($chat_id >= 0) {
            log_message('error','chat_id это id группового чата или канала и он должен быть отрицательным а не '.$chat_id);
            return FALSE;
        }
        $params['chat_id'] = $chat_id;
        $params['permissions'] = $permissions;
        if (!$res = $this->request("setChatPermissions", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            return FALSE;
        }
        return $res;
    }

    /*
    Создать новую ссылку для приглашения в чат
    любая ранее сгенерированная ссылка аннулируется
    @param $chat_id - id чата
    @return URL

    @docs https://core.telegram.org/bots/api#exportchatinvitelink
     */
    public function exportChatInviteLink($chat_id) {
        if ($chat_id >= 0) {
            log_message('error','chat_id это id группового чата или канала и он должен быть отрицательным а не '.$chat_id);
            return FALSE;
        }
        $params['chat_id'] = $chat_id;
        if (!$res = $this->request("exportChatInviteLink", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            log_message('error','exportChatInviteLink: '.$res->description);
            return FALSE;
        }
        if (isset($res->ok) AND $res->ok) {
            return $res->result;
        }
        return $res;
    }

    /*
    Изменить фото чата
    @param $chat_id - id чата
    @param $path  - пусть к фото на сервере
     */
    public function setChatPhoto($chat_id, string $path) {
        if ($chat_id >= 0) {
            log_message('error','chat_id это id группового чата или канала и он должен быть отрицательным а не '.$chat_id);
            return FALSE;
        }
        $params['chat_id'] = $chat_id;
        $params['photo'] = new \CURLFile(realpath($path));

        //делаем запрос по старинке
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:multipart/form-data"]);
        curl_setopt($ch, CURLOPT_URL, $this->buildUrl("setChatPhoto"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $data = curl_exec($ch);
        $res = (object) json_decode($data, TRUE);

        if (isset($res->error_code) AND $res->error_code > 0) {
            return FALSE;
        }
        return $res;
    }

    /*
    Удалить фото чата
    @param $chat_id - id чата
    @return array
     */
    public function deleteChatPhoto($chat_id) {
        if ($chat_id >= 0) {
            log_message('error','chat_id это id группового чата или канала и он должен быть отрицательным а не '.$chat_id);
            return FALSE;
        }
        $params['chat_id'] = $chat_id;
        if (!$res = $this->request("deleteChatPhoto", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            return FALSE;
        }
        return $res;
    }

    /*
    Изменить название чата
    @param $chat_id - id чата
    @param $title - yfpdfybt
    @return array
     */
    public function setChatTitle($chat_id, strng $title) {
        if ($chat_id >= 0) {
            log_message('error','chat_id это id группового чата или канала и он должен быть отрицательным а не '.$chat_id);
            return FALSE;
        }
        $params['chat_id'] = $chat_id;
        $params['title'] = $title; //до 255
        if (!$res = $this->request("setChatTitle", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            return FALSE;
        }
        return $res;
    }

    /*
    Изменить описание чата
    @param $chat_id - id чата
    @param $description - описание
    @return array
     */
    public function setChatDescription($chat_id, strng $description) {
        if ($chat_id >= 0) {
            log_message('error','chat_id это id группового чата или канала и он должен быть отрицательным а не '.$chat_id);
            return FALSE;
        }
        $params['chat_id'] = $chat_id;
        $params['description'] = $description; //до 255
        if (!$res = $this->request("setChatDescription", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            return FALSE;
        }
        return $res;
    }

    /*
    Закрепить сообщение чата/канала
    @param $chat_id - id чата
    @param $message_id - id сообщения в чате
    
    бот должен быть администратором в чате и иметь права администратора 
    can_pin_messages в супергруппе или администратора can_edit_messages в канале
     */
    public function pinChatMessage($chat_id, $message_id, $disable_notification = FALSE) {
        if ($chat_id >= 0) {
            log_message('error','chat_id это id группового чата или канала и он должен быть отрицательным а не '.$chat_id);
            return FALSE;
        }
        $params['chat_id'] = $chat_id;
        $params['message_id'] = $message_id;
        $params['disable_notification'] = $disable_notification;
        if (!$res = $this->request("pinChatMessage", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            return FALSE;
        }
        return $res;
    }

    /*
    Открепить сообщение чата
    @param $chat_id - id чата
    
    Для этого бот должен быть администратором в чате 
    и иметь права администратора can_pin_messages в супергруппе 
    или администратора can_edit_messages в канале.
     */
    public function unpinChatMessage($chat_id) {
        if ($chat_id >= 0) {
            log_message('error','chat_id это id группового чата или канала и он должен быть отрицательным а не '.$chat_id);
            return FALSE;
        }
        $params['chat_id'] = $chat_id;
        if (!$res = $this->request("unpinChatMessage", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            return FALSE;
        }
        return $res;
    }

    /*
    Бот выходит из чата
    @param $chat_id - id чата
    @return array
     */
    public function leaveChat($chat_id) {
        if ($chat_id >= 0) {
            log_message('error','chat_id это id группового чата или канала и он должен быть отрицательным а не '.$chat_id);
            return FALSE;
        }
        $params['chat_id'] = $chat_id;
        if (!$res = $this->request("leaveChat", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            return FALSE;
        }
        return $res;
    }


    /*
    получения актуальной информации о чате
    @param $chat_id - id чата
    @return array

    @example  получить username канала
    $this->getChat($this->SettingsModel->channel_id)->result->username;
     */
    public function getChat($chat_id) {
        if ($chat_id >= 0) {
            log_message('error','chat_id это id группового чата или канала и он должен быть отрицательным а не '.$chat_id);
            return FALSE;
        }
        $params['chat_id'] = $chat_id;
        if (!$res = $this->request("getChat", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            return FALSE;
        }
        return $res;
    }

    /*
    получить список администраторов в чате
    @param $chat_id - id чата
    @return array
     */
    public function getChatAdministrators($chat_id) {
        if ($chat_id >= 0) {
            log_message('error','chat_id это id группового чата или канала и он должен быть отрицательным а не '.$chat_id);
            return FALSE;
        }
        $params['chat_id'] = $chat_id;
        if (!$res = $this->request("getChatAdministrators", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            return FALSE;
        }
        return $res;
    }


    /*
    Получить количество пользователей чата
    @param $chat_id - id чата
    @return int количество
    
    @docs https://core.telegram.org/bots/api#getchatmemberscount
     */
    public function getChatMembersCount($chat_id) {
        if ($chat_id >= 0) {
            log_message('error','chat_id это id группового чата или канала и он должен быть отрицательным а не '.$chat_id);
            return FALSE;
        }
        $params['chat_id'] = $chat_id;
        if (!$res = $this->request("getChatMembersCount", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            return FALSE;
        }
        return !isset($res->result) ? 0 : $res->result;
    }

    /*
    Проверить является ли членом канала или чата

    @example if (!$this->isChatMember($this->SettingsModel->channel_id, $message['message']['chat']['id'])) {
        //не подписан
        }
     */
    public function isChatMember($chat_id, $user_id): bool {
        $result = $this->getChatMember($chat_id, $user_id);
        if ($result === FALSE OR !isset($result->status)) {
            return FALSE; //не удалось получить данные
        }

        switch($result->status) {
            case "left":
            case "restricted": //ограниченный -считаем что не доступно
            case "kicked":
            case "left":
            return FALSE;

            case "creator":
            case "administrator":
            case "member":
            default:
            return TRUE;
        }
    }


    /*
    Получить данные пользователя в чате
    $chat_id - id чата
    $user_id - id пользователя

    @example Получить статус присутствия на канале
    $status = $this->getChatMember($this->SettingsModel->channel_id, $message['message']['chat']['id'])->result->status;
     */
    public function getChatMember($chat_id, $user_id) {
        if ($chat_id >= 0) {
            log_message('error','chat_id это id группового чата или канала и он должен быть отрицательным а не '.$chat_id);
            return FALSE;
        }
        $params['user_id'] = $user_id;
        $params['chat_id'] = $chat_id;
        if (!$res = $this->request("getChatMember", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            return FALSE;
        }
        if (!isset($res->result)) {
            return FALSE;
        }
        return $res->result;
    }

    /*
    Отправить счет
    @docs https://core.telegram.org/bots/api#sendinvoice
    @docs https://yandex.ru/support/checkout/instructions/telegram.html
     */
    public function sendInvoice($chatId, $params = []) {
        $params['chat_id'] = $chatId;

        if (!$res = $this->request("sendInvoice", "POST", $params)) {
            return FALSE;
        }

        if (isset($res->error_code) AND $res->error_code > 0) {
            $this->request("sendMessage", "POST", ['chat_id' => $chatId, 'text' => $res->description]);
            return FALSE;
        }
        return $res;
    }

    /*
    Получить доступные курсы валют
    которые поддерживают платежка телеграм
    @docs https://core.telegram.org/bots/payments#supported-currencies
     */
    public function get_currency($currency_need = "RUB") {
        $items = json_decode(file_get_contents("https://core.telegram.org/bots/payments/currencies.json"));
        foreach($items as $currency => $data) {
            if ($data->symbol == $currency_need) {
                $res = [];
                foreach($data as $name => $val) {
                    $res[$name] = $val;
                }
                $res['min_amount'] = $res['min_amount']/100;
                $res['max_amount'] = $res['max_amount']/100;
                return $res;
            }
        }

        return FALSE;
    }

    /*
     * Удалить сообщение
     * @example $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
     * 
     * Ограничения:
     * - Сообщение можно удалить только в том случае, если оно было отправлено менее 48 часов назад.
     * - Сообщение в приватном чате можно удалить, только если оно было отправлено более 24 часов назад.
     * - Боты могут удалять исходящие сообщения в частных чатах, группах и супергруппах.
     * - Боты могут удалять входящие сообщения в приватных чатах.
     * - Боты, получившие разрешения can_post_messages, могут удалять исходящие сообщения в каналах.
     * - Если бот является администратором группы, он может удалить там любое сообщение.
     * - Если бот имеет разрешение can_delete_messages в супергруппе или канале, он может удалить там любое
     */

    public function deleteMessage($chat_id, $message_id) {
        $params['chat_id'] = $chat_id;
        $params['message_id'] = $message_id;
        if (!$res = $this->request("deleteMessage", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            $this->request("sendMessage", "POST", ['chat_id' => $chatId, 'text' => $res->description]);
            return FALSE;
        }
        return $res;
    }

    /*
     * Отправить местоположение
     * 
     * @example:
     * $params = [];
      $params['latitude'] = $data_order['lat'];
      $params['longitude'] = $data_order['lon'];
      $params['address'] = $data_order['adress'];
      $params['title'] = "Заказ №".$id_order;
      $this->sendVenue($this->support_chat_id, $params);
     */

    public function sendVenue($chatId, array $params = []) {
        $params['chat_id'] = $chatId;
        if (!$res = $this->request("sendVenue", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) { //и не отсутствие поста
            $this->request("sendMessage", "POST", ['chat_id' => $chatId, 'text' => $res->description]);
            return FALSE;
        }
        return $res;
    }

    /*
    Отправить геолокацию
    @docs https://core.telegram.org/bots/api#sendlocation
     */
    public function sendLocation($chatId, $latitude, $longitude, array $params = []) {
        $params['latitude'] = $latitude;
        $params['longitude'] = $longitude;
        $params['chat_id'] = $chatId;
        if (!$res = $this->request("sendLocation", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) { //и не отсутствие поста
            $this->request("sendMessage", "POST", ['chat_id' => $chatId, 'text' => $res->description]);
            return FALSE;
        }
        return $res;
    }

    /**
     * Отправить файл 
     * 
     * @param int $chatId - ID чата, в который отправляем сообщение
     * @param String $src -абсолютный путь
     * @param array $params - дом.параметры (опционально)
     * @param string $type - тип файла (если указать его то отправка будет быстрее)
     * @return mixed
     */
    public function sendFile($chatId, string $file_id, array $params = [], $type = FALSE) {
        $file_id = trim($file_id);
        if (empty($file_id)) {
            return FALSE;
        }

        if (!$type) { //если не указан конкретный тип файла отправляемого
            //получаем данные файла
            $data_file = $this->request('getFile', "GET", ['file_id' => $file_id]); 
            if ($data_file !== FALSE AND $data_file->ok) {
                $type = $this->get_type_file($data_file->result->file_path);
            }
        }

        //отправляем прелоадер
        $this->sendChatAction($chatId, $type);

        if (!empty($params['caption'])) {
            helper('text');
            $params['parse_mode'] = 'HTML';
            $params['caption'] = character_limiter($params['caption'], 4090, '...');
        }
        $params[$type] = $file_id;
        $params['chat_id'] = $chatId;

        //определяем метод отправки файла
        $method = $this->methodSendFile($type);

        if (!$res = $this->request($method, "POST", $params)) {
            return FALSE;
        }
        return (object) $res;
    }

    /*
    Получить метод отправки файла в зависимости от типа файла
     */
    private function methodSendFile(string $type): string {
        switch ($type) {
            case "video_note": return 'sendVideoNote';
            case "video": return 'sendVideo';//mp4 videos
            case "animation": return 'sendAnimation'; //GIF or H.264/MPEG-4 AVC video without sound 50 MB
            case "voice": return 'sendVoice';  //.OGG file encoded with OPUS
            case "audio": return 'sendAudio'; //.MP3 or .M4A 50 MB
            case "document": return'sendDocument'; //50 MB
            case "photo":
            default: 
            return 'sendPhoto';
        }
    }

    /**
     * Обновить клавиатуру в сообщении
     * 
     * @param int $chatId - ID чата, в который отправляем сообщение
     * @param String $message - текст сообщения
     * @param array $params - дом.параметры (опционально)
     * @return mixed
     * 
     * @docs https://core.telegram.org/bots/api#editmessagereplymarkup
     * 
     * @example 
     * Убрать кнопки вообще
     * $this->editMessageReplyMarkup($message['message']['chat']['id'], $message['message']['message_id']);
     */
    public function editMessageReplyMarkup($chatId, $message_id, array $params = []) {
        $params['parse_mode'] = isset($params['parse_mode']) ? $params['parse_mode'] : 'HTML';
        
        $params['chat_id'] = $chatId;
        $params['message_id'] = $message_id;

        if (!$res = $this->request("editMessageReplyMarkup", "POST", $params)) {
            return FALSE;
        }
        return $res;
    }

    /**
     * Обновить обновить файл в сообщении
     * 
     * @param int $chatId - ID чата, в который отправляем сообщение
     * @param String $message - текст сообщения
     * @param array $params - дом.параметры (опционально)
     * @return mixed
     * 
     * @docs https://core.telegram.org/bots/api#editmessagemedia
     */
    public function editMessageMedia($chatId, $file_id, $message_id, array $params = []) {
        $params['parse_mode'] = isset($params['parse_mode']) ? $params['parse_mode'] : 'HTML';
        
        $params['chat_id'] = $chatId;
        $params['message_id'] = $message_id;
        $params['media'] = $file_id;

        if (!$res = $this->request("editMessageMedia", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) { //и не отсутствие поста
            $this->request("sendMessage", "POST", ['chat_id' => $chatId, 'text' => $res->description]);
            return FALSE;
        }
        return $res;
    }

    /**
     * Обновить сообщение с файлом. inline
     * 
     * @param int $chatId - ID чата, в который отправляем сообщение
     * @param String $message - текст сообщения
     * @param array $params - дом.параметры (опционально)
     * @return mixed
     * 
     * @docs https://core.telegram.org/bots/api#updating-messages
     */
    public function editMessageCaption($chatId, $message, $message_id, array $params = []) {
        $params['parse_mode'] = isset($params['parse_mode']) ? $params['parse_mode'] : 'HTML';
        $params['disable_web_page_preview'] = isset($params['disable_web_page_preview']) ? $params['disable_web_page_preview'] : FALSE;
        
        $params['chat_id'] = $chatId;
        $params['message_id'] = $message_id;
        $params['text'] = character_limiter($message, 4090, '...');

        if (!$res = $this->request("editMessageCaption", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) { //и не отсутствие поста
            $this->request("sendMessage", "POST", ['chat_id' => $chatId, 'text' => $res->description]);
            return FALSE;
        }
        return $res;
    }

    /*
    Отправить группу файлов
    @params $files = [
        0 => [
        'type' => 'photo',
        'media' => 'fileid1',
        'caption' => 'test1'
        ],

        2 => [
        'type' => 'photo',
        'media' => 'fileid2',
        'caption' => 'test1'
        ],
    ];
    @docs https://core.telegram.org/bots/api#sendmediagroup
     */
    public function sendMediaGroup($chatId, $files = [], $params = []) {
        if (count($files) < 2) {
            return FALSE;
        }

        $params['media'] = $this->media($files);
        $params['chat_id'] = $chatId;
        
        if (!$res = $this->request("sendMediaGroup", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) { //и не отсутствие поста
            $this->request("sendMessage", "POST", ['chat_id' => $chatId, 'text' => $res->description]);
            return FALSE;
        }
        return $res;
    }

    /*
    Получить массив медиа
    @docs https://core.telegram.org/bots/api#inputmediaphoto
     */
    private function media($files) {
        $result = [];
        $i = 1;
        foreach($files as $file) {
            if ($i > 10) {
                continue;
            }
            $item['parse_mode'] = isset($file['parse_mode']) ? $file['parse_mode'] : 'HTML';
            $item['type'] = isset($file['type']) ? $file['type'] : 'photo';
            $item['media'] = $file['media'];
            if (isset($file['caption'])) {
                $item['caption'] = $file['caption'];
            }
            $result[]=$item;
            $i++;
        }
        return json_encode($result);
    }

    /**
     * Обновить сообщение. inline
     * 
     * @param int $chatId - ID чата, в который отправляем сообщение
     * @param String $message - текст сообщения
     * @param array $params - дом.параметры (опционально)
     * @return mixed
     * 
     * @example  return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
     * 
     * @docs https://core.telegram.org/bots/api#updating-messages
     */
    public function editMessageText($chatId, $message, $message_id, array $params = []) {
        $params['parse_mode'] = isset($params['parse_mode']) ? $params['parse_mode'] : 'HTML';
        $params['disable_web_page_preview'] = isset($params['disable_web_page_preview']) ? $params['disable_web_page_preview'] : FALSE;
        
        $params['chat_id'] = $chatId;
        $params['message_id'] = $message_id;
        $params['text'] = character_limiter($message, 4090, '...');

        if (!$res = $this->request("editMessageText", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) { //и не отсутствие поста
            $this->request("sendMessage", "POST", ['chat_id' => $chatId, 'text' => $res->description]);
            return FALSE;
        }
        return $res;
    }

    /*
    Отправить сообщение
    @docs https://core.telegram.org/bots/api#sendmessage
    @docs https://core.telegram.org/bots/api#html-style
     */
    public function sendMessage($chatId, string $message = NULL, array $params = []) {
        $params['parse_mode'] = isset($params['parse_mode']) ? $params['parse_mode'] : 'HTML';
        $params['disable_web_page_preview'] = isset($params['disable_web_page_preview']) ? $params['disable_web_page_preview'] : FALSE;
        $params['disable_notification'] = FALSE;
        
        if (empty($message)) {
            return FALSE;
        }
        if (empty($chatId)) {
            log_message('error', 'Нет chat_id для отправки сообщения '.$message);
            return FALSE;
        }

        if (isset($params['inline_keyboard']) AND ! is_array($params['inline_keyboard'])) {
            $this->request("sendMessage", "POST", ['chat_id' => $chatId, 'text' => 'ОШИБКА: Клавиатура должна быть массивом!']);
            log_message('error', print_r($params['inline_keyboard'], TRUE));
            return FALSE;
        }

        $params['chat_id'] = $chatId;

        if (stripos($_SERVER['HTTP_HOST'], "videolamp.org") !== FALSE){
            $message.="\n\nДля заказа бота пиши сюда: @KrotovRoman";
            $message.="\nПримеры чат-ботов тут: @BotCreatorNews";
            $message.="\nЭта реклама исчезнет после переноса бота на рабочий сервер!";
        } 

        $params['text'] = character_limiter($message, 4090, '...');

        if (!$res = $this->request("sendMessage", "POST", $params)) {
            return FALSE;
        }
        if (isset($res->error_code) AND $res->error_code > 0) {
            // log_message('error', 'Ошибка '.$res->description.' при отправке сообщения '.$message);
            // $this->request("sendMessage", "POST", ['chat_id' => $chatId, 'text' => "ОШИБКА: ".$res->description]);
            return (object) $res;
        }

        return (object) $res;
    }

    /*
    Получить на файл по id
    @example $src = $this->getFile($file_id, "xlsx");
     */
    public function getFile(string $file_id, $type = FALSE, $save_patch = FALSE) {
        $data_file = $this->request('getFile', "GET", ['file_id' => $file_id]); 
        if ($data_file !== FALSE AND $data_file->ok) {
            $path_info = pathinfo($data_file->result->file_path);
            if ($type !== FALSE AND mb_strtolower($type) <> mb_strtolower($path_info['extension'])) {
                log_message('error','При получении файла, получен файл с не верным расширением: '.$data_file->result->file_path);
                return FALSE;
            }

            $file_path = $data_file->result->file_path;
            $download_url = $this->buildUrlDownload($file_path);
            $save_patch OR $save_patch = realpath(APPPATH."/../writable/uploads/");
            $save_patch.="/".uniqid().".".$path_info['extension'];
            if (!file_put_contents($save_patch, $this->download_file_from_www($download_url))) {
                return FALSE;
            }
            return realpath($save_patch);
        }
        return FALSE;
    }

    /*
    Выводим код файла
     */
    private function file_id_code($message) {
        if (!$file_id = $this->file_id($message) OR !$this->ionAuth->isAdmin($message['message']['chat']['id'])) {
            return FALSE;
        }
        
        if ($this->db()->table('bufer')
                   ->where('chat_id', $message['message']['chat']['id'])
                   ->countAllResults() > 0) {
            return FALSE;
        }
        $params['parse_mode'] = "HTML";
        return $this->sendMessage($message['message']['chat']['id'], "<code>" . $file_id."</code>", $params);
    }

    /*
     * Команда - получить chat_id
     * 
     * /chat_id 1
     */

    protected function chat_id($message) {
        if (isset($message['channel_post'])) {
            return $this->sendMessage($message['channel_post']['chat']['id'], "ID канала: " . $message['channel_post']['chat']['id']);
        }
        return $this->sendMessage($message['message']['chat']['id'], "ID чата: " . $message['message']['chat']['id']);
    }
    protected function chatid($message) {
        return $this->chat_id($message);
    }

    /*
    Очистка буфера
     */
    protected function clear($message) {
        return $this->db()->table('bufer')->delete(['chat_id' => $message['message']['chat']['id']]);
    }

    /*
     * Открываем запись данных
     * @example $this->start_set($field, $message, [$id_computer, $id_book]);
     * @params string $method - метод который будет вызван после записи
     */

    protected function start_set(string $method, array $message, $value = FALSE) {
        $this->clear($message);

        $count = $this->db()->table('bufer')
                   ->where('name', $method)
                   ->where('chat_id', $message['message']['chat']['id'])
                   ->countAllResults();

        if ($count <= 0) {
            $value = !$value ? NULL : json_encode($value);
            return $this->db()->table('bufer')
                       ->insert(['value' => $value, 'name' => $method, 'chat_id' => $message['message']['chat']['id']]);
        }

        return TRUE;
    }

    /*
    Сохраняем данные для буфера обмена
     */
    private function record(array $message) {
        if (!isset($message['message']['chat']['id'])) {
            return FALSE;
        }

        $count = $this->db()->table('bufer')
                   ->where('chat_id', $message['message']['chat']['id'])
                   ->countAllResults();

        if ($count <= 0) { //если нет ничего в буфере
            if (!isset($message['message']['text'])) { //это файл
                //выводим код файла
                return $this->file_id_code($message);
            }
            return FALSE;
        }

        $bufer = $this->db()->table('bufer')
                   ->where('chat_id', $message['message']['chat']['id'])
                   ->get()
                   ->getRowArray();

        $this->clear($message);

        if (!method_exists(get_class($this), $bufer['name'])) {
            return FALSE; //такого метода нет
        }

        if (!empty($bufer['value'])) {
            $message['params'] = json_decode($bufer['value']);
            if (!is_array($message['params'])) {
                $message['params'] = (array) $message['params'];
                array_unshift($message['params'], 0);
                unset($message['params'][0]);
            }
        }

        $this->{$bufer['name']}($message);
        return TRUE;
    }

    /*
     * Получаем id файла
     * @params bool $need - TRUE - проверять тип файла
     */
    protected function file_id($message, $need = FALSE) {

        if ($need !== FALSE AND !isset($message['message'][$need])) {
            return FALSE;
        }

        if (isset($message['message']['photo'])) {
            foreach ($message['message']['photo'] as $data_photo) {
                $file_id = $data_photo['file_id'];
            }
            return $file_id;
        } else if (isset($message['message']['document'])) {
            return $message['message']['document']['file_id'];
        } else if (isset($message['message']['video'])) {
            return $message['message']['video']['file_id'];
        } else if (isset($message['message']['audio'])) {
            return $message['message']['audio']['file_id'];
        } else if (isset($message['message']['voice'])) {
            return $message['message']['voice']['file_id'];
        } else if (isset($message['message']['video_note'])) {
            return $message['message']['video_note']['file_id'];
        }

        return FALSE;
    }

    /*
    Если не известная команда
     */
    protected function notKnow($message) {
        if (!isset($message['message']['chat']['id']) OR $message['message']['chat']['id'] <= 0 ) {
            return FALSE;
        }
        $params = $this->MenuModel->get($message);
        return $this->sendMessage($message['message']['chat']['id'], "Нет такой команды", $params);
    }

    /*
    Добавляем канал в БД
    Наличие прав на приглашение/бан пользователей
     */
    protected function addChannel($message) {
        if (!isset($message['message']['chat']['id']) OR $message['message']['chat']['id'] <= 0 ) {
            return FALSE;
        }

        $data_bot = $this->getMe()->result;

        //проверяем является ли бот администратором канала с правом "добавлять пользователей"
        $data_in_chat = $this->getChatMember($message['message']['forward_from_chat']['id'], $data_bot->id);

        if ($data_in_chat == FALSE  OR !isset($data_in_chat->can_invite_users) OR $data_in_chat->can_invite_users <= 0 OR $data_in_chat->can_restrict_members <= 0) {
            return $this->sendMessage($message['message']['chat']['id'], "Бот должен иметь право <strong>Добавление подписчиков</strong> в канале ".$message['message']['forward_from_chat']['title'].", но такой галочки нет!");
        }

        if (!isset($data_in_chat->status) OR $data_in_chat->status <> "administrator") {
            return $this->sendMessage($message['message']['chat']['id'], "Бот должен иметь статус <strong>Администратор</strong> в канале ".$message['message']['forward_from_chat']['title'].", а имеет статус ".$data_in_chat->status);
        }

        //добавляем в БД
        $this->db = \Config\Database::connect();
        
        if (!$this->db->tableExists('channels')) {
            return FALSE;
        }

        //удаляем не актуальные ссыкли
        $channels = $this->db->table('channels')->get()->getResultArray();
        foreach ($channels as $channel) {
            if (!$data_in_chat = $this->getChatMember($channel['channel_id'], $data_bot->id)) {
                $this->db->table('channels')->delete(['channel_id' => $channel['channel_id']]);
            }
        }

        //если такой уже есть - то обновляем его title
        $db = $this->db->table('channels');
        $db->where('channel_id', $message['message']['forward_from_chat']['id']);
        if ($db->countAllResults() > 0) {
            $this->db->table('channels')
            ->where('channel_id', $message['message']['forward_from_chat']['id'])
            ->update(['title' => json_encode($message['message']['forward_from_chat']['title']) ]);
            return $this->sendMessage($message['message']['chat']['id'], "Название канала ".$message['message']['forward_from_chat']['title']." обновлено в базе бота!");
        } else {
            $this->db->table('channels')->insert(['id_admin' => $message['message']['chat']['id'], 'created' => date("Y-m-d H:i:s"),'channel_id' => $message['message']['forward_from_chat']['id'], 'title' => json_encode($message['message']['forward_from_chat']['title']) ]);
            return $this->sendMessage($message['message']['chat']['id'], "Канал ".$message['message']['forward_from_chat']['title']." добавлен в базу бота!");
        }
    }

    /*
    Добавляем чат в БД
    Наличие прав на приглашение/бан пользователей
     */
    protected function addChat($message) {
        $data_bot = $this->getMe()->result;

        if (trim($message['message']['text']) <> "@".$data_bot->username) {
            return FALSE;
        }

        $data_user_chat = $this->getChatMember($message['message']['chat']['id'], $message['message']['from']['id']);
        if (!isset($data_user_chat->status) OR ($data_user_chat->status <> "administrator" AND $data_user_chat->status <> "creator")) {
            return $this->sendMessage($message['message']['from']['id'], "Вы должны иметь статус <strong>Администратор</strong> или <strong>Создатель</strong> в групповом чате ".$message['message']['chat']['title'].", а имеете статус ".$data_user_chat->status);
        }

        //проверяем является ли бот администратором канала с правом "добавлять пользователей"
        $data_in_chat = $this->getChatMember($message['message']['chat']['id'], $data_bot->id);

        if ($data_in_chat == FALSE AND isset($data_in_chat->can_invite_users)) {
            if ($data_in_chat->can_invite_users <= 0) {
                return $this->sendMessage($message['message']['from']['id'], "Бот должен иметь право <strong>Пригласительные ссылки</strong> в групповом чате ".$message['message']['chat']['title'].", но такой галочки нет!");
            } else if ($data_in_chat->can_restrict_members <= 0){
                return $this->sendMessage($message['message']['from']['id'], "Бот должен иметь право <strong>Блокировка участников</strong> в групповом чате ".$message['message']['chat']['title'].", но такой галочки нет!");
            }
        }

        if (!isset($data_in_chat->status) OR $data_in_chat->status <> "administrator") {
            return $this->sendMessage($message['message']['from']['id'], "Бот должен иметь статус <strong>Администратор</strong> в групповом чате ".$message['message']['chat']['title'].", а имеет статус ".$data_in_chat->status);
        }

        //добавляем в БД
        $this->db = \Config\Database::connect();

        if (!$this->db->tableExists('channels')) {
            return FALSE;
        }

        //удаляем не актуальные ссыкли
        $channels = $this->db->table('channels')->get()->getResultArray();
        foreach ($channels as $channel) {
            if (!$data_in_chat = $this->getChatMember($channel['channel_id'], $data_bot->id)) {
                $this->db->table('channels')->delete(['channel_id' => $channel['channel_id']]);
            }
        }

        //если такой уже есть - то обновляем его title
        $db = $this->db->table('channels');
        $db->where('channel_id', $message['message']['chat']['id']);
        if ($db->countAllResults() > 0) {
            $this->db->table('channels')
            ->where('channel_id', $message['message']['chat']['id'])
            ->update(['title' => json_encode($message['message']['chat']['title']) ]);
            return $this->sendMessage($message['message']['from']['id'], "Название группового чата ".$message['message']['chat']['title']." обновлено в базе бота!");
        } else {
            $this->db->table('channels')->insert(['id_admin' => $message['message']['from']['id'], 'type' => 'group', 'created' => date("Y-m-d H:i:s"), 'channel_id' => $message['message']['chat']['id'], 'title' => json_encode($message['message']['chat']['title']) ]);
            return $this->sendMessage($message['message']['from']['id'], "Групповой чат ".$message['message']['chat']['title']." добавлен в базу бота!");
        }
    }

    /*
     * Определяем команду по тексту кнопки
     */

    private function find_comand(string $text) {

        //определяем id кнопки по названию с учетом языков
        if (!$id_button = $this->LangModel->id_button($text, $this->lang)) {
            return FALSE;
        }

        //находим команду кнопки
        $button = $this->db()
                ->table('menu_buttons')
                ->where('id', $id_button)
                ->limit(1)
                ->select('comand')
                ->get()
                ->getRowArray();

        if (empty($button['comand'])) {
            return FALSE;
        }

        $params = [];
        $command_mas = explode(" ", $button['comand']);
        if (count($command_mas) > 1) {
            $button['comand'] = $command_mas[0];
            unset($command_mas[0]);
            $params = $command_mas;
        }

        return [
            'comand' => $button['comand'],
            'params' => $params
        ];
    }

    /*
    Извлекаем данные которые пришли на вебхук
     */
    public function extractData() {
        $message = json_decode(file_get_contents('php://input'), TRUE);
        
        $command = NULL;
        $is_callback = FALSE;
        $update_id = $message['update_id'];
        if (isset($message['callback_query'])) { //если это команда от inline клавиатуры            
            if (isset($message['chat_instance'])) {
                $chat_instance = $message['chat_instance'];
            }
            $command = str_replace("/", "", $message['callback_query']['data']); //текст команды
            $message = $message['callback_query'];

            $command_mas = explode(" ", $command);
            if (count($command_mas) > 1) { //если есть параметры у команды
                $command = $command_mas[0];
                unset($command_mas[0]);
                $message['params'] = $command_mas;
            }

            $is_callback = TRUE; //помечаем что вызвано через callback
            if (isset($chat_instance)) {
                $message['chat_instance'] = $chat_instance;
            }
        } else if (isset($message['message']['entities'][0]['type']) AND $message['message']['entities'][0]['type'] == 'bot_command') {
            $command = str_replace("/", "", $message['message']['text']); //или команда от обычной клавиатуры
            //если в команде есть параметры
            $command_mas = explode(" ", $command);
            if (count($command_mas) > 1) { //если есть параметры у команды
                $command = $command_mas[0];
                unset($command_mas[0]);
                $message['params'] = $command_mas;
            }
        }

        $message['is_callback'] = $is_callback;
        $message['update_id'] = $update_id;

        //!!
        // return $this->sendMessage($message['message']['chat']['id'], "Отключен до оплаты!");

        //для добавления/обновления группового чата в базу бота - написать @username бота в групповом чате
        if (isset($message['message']['chat']['id']) AND $message['message']['chat']['id'] < 0) {
            if (isset($message['message']['text']) AND !empty($message['message']['text'])) {
                if (isset($message['message']['entities'][0]['type']) AND $message['message']['entities'][0]['type'] == "mention") {
                    if ($this->addChat($message)) {
                        return FALSE;
                    }
                }
            }
        }

        //если боту переслали ссылку с приватного канала - добавляем этот канал в БД
        if (isset($message['message']['forward_from_chat']) AND $message['message']['forward_from_chat']['type'] == "channel") {
            return $this->addChannel($message);
        }

        //получить id канала
        if (isset($message['channel_post']) AND $message['channel_post']['chat']['type'] == "channel") {
            if (isset($message['channel_post']['text']) AND (trim($message['channel_post']['text']) == "/chatid" OR trim($message['channel_post']['text']) == "/chat_id")) {
                $this->chatid($message);
                return FALSE;
            }
        }

        //определяем id пользователя
        $this->set_id($message);

        //определяем язык пользователя
        $this->lang();

        //обновить данные пользователя если такой есть
        $this->update_userdata($message);

        //запрет отвечать на слова в чате групповом
        if (isset($message['message']['chat']['id'])) {
            if ($message['message']['chat']['id'] < 0 AND empty($command)) { //если кто то пишет в чате 
                $count = $this->db()->table('bufer')->where('chat_id', $message['message']['chat']['id'])->countAllResults();
                if ($count <= 0) {
                    return FALSE; //и не открыт буфер записи для этого чата
                }
            }
        }

        // log_message('error', print_r($message,TRUE));

        //если это команда которая есть в этой модели       
        if (!empty($command) AND method_exists(get_class($this), $command)) {
            $this->{$command}($message); //вызываем метод команды   
            return FALSE;            
        }

        // если нажата одна из кнопкок и отправлена русская команда
        if (isset($message['message']['text'])) {
            $find_command = $this->find_comand($message['message']['text']);
            if ($find_command !== FALSE) {
                if (method_exists(get_class($this), $find_command['comand'])) {
                    if (count($find_command['params']) > 0) {
                        $message['params'] = $find_command['params'];
                    }
                    return $this->{$find_command['comand']}($message); //вызываем метод команды  
                }
            }
        }

        //записываем данные текста или файла
        if ($this->record($message)) {
            return FALSE;
        }

        return [
            'message' => $message,
            'command' => $command
        ];
    }

}
