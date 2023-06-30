<?php namespace Admin\Controllers;

/**
 * Контроллер для работы со страницами
 *
 * @author  KrotovRoman <tg: @KrotovRoman>
 */

class Pages extends AbstractAdminController
{	

	/**
	 * Отобразить список страниц
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function index() {
		if (!$this->isAuthorized() OR !$this->isAccess('pages')) {
			return redirect()->to(base_url('/auth/login'));
		}
		$languages = $this->LangModel->languages();
		$data['need_languages'] = count($languages) > 1;
		$data['pages'] = $this->PagesModel->pages();
		$body = view('Admin\pages\pages', $data);
		return $this->view($body, 'Страницы', 'pages');
	}

	/*
	Редактирование страницы
	 */
	public function edit(int $id, $short = "ru") {
		if (!$this->isAuthorized() OR !$this->isAccess('pages')) {
			return redirect()->to(base_url('/auth/login'));
		}
		
		$data = $this->PagesModel->get($id, TRUE, $short);
		$data['id'] = $id;
		$data['short'] = $short;

		$data['languages'] = $this->LangModel->languages();
		$data['translate'] = $this->LangModel->trans_page($id, $short);
		$body = view('Admin\pages\edit', $data);
		return $this->view($body, 'ID'.$id.' <strong>'.$data['name'].'</strong>', 'pages');
	}

	/*
	Сохраняем код файла
	 */
	public function file_id($id) {
		if (!$this->isAuthorized()) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не авторизован!']);
		}
		
		$data = $this->request->getPost();
		if (!$this->PagesModel->set($id, [$data['field'] => trim($data['value'])])) {
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
		if (!$this->PagesModel->set($id, ['disable_web_page_preview' => $disable_web_page_preview])) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не удалось сохранить!']);
		}

		return $this->response->setJSON(['message' => 'Данные сохранены!']);
	}

	/*
	Сохранить страницу
	Возвращение ответов
	@docs https://codeigniter4.github.io/CodeIgniter4/outgoing/response.html
	 */
	public function save_($id, $short = "ru") {
		if (!$this->isAuthorized()) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не авторизован!']);
		}
		
		$data = $this->request->getPost();

		if (!$this->PagesModel->save($data, $id, $short)) {
			return $this->response->setStatusCode(500)->setJSON(['message' => 'Не удалось сохранить!']);
		}

		return $this->response->setJSON(['message' => 'Данные сохранены!']);
	}
}
