<?php
/**
 * Name:    Модель для менюшек конкретного бота
 * Индивидуальные меню данного бота
 *
 * Created:  03.04.2020
 *
 * Description:  
 *
 * Requirements: PHP 7.2 or above
 *
 * @author Krotov Roman <tg: @KrotovRoman>
 */

namespace App\Models;
use CodeIgniter\Model;
use \CodeIgniter\Database\ConnectionInterface;

/**
 * Class MenuModel дочерний от MenusModel
 */
class MenuModel extends \Telegram\Models\MenusModel
{	 
  /*
  Оплата yookassa
   */
  public function crypto_currency($message) {
    //да все верно
    $button2 = [];
    $button2['text'] = $this->btn($message, 35);
    $button2['callback_data'] = '/confirm_finish_order';
    $keyboard[]= [$button2];

    //назад
    $button = [];
    $button['text'] = $this->btn($message, 33);
    $button['callback_data'] = '/select_pay_order';
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Список заказов
   */
  public function history_order($message) {
    $button = [];
    $button['text'] = $this->btn($message, 33);
    $button['callback_data'] = '/history 0';
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  public function history($message) {
    $offset = isset($message['params'][1]) ? $message['params'][1] : 0;
    $offset = $offset <= 0 ? 0 : $offset;

    $this->OrderModel = new \Orders\Models\OrderModel();
    $count = $this->OrderModel->history($message['message']['chat']['id'], $offset, TRUE);
    $offset = $offset > $count ? 0 : $offset;
    
    $items =  $this->OrderModel->history($message['message']['chat']['id'], $offset);

    $keyboard = [];
    foreach ($items as $item) {
      $button = [];
      $button['text'] = stripcslashes('№'.$item['id'].' от '.$item['created']);
      $button['callback_data'] = '/order';
      $button['callback_data'] .= ' ' . $offset;
      $button['callback_data'] .= ' ' . $item['id'];
      $keyboard[] = [$button];
    }

    //добавляем внизу кнопки навигации
    //кнопка вперед
    $button_next = [];
    $button_next['text'] = $this->btn($message, 48); //Вперед >>
    $button_next['callback_data'] = '/history';
    $button_next['callback_data'] .= ' ' . ($offset + $this->limit_menu); //сдвиг вправо

    //кнопка назад
    $button_back = [];
    $button_back['text'] = $this->btn($message, 49); //<< Назад
    $button_back['callback_data'] = '/history';
    $button_back['callback_data'] .= ' ' . ($offset - $this->limit_menu); //сдвиг влево
    
    //добавляем внизу кнопки навигации 
    if ($count > $this->limit_menu) {
      if ($offset > 0) {
        if (count($items) <= 0 OR 
          (($offset + 1) == $count) OR 
          (($offset + $this->limit_menu) >= $count)
        ) {
                $keyboard[] = [$button_back]; //если дошли до конца - то только НАЗАД
            } else {
              $keyboard[] = [$button_back, $button_next];
            }
          } else {
            $keyboard[] = [$button_next]; //если в самом начале - то только ВПЕРЕД
          }
    }//if

    return $this->inline_keyboard($keyboard);
  }

  /*
  Вы подтверждаете отправку заказа
   */
  public function confirm_finish_order($message) {
    //да все верно
    $button2 = [];
    $button2['text'] = $this->btn($message, 39);
    $button2['callback_data'] = '/confirm_finish_order';
    
    //отмена
    $button = [];
    $button['text'] = $this->btn($message, 36);
    $button['callback_data'] = '/cancel';

    $keyboard[]= [$button, $button2];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Оплата WayForPay
   */
  public function wayforpay($message, int $id_order) {
    $id_product = 0;
    $sum = (int) $message['params'][1];
    $id_pay = (int) $message['params'][2];

    //оплатить
    $button = [];
    $button['text'] = $this->btn($message, 32);
    $button['url'] = base_url('wayforpay/pay/'.$id_order);
    $keyboard[]= [$button];

    //назад
    $button = [];
    $button['text'] = $this->btn($message, 33);
    $button['callback_data'] = '/select_pay_order';
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  
  /*
  Запросить геолокацию
   */
  public function enter_address($message) {
    $keyboard = [];

    if (!empty($this->yamap)) {
      $button = [];
      $button['text'] = $this->btn($message, 83);
      $button['request_location'] = TRUE;
      $keyboard[]= [$button];
    }

    return $this->keyboard($keyboard);
  }

  /*
  Оплата с баланса
   */
  public function pay_balance($message, int $id_order) {
    $id_product = (int) $message['params'][1];
    $id_pay = (int) $message['params'][2];

    $button = [];
    $button['text'] = $this->btn($message, 81);
    $button['callback_data'] = '/balance '.$id_product.' '.$id_pay.' 0 yes';
    $keyboard[]= [$button];

    //назад
    $button = [];
    $button['text'] = $this->btn($message, 33);
    $button['callback_data'] = '/select_pay_order';
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Продукт добавлен в корзину
   */
  public function added_product($message, $id_product) {
    $id_category = (int) $message['params'][1];
    $id_parent = (int) $message['params'][2];
    
    //к оплате
    $button = [];
    $button['text'] = $this->btn($message, 74);
    $button['callback_data'] = '/order_create';
    $keyboard[]= [$button];
    
    //назад
    $button = [];
    $button['text'] = $this->btn($message, 33);
    $button['callback_data'] = '/start_order '.$id_parent.' delete';
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Вывести кнопки выбора модификаторов
   */
  public function select_mod($message, $data_product) {
    $id_category = (int) $message['params'][1];
    $id_parent = (int) $message['params'][2];
    $id_mod = isset($message['params'][3]) ? (int) $message['params'][3] : 0;
    $id_mod2 = isset($message['params'][4]) ? (int) $message['params'][4] : 0;

    $this->ModModel = new \Mods\Models\ModModel();
    $this->ProductModel = new \Products\Models\ProductModel();

    $product = $data_product['product'];
    $id_product = $product['id'];

    //выдаем список модификаторов
    $mods_category = $this->ModModel->mods_category($id_category, $id_mod);

    $kb = [];
    foreach ($mods_category as $mod) {
      $checked = "";
      if ($id_mod == $mod['id'] OR $id_mod2 == $mod['id']) {
        $checked = json_decode('"\u2714\ufe0f"').' ';
      }

      $button = [];
      $button['text'] = $checked.$mod['name'];
      $button['callback_data'] = '/select_mod '.$id_category.' '.$id_parent.' '.$mod['id'].' '.$id_mod;
      $kb[]= $button;
    }
        
    if (count($kb) > 2) {
      $kb = array_chunk($kb, $this->mod_count_btns);
      foreach($kb as $item) {
        $keyboard[] = $item;
      }
    } else {
      $keyboard[] = $kb;
    }

    if ($id_mod > 0 AND $id_mod2 AND $data_product['product_item']) {
      $this->OrderModel = new \Orders\Models\OrderModel();
      $count_items_in_cart = $this->OrderModel->count_items_in_cart($message['message']['chat']['id'], $id_product);

      // купить продукт - без выбора единицы товара
      $button = [];
      $button['text'] = $count_items_in_cart <= 0 ? $this->btn($message, 34) : $this->btn($message, 34).' ('.$count_items_in_cart.')';
      $button['callback_data'] = '/cart_add_item '.$id_category.' '.$id_parent.' '.$mod['id'].' '.$id_mod.' '.$data_product['product_item']['id'];
      
      $button2 = [];
      $button2['text'] = $count_items_in_cart <= 0 ? $this->btn($message, 72) : $this->btn($message, 72).' ('.$count_items_in_cart.')';
      $button2['callback_data'] = '/cart_del_item '.$id_category.' '.$id_parent.' '.$mod['id'].' '.$id_mod.' '.$data_product['product_item']['id'];
      
      if ($count_items_in_cart <= 0) {
        $keyboard[]= [$button];
      } else {
        if (!$this->ProductModel->get_free_item($id_product)) {
          $keyboard[]= [$button2]; //если нет в наличии то добавлять нельзя
        } else {
          $keyboard[]= [$button, $button2];
        }

        //к оплате
        $button = [];
        $button['text'] = $this->btn($message, 74);
        $button['callback_data'] = '/order_create';
        $keyboard[]= [$button];
      }
    }

    //назад
    $button = [];
    $button['text'] = $this->btn($message, 33);
    $button['callback_data'] = '/start_order '.$id_parent.' delete';
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Оплата yookassa
   */
  public function yookassa($message, int $id_order) {
    
    //оплатить
    $button = [];
    $button['text'] = $this->btn($message, 74);
    $button['url'] = base_url('yookassa/check/'.$id_order);
    // $button['callback_data'] = '/ya_invoice '.$id_order;
    $keyboard[]= [$button];

    //назад
    $button = [];
    $button['text'] = $this->btn($message, 33);
    $button['callback_data'] = '/select_pay_order';
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }
  
  /*
  Поиск по названию
   */
  public function find($message) {
    //отмена
    $button2 = [];
    $button2['text'] = $this->btn($message, 36);
    $button2['callback_data'] = '/start_order 0';

    $keyboard[]= [$button2];
    return $this->inline_keyboard($keyboard);
  }

  /*
  Меню с уведомлением о том что заказ недооформлен
   */
  public function notify_no_finish($message) {

    $button = [];
    $button['text'] = $this->btn($message, 78);
    $button['callback_data'] = '/start_order';
    $keyboard[]= [$button];

    //только об акциях
    $button = [];
    $button['text'] = $this->btn($message, 66);
    $button['callback_data'] = '/select_subscribe -2';

    //новинки
    $button2 = [];
    $button2['text'] = $this->btn($message, 67);
    $button2['callback_data'] = '/select_subscribe -3';

    $keyboard[]= [$button, $button2];

    //Акции и новинки
    $button = [];
    $button['text'] = $this->btn($message, 68);
    $button['callback_data'] = '/select_subscribe -2 -3';
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Отправьте телфефон 
   */
  public function phone($message) {
    $button = [];
    $button['text'] = $this->btn($message, 84);
    $button['request_contact'] = TRUE;
    $keyboard[]= [$button];

    return $this->keyboard($keyboard);
  }

  /*
  При оформлении заказа
   */
  public function order_create($message) {

    $this->OrderModel = new \Orders\Models\OrderModel();
    $id_order = $this->OrderModel->active($message['message']['chat']['id']);
    $data_order = $this->OrderModel->get($id_order);

    if (isset($data_order['call_whatsapp']) AND $data_order['call_whatsapp'] > 0) {
      $button2 = [];
      $button2['text'] = $this->btn($message, 76);
      $button2['callback_data'] = '/order_create 0';
      $keyboard[]= [$button2];
    } else {
      $button2 = [];
      $button2['text'] = $this->btn($message, 75);
      $button2['callback_data'] = '/order_create 1';
      $keyboard[]= [$button2];
    }

    //отмена
    $button2 = [];
    $button2['text'] = $this->btn($message, 36);
    $button2['callback_data'] = '/cart 0';

    $keyboard[]= [$button2];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Выберите подписку на которую подписаться
   */
  public function select_subscribe($message) {
    //только об акциях
    $button = [];
    $button['text'] = $this->btn($message, 66);
    $button['callback_data'] = '/select_subscribe -2';

    //новинки
    $button2 = [];
    $button2['text'] = $this->btn($message, 67);
    $button2['callback_data'] = '/select_subscribe -3';

    $keyboard[]= [$button, $button2];

    //Акции и новинки
    $button = [];
    $button['text'] = $this->btn($message, 68);
    $button['callback_data'] = '/select_subscribe -2 -3';

    //отключить
    $button2 = [];
    $button2['text'] = $this->btn($message, 69);
    $button2['callback_data'] = '/select_subscribe 0';

    $keyboard[]= [$button, $button2];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Укажите пожалуйста свой европейский размер
   */
  public function select_size($message) {
    $keyboard = [];
    $this->ModModel = new \Mods\Models\ModModel();
    //размеры
    $sizes = $this->ModModel->sizes();

    $kb = [];
    foreach ($sizes as $size) {
      $button = [];
      $button['text'] = $size['name'];
      $button['callback_data'] = '/select_size '.$size['id'];
      $kb[]= $button;
    }

    if (count($kb) > 2) {
      $kb = array_chunk($kb, $this->mod_count_btns);
      foreach($kb as $item) {
        $keyboard[] = $item;
      }
    } else {
      $keyboard[] = $kb;
    }

    //детский
    $button = [];
    $button['text'] = $this->btn($message, 61);
    $button['url'] = $this->support_chat;

    //размер больше
    $button2 = [];
    $button2['text'] = $this->btn($message, 62);
    $button2['url'] = $this->support_chat;

    $keyboard[]= [$button, $button2];

    //найти вещь/ аксессуар
    $button = [];
    $button['text'] = $this->btn($message, 63);
    $button['url'] = $this->support_chat;

    $keyboard[]= [$button];

    //менеджер
    $button2 = [];
    $button2['text'] = $this->btn($message, 64);
    $button2['url'] = $this->support_chat;
    
    $keyboard[]= [ $button2];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Отправка txid
   */
  public function pay_bitcoin($message, int $id_order) {
    $id_order = (int) $message['params'][1];

    $this->OrderModel = new \Orders\Models\OrderModel();
    $data_order = $this->OrderModel->get($id_order);
    $poducts = $this->OrderModel->products($id_order);

    //отмена
    $button = [];
    $button['text'] = $this->btn($message, 36);
    $button['callback_data'] = '/pay '.$poducts[0]['id_product']." ".$data_order['id_pay'];
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Оплата биткоин
   */
  public function bitcoin($message, int $id_order) {
    $id_product = (int) $message['params'][1];
    $id_pay = (int) $message['params'][2];

    $this->PayModel = new \Pays\Models\PayModel();
    $address = $this->PayModel->get($id_pay, "address");
    $data_pay = $this->PayModel->pay($id_pay);

    $this->BlockioModel = new \Blockio\Models\BlockioModel();
    if (!$this->BlockioModel->init($data_pay['currency'])) {
      $this->BlockchainModel = new \Btc\Models\BlockchainModel();
      if (!empty($address) AND empty($this->BlockchainModel->blockchain_api_key())) {
        $button = [];
        $button['text'] = $this->btn($message, 43);
        $button['callback_data'] = '/pay_bitcoin '.$id_order;
        $keyboard[]= [$button];
      }
    }
    
    $pays = $this->PayModel->items(TRUE);

    //назад
    $button = [];
    $button['text'] = $this->btn($message, 52); //33
    if (count($pays) == 1) {
      $button['callback_data'] = '/start_order 1';
    } else {
      $sum = (int) $message['params'][1];
      // $button['callback_data'] = '/select_pay '.$sum;
      $button['callback_data'] = '/start';
    }
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }
  
  /*
  При отправке поста в рассылке
   */
  public function confirm_out_user(array $message, $sum, $bill) {
    $keyboard = [];

    //отмена
    $button = [];
    $button['text'] = $this->btn($message, 36);
    $button['callback_data'] = '/link 0';

    $button2 = [];
    $button2['text'] = $this->btn($message, 39);
    $button2['callback_data'] = '/confirm_out_user yes '.$sum." ".$bill;

    $keyboard[]= [$button, $button2];

    return $this->inline_keyboard($keyboard);
  }

  /*
  При отправке поста в рассылке
   */
  public function post_bonus(array $message, int $id_post) {
    $keyboard = [];
    
    //получить бонус
    $button = [];
    $button['text'] = $this->btn($message, 38);
    $button['callback_data'] = '/post_bonus '.$id_post;
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  /*
  У администрации
  Подтвердить оплату
   */
  public function file_check($message, $id_order = FALSE) {
    $id_order OR $id_order = (int) $message['params'][1];

    //подтвердить
    $button = [];
    $button['text'] = $this->btn($message, 37);
    $button['callback_data'] = '/file_check_approve '.$id_order;
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Оплата ручной способ
   */
  public function pay_hand_file($message, int $id_order) {
    $id_order = (int) $message['params'][1];

    $this->OrderModel = new \Orders\Models\OrderModel();
    $data_order = $this->OrderModel->get($id_order);
    $poducts = $this->OrderModel->products($id_order);

    //отмена
    $button = [];
    $button['text'] = $this->btn($message, 36);
    $button['callback_data'] = '/pay '.$poducts[0]['price']." ".$data_order['id_pay'];
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Оплата ручной способ
   */
  public function pay_hand($message, int $id_order) {
    $id_product = 0;
    $sum = (int) $message['params'][1];
    $id_pay = (int) $message['params'][2];

    $button = [];
    $button['text'] = $this->btn($message, 35);
    $button['callback_data'] = '/pay_hand_file '.$id_order;
    $keyboard[]= [$button];

    //назад
    $button = [];
    $button['text'] = $this->btn($message, 33);
    $sum = (int) $message['params'][1];
    // $button['callback_data'] = '/select_pay '.$sum;
    $button['callback_data'] = '/select_pay_order';
  
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Оплата Qiwi
   */
  public function qiwi($message, int $id_order) {
    $id_product = (int) $message['params'][1];
    $id_pay = (int) $message['params'][2];

    $this->PayModel = new \Pays\Models\PayModel();
    $data_pay = $this->PayModel->pay($id_pay);

    $this->QiwiModel = new \Qiwi\Models\QiwiModel();
    $qiwi = $this->QiwiModel->get_wallet_id();

    $this->OrderModel = new \Orders\Models\OrderModel();
    $data_order = $this->OrderModel->get($id_order);

    //оплатить
    if ($qiwi > 0) {
      $button = [];
      $button['text'] = $this->btn($message, 34);
      $button['url'] = "https://qiwi.com/payment/form/99?extra%5B%27account%27%5D=".$qiwi."&amountInteger=".$data_order['sum_pay']."&amountFraction=0&extra%5B%27comment%27%5D=".$id_order."&currency=643&blocked%5B0%5D=sum&blocked%5B1%5D=account&blocked%5B2%5D=comment";
      $keyboard[]= [$button];
    }

    $pays = $this->PayModel->items(TRUE);

    //назад
    $button = [];
    $button['text'] = $this->btn($message, 52); //33
    if (count($pays) == 1) {
      $button['callback_data'] = '/start_order 1';
    } else {
      $sum = (int) $message['params'][1];
      // $button['callback_data'] = '/select_pay '.$sum;
      $button['callback_data'] = '/start';
    }
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Оплата Карты
   */
  public function card($message, int $id_order) {
    $id_product = (int) $message['params'][1];
    $id_parent = isset($message['params'][2]) ? (int) $message['params'][2] : 0;
    //оплатить
    $button = [];
    $button['text'] = $this->btn($message, 34);
    $button['url'] = base_url('yandex/card/'.$id_order);
    $keyboard[]= [$button];

    $this->PayModel = new \Pays\Models\PayModel();
    $pays = $this->PayModel->items(TRUE);

    //назад
    $button = [];
    $button['text'] = $this->btn($message, 52); //33
    if (count($pays) == 1) {
      $button['callback_data'] = '/start_order '.$id_parent;
    } else {
      $sum = (int) $message['params'][1];
      // $button['callback_data'] = '/select_pay '.$sum;
      $button['callback_data'] = '/start';
    }
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Оплата FreeKassa
   */
  public function freekassa($message, int $id_order) {
    $id_product = (int) $message['params'][1];
    $id_parent = isset($message['params'][2]) ? (int) $message['params'][2] : 0;

    //оплатить
    $button = [];
    $button['text'] = $this->btn($message, 34);
    $button['url'] = base_url('freekassa/pay/'.$id_order);
    $keyboard[]= [$button];

    $this->PayModel = new \Pays\Models\PayModel();
    $pays = $this->PayModel->items(TRUE);

    //назад
    $button = [];
    $button['text'] = $this->btn($message, 52); //33
    if (count($pays) == 1) {
      $button['callback_data'] = '/start_order '.$id_parent;
    } else {
      $sum = (int) $message['params'][1];
      // $button['callback_data'] = '/select_pay '.$sum;
      $button['callback_data'] = '/start';
    }
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Оплата Яндекс.Деньги
   */
  public function yandex($message, int $id_order) {
    $id_product = (int) $message['params'][1];
    $id_parent = isset($message['params'][2]) ? (int) $message['params'][2] : 0;

    //оплатить
    $button = [];
    $button['text'] = $this->btn($message, 34);
    $button['url'] = base_url('yandex/yandex/'.$id_order);
    $keyboard[]= [$button];

    $this->PayModel = new \Pays\Models\PayModel();
    $pays = $this->PayModel->items(TRUE);

    //назад
    $button = [];
    $button['text'] = $this->btn($message, 52); //33
    if (count($pays) == 1) {
      $button['callback_data'] = '/start_order '.$id_parent;
    } else {
      $sum = (int) $message['params'][1];
      // $button['callback_data'] = '/select_pay '.$sum;
      $button['callback_data'] = '/start';
    }
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Подтверждаете покупку?
   */
  public function buy_confirm($message) {
    $id_product = (int) $message['params'][1];
    $id_parent = isset($message['params'][2]) ? (int) $message['params'][2] : 0;
    $count = (int) $message['message']['text'];
    
    //отмена
    $button = [];
    $button['text'] = $this->btn($message, 36);
    $button['callback_data'] = '/product '.$id_product.' '.$id_parent;
    
    //подтвердить
    $button2 = [];
    $button2['text'] = $this->btn($message, 45);
    $button2['callback_data'] = '/buy_confirm '.$id_product.' '.$count;
    
    $keyboard[]= [$button, $button2];

    //главное меню
    $button = [];
    $button['text'] = $this->btn($message, 52);
    $button['callback_data'] = '/start';
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Нажал кнопку оплатить
   */
  public function buy($message) {
    $id_product = (int) $message['params'][1];
    $id_parent = isset($message['params'][2]) ? (int) $message['params'][2] : 0;
    $is_final = isset($message['params'][3]) ? (bool) $message['params'][3] : FALSE;

    //отмена
    $button = [];
    $button['text'] = $this->btn($message, 36);
    $button['callback_data'] = '/product '.$id_product.' '.$id_parent." ".$is_final." true";
    $keyboard[]= [$button];

    //главное меню
    $button = [];
    $button['text'] = $this->btn($message, 52);
    $button['callback_data'] = '/start';
    $keyboard[]= [$button];
    
    return $this->inline_keyboard($keyboard);
  }

  /*
  Выбрать цвет
   */
  public function select_color($message) {
    $id_parent = isset($message['params'][1]) ? (int) $message['params'][1] : 0;
    $offset = isset($message['params'][2]) ? (int) $message['params'][2] : 0;

    $this->ModModel = new \Mods\Models\ModModel();
    $colors = $this->ModModel->colors();

    $kb = [];
    foreach ($colors as $mod) {
      $button = [];
      $button['text'] = $mod['name'];
      $button['callback_data'] = '/color '.$id_parent." 0 ".$mod['id'];
      $kb[]= $button;
    }
    if (count($kb) > 2) {
      $kb = array_chunk($kb, $this->mod_count_btns);
      foreach($kb as $item) {
        $keyboard[] = $item;
      }
    } else {
      $keyboard[] = $kb;
    }
      
    //назад
    $button = [];
    $button['text'] = $this->btn($message, 33);
    $button['callback_data'] = '/start_order 0';
    $keyboard[]= [$button];
    
    return $this->inline_keyboard($keyboard);
  }

  /*
  Выбран продукт
   */
  public function product($message) {

    $id_product = (int) $message['params'][1];
    $id_parent = isset($message['params'][2]) ? (int) $message['params'][2] : 0;
    $is_final = isset($message['params'][3]) ? (bool) $message['params'][3] : FALSE;
    $offset = isset($message['params'][5]) ? (int) $message['params'][5] : 0;
    $id_mod = isset($message['params'][6]) ? (int) $message['params'][6] : FALSE;

    $keyboard = [];

    $this->ModModel = new \Mods\Models\ModModel();
    $this->OrderModel = new \Orders\Models\OrderModel();
    $this->ProductModel = new \Products\Models\ProductModel();

    $product = $this->ProductModel->get($id_product);
    $description = trim($this->ProductModel->text($id_product));

    $count_items_in_cart = $this->OrderModel->count_items_in_cart($message['message']['chat']['id'], $id_product);

    if ($this->need_select_item <= 0) {

      //индивидуальные цены за определенное кол-во
      $product_items_kg = $this->ProductModel->product_items_kg($id_product);
      $arr_btn = [];
      foreach ($product_items_kg as $item_kg) {
        $button = [];
        $button['text'] = $this->btn($message, 85).stripcslashes($item_kg['value'].'кг-'.$item_kg['price'].' '.$this->currency_name);
        $button['callback_data'] = '/cart_add_count '.$id_product.' '.$id_parent." ".$is_final.' '.$offset.' '.$id_mod.' '.$item_kg['value'];
        $arr_btn[]=$button;
      }
      //разбиваем массив на кнопки по 3
      if (count($arr_btn) > 0) {
        $count_btns = 3;
        if (count($arr_btn) > $count_btns) {
          $kb = array_chunk($arr_btn, $count_btns);
          foreach($kb as $item) {
            $keyboard[] = $item;
          }
        } else {
          $keyboard[] = $arr_btn;
        }
      }

      // купить продукт - без выбора единицы товара
      $button = [];
      $button['text'] = $count_items_in_cart <= 0 ? $this->btn($message, 34) : $this->btn($message, 34).' ('.$count_items_in_cart.')';
      $button['callback_data'] = '/cart_add '.$id_product.' '.$id_parent." ".$is_final.' '.$offset.' '.$id_mod;
      
      $button2 = [];
      $button2['text'] = $count_items_in_cart <= 0 ? $this->btn($message, 72) : $this->btn($message, 72).' ('.$count_items_in_cart.')';
      $button2['callback_data'] = '/cart_del '.$id_product.' '.$id_parent." ".$is_final.' '.$offset.' '.$id_mod;
      
      if ($count_items_in_cart <= 0) {
        $keyboard[]= [$button];
      } else {
        if (!$this->ProductModel->get_free_item($id_product)) {
          $keyboard[]= [$button2]; //если нет в наличии то добавлять нельзя
        } else {
          $keyboard[]= [$button, $button2];
        }

        //к оплате
        $button = [];
        $button['text'] = $this->btn($message, 74);
        $button['callback_data'] = '/order_create';
        $keyboard[]= [$button];
      }


    } else {//если нужен выбор единиц товара поштучно
      $button = [];
      $button['text'] = $this->btn($message, 34);
      $button['callback_data'] = '/buy '.$id_product.' '.$id_parent." ".$is_final;
      
      $button2 = [];
      $button2['text'] = $this->btn($message, 51);
      $button2['callback_data'] = '/description '.$id_product.' '.$id_parent." ".$is_final;
      
      if ($this->need_btn_description > 0) {
        if (!empty($description)){
          $keyboard[]= [$button, $button2];
        } else {
          $keyboard[]= [$button2];
        }
      }
      
      //вывести список единиц товара
      $product_items = $this->ProductModel->product_items($id_product, TRUE, $message['message']['chat']['id'], $id_mod);
      $id_item = 0;
      $kb = [];
      foreach ($product_items as $product_item) {
        //получаем модификаторы
        $mods_item_string = $this->ModModel->mods_item_string($product_item['id']);

        $button = [];
        $button['text'] = $mods_item_string; //$product_item['articul'].' '.$product['name'];
        $button['callback_data'] = '/product_item '.$id_product.' '.$id_parent.' '.$is_final.' 0 '.$offset.' '.$product_item['id'];
        $kb[]= $button;

        $id_item = $product_item['id'];
      }

      // //выводим кнопки выбора единиц товара
      if (count($kb) > 2) {
        $kb = array_chunk($kb, $this->mod_count_btns);
        foreach($kb as $item) {
          $keyboard[] = $item;
        }
      } else {
        $keyboard[] = $kb;
      }
    }

    if ($is_final) {
      $path = $this->ProductModel->path($message['message']['chat']['id'], TRUE);
      if (count($path) > 0) {
        $id_parent_ = $path[0];
      } else {
        $id_parent_ = $this->ProductModel->parent_product($id_product);
      }

      if ($id_mod) {//если это список с выборкой по модификатору
        $this->ModModel = new \Mods\Models\ModModel();
        $count = $this->ModModel->products_with_mod($id_mod, $offset, TRUE);
        if ($offset < $count AND $count > $this->limit_products) {
          $new_offset = ($offset + $this->limit_products);
          $products = $this->ModModel->products_with_mod($id_mod, $new_offset);
          if (count($products) > 0) {
            //подгрузить еще
            $button = [];
            $button['text'] = $this->btn($message, 59);
            $button['callback_data'] = '/color '.$id_parent." ".$new_offset." ".$id_mod;
            $keyboard[]= [$button];
          }
        }
      } else {//если это обычная выдача продукта
        $new_offset = ($offset + $this->limit_products);

        if ($this->see_more_always > 0) { //всегда показывать кнопку "подгрузить еще" - ускоряет работу
          //подгрузить еще
          $button = [];
          $button['text'] = $this->btn($message, 59);
          $button['callback_data'] = '/products '.$id_parent." ".$new_offset;
          $keyboard[]= [$button];
        } else { //рассчитывать надо ли показывать кнопку "подгрузить еще"
          $count = $this->ProductModel->products_in_menu($id_parent, $message['message']['chat']['id'], $offset, TRUE);
          if ($offset < $count AND $count > $this->limit_products) {
            $products = $this->ProductModel->products_in_menu($id_parent, $message['message']['chat']['id'], $new_offset);
            if (count($products) > 0) {
              //подгрузить еще
              $button = [];
              $button['text'] = $this->btn($message, 59);
              $button['callback_data'] = '/products '.$id_parent." ".$new_offset;
              $keyboard[]= [$button];
            }
          }
        }

      }

      //назад
      $button = [];
      $button['text'] = $this->btn($message, 33);
      if ($id_mod) {
        $button['callback_data'] = '/select_color '.$id_parent_;
      } else {
        $button['callback_data'] = '/start_order '.$id_parent_;
      }
      if (!empty($product['file_id'])) {
        $button['callback_data'].=" delete";
      }

      //главное меню
      $button2 = [];
      $button2['text'] = $this->btn($message, 52);
      $button2['callback_data'] = '/start';
      $keyboard[]= [$button, $button2];
    }

    //Корзина
    $button = [];
    $button['text'] = $this->btn($message, 60);
    $button['callback_data'] = '/cart';
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Корзина
   */
  public function cart($message) {
    $this->OrderModel = new \Orders\Models\OrderModel();
    $items_in_cart = $this->OrderModel->items_in_cart($message['message']['chat']['id']);
    $offset = $this->OrderModel->offset_cart($message);
    $data_item = $this->OrderModel->cart($message['message']['chat']['id'], $offset);

    
    //удалить единицу товара из корзины
    if (isset($data_item['id'])) {
      $button = [];
      $button['text'] = $this->btn($message, 73);
      $button['callback_data'] = '/cart_delele '.$data_item['id'];
      $keyboard[]= [$button];
    }

    //кнопки листания
    $button = [];
    $button['text'] = $this->btn($message, 49);
    $button['callback_data'] = '/cart '.($offset - 1);

    $button2 = [];
    $button2['text'] = $this->btn($message, 48);
    $button2['callback_data'] = '/cart '.($offset + 1);

    $now = $offset + 1;
    $button_12 = [];
    $button_12['text'] = $now.'/'.$items_in_cart;
    $button_12['callback_data'] = '/cart '.$offset;

    if ($items_in_cart > 1) {
      $keyboard[]= [$button, $button_12, $button2];
    }
    
    //к оплате
    $button = [];
    $button['text'] = $this->btn($message, 74);
    $button['callback_data'] = '/order_create';
    $keyboard[]= [$button];
    
    //главное меню
    $button2 = [];
    $button2['text'] = $this->btn($message, 52);
    $button2['callback_data'] = '/start';
    $keyboard[]= [$button2];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Выбрал единицу товара
   */
  public function product_item($message) {
    $id_product = (int) $message['params'][1];
    $id_parent = isset($message['params'][2]) ? (int) $message['params'][2] : 0;
    $is_final = isset($message['params'][3]) ? (bool) $message['params'][3] : FALSE;
    $offset = isset($message['params'][5]) ? (int) $message['params'][5] : 0;
    $id_item = isset($message['params'][6]) ? (int) $message['params'][6] : 0;

    $this->OrderModel = new \Orders\Models\OrderModel();
    $this->ProductModel = new \Products\Models\ProductModel();
    $product = $this->ProductModel->get($id_product);
    $description = trim($this->ProductModel->text($id_product));
    $files = $this->ProductModel->albom($id_item);

    if (count($files) >= 2) {
      $button = []; //больше фото
      $button['text'] = $this->btn($message, 77);
      $button['callback_data'] = '/photos '.$id_product.' '.$id_parent.' '.$is_final.' 0 '.$offset.' '.$id_item;
      $keyboard[]= [$button];
    }

    $id_order = $this->OrderModel->active($message['message']['chat']['id']);
    if ($this->OrderModel->item_in_cart($id_order, $id_item)) {
      //к оплате
      $button2 = [];
      $button2['text'] = $this->btn($message, 74);
      $button2['callback_data'] = '/order_create';

      //убрать из корзины
      $button = [];
      $button['text'] = $this->btn($message, 72);
      $button['callback_data'] = '/buy_del '.$id_product.' '.$id_parent.' '.$is_final.' 0 '.$offset.' '.$id_item;
      $keyboard[]= [$button, $button2];

    } else {
      //купить
      $button = [];
      $button['text'] = $this->btn($message, 34);
      $button['callback_data'] = '/buy '.$id_product.' '.$id_parent.' '.$is_final.' 0 '.$offset.' '.$id_item;
      $keyboard[]= [$button];
    }

    //кнопки артикулов
    // $product_items = $this->ProductModel->product_items($id_product, TRUE);
    // $kb = [];
    // foreach ($product_items as $product_item) {
    //   $button = [];
    //   $button['text'] = $product_item['articul'];
    //   $button['callback_data'] = '/product_item '.$id_product.' '.$id_parent.' '.$is_final.' 0 '.$offset.' '.$product_item['id'];
    //   $kb[]= $button;
    // }
    // if (count($kb) > 2) {
    //     $kb = array_chunk($kb, $this->mod_count_btns);
    //     foreach($kb as $item) {
    //         $keyboard[] = $item;
    //     }
    // } else {
    //     $keyboard[] = $kb;
    // }

    if ($is_final) {
      $path = $this->ProductModel->path($message['message']['chat']['id'], TRUE);
      if (count($path) > 0) {
        $id_parent_ = $path[0];
      } else {
        $id_parent_ = $this->ProductModel->parent_product($id_product);
      }

      $count = $this->ProductModel->products_in_menu($id_parent, $message['message']['chat']['id'], $offset, TRUE);
      if ($offset < $count AND $count > $this->limit_products) {

        $new_offset = ($offset + $this->limit_products);
        $products = $this->ProductModel->products_in_menu($id_parent, $message['message']['chat']['id'], $new_offset);
        if (count($products) > 0) {
          //подгрузить еще
          $button = [];
          $button['text'] = $this->btn($message, 59);
          $button['callback_data'] = '/products '.$id_parent." ".$new_offset;
          $keyboard[]= [$button];
        }
      }

      //назад
      // $button = [];
      // $button['text'] = $this->btn($message, 33);
      // $button['callback_data'] = '/product '.$id_product." ".$id_parent." ".$is_final." 0 ".$offset;

      // //главное меню
      // $button2 = [];
      // $button2['text'] = $this->btn($message, 52);
      // $button2['callback_data'] = '/start';
      // $keyboard[]= [$button, $button2];
    }


    //назад
    $button = [];
    $button['text'] = $this->btn($message, 33);
    $button['callback_data'] = '/product '.$id_product." ".$id_parent." ".$is_final." 0 ".$offset;

    //главное меню
    $button2 = [];
    $button2['text'] = $this->btn($message, 52);
    $button2['callback_data'] = '/start';
    $keyboard[]= [$button, $button2];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Выберите способ оплаты
   */
  public function select_pay($message) {
    $sum = isset($message['message']['text']) ? (int) $message['message']['text'] : 0;
    if (isset($message['params'][1]) AND $sum <= 0) {
      $sum = isset($message['params'][1]) ? (int) $message['params'][1] : 0;
    }
    
    $this->BalanceModel = new \Balance\Models\BalanceModel();
    $balance = $this->BalanceModel->get($message['message']['chat']['id']);

    $this->OrderModel = new \Orders\Models\OrderModel();
    $id_order = $this->OrderModel->active($message['message']['chat']['id']);
    $data_order = $this->OrderModel->get($id_order);
    if ($data_order['sum'] <= 0) {

      //получить бесплатно
      $button = [];
      $button['text'] = $this->btn($message, 82);
      $button['callback_data'] = '/pay '.$sum." 9";
      $keyboard[]= [$button];

    } else { //если надо выбор способа оплаты
      $this->PayModel = new \Pays\Models\PayModel();
      $pays = $this->PayModel->items(TRUE);
      $keyboard = [];
      foreach ($pays as $pay) {
        if ($pay['id'] <= 0 AND $balance <= 0) {
          continue;
        }

        $name = $pay['name']; 
        if ($pay['id'] <= 0) {
          $name.=" (".$balance." ".$this->currency_name.")";
        }

        $button = [];
        $button['text'] = $name;
        $button['callback_data'] = '/pay '.$sum." ".$pay['id'];
        $keyboard[]= [$button];
      }
    }

    

    // //назад
    // $button = [];
    // $button['text'] = $this->btn($message, 33);
    // $button['callback_data'] = '/start';
    // $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Купить
   */
  public function start_order($message) {
    $this->ProductModel = new \Products\Models\ProductModel();
    $this->OrderModel = new \Orders\Models\OrderModel();

    $id_parent = isset($message['params'][1]) ? (int) $message['params'][1] : 0;
    $offset = isset($message['params'][2]) ? (int) $message['params'][2] : 0;

    //если зашел в корневую - то стираем крошки
    if ($id_parent <= 0) {
      $this->ProductModel->clear_path($message['message']['chat']['id']);
    }

    //записываем хлебные крошки
    $this->ProductModel->add_path($message['message']['chat']['id'], $id_parent);

    $count = $this->ProductModel->categoryes_offset($id_parent, 0, TRUE);
    $offset = $offset < 0 ? 0 : $offset;
    $offset = $offset > $count ? 0 : $offset;

    $categoryes = $this->ProductModel->categoryes_offset($id_parent, $offset);

    $keyboard = [];

    $kb = [];
    foreach ($categoryes as $category) {
      $childs = $this->ProductModel->childs($category['id']);

      $button = [];
      $button['text'] = json_decode($category['name']);
      if (count($childs) <= 0) {
        // $button['callback_data'] = '/category_product '.$category['id'].' '.$id_parent;
        $button['callback_data'] = '/products '.$category['id'];
      } else {
        $button['callback_data'] = '/start_order '.$category['id'];
      }
      $kb[]= $button;
    }

    if (count($kb) > 2 AND $this->count_item_btns > 1) {
      $kb = array_chunk($kb, $this->count_item_btns);
      foreach($kb as $item) {
        $keyboard[] = $item;
      }
    } else {
      foreach ($kb as $kb_) {
        $keyboard[] = [$kb_];
      }
      
    }

    //листать вперед
    $button_next = [];
    $button_next['text'] = $this->btn($message, 48);
    $button_next['callback_data'] = '/start_order';
    $button_next['callback_data'].= ' '.$id_parent;
    $button_next['callback_data'].= ' '.($offset + $this->limit_menu);

    //листать назад
    $button_previous = [];
    $button_previous['text'] = $this->btn($message, 49);
    $button_previous['callback_data'] = '/start_order';
    $button_previous['callback_data'].= ' '.$id_parent;
    $button_previous['callback_data'].= ' '.($offset - $this->limit_menu);

    if ($count > $this->limit_menu) {
      switch ($offset) {
        case 0:
        $keyboard[]= [$button_next];
        break;
        case ($count - $this->limit_menu):
        $keyboard[]= [$button_previous];
        break;
        default:
        $keyboard[]= [$button_previous, $button_next];
        break;
      }
    }


    if ($id_parent > 0) {
      //выше на категорию
      $button = [];
      $button['text'] = $this->btn($message, 33);
      $button['callback_data'] = '/start_order';
      if ($id_parent_parent = $this->ProductModel->parent($id_parent)) {
        $button['callback_data'].= ' '.$id_parent_parent;
      } else {
        $button['callback_data'].= ' 0';
      }
      $keyboard[]= [$button];
    }

    //главное меню
    $button = [];
    $button['text'] = $this->btn($message, 52);
    $button['callback_data'] = '/start';

    $button2 = [];
    $button2['text'] = $this->btn($message, 79);
    $button2['callback_data'] = '/find';

    $keyboard[]= [$button, $button2];

    //Корзина
    $button = [];
    $button['text'] = $this->btn($message, 60);
    $button['callback_data'] = '/cart';
    $keyboard[]= [$button];

    return count($keyboard) <= 0 ? [] : $this->inline_keyboard($keyboard);
  }

  /*
  Выберите тариф
   */
  public function select_tarif($message) {
    $this->ProductModel = new \Products\Models\ProductModel();
    $this->OrderModel = new \Orders\Models\OrderModel();
    $this->PromoModel = new \Promo\Models\PromoModel();

    $keyboard = [];

    $items = $this->PromoModel->items();
    if (count($items) > 0) {
      $button = [];
      $button['text'] = $this->btn($message, 44);
      $button['callback_data'] = '/promocode';
      $keyboard[]= [$button];
    }

    $poducts = $this->ProductModel->items();

    foreach ($poducts as $product) {
      if ($product['price'] <= 0 AND $this->OrderModel->buyed_product($product['id'], $message['message']['chat']['id'])) {
          continue; //если это бесплатный продукт и уже был получен - то не даем получить еще
        }
        $button = [];
        $button['text'] = $product['name'];
        $button['callback_data'] = '/select_pay '.$product['id'];
        $keyboard[]= [$button];
      }

      if (count($keyboard) <= 0) {
        return [];
      }

      return $this->inline_keyboard($keyboard);
    }


  /*
  Ваш заказ оплачен
   */
  public function my_buyes($message) {
    $this->OrderModel = new \Orders\Models\OrderModel();
    $offset = isset($message['params'][1]) ? (int) $message['params'][1] : 0;
    $offset = $offset < 0 ? 0 : $offset;
    

    $keyboard = [];

    $buyed_products = $this->OrderModel->buyed_products($message['message']['chat']['id'], $offset);
    foreach ($buyed_products as $order_product) {
      //мои покупки
      $button = [];
      $button['text'] = "№".$order_product['id_order']." ".json_decode($order_product['name']);
      $button['callback_data'] = '/order_items '.$order_product['id_order'];

      $button2 = [];
      $button2['text'] = $this->btn($message, 50);
      $button2['callback_data'] = '/to_email '.$order_product['id_order'];

      $keyboard[]= [$button, $button2];
    }

    //кнопки листания
    $button = [];
    $button['text'] = $this->btn($message, 49);
    $button['callback_data'] = '/my_buyes '.($offset + 20);

    $button2 = [];
    $button2['text'] = $this->btn($message, 48);
    $button2['callback_data'] = '/my_buyes '.($offset - 20);

    $count = $this->OrderModel->buyed_products($message['message']['chat']['id'], FALSE);
    if ($count > 20) {
      $keyboard[]= [$button, $button2];
    }

    return $this->inline_keyboard($keyboard);
  }

  /*
  Ваш заказ оплачен
   */
  public function to_email_confirm($message, string $email) {
    $id_order = (int) $message['params'][1];

    $keyboard = [];

    //отмена
    $button = [];
    $button['text'] = $this->btn($message, 36);
    $button['callback_data'] = '/my_buyes';

    $button2 = [];
    $button2['text'] = $this->btn($message, 39);
    $button2['callback_data'] = '/order_items_email '.$id_order." ".$email;

    $keyboard[]= [$button, $button2];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Ваш заказ оплачен
   */
  public function to_email($message) {
    $keyboard = [];

    //мои покупки
    $button = [];
    $button['text'] = $this->btn($message, 36);
    $button['callback_data'] = '/my_buyes';
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Меню с одной кнопкой главное меню
   */
  public function btn_main($message) {
    $keyboard = [];

    //главное меню
    $button = [];
    $button['text'] = $this->btn($message, 52);
    $button['callback_data'] = '/start';
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  /*
  Ваш заказ оплачен
   */
  public function payed($message, $id_order) {
    $keyboard = [];
    
    // $button = [];
    // $button['text'] = $this->btn($message, 47);
    // $button['callback_data'] = '/order_items '.$id_order;
    // $keyboard[]= [$button];

    // //мои покупки
    // $button = [];
    // $button['text'] = $this->btn($message, 46);
    // $button['callback_data'] = '/my_buyes';
    // $keyboard[]= [$button];

    //главное меню
    $button = [];
    $button['text'] = $this->btn($message, 52);
    $button['callback_data'] = '/start';
    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  //подтверждение что оплату произвел
  //со стороны админа
  public function admin_out($message, $id_trans) {
    $keyboard = [];
    
    $button = [];
    $button['text'] = $this->btn($message, 22);
    $button['callback_data'] = '/admin_out_confirm '.$id_trans;

    $keyboard[]= [$button];

    return $this->inline_keyboard($keyboard);
  }

  public function profile($message) {
    $this->OrderModel = new \Orders\Models\OrderModel();
    $count_buyed_products = $this->OrderModel->buyed_products($message['message']['chat']['id'], FALSE);
    
    $keyboard = [];

    //изменить размер
    $button = [];
    $button['text'] = $this->btn($message, 70);
    $button['callback_data'] = '/select_size';
    $keyboard[]= [$button];

    //изменить подписку
    $button = [];
    $button['text'] = $this->btn($message, 71);
    $button['callback_data'] = '/select_subscribe';
    $keyboard[]= [$button];

    // if ($count_buyed_products > 0) {
    //   //мои покупки
    //   $button = [];
    //   $button['text'] = $this->btn($message, 46);
    //   $button['callback_data'] = '/my_buyes 0 delete';
    //   $keyboard[]= [$button];
    // }

    $languages = $this->LangModel->languages(); 
    if (count($languages) > 1) {
     $button = [];
     $button['text'] = $this->btn($message, 21);
     $button['callback_data'] = '/language';
     $keyboard[]= [$button];
   }

   if (count($keyboard) <= 0) {
    return [];
  }

  return $this->inline_keyboard($keyboard);
}

}
