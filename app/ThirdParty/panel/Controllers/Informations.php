<?php namespace Admin\Controllers;

/**
 * Admin/Information controller file
 *
 * @package CI-Admin
 * @author  Benoit VRIGNAUD <benoit.vrignaud@zaclys.net>
 * @license https://opensource.org/licenses/MIT	MIT License
 * @link    http://github.com/bbvrignaud/ci-admin
 */

/**
 * Admin/Informations controller
 *
 * @package CI4-Admin
 */
class Informations extends AbstractAdminController
{
	use \CodeIgniter\API\ResponseTrait;

	/**
	 * Display informations page
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function index()
	{
		if (!$this->isAuthorized() OR !$this->isAccess('informations')) {
			return redirect()->to(base_url('/auth/login'));
		}

		helper('form');

		$data = [
			'dbVersion' => \Config\Database::connect()->getVersion(),
			'ciVersion' => \CodeIgniter\CodeIgniter::CI_VERSION,
		];
		$body = view ('Admin\informations', $data);

		return $this->view($body, lang('Admin.informations-title'), 'informations');
	}

	/**
	 * Display phpinfo
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function displayPhpInfo()
	{
		if (!$this->isAuthorized() OR !$this->isAccess('informations')) {
			return redirect()->to(base_url('/auth/login'));
		}

		return phpinfo();
	}

	/**
	 * Download database
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function exportDatabase()
	{
		if (!$this->isAuthorized() OR !$this->isAccess('informations')) {
			return redirect()->to(base_url('/auth/login'));
		}

		$dbUtil = \Config\Database::utils();
		$backup = $dbUtil->backup(['format' => 'gzip', 'filename' => date('Y-m-d') . '-backup.sql']);

		helper('download');
		force_download(date('Y-m-d') . '-backup.sql.gz', $backup);
	}

	/**
	 * Test if e-mail are correctly configured
	 */
	public function sendEmailForTest()
	{	
		if (!$this->isAuthorized() OR !$this->isAccess('informations')) {
			return redirect()->to(base_url('/auth/login'));
		}
		if (isset($_POST['email']))
		{
			$email = \Config\Services::email();
			$email->setFrom('test@'.$_SERVER['HTTP_HOST']);
			$email->setTo($this->request->getPost('email'));
			$email->setSubject('Тестовый Email '.date("d.m.Y H:i"));
			$email->setMessage('Тестовый Email пришел, означает отправка сообщений работает!');

			if ($email->send()) {
				return $this->respondCreated([]);
			}
			return $this->fail('Ошибка при отправки сообщения');
		}
	}
}
