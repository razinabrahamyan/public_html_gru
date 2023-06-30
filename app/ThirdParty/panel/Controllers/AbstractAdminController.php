<?php namespace Admin\Controllers;

/**
 * Admin abstract controller file
 *
 * @package CI-Admin
 * @author  Benoit VRIGNAUD <benoit.vrignaud@zaclys.net>
 * @license https://opensource.org/licenses/MIT	MIT License
 * @link    http://github.com/bbvrignaud/ci-admin
 */

use CodeIgniter\Controller;

/**
 * Admin abstract controller
 *
 * @package CI4-Admin
 */
abstract class AbstractAdminController extends Controller
{
	/**
	 * IonAuth library
	 *
	 * @var \IonAuth\Libraries\IonAuth
	 */
	protected $ionAuth;

	
	/**
	 * User
	 *
	 * @var stdClass
	 */
	protected $user;

	protected $db;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->ionAuth = new \IonAuth\Libraries\IonAuth();
		
		if ($this->ionAuth->loggedIn())
		{	
			$this->db = \Config\Database::connect();
			$this->UsersModel = new \Admin\Models\UsersModel();
			$this->PagesModel = new \Admin\Models\PagesModel();
			$this->ButtonsModel = new \Admin\Models\ButtonsModel();
			$this->LangModel = new \Admin\Models\LangModel();
			$this->SettingsModel = new \Admin\Models\SettingsModel();

			$this->user = $this->ionAuth->user()->row();
			
			//аватарка
			$default = base_url("/assets/img/avatar.png");
        	$this->user->grav_url = "https://www.gravatar.com/avatar/" . md5(strtolower(trim($this->user->email))) . "?d=" . urlencode($default) . "&s=160";

        	$settings = $this->SettingsModel->all(TRUE);
	        foreach ($settings as $settings_) {
	            $this->{$settings_['name']} = trim($settings_['value']);
	        }
		} else {
			return redirect()->to(base_url('/auth/login'));
		}
	}

	/*
	Проверка доступа текущего пользователя к конкретному методу админки
	 */
	protected function isAccess($method): bool
	{
		return $this->UsersModel->section_access($method);
	}

	/**
	 * Check if user is logged in is admin
	 *
	 * @return boolean
	 */
	protected function isAuthorized(): bool
	{
		return $this->ionAuth->loggedIn();
	}

	/*
	Взять данные меню из БД
	 */
	public function leftMenu(): array
	{
		$this->db = \Config\Database::connect();
		
		$panel_sidebar = $this->db->table('panel_sidebar')->where('active', 1)->orderBy('priority', 'DESC')->get()->getResultArray();
		$result = [];
		foreach($panel_sidebar as $panel) {
			$groups = $this->db->table('panel_access')->where('id_item' , $panel['id'])->get()->getResultArray();
			$panel['groups'] = [];
			foreach($groups as $group) {
				$panel['groups'][]=$group['id_group'];
			}
			$result[$panel['method']] = $panel;
		}
		return $result;
	}

	/**
	 * Display the $body page inside the main vue
	 *
	 * @param string $body       Body vue
	 * @param string $pageTitle  Page title
	 * @param string $activeMenu Active menu
	 *
	 * @return string
	 */
	protected function view(string $body, string $pageTitle = '', string $activeMenu = ''): string
	{
		$mainData = [
			'appName'       => env('appName', 'Админка'),
			'user_data'     => $this->user,
			'userFirstName' => json_decode($this->user->first_name),
			'userLastName'  => json_decode($this->user->last_name),
			'is_admin'      => $this->ionAuth->isAdmin(),
			'pageTitle'     => $pageTitle,
			'leftMenu'      => $this->displayLeftMenu($this->leftMenu(), $activeMenu),
			'Breadcrumb'    => $this->displayBreadcrumb($this->leftMenu(), $activeMenu),
			'body'          => $body
		];

		return view('Admin\main', $mainData);
	}

	/**
	 * Parse $menu and return the html menu
	 *
	 * @param array  $menus      Menu to parse
	 * @param string $activeMenu Active menu
	 *
	 * @return string
	 */
	private function displayLeftMenu(array $menus, string $activeMenu): string
	{
		$html = '';
		foreach ($menus as $keyMenu => $menu) {	

			//проверка группы доступа к меню
			if (isset($menu['groups'])) {
				if (!$this->ionAuth->inGroup($menu['groups'])) {
					continue;
				}
			}

			$active = $activeMenu === $keyMenu ? ' active' : '';
			$html  .= '<li class="nav-item"' . (empty($menu['title']) ?
							'' : ' title="' . lang($menu['title']) . '"') . '>';
			
			if (empty($menu['sous-menu'])) {
				$html .= '<a class="nav-link ' . $active . '" href="' . site_url($menu['url']) . '">';
				$html .= isset($menu['icon']) ?
							'<i class="nav-icon fa fa-' . $menu['icon'] . '" aria-hidden="true"></i> ' : '';
				$html .= '<p>' . lang($menu['label']) . '</p>';
				$html .= '</a>';
			} else {
				$html .= $this->displayLeftMenu($menu['sous-menu']);
			}

			$html .= '</li>';
		}
		
		return $html;
	}

	/*
	Хлебные крошки
	 */
	private function displayBreadcrumb(array $menus, string $activeMenu): string
	{
		$html = '<li class="breadcrumb-item">';
		$html .= '<a href="'.base_url('/admin').'">Главная</a>';
		$html .= '</li>';

		foreach ($menus as $keyMenu => $menu)
		{
			if ($activeMenu === $keyMenu) {
				if (isset($menu['groups'])) {
					if (!$this->ionAuth->inGroup($menu['groups'])) {
						continue;
					}
				}
				$html .= '<li class="breadcrumb-item">';
				$html .= isset($menu['icon']) ?
							'<i class="nav-icon fa fa-' . $menu['icon'] . '" aria-hidden="true"></i> ' : '';
				$html .= '<a href="'.base_url($menu['url']).'">'.lang($menu['title']).'</a>';
				$html .= '</li>';



				// $html .= '<li class="breadcrumb-item active">';
				// $html .= lang($menu['title']);
				// $html .= '</li>';
			}
		}
		return $html;
	}
}
