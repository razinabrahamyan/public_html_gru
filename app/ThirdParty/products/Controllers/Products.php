<?php 

/**
 * Контроллер для работы с рассылкой
 *
 * @author  KrotovRoman <tg: @KrotovRoman>
 */

namespace Products\Controllers;
class Products extends \Admin\Controllers\AbstractAdminController
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

	/*
	Редактирование
	 */
	public function edit_item_kg(int $id) {
		if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$validation = \Config\Services::validation();

		$data = $this->ProductModel->item_kg($id);
		if (! empty($_POST)) {
			$validation->setRule('price', 'цена', 'trim|required');
			$validation->setRule('value', 'Кол-во', 'trim|required');

			if ($post = $this->request->getPost() AND $validation->withRequest($this->request)->run()){
				if ($this->ProductModel->set_kg($post)) {
					session()->setFlashdata('message', "Успешно сохранено!");
				}
				return redirect()->to(base_url('products/items_kg/'.$data['id_product']));
			}
		}

		helper(['form']);
		$data['currency_name'] = $this->SettingsModel->currency_name;
		$body = view('Products\items_kg\edit', $data);
		return $this->view($body, 'Редактирование записи №<strong>'.$data['id'].'</strong>', 'products');
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
	public function delete_item_kg(int $id = 0)
	{
		if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['id'] = $id;
		$data['data']  = $this->ProductModel->item_kg($id);

		$validation = \Config\Services::validation();

		$validation->setRule('confirm', 'confirm', 'required');
		$validation->setRule('id', 'id', 'required|integer');

		if (! $validation->withRequest($this->request)->run())
		{
			helper(['form']);
			$body         = view('Products\items_kg\delete', $data);
			return $this->view($body, 'Удалить запись №<strong>'.$data['data']['id'].'</strong>' , 'products');
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

				if ($this->ProductModel->delete_kg($id)) {
					session()->setFlashdata('message', "Успешно удалено!");
				}
			}

			// redirect them back to the auth page
			return redirect()->to(base_url('products/items_kg/'.$data['data']['id_product']));
		}
	}

	/**
	 * Добавление единицы товара
	 *
	 * @return string string|\CodeIgniter\HTTP\RedirectResponse
	 */
	public function add_item_kg(int $id_product)
	{
		if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$validation = \Config\Services::validation();
		$validation->setRule('price', 'цена', 'trim|required');
		$validation->setRule('value', 'Кол-во', 'trim|required');

		if ($this->request->getPost() && $validation->withRequest($this->request)->run())
		{
			$post = $this->request->getPost();
			if ($this->ProductModel->add_kg($post)){
				session()->setFlashdata('message', "Успешно добавлено!");
			} else {
				session()->setFlashdata('message', "Не добавлено ни одной новой записи!");
			}

			return redirect()->to(base_url('products/items_kg/'.$id_product));
		}
		else
		{
			helper(['form']);
			
			$data = $this->ProductModel->get($id_product);
			$data['id_product'] = $id_product;
			$data['title'] = "Добавить цену для веса <strong>".$data['name']."</strong>";
			$data['message'] = $validation->getErrors() ? $validation->listErrors() : session()->getFlashdata('message');
			$data['currency_name'] = $this->SettingsModel->currency_name;
			$body = view('Products\items_kg\add', $data);
			return $this->view($body, $data['title'] , 'products');
		}
	}

	/**
	 * Список единиц товара
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function items_kg(int $id_product) {
		if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return redirect()->to(base_url('/auth/login'));
		}
		$data = $this->ProductModel->get($id_product);
		$data['id_product']= $id_product;
		$data['items'] = $this->ProductModel->product_items_kg($id_product);
		$data['currency_name'] = $this->currency_name;
		$data['decimals'] = $this->decimals;
		$data['message'] = session()->getFlashdata('message');

		$body = view('Products\items_kg\items', $data);
		return $this->view($body, 'Цены по весу для <strong>'.$data['name'].'</strong>', 'products');
	}

    /*
    Запустить парсинг продуктов
     */
    public function parsestart() {
    	if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return redirect()->to(base_url('/auth/login'));
		}
    	if ($this->ProductModel->parse_start()) {
    		session()->setFlashdata('message', "Парсинг продуктов запущен!");
    	}
    	return redirect()->to(base_url('products'));
    }

	/*
     * Список ajax
     * 
     * @docs https://datatables.net/manual/server-side#Sent-parameters
     * @docs https://datatables.net/examples/server_side/simple.html
     * @docs https://datatables.net/examples/data_sources/server_side.html
     */

	public function products_() {
		if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return FALSE;
		}
		$post = $this->request->getPost();

		$return = new \stdClass();
        $return->draw = (int) $post['draw']; //сколько прорисовывать - то что прислали нам
        $return->length = (int) $post['length']; //количество записей на странице
        
        //количество записей до фильтрации
        $return->recordsTotal = $this->ProductModel->products_($post, FALSE);

        //получаем данные с учетом фильтров
        $return->data = $this->ProductModel->products_($post);

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
		if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['message'] = session()->getFlashdata('message');
		$data['currency_name'] = $this->currency_name;
		$data['decimals'] = $this->decimals;
		$body = view('Products\products\items_', $data);
		return $this->view($body, 'Продукты', 'products');
	}


	/*
	Редактирование
	 */
	public function edit_photo(int $id) {
		if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$validation = \Config\Services::validation();

		$data = $this->ProductModel->get_photo($id);
		if (! empty($_POST)) {
			$validation->setRule('media', 'file_id', 'trim|required');
			if ($post = $this->request->getPost() AND $validation->withRequest($this->request)->run()){
				$this->ProductModel->set_photo($post);
				return redirect()->to(base_url('products/photos/'.$data['id_item']));
			}
		}

		helper(['form']);
		$data['currency_name'] = $this->SettingsModel->currency_name;
		$body = view('Products\photos\edit', $data);
		return $this->view($body, 'Редактирование записи №<strong>'.$data['id'].'</strong>', 'products');
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
	public function delete_photo(int $id = 0)
	{
		if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['id'] = $id;
		$data['data']  = $this->ProductModel->get_photo($id);

		$validation = \Config\Services::validation();

		$validation->setRule('confirm', 'confirm', 'required');
		$validation->setRule('id', 'id', 'required|integer');

		if (! $validation->withRequest($this->request)->run())
		{
			helper(['form']);
			$body         = view('Products\photos\delete', $data);
			return $this->view($body, 'Удалить запись №<strong>'.$data['data']['id'].'</strong>' , 'products');
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

				$this->ProductModel->delete_photo($id);
			}

			// redirect them back to the auth page
			return redirect()->to(base_url('products/photos/'.$data['data']['id_item']));
		}
	}

	/**
	 * Добавление единицы товара
	 *
	 * @return string string|\CodeIgniter\HTTP\RedirectResponse
	 */
	public function add_photo(int $id_item)
	{
		if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$validation = \Config\Services::validation();
		$validation->setRule('id_item', 'id_item', 'trim|required');
		$validation->setRule('media', 'Артикул', 'trim|required');

		if ($this->request->getPost() && $validation->withRequest($this->request)->run())
		{	
			$post = $this->request->getPost();
			if ($this->ProductModel->add_photo($post)){
				session()->setFlashdata('message', "Успешно добавлено!");
			} else {
				session()->setFlashdata('message', "Не добавлено ни одной новой записи!");
			}

			return redirect()->to(base_url('products/photos/'.$id_item));
		}
		else
		{
			helper(['form']);
			$this->ModModel = new \Mods\Models\ModModel();
			$data = $this->ProductModel->get_item($id_item);
			$data['id_item'] = $id_item;
			$data['title'] = "Добавить фото единицы товара <strong>".$data['articul']."</strong>";
			$data['message'] = $validation->getErrors() ? $validation->listErrors() : session()->getFlashdata('message');
			$data['currency_name'] = $this->SettingsModel->currency_name;
			$body = view('Products\photos\add', $data);
			return $this->view($body, $data['title'] , 'products');
		}
	}

	/**
	 * Список фото единицы товара
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function photos(int $id_item) {
		if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return redirect()->to(base_url('/auth/login'));
		}
		$data = $this->ProductModel->get_item($id_item);
		$data['id_item']= $id_item;
		$data['items'] = $this->ProductModel->photos($id_item);
		$data['currency_name'] = $this->currency_name;
		$data['decimals'] = $this->decimals;
		$data['message'] = session()->getFlashdata('message');
		$body = view('Products\photos\items', $data);
		return $this->view($body, 'Фото единицы товара <strong>'.$data['articul'].'</strong>', 'products');
	}

	/*
	Сохраняем код файла
	 */
	public function file_id($id) {
		if (!$this->isAuthorized()) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не авторизован!']);
		}
		
		$data = $this->request->getPost();
		if (!$this->ProductModel->set(['id' => $id, $data['field'] => trim($data['value'])], TRUE)) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не удалось сохранить!']);
		}

		return $this->response->setJSON(['message' => 'Данные сохранены!']);
	}

	/*
	Редактирование
	 */
	public function edit_item(int $id) {
		if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$validation = \Config\Services::validation();

		$data = $this->ProductModel->get_item($id);
		if (! empty($_POST)) {
			$validation->setRule('articul', 'Артикул', 'trim|required');
			if ($post = $this->request->getPost() AND $validation->withRequest($this->request)->run()){
				$this->ProductModel->set_item($post);
				return redirect()->to(base_url('products/items/'.$data['id_product']));
			}
		}

		helper(['form']);
		$this->ModModel = new \Mods\Models\ModModel();
		$data['mods_item'] = $this->ModModel->mods_item($id);
		$data['mods'] = $this->ModModel->mods_product($data['id_product']);
		$data['currency_name'] = $this->SettingsModel->currency_name;
		$body = view('Products\items\edit', $data);
		return $this->view($body, 'Редактирование записи №<strong>'.$data['id'].'</strong>', 'products');
	}

	/*
	Копировать единицу товара в другие товары
	 */
	public function copy_item(int $id = 0)
	{
		if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data = $this->ProductModel->get_item($id);

		if (! empty($_POST)) {
			$validation = \Config\Services::validation();
			$validation->setRule('id', 'ID', 'trim');
			if ($post = $this->request->getPost() AND $validation->withRequest($this->request)->run()){
				$this->ProductModel->copy_item($post);
				return redirect()->to(base_url('products/items/'.$data['id_product']));
			}
		}

		helper(['form']);
		$data['items'] = $this->ProductModel->items();
		$data['currency_name'] = $this->SettingsModel->currency_name;
		$body = view('Products\items\copy', $data);
		return $this->view($body, 'Копирование записи №<strong>'.$data['id'].'</strong>', 'products');
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
	public function delete_item(int $id = 0)
	{
		if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['id'] = $id;
		$data['data']  = $this->ProductModel->get_item($id);
		if (!isset($data['data']['id_product'])) {
			return redirect()->to(base_url('products'));
		}
		$this->ProductModel->delete_item($id);
		return redirect()->to(base_url('products/items/'.$data['data']['id_product']));

		$validation = \Config\Services::validation();

		$validation->setRule('confirm', 'confirm', 'required');
		$validation->setRule('id', 'id', 'required|integer');

		if (! $validation->withRequest($this->request)->run())
		{
			helper(['form']);
			$body         = view('Products\items\delete', $data);
			return $this->view($body, 'Удалить запись №<strong>'.$data['data']['id'].'</strong>' , 'products');
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

				$this->ProductModel->delete_item($id);
			}

			// redirect them back to the auth page
			return redirect()->to(base_url('products/items/'.$data['data']['id_product']));
		}
	}

	/**
	 * Добавление единицы товара
	 *
	 * @return string string|\CodeIgniter\HTTP\RedirectResponse
	 */
	public function add_item(int $id_product)
	{
		if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$validation = \Config\Services::validation();
		$validation->setRule('id', 'id продукта', 'trim|required');
		$validation->setRule('articul', 'Артикул', 'trim');

		if ($this->request->getPost() && $validation->withRequest($this->request)->run())
		{
			$post = $this->request->getPost();

			$count = $this->ProductModel->add_item($id_product, $post);
			if (count($count) > 0){
				session()->setFlashdata('message', "Успешно добавлено!");
			} else {
				session()->setFlashdata('message', "Не добавлено ни одной новой записи!");
			}

			return redirect()->to(base_url('products/items/'.$id_product));
		}
		else
		{
			helper(['form']);
			$this->ModModel = new \Mods\Models\ModModel();
			$data = $this->ProductModel->get($id_product);
			$data['id_product'] = $id_product;
			$data['title'] = "Добавить единицу товара <strong>".$data['name']."</strong>";
			$data['mods'] = $this->ModModel->mods_product($id_product);
			$data['message'] = $validation->getErrors() ? $validation->listErrors() : session()->getFlashdata('message');
			$data['currency_name'] = $this->SettingsModel->currency_name;
			$body = view('Products\items\add', $data);
			return $this->view($body, $data['title'] , 'products');
		}
	}

	/**
	 * Список единиц товара
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function items(int $id_product) {
		if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return redirect()->to(base_url('/auth/login'));
		}
		$data = $this->ProductModel->get($id_product);
		$data['id_product']= $id_product;
		$data['items'] = $this->ProductModel->product_items($id_product);
		$data['currency_name'] = $this->currency_name;
		$data['decimals'] = $this->decimals;
		$data['message'] = session()->getFlashdata('message');

		$this->ModModel = new \Mods\Models\ModModel();
		$data['mods'] = $this->ModModel->mods_product($id_product);

		$body = view('Products\items\items', $data);
		return $this->view($body, 'Единицы товара <strong>'.$data['name'].'</strong>', 'products');
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

		$data['title'] = "Добавить продукт";

		$validation = \Config\Services::validation();

		$validation->setRule('name', 'Название', 'trim|required');

		if ($this->request->getPost() && $validation->withRequest($this->request)->run())
		{
			if ($this->ProductModel->add($this->request->getPost()))
			{
				session()->setFlashdata('message', "Успешно добавлено!");
				return redirect()->to(base_url('products'));
			}
		}
		else
		{
			helper(['form']);
			$data['message'] = $validation->getErrors() ? $validation->listErrors() : session()->getFlashdata('message');
			$data['currency_name'] = $this->SettingsModel->currency_name;
			$data['items'] = $this->ProductModel->categoryes();
			$body = view('Products\products\add', $data);
			return $this->view($body, $data['title'] , 'products');
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

		//если надо без подтверждения удалять
		// $this->ProductModel->delete($id);
		// return redirect()->to(base_url('/products'));
		
		///!!!
		$data['id'] = $id;
		$data['data']  = $this->ProductModel->get($id);

		$validation = \Config\Services::validation();

		$validation->setRule('confirm', 'confirm', 'required');
		$validation->setRule('id', 'id', 'required|integer');

		if (! $validation->withRequest($this->request)->run())
		{
			helper(['form']);
			$body         = view('Products\products\delete', $data);
			return $this->view($body, 'Удалить <strong>'.$data['data']['name'].'</strong>' , 'products');
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

				$this->ProductModel->delete($id);
			}

			// redirect them back to the auth page
			return redirect()->to(base_url('/products'));
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
				$this->ProductModel->set($post);
				return redirect()->to(base_url('/products'));
			}
		}

		helper(['form']);

		$this->ModModel = new \Mods\Models\ModModel();
		
		$data = $this->ProductModel->get($id);
		$data['all_groups'] = $this->ModModel->groups();
		$data['product_groups'] = $this->ModModel->product_groups($id);
		$data['items'] = $this->ProductModel->categoryes();
		$data['categoryes'] = $this->ProductModel->categoryes($id);
		$data['currency_name'] = $this->SettingsModel->currency_name;
		$body = view('Products\products\edit', $data);
		return $this->view($body, 'Редактирование <strong>'.$data['name'].'</strong>', 'products');
	}

	/*
	Сохранить описание
	Возвращение ответов
	@docs https://codeigniter4.github.io/CodeIgniter4/outgoing/response.html
	 */
	public function save_($id) {
		if (!$this->isAuthorized()) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не авторизован!']);
		}
		
		$data = $this->request->getPost();

		if (!$this->ProductModel->save_description($data, $id)) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не удалось сохранить!']);
		}

		return $this->response->setJSON(['message' => 'Данные сохранены!']);
	}

	/*
	Редактирование сообщения
	 */
	public function thankyou(int $id) {
		if (!$this->isAuthorized() OR !$this->isAccess('products')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data = $this->ProductModel->get($id);
		$data['items'] = $this->ProductModel->channels();
		$body = view('Products\products\thankyou', $data);
		return $this->view($body, 'Спасибо за покупку <strong>'.$data['name'].'</strong>', 'products');
	}

	/*
	Сохранить описание
	Возвращение ответов
	@docs https://codeigniter4.github.io/CodeIgniter4/outgoing/response.html
	 */
	public function thankyou_($id) {
		if (!$this->isAuthorized()) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не авторизован!']);
		}
		
		$data = $this->request->getPost();

		if (!$this->ProductModel->save_thankyou($data, $id)) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не удалось сохранить!']);
		}

		return $this->response->setJSON(['message' => 'Данные сохранены!']);
	}

	/*
	Сохраняем чекбокс превью сообщения
	 */
	public function end_month($id) {
		if (!$this->isAuthorized()) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не авторизован!']);
		}
		
		$data = $this->request->getPost();
		$end_month = $data['checked'] == "true";
		
		$data = [];
		$data['id'] = $id;
		$data['end_month'] = $end_month;

		if (!$this->ProductModel->set($data)) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не удалось сохранить!']);
		}

		return $this->response->setJSON(['message' => 'Данные сохранены!']);
	}
}
