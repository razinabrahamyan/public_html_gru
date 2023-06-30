<?php namespace Admin\Controllers;

/**
 * Контроллер для работы со кнопками бота
 *
 * @author  KrotovRoman <tg: @KrotovRoman>
 */

class Buttons extends AbstractAdminController
{	

	/**
	 * Отобразить список страниц
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function index() {
		if (!$this->isAuthorized() OR !$this->isAccess('buttons')) {
			return redirect()->to(base_url('/auth/login'));
		}
		
		$languages = $this->LangModel->languages();
		$data['need_languages'] = count($languages) > 1;
		
		$data['buttons'] = $this->ButtonsModel->buttons();

		$body = view('Admin\buttons\buttons', $data);
		return $this->view($body, 'Кнопки', 'buttons');
	}

	/*
	Редактирование кнопки
	 */
	public function edit($id) {
		if (!$this->isAuthorized() OR !$this->isAccess('buttons')) {
			return redirect()->to(base_url('/auth/login'));
		}
		
		$validation = \Config\Services::validation();

		if (! empty($_POST)) {
			$res = $this->request->getPost('nameru');
			$validation->setRule('nameru', 'Название', 'trim|required');
			if ($post = $this->request->getPost() AND $validation->withRequest($this->request)->run()){
				$this->ButtonsModel->set($post);
				return redirect()->to(base_url('/admin/buttons'));
			}
		}

		helper(['form']);

		$data['data']  = $this->ButtonsModel->get($id);
		$data['languages'] = $this->LangModel->languages();
		$data['translate'] = $this->LangModel->trans_btn($id);

		$data['message'] = $validation->getErrors() ? $validation->listErrors() : NULL;
		$data['message'] = (mb_strlen($data['message']) <= 131)  ? NULL : $data['message'];

		$body = view('Admin\buttons\edit', $data);
		return $this->view($body, 'Редактирование <strong>'.$data['data']['name'].'</strong>', 'buttons');
	}
}
