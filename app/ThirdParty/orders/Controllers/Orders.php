<?php 

/**
 * Контроллер для работы с партнерской программой
 *
 * @author  KrotovRoman <tg: @KrotovRoman>
 */

namespace Orders\Controllers;
class Orders extends \Admin\Controllers\AbstractAdminController
{	

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		$this->OrderModel = new \Orders\Models\OrderModel();
	}

	/*
	Редактирование
	 */
	public function edit_item(int $id) {
		if (!$this->isAuthorized() OR !$this->isAccess('orders')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data  = $this->OrderModel->get_order_item($id);

		if (! empty($_POST)) {
			$validation = \Config\Services::validation();
			$validation->setRule('price', 'Стоимость единицы товара', 'trim|required');
			if ($post = $this->request->getPost() AND $validation->withRequest($this->request)->run()){
				if ($this->OrderModel->set_order_item($post)) {
					$this->OrderModel->recount_sum_order($data['id_order']);
				}

				return redirect()->to(base_url('/orders/edit/'.$data['id_order']));
			}
		}

		helper(['form']);
		$data['currency_name'] = $this->currency_name;
		$data['decimals'] = $this->decimals;
		$body = view('Orders\orders\edit_item', $data);
		return $this->view($body, 'Редактирование единицы товара в №<strong>'.$data['id_order'].'</strong>', 'orders');
	}

	public function status(int $id) {
		if (!$this->isAuthorized() OR !$this->isAccess('orders')) {
			return redirect()->to(base_url('/auth/login'));
		}
		$data  = $this->OrderModel->get($id);
		$status = $data['status'] > 0 ? 0 : 1;
		if ($status) {
			session()->setFlashdata('message', "Заказ помечен оплаченным!");
		}

		$this->OrderModel->status($id, $status);
		return redirect()->to(base_url('/orders'));
	}

	/**
	 * Отобразить список 
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function index() {
		if (!$this->isAuthorized() OR !$this->isAccess('orders')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['message'] = session()->getFlashdata('message');

		$data['currency_name'] = $this->currency_name;
		$body = view('Orders\orders\orders_', $data);
		return $this->view($body, 'Заказы', 'orders');
	}

	/*
     * Список ajax
     * 
     * @docs https://datatables.net/manual/server-side#Sent-parameters
     * @docs https://datatables.net/examples/server_side/simple.html
     * @docs https://datatables.net/examples/data_sources/server_side.html
     */

	public function orders_() {
		if (!$this->isAuthorized() OR !$this->isAccess('orders')) {
			return FALSE;
		}
		$post = $this->request->getPost();

		$return = new \stdClass();
        $return->draw = (int) $post['draw']; //сколько прорисовывать - то что прислали нам
        $return->length = (int) $post['length']; //количество записей на странице
        
        //количество записей до фильтрации
        $return->recordsTotal = $this->OrderModel->orders_($post, FALSE);

        //получаем данные с учетом фильтров
        $return->data = $this->OrderModel->orders_($post);

        if (!empty($post['search']['value']) AND ! $post['search']['regex']) {
            $return->recordsFiltered = count($return->data); //если был применен поиск
        } else {
            $return->recordsFiltered = $return->recordsTotal; //если без поиска            
        }
        return print json_encode($return);
    }

	/**
	 * Добавление
	 *
	 * @return string string|\CodeIgniter\HTTP\RedirectResponse
	 */
	public function add()
	{
		if (!$this->isAuthorized() OR !$this->isAccess('orders')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$validation = \Config\Services::validation();

		$validation->setRule('chat_id', 'Пользователь', 'trim|required');
		$validation->setRule('id_pay', 'Способ оплаты', 'trim|required');

		if ($this->request->getPost() && $validation->withRequest($this->request)->run())
		{	
			$data = $this->request->getPost();
			$data['finish'] = 1;
			if ($id_order = $this->OrderModel->add($data))
			{	
				//меняем статус заказа на оплаченный
				$this->OrderModel->status($id_order);

				session()->setFlashdata('message', "Успешно добавлено!");
				return redirect()->to(base_url('/orders'));
			}
		}
		else
		{	
			$this->PayModel = new \Pays\Models\PayModel();
			$this->ProductModel = new \Products\Models\ProductModel();
			helper(['form']);

			$data['message'] = $validation->getErrors() ? $validation->listErrors() : session()->getFlashdata('message');
			$data['currency_name'] = $this->currency_name;
			$data['users'] = $this->UsersModel->noadmin();
			$data['pays'] = $this->PayModel->items();
			$data['products'] = $this->ProductModel->items();
			
			$body = view('Orders\orders\add', $data);
			return $this->view($body, "Добавить оплаченный заказ" , 'orders');
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
		if (!$this->isAuthorized() OR !$this->isAccess('orders')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['data']  = $this->OrderModel->get($id);

		$validation = \Config\Services::validation();

		$validation->setRule('confirm', 'confirm', 'required');
		$validation->setRule('id', 'id', 'required|integer');

		if (! $validation->withRequest($this->request)->run())
		{
			helper(['form']);
			$body = view('Orders\orders\delete', $data);
			return $this->view($body, 'Удалить заказ №<strong>'.$data['data']['id'].'</strong>' , 'orders');
		}
		else
		{
			if ($this->request->getPost('confirm') === 'yes')
			{
				if ($id !== $this->request->getPost('id', FILTER_VALIDATE_INT))
				{
					throw new \Exception(lang('Auth.error_security'));
				}

				if ($this->OrderModel->delete($id)) {
					session()->setFlashdata('message', "Заказ №".$id." успешно удален!");
				}
			}

			// redirect them back to the auth page
			return redirect()->to(base_url('/orders'));
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
	public function delete_product(int $id = 0)
	{
		if (!$this->isAuthorized() OR !$this->isAccess('orders')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data = $this->OrderModel->get_order_item($id);

		$validation = \Config\Services::validation();

		$validation->setRule('confirm', 'confirm', 'required');
		$validation->setRule('id', 'id', 'required|integer');

		if (! $validation->withRequest($this->request)->run())
		{
			helper(['form']);
			$body = view('Orders\orders\delete_product', $data);
			return $this->view($body, 'Удалить единицу товара ID<strong>'.$data['id_item'].'</strong> из заказа №'.$data['id_order'] , 'orders');
		}
		else
		{
			if ($this->request->getPost('confirm') === 'yes')
			{
				if ($id !== $this->request->getPost('id', FILTER_VALIDATE_INT))
				{
					throw new \Exception(lang('Auth.error_security'));
				}

				if ($this->OrderModel->delete_order_item($id)) {
					session()->setFlashdata('message', "Продукт №".$id." успешно удален из заказа!");
				}
			}

			// redirect them back to the auth page
			return redirect()->to(base_url('orders/edit/'.$data['id_order']));
		}
	}

	/*
	Редактирование кнопки
	 */
	public function edit(int $id) {
		if (!$this->isAuthorized() OR !$this->isAccess('orders')) {
			return redirect()->to(base_url('/auth/login'));
		}
		helper(['form']);

		$validation = \Config\Services::validation();

		if (! empty($_POST)) {
			$validation->setRule('name_target', 'Имя получателя', 'trim');
			if ($post = $this->request->getPost() AND $validation->withRequest($this->request)->run()){
				$this->OrderModel->set($post);
				return redirect()->to(base_url('orders'));
			}
		}
		
		$data['ModModel'] = new \Mods\Models\ModModel();
		$data['id']  = $id;
		$data['data']  = $this->OrderModel->get($id);
		$data['products'] = $this->OrderModel->items_in_order($id);

		$data['currency_name'] = $this->currency_name;
		$data['decimals'] = $this->decimals;
		$body = view('Orders\orders\edit', $data);
		return $this->view($body, 'Редактирование заказа №<strong>'.$data['data']['id'].'</strong>', 'orders');
	}

	
}
