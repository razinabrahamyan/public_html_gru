<?php 

/**
 * Контроллер для работы с рассылкой
 *
 * @author  KrotovRoman <tg: @KrotovRoman>
 */

namespace Mods\Controllers;
class Mods extends \Admin\Controllers\AbstractAdminController
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
	 * Список
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function items(int $id_group) {
		if (!$this->isAuthorized() OR !$this->isAccess('groups')) {
			return redirect()->to(base_url('/auth/login'));
		}
		$data = $this->ModModel->group($id_group);
		$data['id_group']= $id_group;
		$data['items'] = $this->ModModel->mods($id_group);
		$data['currency_name'] = $this->currency_name;
		$data['decimals'] = $this->decimals;
		$data['message'] = session()->getFlashdata('message');
		$body = view('Mods\items\items', $data);
		return $this->view($body, 'Модификаторы группы <strong>'.$data['name']."</strong>", 'groups');
	}

	/**
	 * Добавление
	 *
	 * @return string string|\CodeIgniter\HTTP\RedirectResponse
	 */
	public function add(int $id_group)
	{
		if (!$this->isAuthorized() OR !$this->isAccess('groups')) {
			return redirect()->to(base_url('/auth/login'));
		}

		

		$validation = \Config\Services::validation();
		$validation->setRule('name', 'Название', 'trim|required');

		if ($this->request->getPost() && $validation->withRequest($this->request)->run())
		{
			if ($this->ModModel->add_mod($this->request->getPost()))
			{
				session()->setFlashdata('message', "Успешно добавлено!");
				return redirect()->to(base_url('mods/items/'.$id_group));
			}
		}
		else
		{
			helper(['form']);
			$data = $this->ModModel->group($id_group);
			$data['id_group'] = $id_group;
			$data['title'] = "Добавить модификатор в группу <strong>".$data['name']."</strong>";
			$data['message'] = $validation->getErrors() ? $validation->listErrors() : session()->getFlashdata('message');
			$data['currency_name'] = $this->SettingsModel->currency_name;
			$body = view('Mods\items\add', $data);
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
		$data['data']  = $this->ModModel->mod($id);

		$validation = \Config\Services::validation();

		$validation->setRule('confirm', 'confirm', 'required');
		$validation->setRule('id', 'id', 'required|integer');

		if (! $validation->withRequest($this->request)->run())
		{
			helper(['form']);
			$body         = view('Mods\items\delete', $data);
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

				$this->ModModel->delete_mod($id);
			}

			// redirect them back to the auth page
			return redirect()->to(base_url('mods/items/'.$data['data']['id_group']));
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
				$this->ModModel->set_mod($post);
				$data = $this->ModModel->mod($id);
				return redirect()->to(base_url('mods/items/'.$data['id_group']));
			}
		}

		helper(['form']);
		
		$data = $this->ModModel->mod($id);
		$data['currency_name'] = $this->SettingsModel->currency_name;
		$body = view('Mods\items\edit', $data);
		return $this->view($body, 'Редактирование <strong>'.$data['name'].'</strong>', 'groups');
	}
}
