<?php namespace Admin\Controllers;

/**
 * Контроллер для работы со страницами
 *
 * @author  KrotovRoman <tg: @KrotovRoman>
 */

class Settings extends AbstractAdminController
{	
	/**
	 * Настройки
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function index() {
		if (!$this->isAuthorized() OR !$this->isAccess('settings')) {
			return redirect()->to(base_url('/auth/login'));
		}

		if (!empty($_POST)) {
			$post = $this->request->getPost();
			if ($this->SettingsModel->save($post)) {
				$data['message'] = "Сохранено успешно!";
			}
		}

		helper(['form']);
		$data['settings'] = $this->SettingsModel->all();
		$body = view('Admin\settings\main', $data);
		return $this->view($body, 'Настройки', 'settings');
	}
}
