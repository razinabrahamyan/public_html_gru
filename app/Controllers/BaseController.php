<?php
namespace App\Controllers;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 *
 * @package CodeIgniter
 */

use CodeIgniter\Controller;

class BaseController extends Controller
{

	/**
	 * An array of helpers to be loaded automatically upon
	 * class instantiation. These helpers will be available
	 * to all other controllers that extend BaseController.
	 *
	 * @var array
	 */
	protected $helpers = ['text', 'date'];

	/**
	 * Constructor.
	 */
	public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
	{
		// не трогать эту строку
		parent::initController($request, $response, $logger);

		//--------------------------------------------------------------------
		// Предзагрузка models, libraries, etc, here.
		//--------------------------------------------------------------------
		$this->TelegramModel = new \App\Models\TelegramModel();
		$this->CronModel = new \Admin\Models\CronModel();

		$this->SettingsModel = new \Admin\Models\SettingsModel();
		$settings = $this->SettingsModel->all(TRUE);
        foreach ($settings as $settings_) {
            $this->{$settings_['name']} = trim($settings_['value']);
        }

        $this->db = \Config\Database::connect();
	}

	public function db() {
		return $this->db;
	}

}
