<?php 

/**
 * Контроллер для работы с рассылкой
 *
 * @author  KrotovRoman <tg: @KrotovRoman>
 */

namespace Btc\Controllers;
class Btc extends \Admin\Controllers\AbstractAdminController
{	

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		$this->BlockchainModel = new \Btc\Models\BlockchainModel();
	}

	/*
	проверяем оплаты заказов
	 */
	public function cron() {
		$start = microtime(true);
		if ($this->BlockchainModel->check_orders()) {
			
		}
		echo 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';
	}
}
