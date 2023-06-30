<?php
/**
 * Name:    Модель для конкретного бота
 * Индивидуальные функции данного бота
 *
 * Created:  03.04.2020
 *
 * Description:  
 *
 * Requirements: PHP 7.2 or above
 *
 * @author     Krotov Roman <tg: @KrotovRoman>
 */

namespace App\Models;
use CodeIgniter\Model;
use \CodeIgniter\Database\ConnectionInterface;

/**
 * Class TelegramModel дочерний от TgModel
 */
class TelegramModel extends \Telegram\Models\TgModel
{	
    /*
    Выбран заказ в истори
     */
    public function order($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $id_order = (int) $message['params'][2];
        $this->OrderModel = new \Orders\Models\OrderModel();
        $data_order = $this->OrderModel->get($id_order);

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "" : "@".$data_user['username'];
            
        $data_user['id_order'] = $data_order['id'];
        $data_user['products'] = "";
        $products = $this->OrderModel->products($id_order);
        $i = 0;
        foreach ($products as $product) {
            if ($i > 0) {
                $data_user['products'].="\n";
            }
            $data_user['products'].=$product['name'].' ('.$product['count'].')';
            $i++;
        }

        $data_order = array_merge($data_order, $data_user);

        $data_order['call_whatsapp'] = $data_order['call_whatsapp'] > 0 ? "позвонить в whatsapp" : "";
        $items_in_order = $this->OrderModel->items_in_order($id_order);
        $data_order['count'] = count($items_in_order);
        $data_order['items'] = $this->OrderModel->items_in_order_text($id_order);

        $data_order['sum_total'] = $data_order['sum'] + $this->delivery_price_fix;

        $data_page = $this->PagesModel->page(139, $message, $data_order);
        $params = $this->MenuModel->history_order($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
    }
    /*
    История заказов пользователя
     */
    public function history($message) {
        if ($this->activation($message)) {
            return FALSE;
        }

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "" : "@".$data_user['username'];
        $data_page = $this->PagesModel->page(138, $message, $data_user);
        $params = $this->MenuModel->history($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        if (isset($message['params'])) {
            return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
        }
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    При успешной оплате через яндекс кассу
    
    @docs https://core.telegram.org/bots/api#successfulpayment
     */
    public function successful_payment($message) {
        $this->YookassaModel = new \Yookassa\Models\YookassaModel();
        if (!$this->YookassaModel->successful_payment($message)) {
            return $this->answerPreCheckoutQuery($message['pre_checkout_query']['id'], "Не удалось провести оплату!", FALSE);
        }
        
        return TRUE;
    }

    /*
    Отвечаем на запрос оплаты
    @docs https://core.telegram.org/bots/api#answerprecheckoutquery
     */
    public function pre_checkout_query($message) {
        $this->YookassaModel = new \Yookassa\Models\YookassaModel();
        if (!$this->YookassaModel->pre_checkout($message)) {
            return $this->answerPreCheckoutQuery($message['pre_checkout_query']['id'], "Не удалось провести оплату!", FALSE);
        }
        return $this->answerPreCheckoutQuery($message['pre_checkout_query']['id']);
    }

    /*
    Отправить счет на оплату через Яндекс Кассу 
    форма оплаты внутри самого бота

    Когда пользователь подтвердит платёж, Telegram пришлёт вам webhook с Update, 
    который содержит объект PreCheckoutQuery. 
    На этот запрос нужно ответить в течение 10 секунд, вызвав метод answerPreCheckoutQuery.

     */
    public function ya_invoice($message) {
        if (!isset($message['params'])) {
            return FALSE;
        }
        $id_order = (int) $message['params'][1];
        $this->YookassaModel = new \Yookassa\Models\YookassaModel();
        $params = $this->YookassaModel->generate_check($id_order);
        if (is_string($params)) {
            return $this->answerCallbackQuery($message['id'], $params);
        }
        $return = $this->sendInvoice($message['message']['chat']['id'], $params);
        if (!$return->ok) {
            return $this->answerCallbackQuery($message['id'], $return->description, TRUE);
        }
        return TRUE;
    }

     /*
    Получаем файл
     */
    public function file_product($message) {
        if (!$file_id = $this->file_id($message, 'document')) {//не верный тип файла
            $text = "ОШИБКА: Не верный тип файла, нужен документ!";
            $params = $this->MenuModel->get($message);
            $this->sendMessage($message['message']['chat']['id'], $text, $params);

            return $this->upload($message);
        }

        //сохраняем файл на сервере
        if (!$src = $this->getFile($file_id, "xls")) {
            $text = "ОШИБКА: Не верный тип файла, нужен документ с расширением .xls!";
            $params = $this->MenuModel->get($message);
            $this->sendMessage($message['message']['chat']['id'], $text, $params);

            return $this->upload($message);
        }

        $this->sendChatAction($message['message']['chat']['id'], 'document');

        //парсим его и извелкаем данные
        $this->ProductModel = new \Products\Models\ProductModel();
        $count = $this->ProductModel->parsing($src);

        $text = "Файл успешно обработан! Добавлено: ".$count;
        $params = $this->MenuModel->get($message);
        $this->sendMessage($message['message']['chat']['id'], $text, $params);
        
        unlink($src);
        return TRUE;
    }

    /*
    Команда в боте /import
    Для загрузки файла с пользователями которые уже известны ранее
     */
    public function import($message) {
        if (!$this->ionAuth->isAdmin($message['message']['chat']['id'])) {
            return $this->start($message);
        }
        
        $this->start_set("file_product", $message);
        $text = "Отправьте файл .xls с данными пользователей, которых вы хотите внести в БД:";
        $params = $this->MenuModel->get($message);
        return $this->sendMessage($message['message']['chat']['id'], $text, $params);
    }

    /*
    Получили номер заказа который ищем
     */
    public function text_find_order($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);
        $this->OrderModel = new \Orders\Models\OrderModel();
        $id_order = (int) $message['message']['text'];
        $order = $this->OrderModel->get($id_order);

        if (!isset($order['id']) OR $order['chat_id'] <> $message['message']['chat']['id']) { //заказ не найден!
            $data_page = $this->PagesModel->page(121, $message);
            $params = $this->MenuModel->get($message);
            $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
            return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
        }

        $data_page = $this->PagesModel->page(120, $message, $order);
        $params = $this->MenuModel->get($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Поиск по номеру заказа
     */
    public function find_order($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $data_page = $this->PagesModel->page(119, $message, $data_user);
        $this->start_set("text_find_order", $message);
        $params = $this->MenuModel->get($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Команда в боте /upload
    Для загрузки файла с пользователями которые уже известны ранее
     */
    public function upload($message) {
        if (!$this->ionAuth->isAdmin($message['message']['chat']['id'])) {
            return $this->start($message);
        }
        
        $this->start_set("file_users", $message);
        $text = "Отправьте файл .xlsx с данными заказов, которые вы хотите внести в БД:";
        $params = $this->MenuModel->get($message);
        return $this->sendMessage($message['message']['chat']['id'], $text, $params);
    }

    /*
    Получаем файл
     */
    public function file_users($message) {
        if (!$file_id = $this->file_id($message, 'document')) {//не верный тип файла
            $text = "ОШИБКА: Не верный тип файла, нужен документ!";
            $params = $this->MenuModel->get($message);
            $this->sendMessage($message['message']['chat']['id'], $text, $params);

            return $this->upload($message);
        }

        //сохраняем файл на сервере
        if (!$src = $this->getFile($file_id, "xlsx")) {
            $text = "ОШИБКА: Не верный тип файла, нужен документ с расширением .xlsx!";
            $params = $this->MenuModel->get($message);
            $this->sendMessage($message['message']['chat']['id'], $text, $params);

            return $this->upload($message);
        }

        $this->sendChatAction($message['message']['chat']['id'], 'document');

        //парсим его и извелкаем данные
        $this->OrderModel = new \Orders\Models\OrderModel();
        $count = $this->OrderModel->parsing($src);

        $text = "Файл успешно обработан! Добавлено: ".$count;
        $params = $this->MenuModel->get($message);
        $this->sendMessage($message['message']['chat']['id'], $text, $params);
        // log_message('error',$src);
        unlink($src);
        return TRUE;
    }
    
    /*
    Получили название /артикул товара
     */
    public function text_find($message) {
        if ($this->activation($message) OR !isset($message['message']['text'])) {
            return FALSE;
        }
        $this->ProductModel = new \Products\Models\ProductModel();
        $text = trim($message['message']['text']);
        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        if (!$products = $this->ProductModel->find_product($text)) {
            $this->start_set("text_find", $message);
            $data_page = $this->PagesModel->page(118, $message, $data_user);
            $params = $this->MenuModel->find($message);
            $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
            return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
        }

        $count = count($products);

        $id_category = 0;
        $offset = isset($message['params'][2]) ? (int) $message['params'][2] : 0;

        $i = 0;
        foreach ($products as $product) {
            $i++;
            $message['params'][1] = $product['id'];
            $message['params'][2] = $id_category;
            $message['params'][3] = $i == $count;
            $message['params'][5] = $offset;
            if ($i > 10) {
                continue;
            }
            $this->product($message);
        }
    }

    /*
    Поиск по названию/артикулу
     */
    public function find($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $data_page = $this->PagesModel->page(117, $message, $data_user);
        $this->start_set("text_find", $message);
        $params = $this->MenuModel->find($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
    }

    /*
    Нажал кнопку "больше фото"
    отправляем альбом фото
     */
    public function photos($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $id_item = isset($message['params'][6]) ? (int) $message['params'][6] : 0;

        $this->ProductModel = new \Products\Models\ProductModel();
        $files = $this->ProductModel->albom($id_item);
        if (count($files) <= 0) {
            $data_page = $this->PagesModel->page(114, $message);
            return $this->answerCallbackQuery($message['id'], $data_page['text']); 
        }
        $params = [];
        $params['reply_to_message_id'] = $message['message']['message_id'];
        return $this->sendMediaGroup($message['message']['chat']['id'], $files, $params);
    }

    /*
    Сохраняем адрес
     */
    public function text_address($message) {
        $this->OrderModel = new \Orders\Models\OrderModel();
        $id_order = $this->OrderModel->active($message['message']['chat']['id']);

        $oders_data['id'] = $id_order;

        if (!empty($this->yamap) AND isset($message['message']['location']['latitude']) AND isset($message['message']['location']['longitude'])) {
            $this->YandexgeoModel = new \Yandexgeo\Models\YandexgeoModel();
            if (!$address_data = $this->YandexgeoModel->to_text($message['message']['location']['longitude'], $message['message']['location']['latitude'])) {
                return $this->enter_address($message);
            }
            $address = isset($address_data['formatted_address']) ? $address_data['formatted_address'] : NULL;

            $oders_data['area'] = isset($address_data['province']) ? $address_data['province'] : NULL;
            $oders_data['city'] = isset($address_data['city']) ? $address_data['city'] : NULL;
            $oders_data['address'] = isset($address_data['formatted_address']) ? $address_data['formatted_address'] : NULL;

            $oders_data['latitude'] = $message['message']['location']['latitude'];
            $oders_data['longitude'] = $message['message']['location']['longitude'];

        } else if (isset($message['message']['text'])){
            $oders_data['address'] = trim($message['message']['text']);
            $oders_data['city'] = $oders_data['address'];
            
            //в поле описание тоже сохраняем
            $oders_data['description'] = $oders_data['address'];
        } else {
            return $this->enter_address($message);
        }   

        //сохраняем адрес
        if ($this->OrderModel->set($oders_data)) {
            //адрес получен
            $data_order = $this->OrderModel->get($id_order);
            $data_page = $this->PagesModel->page(130, $message, $data_order);
            $params = $this->MenuModel->get($message);
            $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
            $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
        }

        //если нужно запрашивать время доставки заказа
        if ($this->need_time > 0) {
            return $this->enter_delivery_time($message);
        }
        
        return $this->select_pay_order($message, TRUE);
    }

    /*
    Сохраняем описание заказа
     */
    public function text_delivery_time($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->OrderModel = new \Orders\Models\OrderModel();
        $id_order = $this->OrderModel->active($message['message']['chat']['id']);
        $delivery_time = trim($message['message']['text']);
        
        if (!$this->OrderModel->set(['id' => $id_order, 'delivery_time' => $delivery_time])) {
            return $this->order_create($message);
        }

        return $this->select_pay_order($message, TRUE);
    }

    /*
    Введите время доставки заказа
     */
    public function enter_delivery_time($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->OrderModel = new \Orders\Models\OrderModel();
        $id_order = $this->OrderModel->active($message['message']['chat']['id']);
        $data_order = $this->OrderModel->get($id_order);
        $data_page = $this->PagesModel->page(133, $message, $data_order);
        $this->start_set("text_delivery_time", $message);
        $params = $this->MenuModel->get($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        if (isset($message['params'][1])) {
            return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
        }
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }


    /*
    Сформировать заказ - запросить данные
     */
    public function enter_address($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->OrderModel = new \Orders\Models\OrderModel();
        $id_order = $this->OrderModel->active($message['message']['chat']['id']);
        $data_order = $this->OrderModel->get($id_order);
        $data_page = $this->PagesModel->page(129, $message, $data_order);
        $this->start_set("text_address", $message);
        $params = $this->MenuModel->enter_address($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        if (isset($message['params'][1])) {
            return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
        }
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Сохраняем описание заказа
     */
    public function text_description($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->OrderModel = new \Orders\Models\OrderModel();
        $id_order = $this->OrderModel->active($message['message']['chat']['id']);
        $description = trim($message['message']['text']);
        
        if (!$this->OrderModel->set(['id' => $id_order, 'description' => $description])) {
            return $this->order_create($message);
        }
        return $this->enter_address($message);
    }

     /*
    Сохранение телефон
     */
    public function text_phone($message) {
        if ($this->activation($message)) {
            return FALSE;
        }

        $this->clear($message);
        if (isset($message['message']['contact']['phone_number'])) {
            $text = $message['message']['contact']['phone_number'];
        } else if (isset($message['message']['text'])){
            $text = trim($message['message']['text']);
        } else {
            return $this->order_create($message);
        }
        
        $this->OrderModel = new \Orders\Models\OrderModel();
        $id_order = $this->OrderModel->active($message['message']['chat']['id']);
        
        if (!$this->OrderModel->set(['id' => $id_order, 'phone' => $text])) {
            return $this->order_create($message);
        }

        //сохранить телефон
        $this->db->table('users')->update(['phone' => $text], ['chat_id' => $message['message']['chat']['id']]);

        //данные сохранены
        $this->saved($message);

        return $this->enter_address($message);
    }

    /*
    данные сохранены
     */
    public function saved($message) {
        $data_page = $this->PagesModel->page(132, $message);
        $params = $this->MenuModel->keyboard_remove();
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Выбрать способ оплаты
     */
    public function select_pay_order($message, $new = FALSE) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->ModModel = new \Mods\Models\ModModel();
        $this->OrderModel = new \Orders\Models\OrderModel();
        $this->PayModel = new \Pays\Models\PayModel();
        $id_order = $this->OrderModel->active($message['message']['chat']['id']);
        
        $data_order = $this->OrderModel->get($id_order);
        if (!isset($data_order['id'])) {
            return $this->start($message);
        }

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $data_order = array_merge($data_order, $data_user);
        $data_order['id_order'] = $id_order;
        
        $data_order['call_whatsapp'] = $data_order['call_whatsapp'] > 0 ? "позвонить в whatsapp" : "";
        $items_in_order = $this->OrderModel->items_in_order($id_order);
        $data_order['count'] = count($items_in_order);
        $data_order['items'] = $this->OrderModel->items_in_order_text($id_order);

        $data_order['products'] = "";
        $products = $this->OrderModel->products($id_order);
        $i = 0;
        foreach ($products as $product) {
            if ($i > 0) {
                $data_order['products'].="\n";
            }
            $data_order['products'].=$product['name'].' ('.$product['count'].')';
            $i++;
        }

        
        $pays = $this->PayModel->items(TRUE);
        if (count($pays) <= 0) {//если не включены способы оплаты
            //запрашиваем подтверждение фомирование заказа
            $data_page = $this->PagesModel->page(134, $message, $data_order);
            $params = $this->MenuModel->confirm_finish_order($message);
        } else {
            $data_page = $this->PagesModel->page(110, $message, $data_order);
            $params = $this->MenuModel->select_pay($message);
        }
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        if ($new) {
            return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
        }
        return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
    }

    /*
    Закончить оформление заказа и отправить данные админу
     */
    public function confirm_finish_order($message) {
        if ($this->activation($message)) {
            return FALSE;
        }

        $this->OrderModel = new \Orders\Models\OrderModel();
        $this->PayModel = new \Pays\Models\PayModel();
        $id_order = $this->OrderModel->active($message['message']['chat']['id']);
        $data_order = $this->OrderModel->get($id_order);

        if (!isset($data_order['id'])) {
            return $this->answerCallbackQuery($message['id'], "Заказ не найден ".$id_order); 
        }

        $this->OrderModel->set(['id' => $id_order, 'finish' => 1]);

        $data_user = $this->ionAuth->user($data_order['chat_id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

       
        $items_in_order = $this->OrderModel->items_in_order($id_order);
        $data_order['count'] = count($items_in_order);
        $data_order['items'] = $this->OrderModel->items_in_order_text($id_order);

        $data_order['products'] = "";
        $products = $this->OrderModel->products($id_order);
        $i = 0;
        foreach ($products as $product) {
            if ($i > 0) {
                $data_order['products'].="\n";
            }
            $data_order['products'].=$product['name'].' ('.$product['count'].')';
            $i++;
        }

        $data_user = array_merge($data_user, $data_order);

        $data_page = $this->PagesModel->page(136, $message, $data_user);
        $params = [];
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
        
        //если в заказе указаны координаты то их отправлем тоже
        if ($data_order['longitude'] > 0 AND $data_order['latitude'] > 0) {
            $this->sendLocation($this->support_chat_id, $data_order['latitude'], $data_order['longitude']);
        }

        //отправляем файл в чат админов
        $data_page = $this->PagesModel->page(137, $message, $data_user);
        $params = $this->MenuModel->file_check($message, $id_order);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($this->support_chat_id, $data_page['text'], $params);
    }

    /*
    Отменить действие
     */
    public function cancel($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);

        $data_page = $this->PagesModel->page(135, $message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);

        return $this->start($message);
    }

    /*
    Сформировать заказ - запросить данные
     */
    public function order_create($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->OrderModel = new \Orders\Models\OrderModel();
        $id_order = $this->OrderModel->active($message['message']['chat']['id']);

        if (isset($message['params'][1])) {
            $call_whatsapp = (bool) $message['params'][1];
            if ($this->OrderModel->set(['id' => $id_order, 'call_whatsapp' => $call_whatsapp])) {
                $id_page = $call_whatsapp ? 111 : 112;
                $data_page = $this->PagesModel->page($id_page, $message);
                $this->answerCallbackQuery($message['id'], $data_page['text']); 
            }
        }

        $data_order = $this->OrderModel->get($id_order);
        $data_page = $this->PagesModel->page(109, $message, $data_order);
        $this->start_set("text_phone", $message);
        $params = $this->MenuModel->phone($message); //order_create($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        if (isset($message['params'][1])) {
            return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
        }
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Удалить единицу товара из корзину
     */
    public function cart_delele($message) {
        if ($this->activation($message)) {
            return FALSE;
        }

        $id_order_item = isset($message['params'][1]) ? (int) $message['params'][1] : FALSE;
        $this->ProductModel = new \Products\Models\ProductModel();
        $this->OrderModel = new \Orders\Models\OrderModel();

        $order_item = $this->OrderModel->get_order_item($id_order_item);
        if (!isset($order_item['id_product'])) {
            $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
            unset($message['params']);
            return $this->cart($message);
        }

        log_message('error', print_r('cart_delele',TRUE));
        log_message('error', print_r($id_order_item,TRUE));
        
        $data = [];
        $data['id_product'] = $order_item['id_product'];
        $data['id_item'] = $order_item['id_item'];
        $data['chat_id'] = $message['message']['chat']['id'];
        if ($id_order = $this->OrderModel->delete_cart($data)) {
            $data_page = $this->PagesModel->page(107, $message);
            $this->answerCallbackQuery($message['id'], $data_page['text']);
        }

        $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
        unset($message['params']);
        return $this->cart($message);
    }

    /*
    Корзина
     */
    public function cart($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->ProductModel = new \Products\Models\ProductModel();
        $this->ModModel = new \Mods\Models\ModModel();
        $this->OrderModel = new \Orders\Models\OrderModel();
        $this->clear($message);

        $id_order = $this->OrderModel->active($message['message']['chat']['id']);
        $order = $this->OrderModel->get($id_order);

        $offset = $this->OrderModel->offset_cart($message);
        $items_in_cart = $this->OrderModel->items_in_cart($message['message']['chat']['id']);
        if ($items_in_cart <= 0) { //корзина пуста
            $data_page = $this->PagesModel->page(108, $message);
            $params = $this->MenuModel->get($message);
            $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
            return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
        }

        $data_item = $this->OrderModel->cart($message['message']['chat']['id'], $offset);
        $data_item['mod_items'] = isset($data_item['id_item']) ? $this->ModModel->mods_item_string($data_item['id_item']) : "";
        
        $data_item['offset'] = $offset + 1;
        $data_item['count_in_cart'] = $items_in_cart;
        $data_item['sum_cart'] = $order['sum']; 

        $data_page = $this->PagesModel->page(104, $message, $data_item);
        $params = $this->MenuModel->cart($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        if (isset($message['params'][1])) {
            if (!empty($data_item['file_id'])) {
                $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
                $params['caption'] = $data_page['text'];
                return $this->sendFile($message['message']['chat']['id'], $data_item['file_id'], $params);
            }
            $return = $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
            if (!isset($return->ok) OR !$return->ok) {
                $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
            } else {
                return TRUE;
            }
        }

        if (!empty($data_item['file_id'])) {
            $params['caption'] = $data_page['text'];
            return $this->sendFile($message['message']['chat']['id'], $data_item['file_id'], $params);
        }
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Подписаться на уведомления об акциях или новинках
     */
    public function select_subscribe($message) {
        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $id_product = isset($message['params'][1]) ? (int) $message['params'][1] : FALSE;
        if ($id_product !== FALSE) {
            $this->PostsModel = new \Sender\Models\PostsModel();
            if (isset($message['params'][2])) {
                $id_product2 = (int) $message['params'][2];
                $this->PostsModel->set_subscribe($message['message']['chat']['id'], [$id_product, $id_product2]);
            } else {
                $this->PostsModel->set_subscribe($message['message']['chat']['id'], [$id_product]);
            }

            $data_page = $this->PagesModel->page(100, $message, $data_user);
            $this->answerCallbackQuery($message['id'], $data_page['text']); 

            $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
            return $this->start($message);
        }

        $data_page = $this->PagesModel->page(99, $message, $data_user);
        $params = $this->MenuModel->select_subscribe($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Укажите пожалуйста свой европейский размер
     */
    public function select_size($message) {
        $id_mod = isset($message['params'][1]) ? (int) $message['params'][1] : FALSE;
        $this->ModModel = new \Mods\Models\ModModel();

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        if ($id_mod) {
            $data_mod = $this->ModModel->mod($id_mod);
            $size = floatval($data_mod['name']);
            if ($size > 0 AND $this->db->table('users')->update(['size' => $size], ['chat_id' => $message['message']['chat']['id']])) {
                $data_page = $this->PagesModel->page(98, $message, $data_user);
                $this->answerCallbackQuery($message['id'], $data_page['text']);
                
                $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
                return $this->start($message);
            }
        }

        $data_page = $this->PagesModel->page(97, $message, $data_user);
        $params = $this->MenuModel->select_size($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Получили сумму пополнения
     */
    public function text_sum_balance($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->OrderModel = new \Orders\Models\OrderModel();
        $this->BalanceModel = new \Balance\Models\BalanceModel();

        $sum = (int) $message['message']['text'];
        $id_order = $this->OrderModel->active($message['message']['chat']['id']);

        $order = $this->OrderModel->get($id_order);

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
        
        $balance = $this->BalanceModel->get($message['message']['chat']['id']);
        $data_user['balance'] = number_format($balance, $this->decimals, ',', ' ');

        $data_user['sum'] = number_format($sum, $this->decimals, ',', ' ');

        if ($sum <= 0 OR $sum > $order['sum'] OR $sum > $balance OR ($this->min_balance_add > 0 AND $sum < $this->min_balance_add)) {
            
            $data_user['sum_order'] = $order['sum'];
            $data_page = $this->PagesModel->page(78, $message, $data_user);
            $params = [];
            $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
            $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);

            $message['params'][2] = "delete";
            return $this->balance($message);
        }

        //списываем с баланса и применяем к сумме заказа скидку
        if (!$this->BalanceModel->have_balance($message['message']['chat']['id'], 'id_order', $id_order)) {
            //списываем с баланса
            $data = [];
            $data['chat_id'] = $message['message']['chat']['id'];
            $data['value'] = -$sum;
            $data['finish'] = 1;
            $data['id_order'] = $id_order;
            $data['comment'] = "За заказ №".$id_order;
            $data['type'] = "balance";
            $data['currency'] = $this->currency_cod;
            if ($id_trans = $this->BalanceModel->add($data)) {
                //применяем скидку в заказе
                if ($this->OrderModel->recount_sum_order($id_order, FALSE, $sum)) {
                    //уведомляем что деньги списаны с баланса
                    $data_page = $this->PagesModel->page(127, $message, $data_user);
                    $params = $this->MenuModel->get($message);
                    $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
                    $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
                }
            }
        }

        //выдаем выбор способов оплаты
        return $this->select_pay_order($message, TRUE);
    }

    /*
    Нажал кнопку пополнить баланс
     */
    public function balance($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);
        $this->start_set("text_sum_balance", $message);
        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
        
        $data_page = $this->PagesModel->page(54, $message, $data_user);
        $params = $this->MenuModel->btn_main($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        if (isset($message['params'][2]) AND $message['params'][2] == "delete") {
            $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
            return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
        }
        return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
    }

    /*
    Нажал кнопку мои покупки
     */
    public function my_buyes($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);
        
        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
        
        $data_page = $this->PagesModel->page(77, $message, $data_user);
        $params = $this->MenuModel->my_buyes($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];

        if (isset($message['params'][2]) AND $message['params'][2] == "delete") {
            $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
            return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
        }
        return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
    }

    /*
    Скачать файл с купленными аккаунтами в текстовом виде
     */
    public function order_items($message) {
        if ($this->activation($message)) {
            return FALSE;
        }

        $id_order = (int) $message['params'][1];

        $this->OrderModel = new \Orders\Models\OrderModel();
        $this->ProductModel = new \Products\Models\ProductModel();

        $products = $this->OrderModel->products($id_order);
        $data_order = $this->OrderModel->get($id_order);
        $message['message']['chat']['id'] = $data_order['chat_id'];
        // $message['message']['chat']['id'] = 57341412;

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
        
        //генерим файл для отправки
        if (!$src = $this->ProductModel->assets_items_file($id_order)) {
            $data_page = $this->PagesModel->page(76, $message, $data_user);
            if (isset($message['id'])) {
                return $this->answerCallbackQuery($message['id'], $data_page['text']);
            } else {
                return $this->sendMessage($message['message']['chat']['id'], $data_page['text']);
            }
        }

        helper('file');
        $data_file = get_file_info($src, ['size']);
        if ($data_file['size'] <= 0) {
            $data_page = $this->PagesModel->page(76, $message, $data_user);
            if (isset($message['id'])) {
                return $this->answerCallbackQuery($message['id'], $data_page['text']);
            } else {
                return $this->sendMessage($message['message']['chat']['id'], $data_page['text']);
            }
        }

        //отправляем файл
        $data_page = $this->PagesModel->page(75, $message, $data_user);
        $params = $this->MenuModel->btn_main($message);
        $params['caption'] = $data_page['text'];
        $this->sendSrc($message['message']['chat']['id'], $src, $params, 'document');
        unlink($src);
    }

    /*
    Нажал кнопку "отправить на Email"
     */
    public function to_email($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);
        $id_order = (int) $message['params'][1];
        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
        
        $this->start_set("text_email", $message, $id_order);

        $data_page = $this->PagesModel->page(83, $message, $data_user);
        $params = $this->MenuModel->to_email($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
    }

    /*
    Получаем Email
     */
    public function text_email($message) {
        if ($this->activation($message)) {
            return FALSE;
        }

        $id_order = (int) $message['params'];
        $email_to = trim($message['message']['text']);
        helper('email');

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
        $data_user['email_to'] = $email_to;

        if (!valid_email($email_to)) {
            $this->start_set("text_email", $message, $id_order);
            $data_page = $this->PagesModel->page(85, $message, $data_user);
            $params = [];
            $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
            return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
        }

        $data_page = $this->PagesModel->page(84, $message, $data_user);
        $params = $this->MenuModel->to_email_confirm($message, $email_to);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'],  $params);
    }

    /*
    Скачать файл с купленными аккаунтами в текстовом виде
     */
    public function order_items_email($message) {
        if ($this->activation($message)) {
            return FALSE;
        }

        $id_order = (int) $message['params'][1];
        $email_to = $message['params'][2];

        $this->OrderModel = new \Orders\Models\OrderModel();
        $this->ProductModel = new \Products\Models\ProductModel();

        $products = $this->OrderModel->products($id_order);
        $data_order = $this->OrderModel->get($id_order);
        $message['message']['chat']['id'] = $data_order['chat_id'];

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
        
        //генерим файл для отправки
        if (!$src = $this->ProductModel->assets_items_file($id_order)) {
            $data_page = $this->PagesModel->page(76, $message, $data_user);
            return $this->answerCallbackQuery($message['id'], $data_page['text']);
        }

        helper('file');
        $data_file = get_file_info($src, ['size']);
        if ($data_file['size'] <= 0) {
            $data_page = $this->PagesModel->page(76, $message, $data_user);
            if (isset($message['id'])) {
                return $this->answerCallbackQuery($message['id'], $data_page['text']);
            } else {
                return $this->sendMessage($message['message']['chat']['id'], $data_page['text']);
            }
        }

        //отправляем файл
        $data_page = $this->PagesModel->page(75, $message, $data_user);
        
        //docs https://codeigniter.com/user_guide/libraries/email.html
        $email = \Config\Services::email();

        if (!empty($this->smtp_password) AND !empty($this->smtp_user) AND !empty($this->smtp_host)) {
            $config['protocol'] = 'smtp';
            $config['SMTPHost'] = $this->smtp_host;
            $config['SMTPUser'] = $this->smtp_user;
            $config['SMTPPass'] = $this->smtp_password;
            $config['SMTPPort'] = $this->smtp_port;
            $email->initialize($config);
            $email->setFrom($config['SMTPUser'], $this->name_from);
        } else {
            $email->setFrom($this->email_from, $this->name_from);
        }
        
        $email->setTo($email_to);
        $email->setSubject('Файлы заказа № '.$id_order);
        $email->setMessage($data_page['text']);
        $email->attach($src);

        if (!isset($message['id'])) {
            $message['id'] = 0;
        }

        if ($email->send()) {
            unlink($src);
            $data_page = $this->PagesModel->page(81, $message, $data_user);
            $params = [];
            $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
            return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
        }

        $data_page = $this->PagesModel->page(82, $message, $data_user);
        return $this->answerCallbackQuery($message['id'], $data_page['text']);
    }

    /*
    Подтвердить покупку
    списание и создание заказа
     */
    public function buy_confirm($message) {
        if ($this->activation($message)) {
            return FALSE;
        }

        $this->sendChatAction($message['message']['chat']['id']);

        $data_page = $this->PagesModel->page(87, $message);
        $this->answerCallbackQuery($message['id'], $data_page['text'], TRUE);

        $id_product = (int) $message['params'][1];
        $count = isset($message['params'][2]) ? (int) $message['params'][2] : 0;

        $this->ProductModel = new \Products\Models\ProductModel();
        $this->BalanceModel = new \Balance\Models\BalanceModel();

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
        
        $product = $this->ProductModel->get($id_product);
        $data_user['name'] = $product['name'];
        $data_user['price'] = $product['price'];
        $data_user['count'] = $count;
        $data_user['price_sum'] = $count * $product['price'];

        $data_user['description'] = $this->ProductModel->text($id_product);
        $data_user['product_items_count'] = $this->ProductModel->product_items_count($id_product, TRUE);

        $balance = $this->BalanceModel->get($message['message']['chat']['id']);
        $data_user['balance'] = number_format($balance, $this->decimals, ',', ' ');

        $data_user['product_items_count'] = $this->ProductModel->product_items_count($id_product, TRUE);

        if ($data_user['product_items_count'] < $count) {
            $data_page = $this->PagesModel->page(74, $message, $data_user);
            return $this->answerCallbackQuery($message['id'], $data_page['text'], TRUE);
        }

        if ($balance < $data_user['price_sum']) {
            $data_page = $this->PagesModel->page(73, $message, $data_user);
            return $this->answerCallbackQuery($message['id'], $data_page['text'], TRUE);
        }

        //создаем заказ
        $this->OrderModel = new \Orders\Models\OrderModel();

        //создаем заказ
        $data = [];
        $data['products'] = [$id_product];
        $data['chat_id'] = $message['message']['chat']['id'];
        $data['id_pay'] = 0;
        $data['finish'] = 1;
        $data['count'] = $count;
        if (!$id_order = $this->OrderModel->add($data, FALSE, TRUE)) {
            return FALSE;
        }

        //пересчитать цену с учетом закрепленного за пользователем промокода
        $this->PromoModel = new \Promo\Models\PromoModel();
        $this->PromoModel->reprice($message['message']['chat']['id']);

        $data_order = $this->OrderModel->get($id_order);

        //помечаем заказ оплаченным
        if ($data_order['status'] <> 1 AND $this->OrderModel->status($id_order)) {
            if (!$this->BalanceModel->have_balance($message['message']['chat']['id'], 'id_order', $id_order)) {
                //списываем с баланса
                $data = [];
                $data['chat_id'] = $message['message']['chat']['id'];
                $data['value'] = -$data_order['sum'];
                $data['finish'] = 1;
                $data['id_order'] = $id_order;
                $data['comment'] = "За заказ №".$id_order;
                $data['type'] = "balance";
                $data['currency'] = $this->currency_cod;
                $this->BalanceModel->add($data);
            }
        }

        $data_user = array_merge($data_user, $data_order);

        $balance = $this->BalanceModel->get($message['message']['chat']['id']);
        $data_user['balance'] = number_format($balance, $this->decimals, ',', ' ');

        $params = [];
        $data_page = $this->PagesModel->page(71, $message, $data_user);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
    }

    /*
    Получили количество
     */
    public function text_count_buy($message) {
        if ($this->activation($message)) {
            return FALSE;
        }

        $id_product = (int) $message['params'][1];
        $id_parent = isset($message['params'][2]) ? (int) $message['params'][2] : 0;
        $count = (int) $message['message']['text'];

        $this->ProductModel = new \Products\Models\ProductModel();
        $this->BalanceModel = new \Balance\Models\BalanceModel();

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
        
        $product = $this->ProductModel->get($id_product);
        $data_user['name'] = $product['name'];
        $data_user['price'] = $product['price'];
        $data_user['count'] = $count;
        $data_user['price_sum'] = $count * $product['price'];

        $data_user['description'] = $this->ProductModel->text($id_product);
        
        $data_user['product_items_count'] = $this->ProductModel->product_items_count($id_product, TRUE);

        $balance = $this->BalanceModel->get($message['message']['chat']['id']);
        $data_user['balance'] = number_format($balance, $this->decimals, ',', ' ');

        $data_user['product_items_count'] = $this->ProductModel->product_items_count($id_product, TRUE);

        if ($data_user['product_items_count'] < $count) {
            $data_page = $this->PagesModel->page(74, $message, $data_user);
            $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
            $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);

            $this->start_set("text_count_buy", $message, $message['params']);
            $params = $this->MenuModel->buy($message);
            $data_page = $this->PagesModel->page(69, $message, $data_user);
            $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
            return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
        }

        if ($balance < $data_user['price_sum']) {
            $data_page = $this->PagesModel->page(73, $message, $data_user);
            $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
            $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);

            $this->start_set("text_count_buy", $message, $message['params']);
            $params = $this->MenuModel->buy($message);
            $data_page = $this->PagesModel->page(69, $message, $data_user);
            $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
            return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
        }

        $params = $this->MenuModel->buy_confirm($message);
        $data_page = $this->PagesModel->page(70, $message, $data_user);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Нажал кнопку удалить из корзины
     */
    public function buy_del($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);
        $id_product = (int) $message['params'][1];
        $id_item = isset($message['params'][6]) ? (int) $message['params'][6] : 0;

        $this->OrderModel = new \Orders\Models\OrderModel();

        $data = [];
        $data['id_product'] = $id_product;
        $data['id_item'] = $id_item;
        $data['chat_id'] = $message['message']['chat']['id'];
        if (!$id_order = $this->OrderModel->delete_cart($data)) {
            return $this->answerCallbackQuery($message['id'], "Не удалось удалить из корзины!", TRUE);
        }

        $data_page = $this->PagesModel->page(103, $message);
        $this->answerCallbackQuery($message['id'], $data_page['text']);

        //удаляем сообщение
        $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);

        //меняем кнопку "купить" на "убрать из корзины"
        $params = $this->MenuModel->product($message);
        return $this->editMessageReplyMarkup($message['message']['chat']['id'], $message['message']['message_id'], $params);
    }

    /*
    ДОбавить в корзину
     */
    public function addcart($message) {
        if ($this->activation($message)) {
            return FALSE;
        }

        $this->clear($message);

        $this->OrderModel = new \Orders\Models\OrderModel();
        $this->ProductModel = new \Products\Models\ProductModel();
        $this->BalanceModel = new \Balance\Models\BalanceModel();

        if (!isset($message['params'])) {
            $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
            $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
            $params = $this->MenuModel->get($message);
            $data_page = $this->PagesModel->page(125, $message, $data_user);
            $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
            return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params); 
        }
        
        foreach ($message['params'] as $id_product) {
            if (!$id_item = $this->ProductModel->get_free_item($id_product)) {
                return $this->answerCallbackQuery($message['id'], 'Не удалось получить свободную единицу товара!', TRUE);
            }
            $product = $this->ProductModel->get($id_product);
            if (!isset($product['id'])) {
                $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
                $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
                $params = $this->MenuModel->get($message);
                $data_page = $this->PagesModel->page(125, $message, $data_user);
                $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
                $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
                continue; 
            }

            if ($this->need_check_busy_product > 0 AND $this->OrderModel->is_busy($message['message']['chat']['id'], $id_item)) {
                $data_page = $this->PagesModel->page(105, $message);
                $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
                $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
                continue;
            }

            //создаем заказ
            $data = [];
            $data['id_product'] = $id_product;
            $data['id_item'] = $id_item;
            $data['chat_id'] = $message['message']['chat']['id'];
            if (!$id_order = $this->OrderModel->add_cart($data)) {
                log_message('error','не удалось добавить к заказу');
                continue;
            }

            //товар добавлен в корзину
            $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
            $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
            
            $data_user['name'] = $product['name'];
            $data_user['price'] = $product['price'];

            $data_user['description'] = $this->ProductModel->text($id_product);
            $data_user['product_items_count'] = $this->ProductModel->product_items_count($id_product, TRUE);

            $balance = $this->BalanceModel->get($message['message']['chat']['id']);
            $data_user['balance'] = number_format($balance, $this->decimals, ',', ' ');

            $params = $this->MenuModel->get($message);
            $data_page = $this->PagesModel->page(124, $message, $data_user);
            $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
            $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params); 
        }
        
    }

    /*
    Нажал кнопку купить
     */
    public function buy($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);
        $id_product = (int) $message['params'][1];
        $id_item = isset($message['params'][6]) ? (int) $message['params'][6] : 0;

        $this->ProductModel = new \Products\Models\ProductModel();
        $this->OrderModel = new \Orders\Models\OrderModel();
        if ($this->need_check_busy_product > 0 AND $this->OrderModel->is_busy($message['message']['chat']['id'], $id_item)) {
            $data_page = $this->PagesModel->page(105, $message);
            return $this->answerCallbackQuery($message['id'], $data_page['text'], TRUE);
        }

        if ($id_item  <= 0 AND !$id_item = $this->ProductModel->get_free_item($id_product)) {
            return $this->answerCallbackQuery($message['id'], 'Не удалось получить свободную единицу товара!', TRUE);
        }
    
        //создаем заказ
        $data = [];
        $data['id_product'] = $id_product;
        $data['id_item'] = $id_item;
        $data['chat_id'] = $message['message']['chat']['id'];
        if (!$id_order = $this->OrderModel->add_cart($data)) {
            return FALSE;
        }

        $data_page = $this->PagesModel->page(102, $message);
        $this->answerCallbackQuery($message['id'], $data_page['text']);

        //меняем кнопку "купить" на "убрать из корзины"
        $params = $this->MenuModel->product_item($message);
        return $this->editMessageReplyMarkup($message['message']['chat']['id'], $message['message']['message_id'], $params);
    }

    /*
    Добавить сразу в корзину указанное кол-во
     */
    public function cart_add_count($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);
        $this->sendChatAction($message['message']['chat']['id']);
        
        $id_product = (int) $message['params'][1];
        $count_btn = isset($message['params'][6]) ? (int) $message['params'][6] : 1;

        $this->ProductModel = new \Products\Models\ProductModel();
        $this->OrderModel = new \Orders\Models\OrderModel();

        $i = 1;
        while ($i <= $count_btn) {
            //получить свободную единицу товара
            if (!$id_item = $this->ProductModel->get_free_item($id_product)) {
                $data_page = $this->PagesModel->page(105, $message);
                return $this->answerCallbackQuery($message['id'], $data_page['text'], TRUE);
            }
           
            if ($this->need_check_busy_product > 0 AND $this->OrderModel->is_busy($message['message']['chat']['id'], $id_item)) {
                $data_page = $this->PagesModel->page(105, $message);
                return $this->answerCallbackQuery($message['id'], $data_page['text'], TRUE);
            }

            //создаем заказ
            $data = [];
            $data['id_product'] = $id_product;
            $data['id_item'] = $id_item;
            $data['chat_id'] = $message['message']['chat']['id'];
            if (!$id_order = $this->OrderModel->add_cart($data)) {
                $data_page = $this->PagesModel->page(105, $message);
                return $this->answerCallbackQuery($message['id'], $data_page['text'], TRUE);
            }

            $i++;
        }

        $data_page = $this->PagesModel->page(102, $message);
        $this->answerCallbackQuery($message['id'], $data_page['text']);

        //меняем кнопку "купить" на "убрать из корзины"
        $params = $this->MenuModel->product($message);
        return $this->editMessageReplyMarkup($message['message']['chat']['id'], $message['message']['message_id'], $params);
    }

    /*
    Нажал кнопку купить продукт
    без выбора единиц товара
     */
    public function cart_add($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);
        $this->sendChatAction($message['message']['chat']['id']);
        
        $id_product = (int) $message['params'][1];
        
        $this->ProductModel = new \Products\Models\ProductModel();
        $this->OrderModel = new \Orders\Models\OrderModel();

        //получить свободную единицу товара
        if (!$id_item = $this->ProductModel->get_free_item($id_product)) {
            $data_page = $this->PagesModel->page(105, $message);
            return $this->answerCallbackQuery($message['id'], $data_page['text'], TRUE);
        }
       
        if ($this->need_check_busy_product > 0 AND $this->OrderModel->is_busy($message['message']['chat']['id'], $id_item)) {
            $data_page = $this->PagesModel->page(105, $message);
            return $this->answerCallbackQuery($message['id'], $data_page['text'], TRUE);
        }

        //создаем заказ
        $data = [];
        $data['id_product'] = $id_product;
        $data['id_item'] = $id_item;
        $data['chat_id'] = $message['message']['chat']['id'];
        if (!$id_order = $this->OrderModel->add_cart($data)) {
            $data_page = $this->PagesModel->page(105, $message);
            return $this->answerCallbackQuery($message['id'], $data_page['text'], TRUE);
        }

        $data_page = $this->PagesModel->page(102, $message);
        $this->answerCallbackQuery($message['id'], $data_page['text']);

        //меняем кнопку "купить" на "убрать из корзины"
        $params = $this->MenuModel->product($message);
        return $this->editMessageReplyMarkup($message['message']['chat']['id'], $message['message']['message_id'], $params);
    }

    /*
    Нажал кнопку купить продукт
    без выбора единиц товара
     */
    public function cart_del($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->sendChatAction($message['message']['chat']['id']);
        $this->clear($message);
        $id_product = (int) $message['params'][1];
        $this->ProductModel = new \Products\Models\ProductModel();
        $this->OrderModel = new \Orders\Models\OrderModel();

        if ($this->need_check_empty > 0) {
            //получить последнюю единицу товара
            if (!$id_item = $this->OrderModel->get_last_item($message['message']['chat']['id'], $id_product)) {
                $data_page = $this->PagesModel->page(105, $message);
                return $this->answerCallbackQuery($message['id'], $data_page['text'], TRUE);
            }
        }

        //удаляем из корзины
        $data = [];
        $data['id_product'] = $id_product;
        if ($this->need_check_empty > 0) {
            $data['id_item'] = $id_item;
        }
        $data['chat_id'] = $message['message']['chat']['id'];
        $id_order = $this->OrderModel->delete_cart($data);
        $data_page = $this->PagesModel->page(103, $message);
        $this->answerCallbackQuery($message['id'], $data_page['text']);

        //меняем кнопку "купить" на "убрать из корзины"
        $params = $this->MenuModel->product($message);
        return $this->editMessageReplyMarkup($message['message']['chat']['id'], $message['message']['message_id'], $params);
    }

    /*
    Выбран продукт
     */
    public function description($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);
        $id_product = (int) $message['params'][1];
        $this->ProductModel = new \Products\Models\ProductModel();
        $this->BalanceModel = new \Balance\Models\BalanceModel();

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
        
        $product = $this->ProductModel->get($id_product);
        $data_user['name'] = $product['name'];
        $data_user['price'] = $product['price'];

        $data_user['description'] = $this->ProductModel->text($id_product);
        $data_user['product_items_count'] = $this->ProductModel->product_items_count($id_product, TRUE);

        $balance = $this->BalanceModel->get($message['message']['chat']['id']);
        $data_user['balance'] = number_format($balance, $this->decimals, ',', ' ');

        $params = $this->MenuModel->product($message);
        $data_page = $this->PagesModel->page(86, $message, $data_user);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];

        if (!empty($product['file_id'])) {
            $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
            $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params); 
            return $this->answerCallbackQuery($message['id']); 
        }

        return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
    }

    /*
    Выбрал конкретную единицу товара
    отправляем фотку если есть 
     */
    public function product_item($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);
        $id_product = (int) $message['params'][1];
        $id_item = isset($message['params'][6]) ? (int) $message['params'][6] : 0;
        $this->ProductModel = new \Products\Models\ProductModel();
        $this->ModModel = new \Mods\Models\ModModel();

        $data_item = $this->ProductModel->get_item($id_item);

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
        
        $product = $this->ProductModel->get($id_product);
        if (!isset($product['id'])) {
            return FALSE;
        }
        $data_user['name'] = $product['name'];
        $data_user['price'] = $data_item['price'];
        $data_user['articul'] = $data_item['articul'];

        $data_user['description'] = $this->ProductModel->text($id_product);
        $data_user['product_items_count'] = $this->ProductModel->product_items_count($id_product, TRUE);
        $data_user['mod_items'] = $this->ModModel->mods_item_string($id_item);
        $data_page = $this->PagesModel->page(101, $message, $data_user);

        $params = $this->MenuModel->product_item($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        
        if (!empty($data_item['file_id'])) {
            if (isset($message['params'][4])) {
                $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
            }
            $params['caption'] = $data_page['text'];
            $this->sendFile($message['message']['chat']['id'], $data_item['file_id'], $params);
            return $this->answerCallbackQuery($message['id']);
        }
        $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
        if (isset($message['id'])) {
            return $this->answerCallbackQuery($message['id']);
        }
    }

    /*
    Выбран продукт
     */
    public function product($message, $product = FALSE) {
        $id_product = (int) $message['params'][1];

        $this->ProductModel = new \Products\Models\ProductModel();
        $product OR $product = $this->ProductModel->get($id_product);
        if (!isset($product['id'])) {
            return FALSE;
        }

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
        $data_user['name'] = $product['name'];
        $data_user['price'] = $product['price'];
        $data_user['sku'] = $this->ProductModel->sku($id_product);
        $data_user['description'] = $this->ProductModel->text($id_product);
        $data_user['product_items_count'] = $this->ProductModel->product_items_count($id_product, TRUE);

        //получить информацию о модификаторах которые будут добавлены
        $this->ModModel = new \Mods\Models\ModModel();
        $id_item = $this->ProductModel->get_free_item($id_product);
        $data_user['mod_items'] = $id_item ? $this->ModModel->mods_item_string($id_item) : "";

        $params = $this->MenuModel->product($message);
        $data_page = $this->PagesModel->page(68, $message, $data_user);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];

        if (empty($product['file_id'])) {
            if (isset($message['params'][4])) {
                $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
            }
        } else if (!empty($product['file_id'])) {
            if (isset($message['params'][4])) {
                $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
            }
            $params['caption'] = $data_page['text'];
            $this->sendFile($message['message']['chat']['id'], $product['file_id'], $params, 'photo');
            return TRUE;
        }
        
        if (isset($message['params'][4])) {
            $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
            return TRUE;
        }
        $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
        return TRUE;
    }

    /*
    Удалить из корзины единицу товара выбранную по свойствам
     */
    public function cart_del_item($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->ProductModel = new \Products\Models\ProductModel();
        $this->OrderModel = new \Orders\Models\OrderModel();
        $this->clear($message);

        $id_category = (int) $message['params'][1];
        $id_parent = (int) $message['params'][2];
        $id_mod = (int) $message['params'][3];
        $id_mod2 = (int) $message['params'][4];
        $id_item = (int) $message['params'][5];

        $product_item = $this->ProductModel->get_item($id_item);
        $id_product = $product_item['id_product'];

        //удаляем из корзины
        $data = [];
        $data['id_product'] = $id_product;
        $data['id_item'] = $id_item;
        $data['chat_id'] = $message['message']['chat']['id'];
        if (!$id_order = $this->OrderModel->delete_cart($data)) {
            $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
            unset($message['params']);
            return $this->cart($message);
        }

        $data_page = $this->PagesModel->page(103, $message);
        $this->answerCallbackQuery($message['id'], $data_page['text']);

        unset($message['params'][3]);
        unset($message['params'][4]);
        unset($message['params'][5]);
        
        $this->ProductModel = new \Products\Models\ProductModel();
        $data_product['product'] = $this->ProductModel->get($product_item['id_product']);
        $data_product['product_item'] = $product_item;
        $params = $this->MenuModel->select_mod($message, $data_product);
        $return = $this->editMessageReplyMarkup($message['message']['chat']['id'], $message['message']['message_id'], $params);

        if (!isset($return->ok) OR !$return->ok) {
            $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
            unset($message['params']);
            return $this->cart($message);
        }
        // return $this->select_mod($message);
    }

    /*
    Добавил в корзину единицу товара выбранную по свойствам
     */
    public function cart_add_item($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->ProductModel = new \Products\Models\ProductModel();
        $this->OrderModel = new \Orders\Models\OrderModel();
        $this->clear($message);
        $id_category = (int) $message['params'][1];
        $id_parent = (int) $message['params'][2];
        $id_mod = (int) $message['params'][3];
        $id_mod2 = (int) $message['params'][4];
        $id_item = (int) $message['params'][5];

        $product_item = $this->ProductModel->get_item($id_item);
        $id_product = $product_item['id_product'];

        if ($this->need_check_busy_product > 0 AND $this->OrderModel->is_busy($message['message']['chat']['id'], $id_item)) {
            $data_page = $this->PagesModel->page(105, $message);
            return $this->answerCallbackQuery($message['id'], $data_page['text'], TRUE);
        }

        //создаем заказ
        $data = [];
        $data['id_product'] = $id_product;
        $data['id_item'] = $id_item;
        $data['chat_id'] = $message['message']['chat']['id'];
        if (!$id_order = $this->OrderModel->add_cart($data)) {
            return FALSE;
        }

        $data_page = $this->PagesModel->page(102, $message);
        $this->answerCallbackQuery($message['id'], $data_page['text']);

        // unset($message['params'][3]);
        // unset($message['params'][4]);
        // unset($message['params'][5]);

        // $this->ProductModel = new \Products\Models\ProductModel();
        // $data_product['product'] = $this->ProductModel->get($product_item['id_product']);
        // $data_product['product_item'] = $product_item;
        $params = $this->MenuModel->added_product($message, $product_item['id_product']);
        $this->editMessageReplyMarkup($message['message']['chat']['id'], $message['message']['message_id'], $params);
    }

    /*
    Выбрал модификатор
     */
    public function select_mod($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);
        $id_category = (int) $message['params'][1];
        $id_parent = (int) $message['params'][2];
        $id_mod = (int) $message['params'][3];
        $id_mod2 = (int) $message['params'][4];

        $this->ModModel = new \Mods\Models\ModModel();
        $this->ProductModel = new \Products\Models\ProductModel();
        $category = $this->ProductModel->category($id_category);
        if (!$data_product = $this->ProductModel->category_product($id_category, $id_mod, $id_mod2)) {
            $data_page = $this->PagesModel->page(105, $message);
            return $this->answerCallbackQuery($message['id'], $data_page['text']);
        }
        $product = $data_product['product'];
        $id_product = $product['id'];

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
        $data_user = array_merge($data_user, $category);
        $data_user = array_merge($data_user, $product);
        $data_user['description'] = $this->ProductModel->text($product['id']);
        $data_user['product_items_count'] = $this->ProductModel->product_items_count_in_cat($id_category);

        $data_user['mod_items'] = $data_product['product_item'] ? $this->ModModel->mods_item_string($data_product['product_item']['id']) : "";

        $params = $this->MenuModel->select_mod($message, $data_product);
        $data_page = $this->PagesModel->page(68, $message, $data_user);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];

        if (empty($product['file_id'])) {
            if (isset($message['params'][4])) {
                $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
            }

            $params['caption'] = $data_page['text'];
            //если нет фото в базе бота
            if ($src = $this->ProductModel->url_img($id_product)) {
                $result = $this->sendSrc($message['message']['chat']['id'], $src, $params);
                if ($file_id = $this->extract_file_id($result)) {
                    $this->ProductModel->set(['id' => $id_product, 'file_id' => $file_id], TRUE);
                    return TRUE;
                }
            }
        } else if (!empty($product['file_id'])) {
            if (isset($message['params'][4])) {
                $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
            }
            $params['caption'] = $data_page['text'];
            $this->sendFile($message['message']['chat']['id'], $product['file_id'], $params);
            return TRUE;
        }
        
        // if (isset($message['params'][4])) {
        //     $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
        //     return TRUE;
        // }
        $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
        return TRUE;
    }

    /*
    У нас самая дочерняя категория это и есть продукт
     */
    public function category_product($message) {
        $this->sendChatAction($message['message']['chat']['id']);
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);
        $id_category = (int) $message['params'][1];

        $this->ModModel = new \Mods\Models\ModModel();
        $this->ProductModel = new \Products\Models\ProductModel();
        $category = $this->ProductModel->category($id_category);
        if (!$data_product = $this->ProductModel->category_product($id_category)) {
            $data_page = $this->PagesModel->page(105, $message);
            return $this->answerCallbackQuery($message['id'], $data_page['text']);
        }
        $product = $data_product['product'];
        $id_product = $product['id'];

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
        $data_user = array_merge($data_user, $category);
        $data_user = array_merge($data_user, $product);
        $data_user['description'] = $this->ProductModel->text($product['id']);
        $data_user['product_items_count'] = $this->ProductModel->product_items_count_in_cat($id_category);

        $data_user['mod_items'] = $data_product['product_item'] ? $this->ModModel->mods_item_string($data_product['product_item']['id']) : "";

        $params = $this->MenuModel->select_mod($message, $data_product);

        $data_page = $this->PagesModel->page(68, $message, $data_user);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];

        if (empty($product['file_id'])) {
            if (isset($message['params'][4])) {
                $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
            }
        } else if (!empty($product['file_id'])) {
            if (isset($message['params'][4])) {
                $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
            }
            $params['caption'] = $data_page['text'];
            $this->sendFile($message['message']['chat']['id'], $product['file_id'], $params);
            return TRUE;
        }
        
        if (isset($message['params'][4])) {
            $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
            return TRUE;
        }
        $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
        return TRUE;
    }

    /*
    Выбрал категорию с продуктами
     */
    public function products($message) {
        $this->sendChatAction($message['message']['chat']['id']);
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);
        $id_category = (int) $message['params'][1];
        
        //по цвету
        if (isset($this->id_category_color) AND $this->id_category_color <> 0 AND $id_category == $this->id_category_color) {
            return $this->select_color($message);
        }

        $this->ProductModel = new \Products\Models\ProductModel();

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $offset = isset($message['params'][2]) ? (int) $message['params'][2] : 0;
        $products = $this->ProductModel->products_in_menu($id_category, $message['message']['chat']['id'], $offset);
        
        $count = count($products);
        if ($count <= 0) {
            $data_page = $this->PagesModel->page(96, $message, $data_user);
            return $this->answerCallbackQuery($message['id'], $data_page['text']);
        } else {
            if (isset($message['id'])) {
                $this->answerCallbackQuery($message['id']);
            }
        }

        $i = 0;
        foreach ($products as $product) {
            $i++;
            $message['params'][1] = $product['id'];
            $message['params'][2] = $id_category;
            $message['params'][3] = $i == $count;
            $message['params'][5] = $offset;
            $this->product($message, $product);
        }
    }

    /*
    Выбрал конкретный цвет
    выкатываем товары с этим цветом
     */
    public function color($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);
        $id_category = isset($message['params'][1]) ? (int) $message['params'][1] : 0;
        $offset = isset($message['params'][2]) ? (int) $message['params'][2] : 0;
        $id_mod = isset($message['params'][3]) ? (int) $message['params'][3] : 0;
        $this->ProductModel = new \Products\Models\ProductModel();
        $this->ModModel = new \Mods\Models\ModModel();

        $products = $this->ModModel->products_with_mod($id_mod, $offset);
        $count = $this->ModModel->products_with_mod($id_mod, $offset, TRUE);
        
        if ($count <= 0) {
            $data_page = $this->PagesModel->page(96, $message);
            return $this->answerCallbackQuery($message['id'], $data_page['text']);
        } else {
            $this->answerCallbackQuery($message['id']);
        }

        $i = 0;
        foreach ($products as $product) {
            $i++;
            $message['params'][1] = $product['id'];
            $message['params'][2] = $id_category;
            $message['params'][3] = $i == count($products);
            $message['params'][5] = $offset;
            $message['params'][6] = $id_mod;
            $this->product($message);
        }
    }

    /*
    Выбрал категорию по цвету
    Выдаем выбор цвета
     */
    public function select_color($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
        $params = $this->MenuModel->select_color($message);

        $data_page = $this->PagesModel->page(122, $message, $data_user);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];

        if (isset($message['params'])) {
            if (isset($message['params'][2]) AND $message['params'][2] == "delete") {
                $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
                $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
                return $this->answerCallbackQuery($message['id']);
            }
            return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
        }
        $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
        return $this->answerCallbackQuery($message['id']);
    }

    /*
    Купить
     */
    public function start_order($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);
        $id_parent = isset($message['params'][1]) ? (int) $message['params'][1] : 0;
        
        //по цвету
        if (isset($this->id_category_color) AND $this->id_category_color <> 0 AND $id_parent == $this->id_category_color) {
            return $this->select_color($message);
        }

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
        $params = $this->MenuModel->start_order($message);
        $id_page = count($params) <= 0 ? 42 : 37;
        $data_page = $this->PagesModel->page($id_page, $message, $data_user);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        if (isset($message['params'])) {
            if (isset($message['params'][2]) AND $message['params'][2] == "delete") {
                $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
                $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);

                if (isset($message['id'])) {
                    $this->answerCallbackQuery($message['id']);
                }
                return TRUE;
            }
            return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
        }
        $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
        if (isset($message['id'])) {
            $this->answerCallbackQuery($message['id']);
        }
        return TRUE;
    }

    /*
    Напишите промокод
     */
    public function promocode($message) {
        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_page = $this->PagesModel->page(67, $message, $data_user);
        $params = $this->MenuModel->get($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Принимаем промокод
     */
    public function notKnow($message) {
        if (!isset($message['message']['chat']['id']) OR $message['message']['chat']['id'] <= 0 OR !isset($message['message']['text'])) {
            return FALSE;
        }

        $params = $this->MenuModel->get($message);

        if (realpath(APPPATH."/ThirdParty/promo")) {
            $this->PromoModel = new \Promo\Models\PromoModel();
            if ($promocode = $this->PromoModel->link_user($message['message']['chat']['id'], $message['message']['text'])) {
                 
                $data_page = $this->PagesModel->page(66, $message, $promocode);
                $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
                return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
            }
        }

        $this->start_set("text_find", $message);
        $data_page = $this->PagesModel->page(65, $message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Отправить сообщение в чат поддержки
     */
    public function support($text) {
        if ($this->support_chat_id == 0 OR !is_string($text)) {
            return FALSE;
        }
        $params = [];
        $params['disable_web_page_preview'] = TRUE;
        return $this->sendMessage($this->support_chat_id, $text, $params);
    }

    /*
    Нажал кнопку
    Получить бонус
     */
    public function post_bonus($message) {
        $id_post = (int) $message['params'][1];

        if ($message['message']['chat']['id'] > 0) { //если отправил в личку - то убираем кнопку
            $this->editMessageReplyMarkup($message['message']['chat']['id'], $message['message']['message_id']);
        }

        $this->PostsModel = new \Sender\Models\PostsModel();
        $data_post = $this->PostsModel->get($id_post, TRUE);
        unset($data_post['text']);
        if ($data_post['sum_bonus'] <= 0) {
            return FALSE;
        }

        //начисляем бонус который задан в посте
        $this->BalanceModel = new \Balance\Models\BalanceModel();

        //если такой бонус уже начисляли
        if ($this->BalanceModel->have_balance($message['message']['chat']['id'], 'id_post', $id_post)) {
            $balance = $this->BalanceModel->get($message['message']['chat']['id']);
            $data_post['balance'] = number_format($balance, $this->decimals, ',', ' ');

            //уведомляем всплывающим сообщением что начислен бонус
            $data_page = $this->PagesModel->page(50, $message, $data_post);
            return $this->answerCallbackQuery($message['id'], $data_page['text'], TRUE);
        }

        $data = [];
        $data['chat_id'] = $message['message']['chat']['id'];
        $data['value'] = $data_post['sum_bonus'];
        $data['finish'] = 1;
        $data['comment'] = "За пост №".$id_post;
        $data['type'] = "bonus";
        $data['id_post'] = $id_post;
        $data['currency'] = $this->currency_cod;
        if (!$data_post['id_trans'] = $this->BalanceModel->add($data)) {
            //уведомляем всплывающим сообщением что начислен бонус
            $data_page = $this->PagesModel->page(51, $message, $data_post);
            return $this->answerCallbackQuery($message['id'], $data_page['text'], TRUE);
        }

        $balance = $this->BalanceModel->get($message['message']['chat']['id']);
        $data_post['balance'] = number_format($balance, $this->decimals, ',', ' ');

        //уведомляем всплывающим сообщением что начислен бонус
        $data_page = $this->PagesModel->page(49, $message, $data_post);
        return $this->answerCallbackQuery($message['id'], $data_page['text'], TRUE);
    }

    /*
    Выбрал способ оплаты
     */
    public function pay($message, $new = FAlSE) {
        switch ($message['params'][2]) {
            case 0: //баланс
            return $this->balance_pay($message, $new);
            case 1: //yandex
            return $this->yandex($message);
            case 2: //card
            return $this->card($message);
            case 3: //qiwi
            return $this->qiwi($message);
            case 4: //BTC
            return $this->bitcoin($message);
            case 6: //freekassa
            return $this->freekassa($message);

            case 7: //tronapi
            case 8: //tronapi
            return $this->tronapi($message);

            case 9: //YooKassa
            return $this->yookassa($message, $new);
            case 10:
            return $this->wayforpay($message, $new);
            
            
            //ручной способ оплаты
            default:
            return $this->pay_hand($message);
        }
    }

    /*
    Оплата Crypto
     */
    public function tronapi($message, $new = FALSE) {
        $id_product = (int) $message['params'][1];
        $id_pay = (int) $message['params'][2];

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $this->PayModel = new \Pays\Models\PayModel();
        if (!$data_pay = $this->PayModel->pay($id_pay)) {
            return FALSE;
        }

        $this->OrderModel = new \Orders\Models\OrderModel();
        $id_order = $this->OrderModel->active($message['message']['chat']['id']);
        $this->OrderModel->set(['id' => $id_order, 'id_pay' => $id_pay]);
        $this->OrderModel->recount_sum_order($id_order);
        
        //пересчитать цену с учетом закрепленного за пользователем промокода
        if (realpath(APPPATH."/ThirdParty/promo")) {
            $this->PromoModel = new \Promo\Models\PromoModel();
            $this->PromoModel->reprice($message['message']['chat']['id']);
        }

        $data_order = $this->OrderModel->get($id_order);

        if ($data_order['sum'] <= 0) {
            //убираем кнопку вывода
            $this->editMessageReplyMarkup($message['message']['chat']['id'], $message['message']['message_id']);

            return $this->OrderModel->status($id_order);
        }

        $data_user['id_order'] = $id_order;
        $data_user = array_merge($data_user, $data_order);
        $data_user = array_merge($data_user, $data_pay);
        $data_user['currency_pay'] = $data_pay['currency'];
        $data_user['products'] = "";
        $products = $this->OrderModel->products($id_order);
        $i = 0;
        helper("date");
        foreach ($products as $product) {
            if ($i > 0) {
                $data_user['products'].="\n";
            }
            // $name = $this->LangModel->trans_product($product['id_product'], $this->lang, 'name');
            $name = $product['name'];
            $data_user['products'].=$name;
            $data_user['name_product'] = $name;
            $i++;
        }

        //получаем адрес USDT для заказа
        $this->TronapiModel = new \Tronapi\Models\TronapiModel();
        $data_user['address'] = $this->TronapiModel->get_address($id_order, $data_pay['currency']);
        
        $id_page = $this->TronapiModel->id_page($data_pay['currency']);
        $data_page = $this->PagesModel->page($id_page, $message, $data_user);
        $params = $this->MenuModel->crypto_currency($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        if (!$new) {
            $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
        } else {
            $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
        }
            
        // $src = $this->TronapiModel->qrcode($data_user['address']);
        // if ($src) {
        //     $this->sendSrc($message['message']['chat']['id'], $src, [], 'photo');
        // }
        return TRUE;
    }

    /*
    Оплата ручным способом оплаты
     */
    public function wayforpay($message) {
        $this->clear($message);
        $id_product = 0;
        $sum = (int) $message['params'][1];
        $id_pay = (int) $message['params'][2];

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $this->PayModel = new \Pays\Models\PayModel();
        if (!$data_pay = $this->PayModel->pay($id_pay)) {
            return FALSE;
        }

        $this->OrderModel = new \Orders\Models\OrderModel();
        $id_order = $this->OrderModel->active($message['message']['chat']['id']);

        $this->OrderModel->set(['id' => $id_order, 'id_pay' => $id_pay]);

        if (realpath(APPPATH."/ThirdParty/promo")) {
            //пересчитать цену с учетом закрепленного за пользователем промокода
            $this->PromoModel = new \Promo\Models\PromoModel();
            $this->PromoModel->reprice($message['message']['chat']['id']);
        }

        $data_order = $this->OrderModel->get($id_order);

        $data_user = array_merge($data_user, $data_order);
        $data_user = array_merge($data_user, $data_pay);

        $data_user['id_order'] = $id_order;

        $this->CourseModel = new \Course\Models\CourseModel();
        $data_user['currency_pay'] = $this->CourseModel->shortnamesmall[$data_pay['currency']];

        $data_user['products'] = "";
        $products = $this->OrderModel->products($id_order);
        $i = 0;
        helper("date");
        foreach ($products as $product) {
            if ($i > 0) {
                $data_user['products'].="\n";
            }
            $data_user['products'].=$product['name'];
            $data_user['name_product'] = $product['name'];
            $i++;
        }

        $data_page = $this->PagesModel->page(131, $message, $data_user);
        $params = $this->MenuModel->wayforpay($message, $id_order);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
    }

    /*
    Оплата с баланса
     */
    public function balance_pay($message, $new = FALSE) {
        $this->clear($message);
        $id_product = (int) $message['params'][1];
        $id_pay = (int) $message['params'][2];

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $this->PayModel = new \Pays\Models\PayModel();
        if (!$data_pay = $this->PayModel->pay($id_pay)) {
            return FALSE;
        }

        $this->OrderModel = new \Orders\Models\OrderModel();
        $id_order = $this->OrderModel->active($message['message']['chat']['id']);

        //пересчитать цену с учетом закрепленного за пользователем промокода
        if (realpath(APPPATH."/ThirdParty/promo")) {
            $this->PromoModel = new \Promo\Models\PromoModel();
            $this->PromoModel->reprice($message['message']['chat']['id']);
        }

        $data_order = $this->OrderModel->get($id_order);

        $data_user = array_merge($data_user, $data_order);
        $data_user = array_merge($data_user, $data_pay);

        $data_user['id_order'] = $id_order;

        $this->CourseModel = new \Course\Models\CourseModel();
        $data_user['currency_pay'] = $data_pay['currency'];

        $this->AffModel = new \Aff\Models\AffModel();
        $this->BalanceModel = new \Balance\Models\BalanceModel();

        $balance = $this->BalanceModel->get($message['message']['chat']['id']);
        $data_user['balance'] = number_format($balance, $this->decimals, ',', ' ');
        
        $data_user['products'] = "";
        $products = $this->OrderModel->products($id_order);
        $i = 0;
        helper("date");
        foreach ($products as $product) {
            if ($i > 0) {
                $data_user['products'].="\n";
            }
            // $product['name'] = $this->LangModel->trans_product($product['id_product'], $this->lang, 'name');
            
            $data_user['products'].=$product['name'];
            // if ($product['count_days'] > 0) {
            //     $data_user['products'].=" (".date("d.m.Y H:i", human_to_unix($product['date_finish'])).")";
            // }
            // $data_user['count_days'] = $product['count_days'];
            $data_user['name_product'] = $product['name'];
            $i++;
        }

        if (isset($message['params'][4]) AND $message['params'][4] === "yes") {

            if ($balance < $data_order['sum_pay']) {
                $data_page = $this->PagesModel->page(96, $message, $data_user);
                return $this->answerCallbackQuery($message['id'], $data_page['text'], TRUE);
            }

            //списываем с баланса
            //создаем транзакцию
            $data = [];
            $data['chat_id'] = $message['message']['chat']['id'];
            $data['value'] = -$data_order['sum_pay'];
            $data['finish'] = 1;
            $data['comment'] = "Оплата заказа №".$id_order;
            $data['type'] = "out";
            $data['currency'] = $this->currency_cod;
            if ($this->BalanceModel->add($data)) {
                if ($this->OrderModel->status($id_order)) {
                    return $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
                }
            }
        }

        $data_page = $this->PagesModel->page(126, $message, $data_user);
        $params = $this->MenuModel->pay_balance($message, $id_order);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        if (!$new) {
            return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
        }
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Оплата bitcoin
     */
    public function bitcoin($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $id_product = 0;
        $sum = (int) $message['params'][1];
        $id_pay = (int) $message['params'][2];

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $this->PayModel = new \Pays\Models\PayModel();
        if (!$data_pay = $this->PayModel->pay($id_pay)) {
            return FALSE;
        }

        $this->OrderModel = new \Orders\Models\OrderModel();

        //создаем заказ
        $data = [];
        $data['products'] = [$id_product];
        $data['chat_id'] = $message['message']['chat']['id'];
        $data['id_pay'] = $id_pay;
        $data['sum'] = $sum;
        $data['finish'] = $this->finalize_order;
        if (!$id_order = $this->OrderModel->add($data)) {
            return FALSE;
        }

        //пересчитать цену с учетом закрепленного за пользователем промокода
        $this->PromoModel = new \Promo\Models\PromoModel();
        $this->PromoModel->reprice($message['message']['chat']['id']);

        $data_order = $this->OrderModel->get($id_order);

        $data_user = array_merge($data_user, $data_order);
        $data_user = array_merge($data_user, $data_pay);

        $data_user['id_order'] = $id_order;

        $data_user['products'] = "";
        $products = $this->OrderModel->products($id_order);
        $i = 0;
        helper("date");
        foreach ($products as $product) {
            if ($i > 0) {
                $data_user['products'].="\n";
            }
            $data_user['products'].=$product['name'];
            $i++;
        }

        $data_user['currency_pay'] = $data_pay['currency'];
        $data_user['sum_pay'] = number_format($data_order['sum_pay'], 8, ',', ' ');

        //если подключен block.io
        $this->BlockioModel = new \Blockio\Models\BlockioModel();
        if ($this->BlockioModel->init($data_pay['currency'])) {
            $address = $this->BlockioModel->get_new_address($message['message']['chat']['id'], $data_pay['currency']);
            if (is_array($address)) {
                return $this->answerCallbackQuery($message['id'], "ERROR API: ".$address['error'], TRUE);
            } else {
                $data_user['address'] = $address;
            }
        } else { //если не подключен
            $this->BlockchainModel = new \Btc\Models\BlockchainModel();
            if (empty($this->BlockchainModel->blockchain_api_key())) {
                $data_user['address'] = $this->PayModel->get($id_pay, "address");
            } else {
                $data_user['address'] = $this->BlockchainModel->address($message['message']['chat']['id']);
            }
        }
        
        $data_page = $this->PagesModel->page(62, $message, $data_user);
        $params = $this->MenuModel->bitcoin($message, $id_order);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
    }

    /*
    Получаем TXID и проверяем поступила оплата или нет

     */
    public function text_txid($message) {
        $id_order = (int) $message['params'][1];
        $txid = trim($message['message']['text']);

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $this->OrderModel = new \Orders\Models\OrderModel();
        $this->PayModel = new \Pays\Models\PayModel();
        
        $data_order = $this->OrderModel->get($id_order);

        if (!$data_pay = $this->PayModel->pay($data_order['id_pay'])) {
            return FALSE;
        }
        $data_user = array_merge($data_user, $data_order);
        $data_user = array_merge($data_user, $data_pay);

        $data_user['id_order'] = $id_order;
        $data_user['currency_pay'] = $data_pay['currency'];

        $data_user['products'] = "";
        $products = $this->OrderModel->products($id_order);
        $i = 0;
        helper("date");
        foreach ($products as $product) {
            if ($i > 0) {
                $data_user['products'].="\n";
            }
            $data_user['products'].=$product['name'] ." (".date("d.m.Y H:i", human_to_unix($product['date_finish'])).")";
            $data_user['count_days'] = $product['count_days'];
            $data_user['name_product'] = $product['name'];
            $i++;
        }

        $data_user['address'] = $this->PayModel->get($data_order['id_pay'], "address");

        //сохраняем хеш транзакции
        $this->OrderModel->set(['id' => $id_order, 'txid' => $txid]);

        //сразу проверяем оплачен заказ или нет
        $this->BlockchainModel = new \Btc\Models\BlockchainModel();
        if ($data_order['status'] <= 0 AND $this->BlockchainModel->is_payed($data_user['address'], $txid, $data_order['sum_pay'])) {
            return $this->OrderModel->status($id_order); //заказ оплачен
        }

        $data_user['sum_pay'] = number_format($data_order['sum_pay'], 8, ',', ' ');

        $data_page = $this->PagesModel->page(64, $message, $data_user);
        $params = $this->MenuModel->pay_bitcoin($message, $id_order);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Отправьте txid транзакции
     */
    public function pay_bitcoin($message, $new = FALSE) {
        $id_order = (int) $message['params'][1];

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $this->OrderModel = new \Orders\Models\OrderModel();
        $this->PayModel = new \Pays\Models\PayModel();
        
        $data_order = $this->OrderModel->get($id_order);
        if (empty($data_order['id_pay']) OR !$data_pay = $this->PayModel->pay($data_order['id_pay'])) {
            return FALSE;
        }
        $data_user = array_merge($data_user, $data_order);
        $data_user = array_merge($data_user, $data_pay);

        $data_user['id_order'] = $id_order;
        
        $data_user['currency_pay'] = $data_pay['currency'];

        $data_user['products'] = "";
        $products = $this->OrderModel->products($id_order);
        $i = 0;
        helper("date");
        foreach ($products as $product) {
            if ($i > 0) {
                $data_user['products'].="\n";
            }
            $data_user['products'].=$product['name'] ." (".date("d.m.Y H:i", human_to_unix($product['date_finish'])).")";
            $data_user['count_days'] = $product['count_days'];
            $data_user['name_product'] = $product['name'];
            $i++;
        }

        $data_user['sum_pay'] = number_format($data_order['sum_pay'], 8, ',', ' ');

        $this->start_set("text_txid", $message, $id_order);
        $data_page = $this->PagesModel->page(63, $message, $data_user);
        $params = $this->MenuModel->pay_bitcoin($message, $id_order);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        if (!$new) {
            return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
        }
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Администратор нажал кнопку 
    "Подтвердить оплату"
     */
    public function file_check_approve($message) {
        $id_order = (int) $message['params'][1];

        //убираем кнопку у админов
        $this->editMessageReplyMarkup($message['message']['chat']['id'], $message['message']['message_id']);

        $this->OrderModel = new \Orders\Models\OrderModel();
        return $this->OrderModel->status($id_order);
    }

    /*
    Сохраняем скан чек
     */
    public function file_check($message) {
        $id_order = (int) $message['params'][1];
        $this->OrderModel = new \Orders\Models\OrderModel();

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $this->OrderModel = new \Orders\Models\OrderModel();
        $this->PayModel = new \Pays\Models\PayModel();
        
        $data_order = $this->OrderModel->get($id_order);

        if (!$data_pay = $this->PayModel->pay($data_order['id_pay'])) {
            return FALSE;
        }
        $data_user = array_merge($data_user, $data_order);
        $data_user = array_merge($data_user, $data_pay);

        $data_user['id_order'] = $id_order;

        $data_user['products'] = "";
        $products = $this->OrderModel->products($id_order);
        $i = 0;
        helper("date");
        foreach ($products as $product) {
            if ($i > 0) {
                $data_user['products'].="\n";
            }
            $data_user['products'].=$product['name'].' ('.$product['count'].')';
            $data_user['name_product'] = $product['name'];
            $i++;
        }

        $data_user['number'] = $this->PayModel->get($data_order['id_pay'], 'number');

        $this->CourseModel = new \Course\Models\CourseModel();
        $data_user['currency_pay'] = $this->CourseModel->shortnamesmall[$data_pay['currency']];

        if (!$file_id = $this->file_id($message, 'photo')) {//не верный тип файла
            $data_page = $this->PagesModel->page(46, $message, $data_user);
            $params = [];
            $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
            $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);

            return $this->pay_hand_file($message, TRUE);
        }

        $this->OrderModel->set(['id' => $id_order, 'file_id_check' => $file_id, 'finish' => 1]);

        //пишем что файл принят - ожидайте
        $data_page = $this->PagesModel->page(47, $message, $data_user);
        $params = $this->MenuModel->get($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);

        //отправляем файл в чат админов
        $data_page = $this->PagesModel->page(48, $message, $data_user);
        $params = $this->MenuModel->file_check($message, $id_order);
        $params['caption'] = $data_page['text'];
        return $this->sendFile($this->support_chat_id, $file_id, $params, 'photo');
    }

    /*
    Скачать файл
     */
    public function aff_export($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);
        $this->AffModel = new \Aff\Models\AffModel();

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        if (!$path = $this->AffModel->export($message['message']['chat']['id'])) {
            $data_page = $this->PagesModel->page(56, $message, $data_user);
            return $this->answerCallbackQuery($message['id'], $data_page['text'], TRUE);
        }
        
        //отправляем файл в чат админов
        $data_page = $this->PagesModel->page(57, $message, $data_user);
        $params = [];
        $params['caption'] = $data_page['text'];
        return $this->sendSrc($message['message']['chat']['id'], $path, $params, 'document');
    }

    /*
    Отправьте файл со скрином оплаты заказа
     */
    public function pay_hand_file($message, $new = FALSE) {
        $id_order = (int) $message['params'][1];

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $this->OrderModel = new \Orders\Models\OrderModel();
        $this->PayModel = new \Pays\Models\PayModel();
        
        $data_order = $this->OrderModel->get($id_order);
        if (empty($data_order['id_pay'])) {
            $this->answerCallbackQuery($message['id'], "Не выбран способ оплаты!");
            return $this->start($message);
        }

        if (!$data_pay = $this->PayModel->pay($data_order['id_pay'])) {
            return FALSE;
        }
        $data_user = array_merge($data_user, $data_order);
        $data_user = array_merge($data_user, $data_pay);

        $data_user['id_order'] = $id_order;
        
        $this->CourseModel = new \Course\Models\CourseModel();
        $data_user['currency_pay'] = $this->CourseModel->shortnamesmall[$data_pay['currency']];

        $data_user['products'] = "";
        $products = $this->OrderModel->products($id_order);
        $i = 0;
        foreach ($products as $product) {
            if ($i > 0) {
                $data_user['products'].="\n";
            }
            $data_user['products'].=$product['name'].' ('.$product['count'].')';
            $i++;
        }

        $data_user['number'] = $this->PayModel->get($data_order['id_pay'], 'number');

        $this->start_set("file_check", $message, $id_order);
        $data_page = $this->PagesModel->page(45, $message, $data_user);
        $params = $this->MenuModel->pay_hand_file($message, $id_order);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        if (!$new) {
            return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
        }
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Оплата ручным способом оплаты
     */
    public function pay_hand($message) {
        $this->clear($message);
        $id_product = 0;
        $sum = (int) $message['params'][1];
        $id_pay = (int) $message['params'][2];

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $this->PayModel = new \Pays\Models\PayModel();
        if (!$data_pay = $this->PayModel->pay($id_pay)) {
            return FALSE;
        }

        $this->OrderModel = new \Orders\Models\OrderModel();
        $id_order = $this->OrderModel->active($message['message']['chat']['id']);

        $this->OrderModel->set(['id' => $id_order, 'id_pay' => $id_pay]);

        if (realpath(APPPATH."/ThirdParty/promo")) {
            //пересчитать цену с учетом закрепленного за пользователем промокода
            $this->PromoModel = new \Promo\Models\PromoModel();
            $this->PromoModel->reprice($message['message']['chat']['id']);
        }

        $data_order = $this->OrderModel->get($id_order);

        $data_user = array_merge($data_user, $data_order);
        $data_user = array_merge($data_user, $data_pay);

        $data_user['id_order'] = $id_order;

        $this->CourseModel = new \Course\Models\CourseModel();
        $data_user['currency_pay'] = $this->CourseModel->shortnamesmall[$data_pay['currency']];

        $data_user['products'] = "";
        $products = $this->OrderModel->products($id_order);
        $i = 0;
        foreach ($products as $product) {
            if ($i > 0) {
                $data_user['products'].="\n";
            }
            $data_user['products'].=$product['name'].' ('.$product['count'].')';
            $i++;
        }

        $data_user['number'] = $this->PayModel->get($id_pay, 'number');

        $data_page = $this->PagesModel->page(44, $message, $data_user);
        $params = $this->MenuModel->pay_hand($message, $id_order);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
    }

    /*
    Оплата Qiwi
     */
    public function qiwi($message) {
        $id_product = 0;
        $sum = (int) $message['params'][1];
        $id_pay = (int) $message['params'][2];

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $this->PayModel = new \Pays\Models\PayModel();
        if (!$data_pay = $this->PayModel->pay($id_pay)) {
            return FALSE;
        }

        $this->OrderModel = new \Orders\Models\OrderModel();

        //создаем заказ
        $data = [];
        $data['products'] = [$id_product];
        $data['chat_id'] = $message['message']['chat']['id'];
        $data['id_pay'] = $id_pay;
        $data['sum'] = $sum;
        $data['finish'] = $this->finalize_order;
        if (!$id_order = $this->OrderModel->add($data)) {
            return FALSE;
        }

        //пересчитать цену с учетом закрепленного за пользователем промокода
        $this->PromoModel = new \Promo\Models\PromoModel();
        $this->PromoModel->reprice($message['message']['chat']['id']);

        $data_order = $this->OrderModel->get($id_order);

        $data_user = array_merge($data_user, $data_order);
        $data_user = array_merge($data_user, $data_pay);

        $data_user['id_order'] = $id_order;

        $data_user['products'] = "";
        $products = $this->OrderModel->products($id_order);
        $i = 0;
        helper("date");
        foreach ($products as $product) {
            if ($i > 0) {
                $data_user['products'].="\n";
            }
            $data_user['products'].=$product['name'];
            $data_user['count_days'] = $product['count_days'];
            $data_user['name_product'] = $product['name'];
            $i++;
        }

        $this->QiwiModel = new \Qiwi\Models\QiwiModel();
        $data_user['qiwi'] = $this->QiwiModel->get_wallet_id();

        $data_page = $this->PagesModel->page(41, $message, $data_user);
        $params = $this->MenuModel->qiwi($message, $id_order);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
    }

    /*
    Оплата Карты
     */
    public function card($message) {
        $id_product = 0;
        $sum = (int) $message['params'][1];
        $id_pay = (int) $message['params'][2];

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $this->PayModel = new \Pays\Models\PayModel();
        if (!$data_pay = $this->PayModel->pay($id_pay)) {
            return FALSE;
        }

        $this->OrderModel = new \Orders\Models\OrderModel();

        //создаем заказ
        $data = [];
        $data['products'] = [$id_product];
        $data['chat_id'] = $message['message']['chat']['id'];
        $data['id_pay'] = $id_pay;
        $data['sum'] = $sum;
        $data['finish'] = $this->finalize_order;
        if (!$id_order = $this->OrderModel->add($data)) {
            return FALSE;
        }


        //пересчитать цену с учетом закрепленного за пользователем промокода
        $this->PromoModel = new \Promo\Models\PromoModel();
        $this->PromoModel->reprice($message['message']['chat']['id']);

        $data_order = $this->OrderModel->get($id_order);

        $data_user['id_order'] = $id_order;
        $data_user = array_merge($data_user, $data_order);
        $data_user = array_merge($data_user, $data_pay);

        $data_user['products'] = "";
        $products = $this->OrderModel->products($id_order);
        $i = 0;
        helper("date");
        foreach ($products as $product) {
            if ($i > 0) {
                $data_user['products'].="\n";
            }
            $data_user['products'].=$product['name'];
            $data_user['count_days'] = $product['count_days'];
            $data_user['name_product'] = $product['name'];
            $i++;
        }

        $data_page = $this->PagesModel->page(40, $message, $data_user);
        $params = $this->MenuModel->card($message, $id_order);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
    }

    /*
    Оплата FreeKassa
     */
    public function freekassa($message) {
        $id_product = 0;
        $sum = (int) $message['params'][1];
        $id_pay = (int) $message['params'][2];

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $this->PayModel = new \Pays\Models\PayModel();
        if (!$data_pay = $this->PayModel->pay($id_pay)) {
            return FALSE;
        }

        $this->OrderModel = new \Orders\Models\OrderModel();

        //создаем заказ
        $data = [];
        $data['products'] = [$id_product];
        $data['chat_id'] = $message['message']['chat']['id'];
        $data['id_pay'] = $id_pay;
        $data['sum'] = $sum;
        $data['finish'] = $this->finalize_order;
        if (!$id_order = $this->OrderModel->add($data)) {
            return FALSE;
        }

        //пересчитать цену с учетом закрепленного за пользователем промокода
        $this->PromoModel = new \Promo\Models\PromoModel();
        $this->PromoModel->reprice($message['message']['chat']['id']);

        $data_order = $this->OrderModel->get($id_order);

        $data_user['id_order'] = $id_order;
        $data_user = array_merge($data_user, $data_order);
        $data_user = array_merge($data_user, $data_pay);

        $data_user['products'] = "";
        $products = $this->OrderModel->products($id_order);
        $i = 0;
        helper("date");
        foreach ($products as $product) {
            if ($i > 0) {
                $data_user['products'].="\n";
            }
            $data_user['products'].=$product['name'];
            $data_user['count_days'] = $product['count_days'];
            $data_user['name_product'] = $product['name'];
            $i++;
        }

        $data_page = $this->PagesModel->page(80, $message, $data_user);
        $params = $this->MenuModel->freekassa($message, $id_order);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
    }

    /*
    Оплата Yookassa
     */
    public function yookassa($message, $new = FALSE) {
        $id_product = (int) $message['params'][1];
        $id_pay = (int) $message['params'][2];

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $this->PayModel = new \Pays\Models\PayModel();
        if (!$data_pay = $this->PayModel->pay($id_pay)) {
            return FALSE;
        }

        $this->OrderModel = new \Orders\Models\OrderModel();
        $id_order = $this->OrderModel->active($message['message']['chat']['id']);

        //пересчитать цену с учетом закрепленного за пользователем промокода
        if (realpath(APPPATH."/ThirdParty/promo")) {
            $this->PromoModel = new \Promo\Models\PromoModel();
            $this->PromoModel->reprice($message['message']['chat']['id']);
        }

        $data_order = $this->OrderModel->get($id_order);

        if ($data_order['sum'] <= 0) {
            //убираем кнопку вывода
            $this->editMessageReplyMarkup($message['message']['chat']['id'], $message['message']['message_id']);

            return $this->OrderModel->status($id_order);
        }

        $data_user['id_order'] = $id_order;
        $data_user = array_merge($data_user, $data_order);
        $data_user = array_merge($data_user, $data_pay);

        $data_user['products'] = "";
        $products = $this->OrderModel->products($id_order);
        $i = 0;
        helper("date");
        foreach ($products as $product) {
            if ($i > 0) {
                $data_user['products'].="\n";
            }
            // $name = $this->LangModel->trans_product($product['id_product'], $this->lang, 'name');
            $name = $product['name'];
            $data_user['products'].=$name;
            // $data_user['count_days'] = $product['count_days'];
            $data_user['name_product'] = $name;
            $i++;
        }

        $data_page = $this->PagesModel->page(123, $message, $data_user);
        $params = $this->MenuModel->yookassa($message, $id_order);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        if (!$new) {
            return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
        }
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Оплата ЯндексДеньги
     */
    public function yandex($message) {
        $id_product = 0;
        $sum = (int) $message['params'][1];
        $id_pay = (int) $message['params'][2];

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $this->PayModel = new \Pays\Models\PayModel();
        if (!$data_pay = $this->PayModel->pay($id_pay)) {
            return FALSE;
        }

        $this->OrderModel = new \Orders\Models\OrderModel();

        //создаем заказ
        $data = [];
        $data['products'] = [$id_product];
        $data['chat_id'] = $message['message']['chat']['id'];
        $data['id_pay'] = $id_pay;
        $data['sum'] = $sum;
        $data['finish'] = $this->finalize_order;
        if (!$id_order = $this->OrderModel->add($data)) {
            return FALSE;
        }

        //пересчитать цену с учетом закрепленного за пользователем промокода
        $this->PromoModel = new \Promo\Models\PromoModel();
        $this->PromoModel->reprice($message['message']['chat']['id']);

        $data_order = $this->OrderModel->get($id_order);

        $data_user['id_order'] = $id_order;
        $data_user = array_merge($data_user, $data_order);
        $data_user = array_merge($data_user, $data_pay);

        $data_user['products'] = "";
        $products = $this->OrderModel->products($id_order);
        $i = 0;
        helper("date");
        foreach ($products as $product) {
            if ($i > 0) {
                $data_user['products'].="\n";
            }
            $data_user['products'].=$product['name'];
            $data_user['count_days'] = $product['count_days'];
            $data_user['name_product'] = $product['name'];
            $i++;
        }

        $data_page = $this->PagesModel->page(39, $message, $data_user);
        $params = $this->MenuModel->yandex($message, $id_order);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
    }


    /*
    Выбрал тариф
    Выберите способ оплаты
     */
    public function select_pay($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);
        $id_product = 0;
        $sum = (int) $message['params'][1];
        
        $this->ProductModel = new \Products\Models\ProductModel();
        $this->PayModel = new \Pays\Models\PayModel();
        $pays = $this->PayModel->items(TRUE);

        if (count($pays) == 1) {
            $message['params'][2] = $pays[0]['id'];
            return $this->pay($message);
        } else if (count($pays) <= 0) {
            //если отключены все способы оплаты
            $data_page = $this->PagesModel->page(43, $message);
            return $this->answerCallbackQuery($message['id'], $data_page['text'], TRUE);
        }

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
        $data_user['sum'] = number_format($sum, $this->decimals, ',', ' ');
        $data_page = $this->PagesModel->page(38, $message, $data_user);
        $params = $this->MenuModel->select_pay($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        if (isset($message['params'])) {
            return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
        }
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Уведомляем что заказ оплачен
     */
    public function notify_balance($data_balance) {
        $this->BalanceModel = new \Balance\Models\BalanceModel();

        $message['message']['chat']['id'] = $data_balance['chat_id'];

        $this->sendChatAction($message['message']['chat']['id']);

        $balance = $this->BalanceModel->get($message['message']['chat']['id']);
        $data_balance['balance'] = number_format($balance, $this->decimals, ',', ' ');

        //отправляем тексты спасибо за покупку
        $params = $this->MenuModel->get($message);

        $params = $this->MenuModel->get($message);
        $data_page = $this->PagesModel->page(88, $message, $data_balance);
        
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);

        if ($this->support_chat_id == 0) {
            return FALSE;
        }

        //уведомление админу
        $data_user = $this->ionAuth->user($data_balance['chat_id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
        $data_balance = array_merge($data_balance, $data_user);

        $data_page = $this->PagesModel->page(89, $message, $data_balance);
        $params = [];
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($this->support_chat_id, $data_page['text'], $params);
    }

    /*
    Автоматически удален заказ
     */
    public function notify_deleted(array $data_order) {
        $this->OrderModel = new \Orders\Models\OrderModel();
        $message['message']['chat']['id'] = $data_order['chat_id'];

        $this->sendChatAction($message['message']['chat']['id']);

        $params = $this->MenuModel->get($message);
        $data_page = $this->PagesModel->page(106, $message, $data_order);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Уведомление что заказ недооформлен
     */
    public function notify_no_finish(array $data_order, $id_page = 115) {
        $this->OrderModel = new \Orders\Models\OrderModel();
        $message['message']['chat']['id'] = $data_order['chat_id'];

        $this->sendChatAction($message['message']['chat']['id']);

        $params = $this->MenuModel->get($message);
        $data_page = $this->PagesModel->page($id_page, $message, $data_order);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Уведомление что начислен бонус
     */
    public function notify_bonus_cat(array $bonus, int $id_order) {
        $this->OrderModel = new \Orders\Models\OrderModel();
        $this->BalanceModel = new \Balance\Models\BalanceModel();
        $message['message']['chat']['id'] = $bonus['chat_id'];

        $this->sendChatAction($message['message']['chat']['id']);

        //отправляем тексты спасибо за покупку
        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $data_user = array_merge($data_user, $bonus);

        $balance = $this->BalanceModel->get($message['message']['chat']['id']);
        $data_user['balance'] = number_format($balance, $this->decimals, ',', ' ');

        $items_in_order = $this->OrderModel->items_in_order($id_order);
        $data_user['count'] = count($items_in_order);
        $data_user['items'] = $this->OrderModel->items_in_order_text($id_order);
        $params = $this->MenuModel->get($message);
        $data_page = $this->PagesModel->page(128, $message, $data_user);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }


    /*
    Уведомляем что заказ оплачен
     */
    public function notify_payed(int $id_order) {
        $this->ProductModel = new \Products\Models\ProductModel();
        $this->OrderModel = new \Orders\Models\OrderModel();
        $this->BalanceModel = new \Balance\Models\BalanceModel();
        $this->ModModel = new \Mods\Models\ModModel();

        $products = $this->OrderModel->products($id_order);
        $data_order = $this->OrderModel->get($id_order);
        if (!isset($data_order['id'])) {
            return FALSE;
        }
        $message['message']['chat']['id'] = $data_order['chat_id'];

        $this->sendChatAction($message['message']['chat']['id']);

        $balance = $this->BalanceModel->get($message['message']['chat']['id']);
        $data_order['balance'] = number_format($balance, $this->decimals, ',', ' ');

        //отправляем тексты спасибо за покупку
        $params = $this->MenuModel->get($message);
        foreach ($products as $product) {
            $text = $this->ProductModel->text($product['id_product'], "thankyou", $product);
            $this->sendMessage($message['message']['chat']['id'], $text, $params);
        }
        
       
        $i = 0;
        $is_balance = FALSE;
        
        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $data_user['products'] = "";
        $products = $this->OrderModel->products($id_order);
        $i = 0;
        foreach ($products as $product) {
            if ($i > 0) {
                $data_user['products'].="\n";
            }
            $data_user['products'].=$product['name'].' ('.$product['count'].')';
            $i++;
        }

        $data_order = array_merge($data_order, $data_user);

        $data_order['call_whatsapp'] = $data_order['call_whatsapp'] > 0 ? "позвонить в whatsapp" : "";
        $items_in_order = $this->OrderModel->items_in_order($id_order);
        $data_order['count'] = count($items_in_order);
        $data_order['items'] = $this->OrderModel->items_in_order_text($id_order);
        $data_order['id_order'] = $id_order;
        
        $params = $this->MenuModel->payed($message, $id_order);
        $data_page = $this->PagesModel->page(33, $message, $data_order);

        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);

        if ($this->support_chat_id == 0) {
            return FALSE;
        }

        //уведомление админу
        $data_user = $this->ionAuth->user($data_order['chat_id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];

        $data_user['products'] = "";
        $products = $this->OrderModel->products($id_order);
        $i = 0;
        foreach ($products as $product) {
            if ($i > 0) {
                $data_user['products'].="\n";
            }
            $data_user['products'].=$product['name'].' ('.$product['count'].')';
            $i++;
        }

        $data_order = array_merge($data_order, $data_user);
        
        $this->PayModel = new \Pays\Models\PayModel();
        $data_pay = $this->PayModel->pay($data_order['id_pay']);
        $data_order['name'] = $data_pay['name'];
        $data_order['currency_pay'] = $data_pay['currency'];

        $data_order['sum_pay'] = number_format($data_order['sum_pay'], 8, ',', ' ');

        $data_page = $this->PagesModel->page(60, $message, $data_order);
        $params = [];
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($this->support_chat_id, $data_page['text'], $params);
    }

    /*
    Ваш аккаунт не активен
     */
    public function activation($message) {
        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
        if (!isset($data_user['active'])) {
            return $this->start($message);
        }
        if ($data_user['active'] > 0) {
            return FALSE;
        }
        $data_page = $this->PagesModel->page(29, $message, $data_user);
        $params = $this->MenuModel->keyboard_remove();
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        if (!empty($data_page['file_id'])) {
            $this->sendFile($message['message']['chat']['id'], $data_page['file_id']);
        }
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Уведомление о начисленном бонусе
     */
    public function notify_new_bonus($data, $data_order) {

        $message['message']['chat']['id'] = $data['chat_id'];

        //уведомляем пользователя что вывод произведен
        $data_user = $this->ionAuth->user($data_order['chat_id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
        
        $data_user = array_merge($data_user, $data_order);
        
        $this->BalanceModel = new \Balance\Models\BalanceModel();
        $balance = $this->BalanceModel->get($message['message']['chat']['id']);
        $data_user['balance'] = number_format($balance, $this->decimals, ',', ' ');
        $data_user['sum'] = number_format($data['value'], $this->decimals, ',', ' ');
        $data_user['comment'] = $data['comment'];

        $data_page = $this->PagesModel->page(52, $message, $data_user);
        $params = $this->MenuModel->get($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Уведомление о новых комиссионных
     */
    public function notify_new_comission($comission, $data_order) {

        $message['message']['chat']['id'] = $comission['chat_id'];

        //уведомляем пользователя что вывод произведен
        $data_user = $this->ionAuth->user($data_order['chat_id'])->getRowArray();

        $data_user = array_merge($data_user, $data_order);
        
        $this->BalanceModel = new \Balance\Models\BalanceModel();
        $balance = $this->BalanceModel->get($message['message']['chat']['id']);
        $data_user['balance'] = number_format($balance, $this->decimals, ',', ' ');
        $data_user['sum'] = $comission['sum'];

        $data_page = $this->PagesModel->page(31, $message, $data_user);
        $params = $this->MenuModel->get($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    public function admin_out_confirm($message) {
        $id_trans = (int) $message['params'][1];

        $this->BalanceModel = new \Balance\Models\BalanceModel();

        //помечаем транзакцию завершенной
        $this->BalanceModel->set(['id' => $id_trans, 'finish' => 1]);

        //убираем кнопку вывода
        $this->editMessageReplyMarkup($message['message']['chat']['id'], $message['message']['message_id']);

        $data_balance = $this->BalanceModel->get_data($id_trans);

        $message['message']['chat']['id'] = $data_balance['chat_id'];

        //уведомляем пользователя что вывод произведен
        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();

        $data_user['id_trans'] = $id_trans;
        $data_user['sum'] = abs($data_balance['value']);
        $data_user['bill'] = $data_balance['comment'];

        $data_page = $this->PagesModel->page(28, $message, $data_user);
        $params = $this->MenuModel->get($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    public function text_sum($message, $upd = FALSE) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();

        $sum = (int) trim($message['message']['text']);
        $data_user['sum'] = $sum;
        $data_user['bill'] = $message['params'][1];
        $bill = $data_user['bill'];
        $message['message']['text'] = $message['params'][1];
        
        if ($sum <= 0) {
            //отрицательная сумма
            $data_page = $this->PagesModel->page(21, $message, $data_user);
            $params = $this->MenuModel->get($message);
            $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
            $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
            return $this->text_bill($message);
        } else if ($this->min_out > 0 AND $sum < $this->min_out) {
            //меньше минимальной суммы на вывод
            $data_page = $this->PagesModel->page(22, $message, $data_user);
            $params = $this->MenuModel->get($message);
            $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
            $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);

            return $this->text_bill($message);
        }

        $this->BalanceModel = new \Balance\Models\BalanceModel();
        if ($this->BalanceModel->have_no_finish($message['message']['chat']['id'])) {
            //есть не завершенные транзакции
            $data_page = $this->PagesModel->page(23, $message, $data_user);
            $params = $this->MenuModel->get($message);
            $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
            $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);

            return $this->wallet($message);
        }

        $balance = $this->BalanceModel->get($message['message']['chat']['id']);
        $data_user['balance'] = number_format($balance, $this->decimals, ',', ' ');

        if ($balance < $sum) {
            //не хватает на балансе
            $data_page = $this->PagesModel->page(24, $message, $data_user);
            $params = $this->MenuModel->get($message);
            $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
            $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);

            return $this->text_bill($message);
        }

        unset($message['params']);
        return $this->confirm_out_user($message, FALSE, $sum, $bill);
    }

    /*
     Вы подтвеждаете создание запроса на вывод?
     */
     public function confirm_out_user($message, $upd = FALSE, $sum = NULL, $bill = NULL) {
        $this->BalanceModel = new \Balance\Models\BalanceModel();

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $balance = $this->BalanceModel->get($message['message']['chat']['id']);
        $data_user['balance'] = number_format($balance, $this->decimals, ',', ' ');
        $data_user['sum'] = $sum;
        $data_user['bill'] = $bill;

        if (isset($message['params'][1]) AND $message['params'][1] == "yes") {
            $data_user['sum'] = $message['params'][2];
            $data_user['bill'] = $message['params'][3];

            //создаем транзакцию
            $data = [];
            $data['chat_id'] = $message['message']['chat']['id'];
            $data['value'] = -$data_user['sum'];
            $data['finish'] = 0;
            $data['comment'] = $data_user['bill'];
            $data['type'] = "out";
            $data['currency'] = $this->currency_cod;

            if (!$data_user['id_trans'] = $this->BalanceModel->add($data)) {
                //не удалось создать транзакцию
                $data_page = $this->PagesModel->page(25, $message, $data_user);
                $params = $this->MenuModel->get($message);
                $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
                $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);

                return $this->text_bill($message);
            }

            //уведомляем пользователя что запрос на вывод создан
            $data_page = $this->PagesModel->page(26, $message, $data_user);
            $params = [];
            $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
            $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);

            //уведомляем админа сообщением с запросом на вывод
            $message['message']['chat']['id'] = $this->support_chat_id;
            $data_page = $this->PagesModel->page(27, $message, $data_user);
            $params = $this->MenuModel->admin_out($message, $data_user['id_trans']);
            $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
            return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
        }

        //подтверждающее сообщение на вывод
        $data_page = $this->PagesModel->page(53, $message, $data_user);
        $params = $this->MenuModel->confirm_out_user($message, $sum, $bill);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    public function text_bill($message, $upd = FALSE) {
        $this->BalanceModel = new \Balance\Models\BalanceModel();

        $bill = trim($message['message']['text']);

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();

        //проверяем верно ли введен кошелек биткоин
        //@docs https://www.regextester.com/24
        // $this->wallet_out($message, FALSE);
        
        $this->start_set("text_sum", $message, $bill);

        $balance = $this->BalanceModel->get($message['message']['chat']['id']);
        $data_user['balance'] = round($balance);

        $data_page = $this->PagesModel->page(20, $message, $data_user);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        if ($upd) {
            return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
        }
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    public function wallet_out($message, $upd = TRUE) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);
        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        
        $this->BalanceModel = new \Balance\Models\BalanceModel();
        $balance = $this->BalanceModel->get($message['message']['chat']['id']);
        $data_user['balance'] = number_format($balance, $this->decimals, ',', ' ');

        //не хватает на балансе
        if ($balance < $this->min_out) {
            $data_page = $this->PagesModel->page(24, $message, $data_user);
            return $this->answerCallbackQuery($message['id'], $data_page['text']);
        }

        $this->start_set("text_bill", $message);
        $data_page = $this->PagesModel->page(18, $message, $data_user);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        if ($upd) {
            return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
        }
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    public function profile($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->BalanceModel = new \Balance\Models\BalanceModel();
        $this->clear($message);
        $this->SubscribeModel = new \Orders\Models\SubscribeModel();

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['username'] = empty($data_user['username']) ? "<a href='tg://user?id=".$data_user['chat_id']."'>написать</a>" : "@".$data_user['username'];
        
        $data_user['created_on'] = date("d.m.Y H:i", $data_user['created_on']);

        $balance = $this->BalanceModel->get($message['message']['chat']['id']);
        $data_user['balance'] = number_format($balance, $this->decimals, ',', ' ');

        $this->PostsModel = new \Sender\Models\PostsModel();
        $data_user['subscribes'] = $this->PostsModel->my_subscribe_string($message['message']['chat']['id']);

        $data_page = $this->PagesModel->page(14, $message, $data_user);
        $params = $this->MenuModel->profile($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        if (!empty($data_page['file_id'])) {
            $params['caption'] = $data_page['text'];
            return $this->sendFile($message['message']['chat']['id'], $data_page['file_id'], $params);
        }
        $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    public function link($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);

        $this->AffModel = new \Aff\Models\AffModel();
        $this->BalanceModel = new \Balance\Models\BalanceModel();

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_user['bot_name'] = $this->getMe()->result->username;

        $count_aff_by_levels = $this->AffModel->count_aff_by_levels($message['message']['chat']['id']);
        foreach ($count_aff_by_levels as $level => $count) {
            $data_user['count_'.$level] = $count;
        }

        $data_user['items'] = "";
        $count_aff_by_levels = $this->AffModel->count_aff_by_levels($message['message']['chat']['id']);
        $data_user['levels'] = $this->AffModel->count_levels();
        foreach ($count_aff_by_levels as $level => $count) {
            $data_user['count_'.$level] = $count;
            if ($level > $data_user['levels']) {
                continue;
            }
            if ($level > 1) {
                $data_user['items'].="\n";
            }
            $data_user['items'].=$level." - ".$count." \xF0\x9F\x91\xA4";

            $profit = $this->AffModel->profit_invited_level($message['message']['chat']['id'], $level);
            if ($profit > 0) {
                $data_user['items'].=" (".number_format($profit, $this->decimals, ',', ' ')." ".$this->currency_name.")";
            }
        }
        if (count($count_aff_by_levels) <= 0) {
            $data_user['items'] = "-";
        }

        $data_user['total_aff'] = $this->AffModel->total_aff($message['message']['chat']['id']);

        $payed = $this->BalanceModel->total_payed($message['message']['chat']['id']);
        $data_user['payed'] = number_format($payed, $this->decimals, ',', ' ');
        
        $balance = $this->BalanceModel->get($message['message']['chat']['id']);
        $data_user['balance'] = number_format($balance, $this->decimals, ',', ' ');

        $balance_aff = $this->BalanceModel->in($message['message']['chat']['id'], "aff");
        $data_user['balance_aff'] = number_format($balance_aff, $this->decimals, ',', ' ');

        $balance_bonus = $this->BalanceModel->in($message['message']['chat']['id'], "bonus");
        $data_user['balance_bonus'] = number_format($balance_bonus, $this->decimals, ',', ' ');

        $in = $this->BalanceModel->in($message['message']['chat']['id'], "aff");
        $data_user['in'] = number_format($in, $this->decimals, ',', ' ');

        $id_page = 10;
        $params = $this->MenuModel->get($message, 4);

        if ($this->only_payed) {
            $this->OrderModel = new \Orders\Models\OrderModel();
            $count_buyed_products = $this->OrderModel->buyed_products($message['message']['chat']['id'], FALSE);
            if ($count_buyed_products <= 0) {
                $id_page = 59; //если нет активных подписок не показываем партнерскую ссылку
                $params = $this->MenuModel->get($message);
            }
        }

        $data_page = $this->PagesModel->page($id_page, $message, $data_user);
        
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        
        if (!empty($data_page['file_id'])) {
            if (isset($message['params'][1])) {
                $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
            }
            $params['caption'] = $data_page['text'];
            return $this->sendFile($message['message']['chat']['id'], $data_page['file_id'], $params);
        }
        if (isset($message['params'][1])) {
            return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
        }
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Выбрал частый вопрос
     */
    public function manual($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $id_page = (int) $message['params'][1];
        $this->clear($message);
        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_page = $this->PagesModel->page($id_page, $message, $data_user);
        $params = $this->MenuModel->get($message, 2);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        if (!empty($data_page['file_id'])) {
            $return = $this->sendFile($message['message']['chat']['id'], $data_page['file_id'], $params);
            if ($return->ok) {
                return TRUE;
            }
        }
        // return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    public function help($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);
        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_page = $this->PagesModel->page(55, $message, $data_user);
        $params = $this->MenuModel->get($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        // if (!empty($data_page['file_id'])) {
        //     $return = $this->sendFile($message['message']['chat']['id'], $data_page['file_id'], $params);
        //     if ($return->ok) {
        //         return TRUE;
        //     }
        // }
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    public function contacts($message) {
        if ($this->activation($message)) {
            return FALSE;
        }
        $this->clear($message);
        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();
        $data_page = $this->PagesModel->page(4, $message, $data_user);
        $params = $this->MenuModel->get($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        if (!empty($data_page['file_id'])) {
            $params['caption'] = $data_page['text'];
            $return = $this->sendFile($message['message']['chat']['id'], $data_page['file_id'], $params);
            if ($return->ok) {
                return TRUE;
            }
        }
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

	/*
    Начальная команда
     */
    public function start($message, $upd = FALSE) {
        $this->register($message);
        $this->clear($message);

        if ($this->activation($message)) {
            return FALSE;
        }

        $data_user = $this->ionAuth->user($message['message']['chat']['id'])->getRowArray();

        if (empty($data_user['lang'])) {
            unset($message['params']);
            if ($this->language($message)) {
                return FALSE;
            }
        }

        // $this->start_set("text_find", $message);

        $data_page = $this->PagesModel->page(1, $message, $data_user);
        $params = $this->MenuModel->get($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        if (isset($message['params'][1]) OR $upd) {
            return $this->editMessageText($message['message']['chat']['id'], $data_page['text'], $message['message']['message_id'], $params);
        }
        if (!empty($data_page['file_id'])) {
            $this->sendFile($message['message']['chat']['id'], $data_page['file_id']);
        }
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Выбор языка
     */
    public function language($message) {
        if ($message['message']['chat']['id'] <= 0) {
            return FALSE; //если команду набрали в групповом чате
        }

        if (count($this->LangModel->languages()) <= 1) {
            return FALSE; //если язык всего один в системе выбор не нужен
        }

        if (isset($message['params'][1])) {//сохраняем выбранный язык
            if (
                $this->db->table('users')
                ->where('chat_id', $message['message']['chat']['id'])
                ->update(['lang' => $message['params'][1]])
            ) {

                //уведомляем что язык сохранен
                $data_page = $this->PagesModel->page(3, $message);
                $this->answerCallbackQuery($message['id'], $data_page['text']);

                $this->deleteMessage($message['message']['chat']['id'], $message['message']['message_id']);
                unset($message['params']);

                return $this->start($message);
            }
        }

        $data_page = $this->PagesModel->page(2, $message);
        $params = $this->MenuModel->language($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

    /*
    Уведомляем что приглашен новый партнер
     */
    public function notify_new_aff($chat_id_invited, $chat_id_parent) {
        if ($this->notify_new_aff <= 0) {
            return FALSE;
        }

        $message['message']['chat']['id'] = $chat_id_parent;
        $data_user = $this->ionAuth->user($chat_id_invited)->getRowArray();
        $data_page = $this->PagesModel->page(5, $message, $data_user);
        $params = $this->MenuModel->get($message);
        $params['disable_web_page_preview'] = $data_page['disable_web_page_preview'];
        return $this->sendMessage($message['message']['chat']['id'], $data_page['text'], $params);
    }

	/*
     * Регистрируем в боте
     */

    public function register($message) {
        if ($message['message']['chat']['id'] <= 0) {
            return FALSE;
        }

        $identity = $message['message']['chat']['id'];
        $email = $message['message']['chat']['id']."@".$_SERVER['HTTP_HOST'];
        $groups_id = [2]; //группы пользователей
        $password = uniqid();

        if ($this->ionAuth->idCheck($identity)) {
        	return TRUE; //если такой пользователь уже есть
        }

        $additional_data['chat_id'] = $message['message']['chat']['id'];

        if (isset($message['message']['chat']['first_name'])) {
            $additional_data['first_name'] = $message['message']['chat']['first_name'];
        }
        if (isset($message['message']['chat']['last_name'])) {
            $additional_data['last_name'] = $message['message']['chat']['last_name'];
        }
        if (isset($message['message']['chat']['username'])) {
            $additional_data['username'] = $message['message']['chat']['username'];
        }

        if ($this->ionAuth->register($identity, $password, $email, $additional_data, $groups_id)) {

            //при первом старте задаем настройки "все уведомления"
            $this->PostsModel = new \Sender\Models\PostsModel();
            $this->PostsModel->set_subscribe($message['message']['chat']['id'], [-2, -3]);

            if (isset($message['params'][1])) {
                //закрепляем партнера
                $this->AffModel = new \Aff\Models\AffModel();
                if ($this->AffModel->set_aff($message['message']['chat']['id'], $message['params'][1])) {
                        //уведомить родителя о том что приглашен новый пользователь
                    $this->notify_new_aff($message['message']['chat']['id'], $message['params'][1]);
                }
            }


            return TRUE;
        }

        return FALSE;
    }

	/*
	Получение данных от Telegram
	 */
	public function hook() {
        if (!$data = $this->extractData() OR !is_array($data)) {
        	return FALSE;
        }
        $message = $data['message'];

        //!!!
        // return $this->sendMessage($message['message']['chat']['id'], "Жду доступ к хостингу для переноса. Зарегистрируйте хостинг пожалуйста любой.");

        //тут можно добавить обработчики данных сообщения которое пришло боту
        //если до этого не сработала никакая команда

        // Ответ бота на неизвестную команду
        return $this->notKnow($message);
    }

}
