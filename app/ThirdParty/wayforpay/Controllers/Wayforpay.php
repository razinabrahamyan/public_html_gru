<?php 

/**
 * Контроллер для работы с Wayforpay
 *
 * @author  KrotovRoman <tg: @KrotovRoman>
 * @src https://github.com/wayforpay/php-sdk
 * @docs https://wiki.wayforpay.com/
 */

namespace Wayforpay\Controllers;
class Wayforpay extends \App\Controllers\BaseController
{	
	/*
	Поступление уведомлений о платежах
	 */
	public function returnurl(int $id_order) {
		$this->WayforpayModel->returnurl($id_order);
	}
	
	/*
	Поступление уведомлений о платежах
	 */
	public function check(int $id_order) {
		$this->WayforpayModel->check($id_order);
	}

	/*
	Страница оплаты
	 */
	public function pay(int $id_order) {
		if (!$data_order = $this->OrderModel->get($id_order)) {
			echo "ОШИБКА: Нет такого заказа №".$id_order;
			return FALSE;
		}
		if (empty($this->WayforpayModel->id_wayforpay()) OR empty($this->WayforpayModel->key_wayforpay())) {
			echo "ОШИБКА: Не указаны API ключи платежной системы, в админке - способы оплаты - WayForPay!";
			return FALSE;
		}
		$data_order['btn'] = $this->WayforpayModel->btn($id_order);
		$data_order['title'] = "Оплата заказа №".$id_order;
		return view('Wayforpay\wayforpay\pay', $data_order);
	}

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->WayforpayModel = new \Wayforpay\Models\WayforpayModel();
		$this->OrderModel = new \Orders\Models\OrderModel();

		$this->SettingsModel = new \Admin\Models\SettingsModel();
		$settings = $this->SettingsModel->all(TRUE);
        foreach ($settings as $settings_) {
            $this->{$settings_['name']} = trim($settings_['value']);
        }
	}

}
