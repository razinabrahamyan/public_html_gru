<?php 

/**
 * Контроллер для работы с рассылкой
 *
 * @author  KrotovRoman <tg: @KrotovRoman>
 */

namespace Products\Controllers;
class Category extends \Admin\Controllers\AbstractAdminController
{	

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		$this->ProductModel = new \Products\Models\ProductModel();
	}

	/**
	 * Отобразить список сообщений
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function tree() {
		if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return redirect()->to(base_url('/auth/login'));
		}
		$data['tree'] = $this->ProductModel->tree();
		$data['currency_name'] = $this->currency_name;
		$data['decimals'] = $this->decimals;
		$body = view('Products\category\tree', $data);
		return $this->view($body, 'Дерево категорий', 'category');
	}

	/**
	 * Отобразить список сообщений
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function index() {
		if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return redirect()->to(base_url('/auth/login'));
		}
		$data['categoryes'] = $this->ProductModel->categoryes();
		$data['currency_name'] = $this->currency_name;
		$data['decimals'] = $this->decimals;
		$data['message'] = session()->getFlashdata('message');
		$body = view('Products\category\items', $data);
		return $this->view($body, 'Категории', 'category');
	}

	/**
	 * Добавление
	 *
	 * @return string string|\CodeIgniter\HTTP\RedirectResponse
	 */
	public function add()
	{
		if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['title'] = "Добавить категорию";

		$validation = \Config\Services::validation();

		$validation->setRule('name', 'Название', 'trim|required');

		if ($this->request->getPost() && $validation->withRequest($this->request)->run())
		{
			if ($this->ProductModel->add_category($this->request->getPost()))
			{
				session()->setFlashdata('message', "Успешно добавлено!");
				return redirect()->to(base_url('category'));
			}
		}
		else
		{
			helper(['form']);
			$data['message'] = $validation->getErrors() ? $validation->listErrors() : session()->getFlashdata('message');
			$data['currency_name'] = $this->SettingsModel->currency_name;
			$data['items'] = $this->ProductModel->categoryes();
			$body = view('Products\category\add', $data);
			return $this->view($body, $data['title'] , 'category');
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
		if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['id'] = $id;
		$data['data']  = $this->ProductModel->category($id);

		$validation = \Config\Services::validation();

		$validation->setRule('confirm', 'confirm', 'required');
		$validation->setRule('id', 'id', 'required|integer');

		if (! $validation->withRequest($this->request)->run())
		{
			helper(['form']);
			$body         = view('Products\category\delete', $data);
			return $this->view($body, 'Удалить <strong>'.$data['data']['name'].'</strong>' , 'category');
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

				$this->ProductModel->delete_category($id);
			}

			// redirect them back to the auth page
			return redirect()->to(base_url('category'));
		}
	}

	/*
	Редактирование
	 */
	public function edit(int $id) {
		if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$validation = \Config\Services::validation();

		if (! empty($_POST)) {
			$validation->setRule('name', 'Название', 'trim|required');
			if ($post = $this->request->getPost() AND $validation->withRequest($this->request)->run()){
				$this->ProductModel->set_category($post);
				return redirect()->to(base_url('category'));
			}
		}

		helper(['form']);
		
		$data = $this->ProductModel->category($id);
		$data['items'] = $this->ProductModel->categoryes(FALSE, $id);
		$data['parents'] = $this->ProductModel->parents($id);
		$data['currency_name'] = $this->SettingsModel->currency_name;
		$body = view('Products\category\edit', $data);
		return $this->view($body, 'Редактирование <strong>'.$data['name'].'</strong>', 'category');
	}

}
