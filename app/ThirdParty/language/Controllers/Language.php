<?php 

/**
 * Контроллер для работы с рассылкой
 *
 * @author  KrotovRoman <tg: @KrotovRoman>
 */

namespace Language\Controllers;
class Language extends \Admin\Controllers\AbstractAdminController
{	

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		$this->LangModel = new \Admin\Models\LangModel();
	}

	/*
	Экспорировать в эксель
	 */
	public function export($table = "menu_buttons") {
    	if (!$this->isAuthorized() OR !$this->isAccess('language')) {
			return FALSE;
		}
		return $this->LangModel->export($table);
	}

	/*
	Активация/деактивация
	 */
	public function deactivate(int $id, $active = 0) {
		if (!$this->isAuthorized() OR !$this->isAccess('language')) {
			return redirect()->to(base_url('/auth/login'));
		}
		$this->LangModel->set(['id' => $id, 'active' => $active]);
		return redirect()->to(base_url('/language'));
	}

	/**
	 * Добавление
	 *
	 * @return string string|\CodeIgniter\HTTP\RedirectResponse
	 */
	public function add()
	{
		if (!$this->isAuthorized() OR !$this->isAccess('language')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['title'] = "Добавить язык";

		$validation = \Config\Services::validation();

		$validation->setRule('name', 'Название', 'trim|required');
		$validation->setRule('short', 'Код языка', 'trim|required');

		if ($this->request->getPost() && $validation->withRequest($this->request)->run())
		{
			if ($this->LangModel->add($this->request->getPost()))
			{
				session()->setFlashdata('message', "Успешно добавлено!");
				return redirect()->to(base_url('/language'));
			}
		}
		else
		{
			helper(['form']);
			$data['message'] = $validation->getErrors() ? $validation->listErrors() : session()->getFlashdata('message');

			$body         = view('Language\language\add', $data);
			return $this->view($body, 'Добавить язык' , 'language');
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
		if (!$this->isAuthorized() OR !$this->isAccess('language')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$validation = \Config\Services::validation();

		$validation->setRule('confirm', 'confirm', 'required');
		$validation->setRule('id', 'id', 'required|integer');

		if (! $validation->withRequest($this->request)->run())
		{
			helper(['form']);
			$data['data']  = $this->LangModel->get($id);
			$body         = view('Language\language\delete', $data);
			return $this->view($body, 'Удалить <strong>'.$data['data']['name'].'</strong>' , 'language');
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

				$this->LangModel->delete($id);
			}

			// redirect them back to the auth page
			return redirect()->to(base_url('/language'));
		}
	}

	/*
	Редактирование кнопки
	 */
	public function edit(int $id) {
		if (!$this->isAuthorized() OR !$this->isAccess('language')) {
			return redirect()->to(base_url('/auth/login'));
		}
		
		$validation = \Config\Services::validation();

		if (! empty($_POST)) {
			$validation->setRule('name', 'Название', 'trim|required');
			$validation->setRule('short', 'Код языка', 'trim|required');
			if ($post = $this->request->getPost() AND $validation->withRequest($this->request)->run()){
				$this->LangModel->set($post);
				return redirect()->to(base_url('/language'));
			}
		}

		helper(['form']);

		$data['data']  = $this->LangModel->get($id);

		$data['message'] = $validation->getErrors() ? $validation->listErrors() : NULL;
		$data['message'] = (mb_strlen($data['message']) <= 131)  ? NULL : $data['message'];

		$body = view('Language\language\edit', $data);
		return $this->view($body, 'Редактирование <strong>'.$data['data']['name'].'</strong>', 'language');
	}

	/**
	 * Отобразить список сообщений
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function index() {
		if (!$this->isAuthorized() OR !$this->isAccess('language')) {
			return redirect()->to(base_url('/auth/login'));
		}
		$data['buttons'] = $this->LangModel->languages(TRUE);
		$body = view('Language\language\items', $data);
		return $this->view($body, 'Языки', 'language');
	}
}
