<?php 

/**
 * Контроллер для работы с партнерской программой
 *
 * @author  KrotovRoman <tg: @KrotovRoman>
 */

namespace Stat\Controllers;
class Stat extends \Admin\Controllers\AbstractAdminController
{	

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		$this->StatModel = new \Stat\Models\StatModel();
	}

	/**
	 * График активности по дням недели
	 * 
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function days() {
		if (!$this->isAuthorized() OR !$this->isAccess('stat')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['data_stat'] = $this->StatModel->days();
		$data['message'] = session()->getFlashdata('message');
		$body = view('Stat\stat\days', $data);
		return $this->view($body, 'По дням недели', 'stat');
	}

	/**
	 * График активности по часам
	 * 
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function hours() {
		if (!$this->isAuthorized() OR !$this->isAccess('stat')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['data_stat'] = $this->StatModel->hours();
		$data['message'] = session()->getFlashdata('message');
		$body = view('Stat\stat\hours', $data);
		return $this->view($body, 'По часам', 'stat');
	}

	/**
	 * Отобразить список 
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function index() {
		if (!$this->isAuthorized() OR !$this->isAccess('stat')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$this->ProductModel = new \Products\Models\ProductModel();
		$this->StatModel = new \Stat\Models\StatModel();

		if (!empty($_POST)) {
			$data = $this->request->getPost();
			$daterange = $this->StatModel->extract_range($data['daterange']); 
			$data['date_start'] = $daterange['date_start'];
			$data['date_finish'] = $daterange['date_finish'];
			$data['items'] = $this->ProductModel->items();
		} else {
			$data['date_start'] = date("Y-m-d H:i", time() - 3600 * 24 * 7);
			$data['date_finish'] = date("Y-m-d H:i");
			$data['daterange'] = $data['date_start']." / ".$data['date_finish'];
			$data['items'] = $this->ProductModel->items();
			$products = $this->ProductModel->items();
			$data['products'] = [];
			foreach ($products as $product) {
				$data['products'][]=$product['id'];
			}
		}
		
		$data['data_array'] = $this->StatModel->candles($data['date_start'], $data['date_finish'], $data['products']);
		$data['message'] = session()->getFlashdata('message');
		helper(['form']);
		$body = view('Stat\stat\index_', $data);
		return $this->view($body, 'Статистика', 'stat');
	}

	/**
	 * Отобразить список 
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function history() {
		if (!$this->isAuthorized() OR !$this->isAccess('stat')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$data['data_array'] = $this->StatModel->history();
		$data['message'] = session()->getFlashdata('message');
		$body = view('Stat\stat\history', $data);
		return $this->view($body, 'История', 'stat');
	}

	/*
     * Список ajax
     * 
     * @docs https://datatables.net/manual/server-side#Sent-parameters
     * @docs https://datatables.net/examples/server_side/simple.html
     * @docs https://datatables.net/examples/data_sources/server_side.html
     */

	public function index_() {
		if (!$this->isAuthorized() OR !$this->isAccess('ruletka')) {
			return FALSE;
		}
		$post = $this->request->getPost();

		$return = new \stdClass();
        $return->draw = (int) $post['draw']; //сколько прорисовывать - то что прислали нам
        $return->length = (int) $post['length']; //количество записей на странице
        
        //количество записей до фильтрации
        $return->recordsTotal = $this->StatModel->items_($post, FALSE);

        //получаем данные с учетом фильтров
        $return->data = $this->StatModel->items_($post);

        if (!empty($post['search']['value']) AND ! $post['search']['regex']) {
            $return->recordsFiltered = count($return->data); //если был применен поиск
        } else {
            $return->recordsFiltered = $return->recordsTotal; //если без поиска            
        }
        return print json_encode($return);
    }

}
