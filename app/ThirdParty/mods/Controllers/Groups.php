<?php 

/**
 * Контроллер для работы с группами модификаторов
 *
 * @author  KrotovRoman <tg: @KrotovRoman>
 */

namespace Mods\Controllers;
class Groups extends \Admin\Controllers\AbstractAdminController
{	

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		$this->ModModel = new \Mods\Models\ModModel();
	}


	/**
	 * Отобразить список сообщений
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function index() {
		if (!$this->isAuthorized() OR !$this->isAccess('groups')) {
			return redirect()->to(base_url('/auth/login'));
		}
		$data['items'] = $this->ModModel->groups();
		$data['currency_name'] = $this->currency_name;
		$data['decimals'] = $this->decimals;
		$data['message'] = session()->getFlashdata('message');
		$body = view('Mods\groups\items', $data);
		return $this->view($body, 'Группы модификаторов', 'groups');
	}

	/**
	 * Добавление
	 *
	 * @return string string|\CodeIgniter\HTTP\RedirectResponse
	 */
	public function add()
	{
		if (!$this->isAuthorized() OR !$this->isAccess('groups')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$validation = \Config\Services::validation();
		$validation->setRule('name', 'Название', 'trim|required');

		if ($this->request->getPost() && $validation->withRequest($this->request)->run())
		{
			if ($this->ModModel->add_group($this->request->getPost()))
			{
				session()->setFlashdata('message', "Успешно добавлено!");
				return redirect()->to(base_url('groups'));
			}
		}
		else
		{
			helper(['form']);
			$data['title'] = "Добавить группу";
			$data['message'] = $validation->getErrors() ? $validation->listErrors() : session()->getFlashdata('message');
			$data['currency_name'] = $this->SettingsModel->currency_name;
			$body = view('Mods\groups\add', $data);
			return $this->view($body, $data['title'] , 'groups');
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
		if (!$this->isAuthorized() OR !$this->isAccess('groups')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['id'] = $id;
		$data['data']  = $this->ModModel->group($id);

		$validation = \Config\Services::validation();

		$validation->setRule('confirm', 'confirm', 'required');
		$validation->setRule('id', 'id', 'required|integer');

		if (! $validation->withRequest($this->request)->run())
		{
			helper(['form']);
			$body         = view('Mods\groups\delete', $data);
			return $this->view($body, 'Удалить <strong>'.$data['data']['name'].'</strong>' , 'groups');
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
				session()->setFlashdata('message', "Успешно удалено!");
				$this->ModModel->delete_group($id);
			}

			// redirect them back to the auth page
			return redirect()->to(base_url('groups'));
		}
	}

	/*
	Редактирование
	 */
	public function edit(int $id) {
		if (!$this->isAuthorized() OR !$this->isAccess('groups')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$validation = \Config\Services::validation();

		if (! empty($_POST)) {
			$validation->setRule('name', 'Название', 'trim|required');
			if ($post = $this->request->getPost() AND $validation->withRequest($this->request)->run()){
				$this->ModModel->set_group($post);
				session()->setFlashdata('message', "Успешно сохранено!");
				return redirect()->to(base_url('groups'));
			}
		}

		helper(['form']);
		
		$data = $this->ModModel->group($id);
		$data['currency_name'] = $this->SettingsModel->currency_name;
		$body = view('Mods\groups\edit', $data);
		return $this->view($body, 'Редактирование <strong>'.$data['name'].'</strong>', 'groups');
	}

}
