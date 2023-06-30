<?php namespace Admin\Controllers;

/**
 * Admin abstract controller file
 *
 * @package CI-Admin
 * @author  Benoit VRIGNAUD <benoit.vrignaud@zaclys.net>
 * @license https://opensource.org/licenses/MIT	MIT License
 * @link    http://github.com/bbvrignaud/ci-admin
 */
use \CodeIgniter\Database\ConnectionInterface;

class Home extends AbstractAdminController
{	

	/**
	 * Affiche la page d'entrée du site en fonction du statut de l'utilisateur (non connecté, gamer ou leader)
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function index()
	{
		if (! $this->isAuthorized()) {
			return redirect()->to(base_url('/auth/login'));
		}
		$data = [];
		$data['currency_name'] = $this->currency_name;
		
		$data['orders'] = $this->db->table('orders')->countAllResults();
		$data['payed'] = $this->db->table('orders')->where('status', 1)->countAllResults();
		$data['sum'] = $this->db->table('orders')->where('status', 1)->selectSum('sum')->get()->getRow()->sum;
		
		$data['users'] = $this->db->table('users')->countAllResults();
		
		$data['users_orders'] = count($this->db->table('orders')->groupBy('chat_id')->select('chat_id')->get()->getResultArray());
		$data['conv_orders'] = $data['users_orders'] <= 0 ? 0 : round(100 / ($data['users'] / $data['users_orders']), 2);

		$data['users_payed'] = count($this->db->table('orders')->where('status', 1)->groupBy('chat_id')->select('chat_id')->get()->getResultArray());
		$data['conv'] = $data['users_payed'] <= 0 ? 0 : round(100 / ($data['users'] / $data['users_payed']), 2);
		
		$body = view('Admin\home', $data);
		return $this->view($body, lang('Admin.home-title'), 'home');
	}

	/*
	Добавить пункт в сайдбар слева
	 */
	public function add_item() {
		return FALSE;
		//!!!

		$this->db = \Config\Database::connect();

		//открываем транзакцию
		$this->db->transBegin();

		$data = [];
		$data['priority'] = 699;
		$data['method'] = 'mods';
		$data['label'] = "Модификаторы";
		$data['title'] = "Модификаторы";
		$data['url'] = 'mods';
		$data['icon'] = 'cog';
		$this->db->table('panel_sidebar')->insert($data);

		$id_item = $this->db->insertID();

		//права админа
		$this->db->table('panel_access')->insert(['id_item' => $id_item, 'id_group' => 1]);
		//права пользователя
		// $this->db->table('panel_access')->insert(['id_item' => $id_item, 'id_group' => 2]);

		//закрываем транзакцию
		$this->db->transComplete();
		if ($this->db->transStatus() === FALSE) {
			$this->db->transRollback(); //откатить изменения
			return FALSE;
		} else {
			$this->db->transCommit(); //зафиксировать изменения в БД
			echo "страница ".$data['label']." добавлена";
			return TRUE;
		}
	}

	public function reset() {
		$this->db = \Config\Database::connect();

		//открываем транзакцию
		$this->db->transBegin();

		//получаем список таблиц в БД
		$tables = $this->db->listTables();

		//список таблиц которые не надо чистить:
		$exclude = [
			'groups', 'languages', 'menu_buttons', 'menu_buttons_translate', 
			'menu_items', 'menus', 'pages', 'pages_group', 'pages_translate',
			'panel_access', 'panel_sidebar', 'pay_methods', 'settings', 
			'settings_groups', 'users', 'users_groups', 'pay_settings', 'products'
		];

		foreach($tables as $table) {
			if (in_array($table, $exclude)) {
				continue;
			}
			if (!$this->db->tableExists($table)) {
				continue;
			}
			if ($this->db->table($table)->truncate()) {
				echo "<br>".$table;
			}
		}

		//удаляем пользователей кроме главного админа
		$this->db->table('users')->where('id>', 1)->delete();
		$this->db->table('users_groups')->where('user_id>', 1)->delete();

		//закрываем транзакцию
		$this->db->transComplete();
		if ($this->db->transStatus() === FALSE) {
			$this->db->transRollback(); //откатить изменения
			return FALSE;
		} else {
			$this->db->transCommit(); //зафиксировать изменения в БД
			echo "<h1>Данные успешно сброшены!</h1>";
			return TRUE;
		}
	}
}
