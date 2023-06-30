<?php namespace Admin\Controllers;

/**
 * Admin/Users controller file
 *
 * @package CI-Admin
 * @author  Benoit VRIGNAUD <benoit.vrignaud@zaclys.net>
 * @license https://opensource.org/licenses/MIT	MIT License
 * @link    http://github.com/bvrignaud/ci-admin
 */

class Users extends AbstractAdminController
{
	/**
	 * Configuration
	 *
	 * @var \IonAuth\Config\IonAuth
	 */
	private $configIonAuth;
	protected $validationListTemplate = 'list';

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		$this->configIonAuth = config('IonAuth');

		$this->session       = \Config\Services::session();

		if (! empty($this->configIonAuth->templates['errors']['list']))
		{
			$this->validationListTemplate = $this->configIonAuth->templates['errors']['list'];
		}
	}

	/*
	Экспорировать в эксель
	 */
	public function export() {
    	if (!$this->isAuthorized() OR !$this->isAccess('users')) {
			return FALSE;
		}
		return $this->UsersModel->export();
	}

	/*
     * Список ajax
     * 
     * @docs https://datatables.net/manual/server-side#Sent-parameters
     * @docs https://datatables.net/examples/server_side/simple.html
     * @docs https://datatables.net/examples/data_sources/server_side.html
     */

    public function users_() {
    	if (!$this->isAuthorized() OR !$this->isAccess('users')) {
			return FALSE;
		}
        $post = $this->request->getPost();

        $return = new \stdClass();
        $return->draw = (int) $post['draw']; //сколько прорисовывать - то что прислали нам
        $return->length = (int) $post['length']; //количество записей на странице
        
        //количество записей до фильтрации
        $return->recordsTotal = $this->UsersModel->users_($post, FALSE);

        //получаем данные с учетом фильтров
        $return->data = $this->UsersModel->users_($post);

        if (!empty($post['search']['value']) AND ! $post['search']['regex']) {
            $return->recordsFiltered = count($return->data); //если был применен поиск
        } else {
            $return->recordsFiltered = $return->recordsTotal; //если без поиска            
        }
        return print json_encode($return);
    }

    /**
	 * Display informations page
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse|string
	 */
	public function index()
	{
		if (!$this->isAuthorized() OR !$this->isAccess('users')) {
			return redirect()->to(base_url('/auth/login'));
		}
		$data['currency_name'] = $this->SettingsModel->currency_name;
		$data['message'] = session()->getFlashdata('message');
		$body = view('Admin\users\users_', $data);
		return $this->view($body, lang('Auth.index_heading'), 'users');
	}

	/**
	 * Create a new user
	 *
	 * @return string|\CodeIgniter\HTTP\RedirectResponse
	 */
	public function createUser()
	{
		if (! $this->isAuthorized() OR !$this->ionAuth->isAdmin())
		{
			return redirect()->to(base_url('/auth/login'));
		}

		$data['title'] = lang('Auth.create_user_heading');

		$tables                 = $this->configIonAuth->tables;
		$identityColumn         = $this->configIonAuth->identity;
		$data['identityColumn'] = $identityColumn;

		$validation = \Config\Services::validation();
		$validation->setRule('first_name', lang('Auth.create_user_validation_fname_label'), 'trim|required');
		$validation->setRule('last_name', lang('Auth.create_user_validation_lname_label'), 'trim|required');
		if ($identityColumn !== 'email')
		{
			$validation->setRule(
				'identity',
				lang('Auth.create_user_validation_identity_label'),
				'trim|required|is_unique[' . $tables['users'] . '.' . $identityColumn . ']');
			$validation->setRule(
				'email', lang('Auth.create_user_validation_email_label'), 'trim|required|valid_email');
		}
		else
		{
			$validation->setRule(
				'email',
				lang('Auth.create_user_validation_email_label'),
				'trim|required|valid_email|is_unique[' . $tables['users'] . '.email]');
		}
		$validation->setRule('phone', lang('Auth.create_user_validation_phone_label'), 'trim');
		$validation->setRule(
			'password',
			lang('Auth.create_user_validation_password_label'),
			'required|min_length[' . $this->configIonAuth->minPasswordLength . ']|matches[password_confirm]');
		$validation->setRule(
			'password_confirm', lang('Auth.create_user_validation_password_confirm_label'), 'required');

		if ($this->request->getPost() && $validation->withRequest($this->request)->run())
		{
			$email    = strtolower($this->request->getPost('email'));
			$identity = ($identityColumn === 'email') ? $email : $this->request->getPost('identity');
			$password = $this->request->getPost('password');

			$additionalData = [
				'first_name' => $this->request->getPost('first_name'),
				'last_name'  => $this->request->getPost('last_name'),
				'email'    => $this->request->getPost('email'),
				'phone'      => $this->request->getPost('phone'),
			];
		}
		if (
			$this->request->getPost()
			&& $validation->withRequest($this->request)->run()
			&& $this->ionAuth->register($identity, $password, $email, $additionalData)
		)
		{
			// check to see if we are creating the user
			// redirect them back to the admin page
			session()->setFlashdata('message', $this->ionAuth->messages());
			return redirect()->to(base_url('/admin/users'));
		}
		else
		{
			// display the create user form
			helper(['form']);
			// set the flash data error message if there is one
			$data['message'] = $validation->getErrors() ?
			$validation->listErrors() :
			($this->ionAuth->errors() ? $this->ionAuth->errors() : session()->getFlashdata('message'));
			
			$data['message'] = (mb_strlen($data['message']) <= 131)  ? NULL : $data['message'];

			$data['firstName']       = [
				'name'  => 'first_name',
				'id'    => 'first_name',
				'type'  => 'text',
				'value' => set_value('first_name'),
			];
			$data['lastName']        = [
				'name'  => 'last_name',
				'id'    => 'last_name',
				'type'  => 'text',
				'value' => set_value('last_name'),
			];
			$data['identity']        = [
				'name'  => 'identity',
				'id'    => 'identity',
				'type'  => 'text',
				'value' => set_value('identity'),
			];
			$data['email']           = [
				'name'  => 'email',
				'id'    => 'email',
				'type'  => 'email',
				'value' => set_value('email'),
			];
			$data['phone']           = [
				'name'  => 'phone',
				'id'    => 'phone',
				'type'  => 'text',
				'value' => set_value('phone'),
			];
			$data['password']        = [
				'name'  => 'password',
				'id'    => 'password',
				'type'  => 'password',
				'value' => set_value('password'),
			];
			$data['passwordConfirm'] = [
				'name'  => 'password_confirm',
				'id'    => 'password_confirm',
				'type'  => 'password',
				'value' => set_value('password_confirm'),
			];

			$body = view('Admin\users\create_user', $data);
			return $this->view($body, lang('Auth.create_user_heading'), 'users');
		}
	}

	/**
	 * Edit a user
	 *
	 * @param integer $id User id
	 *
	 * @return string string|\CodeIgniter\HTTP\RedirectResponse
	 */
	public function edit(int $id)
	{	
		if (!$this->isAuthorized()) {
			return redirect()->to(base_url('/auth/login'));
		}
		if (! $this->isAuthorized() AND ! ((int)$this->ionAuth->user()->row()->id === $id)) {
			return redirect()->to(base_url('/admin/users'));
		}
		if (!$this->ionAuth->isAdmin()) {
			return redirect()->to(base_url('/admin'));
		}
		$validation = \Config\Services::validation();

		$data['title'] = lang('Auth.edit_user_heading');

		$user          = $this->ionAuth->user($id)->row();
		$groups        = $this->ionAuth->groups()->resultArray();
		$currentGroups = $this->ionAuth->getUsersGroups($id)->getResult();


		if (! empty($_POST))
		{
			// validate form input
			$validation->setRule('first_name', lang('Auth.edit_user_validation_fname_label'), 'trim');
			$validation->setRule('last_name', lang('Auth.edit_user_validation_lname_label'), 'trim');
			$validation->setRule('phone', lang('Auth.edit_user_validation_phone_label'), 'trim');
			$validation->setRule('email', lang('Auth.edit_user_validation_email_label'), 'trim|required');

			// do we have a valid request?
			if ($id !== $this->request->getPost('id', FILTER_VALIDATE_INT))
			{
				throw new \Exception(lang('Auth.error_security'));
			}

			// update the password if it was posted
			if ($this->request->getPost('password'))
			{
				$validation->setRule(
					'password',
					lang('Auth.edit_user_validation_password_label'),
					'required|min_length[' . $this->configIonAuth->minPasswordLength . ']|matches[password_confirm]');
				$validation->setRule(
					'password_confirm',
					lang('Auth.edit_user_validation_password_confirm_label'),
					'required');
			}

			if ($this->request->getPost() && $validation->withRequest($this->request)->run())
			{
				$data = [
					'first_name' => $this->request->getPost('first_name'),
					'last_name'  => $this->request->getPost('last_name'),
					'email'    => $this->request->getPost('email'),
					'phone'      => $this->request->getPost('phone'),
				];

				// update the password if it was posted
				if ($this->request->getPost('password'))
				{
					$data['password'] = $this->request->getPost('password');
				}

				// Only allow updating groups if user is admin
				if ($this->ionAuth->isAdmin())
				{
					// Update the groups user belongs to
					$groupData = $this->request->getPost('groups');

					if (! empty($groupData) AND 

						//у единственного админа нельзя менять группы доступа
						$this->db->table('users_groups')
			            ->where('user_id<>', $id) //не текущий пользователь
			            ->where('group_id', 1) //админ
			            ->countAllResults() > 0)
					{	
						
						$this->ionAuth->removeFromGroup('', $id);

						foreach ($groupData as $grp)
						{
							$this->ionAuth->addToGroup($grp, $id);
						}
					}
				}

				// check to see if we are updating the user
				if ($this->ionAuth->update($user->id, $data))
				{
					session()->setFlashdata('message', $this->ionAuth->messages());
				}
				else
				{
					session()->setFlashdata('message', $this->ionAuth->errors($this->validationListTemplate));
				}
				return redirect()->to(base_url('/admin/users'));
			}
		}

		// display the edit user form
		helper(['form']);

		// set the flash data error message if there is one
		$data['message'] = $validation->getErrors() ? $validation->listErrors() : ($this->ionAuth->errors() ? $this->ionAuth->errors() : session()->getFlashdata('message'));
		$data['message'] = (mb_strlen($data['message']) <= 131)  ? NULL : $data['message'];
		
		// pass the user to the view
		$data['user']          = $user;
		$data['groups']        = $groups;
		$data['currentGroups'] = $currentGroups;

		$data['firstName']       = [
			'name'  => 'first_name',
			'id'    => 'first_name',
			'type'  => 'text',
			'value' => set_value('first_name', json_decode($user->first_name) ?: ''),
		];
		$data['lastName']        = [
			'name'  => 'last_name',
			'id'    => 'last_name',
			'type'  => 'text',
			'value' => set_value('last_name', json_decode($user->last_name) ?: ''),
		];
		$data['phone']           = [
			'name'  => 'phone',
			'id'    => 'phone',
			'type'  => 'text',
			'value' => set_value('phone', empty($user->phone) ? '' : $user->phone),
		];
		$data['email']           = [
			'name'  => 'email',
			'id'    => 'email',
			'type'  => 'email',
			'value' => set_value('email', empty($user->email) ? '' : $user->email),
		];
		$data['password'] = [
			'name' => 'password',
			'id'   => 'password',
			'type' => 'password',
		];
		$data['passwordConfirm'] = [
			'name' => 'password_confirm',
			'id'   => 'password_confirm',
			'type' => 'password',
		];
		$data['ionAuth']         = $this->ionAuth;

		$body = view('Admin\users\edit_user', $data);
		return $this->view($body, lang('Auth.edit_user_heading'), 'users');
	}

	/**
	 * Activate the user
	 *
	 * @param integer $id The user ID
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse
	 */
	public function activate(int $id): \CodeIgniter\HTTP\RedirectResponse
	{	
		if (!$this->ionAuth->isAdmin()) {
			return redirect()->to(base_url('/admin'));
		}
		$this->ionAuth->activate($id);
		session()->setFlashdata('message', $this->ionAuth->messages());
		return redirect()->to(base_url('/admin/users'));
	}

	/**
	 * Delete the user
	 *
	 * @param integer $id The user ID
	 *
	 * @throw Exception
	 *
	 * @return string|\CodeIgniter\HTTP\RedirectResponse
	 */
	public function delete(int $id = 0)
	{
		if (! $this->isAuthorized() OR !$this->ionAuth->isAdmin())
		{
			// redirect them to the home page because they must be an administrator to view this
			throw new \Exception('You must be an administrator to view this page.');
		}

		$validation = \Config\Services::validation();

		$validation->setRule('confirm', 'confirm', 'required');
		$validation->setRule('id', lang('Auth.deactivate_validation_user_id_label'), 'required|integer');

		if (! $validation->withRequest($this->request)->run())
		{
			helper(['form']);
			$data['user'] = $this->ionAuth->user($id)->row();
			$body         = view('Admin\users\delete_user', $data);
			return $this->view($body, lang('Auth.deactivate_heading'), 'users');
		}
		else
		{
			// do we really want to deactivate?
			if ($this->request->getPost('confirm') === 'yes')
			{
				// do we have a valid request?
				if ($id !== $this->request->getPost('id', FILTER_VALIDATE_INT))
				{
					throw new \Exception(lang('Auth.error_security'));
				}

				// do we have the right userlevel?
				if ($this->ionAuth->loggedIn() && $this->ionAuth->isAdmin())
				{
					
					if ($this->UsersModel->delete($id)) {
						$message = $this->ionAuth->deleteUser($id) ? $this->ionAuth->messages() : $this->ionAuth->errors();
					} else {
						$message = "Нельзя удалить единственного администратора!";
					}

					session()->setFlashdata('message', $message);
				}
			}

			// redirect them back to the auth page
			return redirect()->to(base_url('/admin/users'));
		}
	}

	/**
	 * Deactivate the user
	 *
	 * @param integer $id The user ID
	 *
	 * @throw Exception
	 *
	 * @return string|\CodeIgniter\HTTP\RedirectResponse
	 */
	public function deactivate(int $id = 0)
	{
		if (! $this->isAuthorized() OR !$this->ionAuth->isAdmin())
		{
			// redirect them to the home page because they must be an administrator to view this
			throw new \Exception('You must be an administrator to view this page.');
		}
		
		$validation = \Config\Services::validation();

		$validation->setRule('confirm', lang('Auth.deactivate_validation_confirm_label'), 'required');
		$validation->setRule('id', lang('Auth.deactivate_validation_user_id_label'), 'required|integer');

		if (! $validation->withRequest($this->request)->run())
		{
			helper(['form']);
			$data['user'] = $this->ionAuth->user($id)->row();
			$body         = view('Admin\users\deactivate_user', $data);
			return $this->view($body, lang('Auth.deactivate_heading'), 'users');
		}
		else
		{
			// do we really want to deactivate?
			if ($this->request->getPost('confirm') === 'yes')
			{
				// do we have a valid request?
				if ($id !== $this->request->getPost('id', FILTER_VALIDATE_INT))
				{
					throw new \Exception(lang('Auth.error_security'));
				}

				// do we have the right userlevel?
				if ($this->ionAuth->loggedIn() && $this->ionAuth->isAdmin())
				{
					$message = $this->ionAuth->deactivate($id) ? $this->ionAuth->messages() : $this->ionAuth->errors();
					session()->setFlashdata('message', $message);
				}
			}

			// redirect them back to the auth page
			return redirect()->to(base_url('/admin/users'));
		}
	}

	/**
	 * Edit a group
	 *
	 * @param integer $id Group id
	 *
	 * @return string|CodeIgniter\Http\Response
	 */
	public function editGroup(int $id = 0)
	{
		// bail if no group id given
		if (! $this->isAuthorized() || ! $id)
		{
			return redirect()->to(base_url('/admin/users'));
		}

		$validation = \Config\Services::validation();

		$data['title'] = lang('Auth.edit_group_title');

		$group = $this->ionAuth->group($id)->row();

		// validate form input
		$validation->setRule('group_name', lang('Auth.edit_group_validation_name_label'), 'required|alpha_dash');

		if ($this->request->getPost())
		{
			if ($validation->withRequest($this->request)->run())
			{
				$groupUpdate = $this->ionAuth->updateGroup(
					$id,
					$this->request->getPost('group_name'),
					['description' => $this->request->getPost('group_description')]);

				if ($groupUpdate)
				{
					session()->setFlashdata('message', lang('Auth.edit_group_saved'));
				}
				else
				{
					session()->setFlashdata('message', $this->ionAuth->errors());
				}
				return redirect()->to(base_url('/admin/users'));
			}
		}

		helper(['form']);

		// set the flash data error message if there is one
		$data['message'] = $validation->listErrors() ?: ($this->ionAuth->errors() ?: session()->getFlashdata('message'));

		// pass the user to the view
		$data['group'] = $group;

		$readonly = $this->configIonAuth->adminGroup === $group->name ? 'readonly' : '';

		$data['groupName']        = [
			'name'    => 'group_name',
			'id'      => 'group_name',
			'type'    => 'text',
			'value'   => set_value('group_name', $group->name),
			$readonly => $readonly,
		];
		$data['groupDescription'] = [
			'name'  => 'group_description',
			'id'    => 'group_description',
			'type'  => 'text',
			'value' => set_value('group_description', $group->description),
		];

		$body = view('Admin\users\edit_group', $data);
		return $this->view($body, lang('Auth.edit_group_title'), 'users');
	}

	/**
	 * Create a new group
	 *
	 * @return string string|\CodeIgniter\HTTP\RedirectResponse
	 */
	public function createGroup()
	{
		if (! $this->isAuthorized())
		{
			return redirect()->to(base_url('/auth'));
		}

		$data['title'] = lang('Auth.create_group_title');

		$validation = \Config\Services::validation();

		// validate form input
		$validation->setRule('group_name', lang('Auth.create_group_validation_name_label'), 'trim|required|alpha_dash');

		if ($this->request->getPost() && $validation->withRequest($this->request)->run())
		{
			$newGroupId = $this->ionAuth->createGroup($this->request->getPost('group_name'), $this->request->getPost('description'));
			if ($newGroupId)
			{
				// check to see if we are creating the group
				// redirect them back to the admin page
				session()->setFlashdata('message', $this->ionAuth->messages());
				return redirect()->to(base_url('/admin/users'));
			}
		}
		else
		{
			// display the create group form
			helper(['form']);
			// set the flash data error message if there is one
			$data['message'] = $validation->getErrors() ? $validation->listErrors() : ($this->ionAuth->errors() ? $this->ionAuth->errors() : session()->getFlashdata('message'));

			$data['groupName']   = [
				'name'  => 'group_name',
				'id'    => 'group_name',
				'type'  => 'text',
				'value' => set_value('group_name'),
			];
			$data['description'] = [
				'name'  => 'description',
				'id'    => 'description',
				'type'  => 'text',
				'value' => set_value('description'),
			];

			$body = view('Admin\users\create_group', $data);
			return $this->view($body, lang('Auth.create_group_title'), 'users');
		}
	}
}
