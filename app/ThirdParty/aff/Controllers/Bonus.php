<?php 

/**
 * Контроллер для работы с партнерской программой
 *
 * @author  KrotovRoman <tg: @KrotovRoman>
 */

namespace Aff\Controllers;
class Bonus extends \Admin\Controllers\AbstractAdminController
{	

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		$this->AffModel = new \Aff\Models\AffModel();
		$this->ProductModel = new \Products\Models\ProductModel();
	}

	/**
	 * Добавление
	 *
	 * @return string string|\CodeIgniter\HTTP\RedirectResponse
	 */
	public function add()
	{
		if (!$this->isAuthorized() OR !$this->isAccess('bonus')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['title'] = "Добавить настройку бонуса";

		$validation = \Config\Services::validation();

		$validation->setRule('id_product', 'Продукт', 'trim|required');
		$validation->setRule('sum', 'Сумма', 'trim');

		if ($this->request->getPost() && $validation->withRequest($this->request)->run())
		{
			if ($this->AffModel->add_bonus($this->request->getPost()))
			{
				session()->setFlashdata('message', "Успешно добавлено!");
				return redirect()->to(base_url('bonus'));
			}
		}
		else
		{
			helper(['form']);
			$data['message'] = $validation->getErrors() ? $validation->listErrors() : session()->getFlashdata('message');
			$data['currency_name'] = $this->SettingsModel->currency_name;
			$data['items'] =$this->ProductModel->items();
			$body         = view('Aff\bonus\add', $data);
			return $this->view($body, $data['title'] , 'bonus');
		}
	}

	/**
	 * Удалить
	 *
	 * @param integer $id
	 *
	 * @throw Exception
	 *
	 * @return string|\CodeIgniter\HTTP\RedirectResponse
	 */
	public function delete(int $id = 0)
	{
		if (!$this->isAuthorized() OR !$this->isAccess('bonus')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['data']  = $this->AffModel->get_bonus($id);

		$validation = \Config\Services::validation();

		$validation->setRule('confirm', 'confirm', 'required');
		$validation->setRule('id', 'id', 'required|integer');

		if (! $validation->withRequest($this->request)->run())
		{
			helper(['form']);
			$body         = view('Aff\bonus\delete', $data);
			return $this->view($body, 'Удалить бонус <strong>'.$data['data']['sum'].'</strong>' , 'bonus');
		}
		else
		{
			if ($this->request->getPost('confirm') === 'yes')
			{
				// do we have a valid request?
				if ($id !== $this->request->getPost('id', FILTER_VALIDATE_INT))
				{
					throw new \Exception(lang('Auth.error_security'));
				}

				$this->AffModel->delete_bonus($id);
			}

			// redirect them back to the auth page
			return redirect()->to(base_url('bonus'));
		}
	}

	/*
	Редактирование
	 */
	public function edit(int $id) {
		if (!$this->isAuthorized() OR !$this->isAccess('bonus')) {
			return redirect()->to(base_url('/auth/login'));
		}
		
		$validation = \Config\Services::validation();

		if (! empty($_POST)) {
			$validation->setRule('id_product', 'Продукт', 'trim|required');
			$validation->setRule('sum', 'Сумма', 'trim');
			if ($post = $this->request->getPost() AND $validation->withRequest($this->request)->run()){
				$this->AffModel->set_bonus($post);
				return redirect()->to(base_url('bonus'));
			}
		}

		helper(['form']);

		$data  = $this->AffModel->get_bonus($id);

		$data['message'] = $validation->getErrors() ? $validation->listErrors() : NULL;
		$data['message'] = (mb_strlen($data['message']) <= 131)  ? NULL : $data['message'];
		$data['items'] =$this->ProductModel->items();
		$data['currency_name'] = $this->SettingsModel->currency_name;
		$body = view('Aff\bonus\edit', $data);
		return $this->view($body, 'Редактирование <strong>'.$data['sum'].'</strong>', 'bonus');
	}

	/**
	 * Отобразить список сообщений
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function index() {
		if (!$this->isAuthorized() OR !$this->isAccess('bonus')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['currency_name'] = $this->SettingsModel->currency_name;
		$data['items'] = $this->AffModel->bonuses();
		$body = view('Aff\bonus\items', $data);
		return $this->view($body, 'Бонусы', 'bonus');
	}
}
