<?php 

/**
 * Контроллер для работы с партнерской программой
 *
 * @author  KrotovRoman <tg: @KrotovRoman>
 */

namespace Aff\Controllers;
class Aff extends \Admin\Controllers\AbstractAdminController
{	

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		$this->AffModel = new \Aff\Models\AffModel();
	}

	/**
	 * Добавление
	 *
	 * @return string string|\CodeIgniter\HTTP\RedirectResponse
	 */
	public function add()
	{
		if (!$this->isAuthorized() OR !$this->isAccess('aff')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['title'] = "Добавить уровень";

		$validation = \Config\Services::validation();

		$validation->setRule('level', 'Уровень', 'trim|required');
		$validation->setRule('sum', 'Сумма', 'trim');

		if ($this->request->getPost() && $validation->withRequest($this->request)->run())
		{
			if ($this->AffModel->add($this->request->getPost()))
			{
				session()->setFlashdata('message', "Успешно добавлено!");
				return redirect()->to(base_url('/aff'));
			}
		}
		else
		{
			helper(['form']);
			$data['message'] = $validation->getErrors() ? $validation->listErrors() : session()->getFlashdata('message');
			$data['currency_name'] = $this->SettingsModel->currency_name;
			$body         = view('Aff\aff\add', $data);
			return $this->view($body, $data['title'] , 'aff');
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
		if (!$this->isAuthorized() OR !$this->isAccess('aff')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['data']  = $this->AffModel->get($id);

		$validation = \Config\Services::validation();

		$validation->setRule('confirm', 'confirm', 'required');
		$validation->setRule('id', 'id', 'required|integer');

		if (! $validation->withRequest($this->request)->run())
		{
			helper(['form']);
			$body         = view('Aff\aff\delete', $data);
			return $this->view($body, 'Удалить <strong>'.$data['data']['level'].'</strong> уровень' , 'aff');
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

				$this->AffModel->delete($id);
			}

			// redirect them back to the auth page
			return redirect()->to(base_url('/aff'));
		}
	}

	/*
	Редактирование кнопки
	 */
	public function edit(int $id) {
		if (!$this->isAuthorized() OR !$this->isAccess('aff')) {
			return redirect()->to(base_url('/auth/login'));
		}
		
		$validation = \Config\Services::validation();

		if (! empty($_POST)) {
			$validation->setRule('level', 'Уровень', 'trim|required');
			$validation->setRule('percent', '%', 'trim');
			if ($post = $this->request->getPost() AND $validation->withRequest($this->request)->run()){
				$this->AffModel->set($post);
				return redirect()->to(base_url('/aff'));
			}
		}

		helper(['form']);

		$data['data']  = $this->AffModel->get($id);

		$data['message'] = $validation->getErrors() ? $validation->listErrors() : NULL;
		$data['message'] = (mb_strlen($data['message']) <= 131)  ? NULL : $data['message'];

		$data['currency_name'] = $this->SettingsModel->currency_name;
		$body = view('Aff\aff\edit', $data);
		return $this->view($body, 'Редактирование <strong>'.$data['data']['level'].'</strong>', 'aff');
	}

	/**
	 * Отобразить список сообщений
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function index() {
		if (!$this->isAuthorized() OR !$this->isAccess('aff')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['currency_name'] = $this->SettingsModel->currency_name;
		$data['items'] = $this->AffModel->items();
		$body = view('Aff\aff\items', $data);
		return $this->view($body, 'Уровни комиссионных', 'aff');
	}
}
