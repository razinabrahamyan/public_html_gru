<?php 

/**
 * Контроллер для работы с рассылкой
 *
 * @author  KrotovRoman <tg: @KrotovRoman>
 * @docs https://yookassa.ru/developers/api?lang=php
 */

namespace Yookassa\Controllers;
class Yookassa extends \App\Controllers\BaseController
{	

	/*
	Получаем уведомления от Yandex
	 */
	public function index() {
		$this->YookassaModel->checkpay();
		echo "ok";
	}

	public function test() {

	}

	/*
	Сгенерить ссылку на чек и выдать
	 */
	public function check(int $id_order) {
		if (!$url = $this->YookassaModel->createbill($id_order)) {
			echo "<h1>Сообщите владельцу бота что что то пошло не так - ошибка API ЮКассы!</h1>";
			return FALSE;
		}
		return redirect()->to($url);
	}

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->YookassaModel = new \Yookassa\Models\YookassaModel();
		$this->SettingsModel = new \Admin\Models\SettingsModel();
		$settings = $this->SettingsModel->all(TRUE);
		foreach ($settings as $settings_) {
			$this->{$settings_['name']} = trim($settings_['value']);
		}
	}
}
