<?php 

/**
 * Контроллер для работы с Usdt
 *
 * @author  KrotovRoman <tg: @KrotovRoman>
 */

namespace Tronapi\Controllers;
class Tronapi extends \App\Controllers\BaseController
{	

	public function test() {
		$this->TronapiModel->test();
	}

	/*
	Автовывод
	 */
	public function cron() {
		$this->TronapiModel->autoout();
	}

	/*
	Уведомления об оплате
	 */
	public function hook() {
		return $this->TronapiModel->check();
	}

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->TronapiModel = new \Tronapi\Models\TronapiModel();
		$this->OrderModel = new \Orders\Models\OrderModel();
		
		$this->SettingsModel = new \Admin\Models\SettingsModel();
		$settings = $this->SettingsModel->all(TRUE);
        foreach ($settings as $settings_) {
            $this->{$settings_['name']} = trim($settings_['value']);
        }
	}
}
