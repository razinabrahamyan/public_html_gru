<?php 

/**
 * Контроллер для работы с рассылкой
 *
 * @author  KrotovRoman <tg: @KrotovRoman>
 */

namespace Sender\Controllers;
class Sender extends \Admin\Controllers\AbstractAdminController
{	

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		$this->PostsModel = new \Sender\Models\PostsModel();
	}

	/*
	Рассылка очередями
	в крон на каждую минуту
	 */
	public function cron() {
		$start = microtime(true);
		$this->PostsModel->send();
		echo 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';
	}


	/**
	 * Отобразить список сообщений
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function stat(int $id) {
		if (!$this->isAuthorized() OR !$this->isAccess('sender')) {
			return redirect()->to(base_url('/auth/login'));
		}
		$data = $this->PostsModel->get($id, TRUE);
		$data['datapie'] = $this->PostsModel->pie($id);
		$data['id'] = $id;
		$body = view('Sender\sender\stat_', $data);
		return $this->view($body, 'История доставки поста №'.$id, 'sender');
	}


	/*
     * Список ajax
     * 
     * @docs https://datatables.net/manual/server-side#Sent-parameters
     * @docs https://datatables.net/examples/server_side/simple.html
     * @docs https://datatables.net/examples/data_sources/server_side.html
     */

    public function stat_($id) {
    	if (!$this->isAuthorized() OR !$this->isAccess('users')) {
			return FALSE;
		}
        $post = $this->request->getPost();

        $return = new \stdClass();
        $return->draw = (int) $post['draw']; //сколько прорисовывать - то что прислали нам
        $return->length = (int) $post['length']; //количество записей на странице
        
        //количество записей до фильтрации
        $return->recordsTotal = $this->PostsModel->stat_($id, $post, FALSE);

        //получаем данные с учетом фильтров
        $return->data = $this->PostsModel->stat_($id, $post);

        if (!empty($post['search']['value']) AND ! $post['search']['regex']) {
            $return->recordsFiltered = count($return->data); //если был применен поиск
        } else {
            $return->recordsFiltered = $return->recordsTotal; //если без поиска            
        }
        return print json_encode($return);
    }

	/**
	 * Отобразить список сообщений
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function index() {
		if (!$this->isAuthorized() OR !$this->isAccess('sender')) {
			return redirect()->to(base_url('/auth/login'));
		}
		$data['message'] = session()->getFlashdata('message');
		$body = view('Sender\sender\sender_',$data);
		return $this->view($body, 'Рассылка', 'sender');
	}


	/*
     * Список ajax
     * 
     * @docs https://datatables.net/manual/server-side#Sent-parameters
     * @docs https://datatables.net/examples/server_side/simple.html
     * @docs https://datatables.net/examples/data_sources/server_side.html
     */

    public function index_() {
    	if (!$this->isAuthorized() OR !$this->isAccess('users')) {
			return FALSE;
		}
        $post = $this->request->getPost();

        $return = new \stdClass();
        $return->draw = (int) $post['draw']; //сколько прорисовывать - то что прислали нам
        $return->length = (int) $post['length']; //количество записей на странице
        
        //количество записей до фильтрации
        $return->recordsTotal = $this->PostsModel->items_($post, FALSE);

        //получаем данные с учетом фильтров
        $return->data = $this->PostsModel->items_($post);

        if (!empty($post['search']['value']) AND ! $post['search']['regex']) {
            $return->recordsFiltered = count($return->data); //если был применен поиск
        } else {
            $return->recordsFiltered = $return->recordsTotal; //если без поиска            
        }
        return print json_encode($return);
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
		if (!$this->isAuthorized() OR !$this->isAccess('sender')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['data']  = $this->PostsModel->get($id, TRUE);

		$validation = \Config\Services::validation();

		$validation->setRule('confirm', 'confirm', 'required');
		$validation->setRule('id', 'id', 'required|integer');

		if (! $validation->withRequest($this->request)->run())
		{
			helper(['form']);
			$body = view('Sender\sender\delete', $data);
			return $this->view($body, 'Удалить пост №<strong>'.$data['data']['id'].'</strong>' , 'sender');
		}
		else
		{
			if ($this->request->getPost('confirm') === 'yes')
			{
				if ($id !== $this->request->getPost('id', FILTER_VALIDATE_INT))
				{
					throw new \Exception(lang('Auth.error_security'));
				}

				if ($this->PostsModel->delete($id)) {
					session()->setFlashdata('message', "Пост №".$id." успешно удален!");
				}
			}

			// redirect them back to the auth page
			return redirect()->to(base_url('sender'));
		}
	}

    /*
	Завершение редактирование черновика
	Отправка в очередь
	 */
	public function finish(int $id) {
		if (!$this->isAuthorized() OR !$this->isAccess('sender')) {
			return redirect()->to(base_url('/auth/login'));
		}
		if (!$count = $this->PostsModel->finish($id)) {
			session()->setFlashdata('message', "Пост №".$id." не может быть отправлен.");
		}
		if ($count > 0) {
			session()->setFlashdata('message', "Пост №".$id." будет отправлен ".$count." пользователям!");
		} else {
			session()->setFlashdata('message', "Пост №".$id." не может быть отправлен. Не выбраны пользователи!");
		}
		return redirect()->to(base_url('sender'));
	}

    /*
	Добавление черновика
	 */
	public function add() {
		if (!$this->isAuthorized() OR !$this->isAccess('sender')) {
			return redirect()->to(base_url('/auth/login'));
		}
		$id = $this->PostsModel->create();
		return redirect()->to(base_url('sender/edit/'.$id));
	}

	/*
	Редактирование
	 */
	public function edit(int $id) {
		if (!$this->isAuthorized() OR !$this->isAccess('sender')) {
			return redirect()->to(base_url('/auth/login'));
		}
		$this->ProductModel = new \Products\Models\ProductModel();
		$data = $this->PostsModel->get($id, TRUE);
		$data['id'] = $id;
		$data['segment_posting'] = $this->PostsModel->segment_posting($id);
		$data['products'] = $this->PostsModel->segments_send();

		$data['currency_name'] = $this->SettingsModel->currency_name;
		$data['message'] = session()->getFlashdata('message');
		$body = view('Sender\sender\edit', $data);
		return $this->view($body, 'Редактирование поста №<strong>'.$id.'</strong>', 'sender');
	}

	/*
	Сохраняем сегменты
	 */
	public function products_($id) {
		if (!$this->isAuthorized()) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не авторизован!']);
		}
		
		$data = $this->request->getPost();

		if (!$this->PostsModel->set_products($id, $data['value'])) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не удалось сохранить!']);
		}

		return $this->response->setJSON(['message' => 'Данные сохранены!']);
	}

	/*
	Сохраняем сумму бонуса
	 */
	public function sum_bonus($id) {
		if (!$this->isAuthorized()) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не авторизован!']);
		}
		
		$data = $this->request->getPost();
		if (!$this->PostsModel->set($id, [$data['field'] => trim($data['value'])])) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не удалось сохранить!']);
		}

		return $this->response->setJSON(['message' => 'Данные сохранены!']);
	}

	/*
	Сохраняем код файла
	 */
	public function file_id($id) {
		if (!$this->isAuthorized()) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не авторизован!']);
		}
		
		$data = $this->request->getPost();
		if (!$this->PostsModel->set($id, [$data['field'] => trim($data['value'])])) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не удалось сохранить!']);
		}

		return $this->response->setJSON(['message' => 'Данные сохранены!']);
	}

	/*
	Сохраняем чекбокс превью сообщения
	 */
	public function disable_web_page_preview($id) {
		if (!$this->isAuthorized()) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не авторизован!']);
		}
		
		$data = $this->request->getPost();
		$disable_web_page_preview = $data['checked'] == "true";
		if (!$this->PostsModel->set($id, ['disable_web_page_preview' => $disable_web_page_preview])) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не удалось сохранить!']);
		}

		return $this->response->setJSON(['message' => 'Данные сохранены!']);
	}

	/*
	Сохранить страницу
	Возвращение ответов
	@docs https://codeigniter4.github.io/CodeIgniter4/outgoing/response.html
	 */
	public function save_($id) {
		if (!$this->isAuthorized()) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не авторизован!']);
		}
		
		$data = $this->request->getPost();

		if (!$this->PostsModel->save($data, $id)) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не удалось сохранить!']);
		}

		return $this->response->setJSON(['message' => 'Данные сохранены!']);
	}
}
