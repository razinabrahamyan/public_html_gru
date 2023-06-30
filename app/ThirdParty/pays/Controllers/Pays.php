<?php 

/**
 * Контроллер для работы с партнерской программой
 *
 * @author  KrotovRoman <tg: @KrotovRoman>
 */

namespace Pays\Controllers;
class Pays extends \Admin\Controllers\AbstractAdminController
{	

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		$this->PayModel = new \Pays\Models\PayModel();
	}

	/**
	 * Добавление
	 *
	 * @return string string|\CodeIgniter\HTTP\RedirectResponse
	 */
	public function add()
	{
		if (!$this->isAuthorized() OR !$this->isAccess('pays')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['title'] = "Добавить способ оплаты";

		$validation = \Config\Services::validation();

		$validation->setRule('name', 'Название', 'trim|required');
		$validation->setRule('currency', 'Код валюты', 'trim');

		if ($this->request->getPost() && $validation->withRequest($this->request)->run())
		{
			if ($this->PayModel->add($this->request->getPost()))
			{
				session()->setFlashdata('message', "Успешно добавлено!");
				return redirect()->to(base_url('/pays'));
			}
		}
		else
		{
			helper(['form']);
			$data['message'] = $validation->getErrors() ? $validation->listErrors() : session()->getFlashdata('message');
			$data['currency_name'] = $this->SettingsModel->currency_name;
			$data['currency_cod'] = $this->SettingsModel->currency_cod;
			$body         = view('Pays\pays\add', $data);
			return $this->view($body, $data['title'] , 'pays');
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
		if (!$this->isAuthorized() OR !$this->isAccess('pays')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['data']  = $this->PayModel->pay($id);

		$validation = \Config\Services::validation();

		$validation->setRule('confirm', 'confirm', 'required');
		$validation->setRule('id', 'id', 'required|integer');

		if (! $validation->withRequest($this->request)->run())
		{
			helper(['form']);
			$body         = view('Pays\pays\delete', $data);
			return $this->view($body, 'Удалить способ оплаты <strong>'.$data['data']['name'].'</strong>?' , 'pays');
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

				$this->PayModel->delete($id);
			}

			// redirect them back to the auth page
			return redirect()->to(base_url('/pays'));
		}
	}

	public function active(int $id) {
		if (!$this->isAuthorized() OR !$this->isAccess('pays')) {
			return redirect()->to(base_url('/auth/login'));
		}
		$data  = $this->PayModel->pay($id);
		$active = !$data['active'];
		$this->PayModel->pay_set(['id' => $id, 'active' => $active]);
		return redirect()->to(base_url('/pays'));
	}

	/*
	Редактирование кнопки
	 */
	public function edit(int $id) {

		if (!$this->isAuthorized() OR !$this->isAccess('pays')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$id = (int) $id;
		
		$validation = \Config\Services::validation();

		if (! empty($_POST)) {
			$validation->setRule('name', 'Название', 'trim|required');
			if ($post = $this->request->getPost() AND $validation->withRequest($this->request)->run()){
				$this->PayModel->pay_set($post);
				$this->PayModel->pay_settings_set($id, $post);
				return redirect()->to(base_url('/pays'));
			}
		}

		helper(['form']);

		$data  = $this->PayModel->pay($id);

		$data['message'] = $validation->getErrors() ? $validation->listErrors() : NULL;
		$data['message'] = (mb_strlen($data['message']) <= 131)  ? NULL : $data['message'];

		$data['settings'] = $this->PayModel->settings($id);
		$body = view('Pays\pays\edit', $data);
		return $this->view($body, 'Редактирование <strong>'.$data['name'].'</strong>', 'pays');
	}

	/**
	 * Отобразить список сообщений
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function index() {
		if (!$this->isAuthorized() OR !$this->isAccess('pays')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['currency_name'] = $this->currency_name;
		$data['items'] = $this->PayModel->items();
		$body = view('Pays\pays\items', $data);
		return $this->view($body, 'Способы оплаты', 'pays');
	}
}
