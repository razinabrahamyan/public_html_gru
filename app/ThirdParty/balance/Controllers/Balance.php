<?php 

/**
 * Контроллер для работы с партнерской программой
 *
 * @author  KrotovRoman <tg: @KrotovRoman>
 */

namespace Balance\Controllers;
class Balance extends \Admin\Controllers\AbstractAdminController
{	

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		$this->BalanceModel = new \Balance\Models\BalanceModel();
	}

	/**
	 * Добавление
	 *
	 * @return string string|\CodeIgniter\HTTP\RedirectResponse
	 */
	public function add($chat_id)
	{
		if (!$this->isAuthorized()) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data = $this->ionAuth->user($chat_id)->rowArray();
		$data['currency_name'] = $this->SettingsModel->currency_name;

		$data['title'] = 'Добавить транзакцию для пользователя '.json_decode($data['first_name'])." ".json_decode($data['last_name']);

		$validation = \Config\Services::validation();

		$validation->setRule('comment', 'Комментарий', 'trim|required');
		$validation->setRule('value', 'Сумма', 'trim|required');

		if ($this->request->getPost() && $validation->withRequest($this->request)->run())
		{
			$post = $this->request->getPost();
			$post['type'] = "hand";
			$post['chat_id'] = $chat_id;
			$post['currency'] = $this->SettingsModel->currency_cod;
			if ($this->BalanceModel->add($post))
			{
				session()->setFlashdata('message', "Успешно добавлено!");
				return redirect()->to(base_url('/balance/items/'.$chat_id));
			}
		}
		else
		{
			helper(['form']);
			$data['message'] = $validation->getErrors() ? $validation->listErrors() : session()->getFlashdata('message');
			$body  = view('Balance\balance\add', $data);
			return $this->view($body, $data['title'] , 'balance');
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
		if (!$this->isAuthorized()) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['data']  = $this->BalanceModel->get_data($id);

		$validation = \Config\Services::validation();

		$validation->setRule('confirm', 'confirm', 'required');
		$validation->setRule('id', 'id', 'required|integer');

		if (! $validation->withRequest($this->request)->run())
		{
			helper(['form']);
			$body = view('Balance\balance\delete', $data);
			return $this->view($body, 'Удалить <strong>'.$data['data']['value']." ".$data['data']['currency'].'</strong>' , 'balance');
		}
		else
		{
			if ($this->request->getPost('confirm') === 'yes')
			{
				if ($id !== $this->request->getPost('id', FILTER_VALIDATE_INT))
				{
					throw new \Exception(lang('Auth.error_security'));
				}

				$this->BalanceModel->delete($id);
			}

			// redirect them back to the auth page
			return redirect()->to(base_url('/balance/items/'.$data['data']['chat_id']));
		}
	}

	/*
	Редактирование кнопки
	 */
	public function edit(int $id) {
		if (!$this->isAuthorized()) {
			return redirect()->to(base_url('/auth/login'));
		}
		
		$data['data']  = $this->BalanceModel->get_data($id);

		$validation = \Config\Services::validation();

		if (! empty($_POST)) {
			$validation->setRule('comment', 'Комментарий', 'trim');
			$validation->setRule('value', 'Сумма', 'trim|required');
			if ($post = $this->request->getPost() AND $validation->withRequest($this->request)->run()){
				$this->BalanceModel->set($post);
				return redirect()->to(base_url('/balance/items/'.$data['data']['chat_id']));
			}
		}

		helper(['form']);

		$data['message'] = $validation->getErrors() ? $validation->listErrors() : NULL;
		$data['message'] = (mb_strlen($data['message']) <= 131)  ? NULL : $data['message'];

		$body = view('Balance\balance\edit', $data);
		return $this->view($body, 'Редактирование', 'balance');
	}

	/**
	 * Отобразить список сообщений
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function index($chat_id) {
		if (!$this->isAuthorized()) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data = $this->ionAuth->user($chat_id)->rowArray();
		$data['currency_name'] = $this->SettingsModel->currency_name;
		$data['balance'] = number_format($this->BalanceModel->get($chat_id), 2, ',', ' ');

		$data['items'] = $this->BalanceModel->items($chat_id);
		$body = view('Balance\balance\items', $data);
		return $this->view($body, 'Баланс '.json_decode($data['first_name'])." ".json_decode($data['last_name']).": ".$data['balance']." ".$data['currency_name'], 'balance');
	}
}
