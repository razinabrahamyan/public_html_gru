<?php namespace App\Controllers;
/*
Использоваение ограничения времени из админки
if ($this->CronModel->cron_is_now($this->cron_updatelink)) {
}
 */
class Cron extends BaseController
{	

	public function test($id_order = 0) {
		$id_order_item = 40;
		$this->OrderModel = new \Orders\Models\OrderModel();
		$order_item = $this->OrderModel->get_order_item($id_order_item);

		$data = [];
        $data['id_product'] = $order_item['id_product'];
        $data['id_item'] = $order_item['id_item'];
        $data['chat_id'] = 5134978260;
        $id_order = $this->OrderModel->delete_cart($data);
        var_dump($id_order);
	}

	/*
	Курсы валют
	 */
	public function course() {
		$start = microtime(true);

		$this->BinanceModel = new \Btc\Models\BinanceModel();
        $this->BinanceModel->cron();

		$this->CourseModel = new \Course\Models\CourseModel();
		$this->CourseModel->update();
		$this->CourseModel->update_cash();
		echo 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';
	}

	/*
	Автоудаление старых заказов
	 */
	public function autodel() {
		$start = microtime(true);
		$this->OrderModel = new \Orders\Models\OrderModel();
		$this->OrderModel->autodel();
		echo 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';
	}
	
	/*
	Уведомление о неоформленном заказе
	 */
	public function index() {
		$start = microtime(true);
		$this->OrderModel = new \Orders\Models\OrderModel();
		$this->OrderModel->notify_no_finish();
		echo 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';
	}
}
