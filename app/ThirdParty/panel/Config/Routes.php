<?php namespace Config;
$routes = Services::routes(true);

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Home::index');

//для системы авторизации и регистрации
$routes->group('auth', ['namespace' => 'IonAuth\Controllers'], function ($routes) {
	$routes->get('/', 'Auth::index');
	$routes->add('login', 'Auth::login');
	$routes->get('logout', 'Auth::logout');
	$routes->add('forgot_password', 'Auth::forgot_password');
	$routes->add('reset_password/(:any)', 'Auth::reset_password/$1');
});

//админка
$routes->group('admin', ['namespace' => 'Admin\Controllers'], function ($routes) {
	$routes->add('/', 'Home::index');
	$routes->add('add_item', 'Home::add_item');
	$routes->add('reset', 'Home::reset');
	$routes->add('update', 'Home::update');

	$routes->group('settings', ['namespace' => 'Admin\Controllers'], function ($routes) {
		$routes->add('/', 'Settings::index');
	});

	//управление страницами
	$routes->group('pages', ['namespace' => 'Admin\Controllers'], function ($routes) {
		$routes->get('/', 'Pages::index');
		$routes->add('edit/(:num)', 'Pages::edit/$1');
		$routes->add('edit/(:num)/(:alpha)', 'Pages::edit/$1/$2');
		$routes->add('save_/(:num)/(:alpha)', 'Pages::save_/$1/$2');
		$routes->add('disable_web_page_preview/(:num)', 'Pages::disable_web_page_preview/$1');
		$routes->add('file_id/(:num)', 'Pages::file_id/$1');
	});

	//управление кнопками
	$routes->group('buttons', ['namespace' => 'Admin\Controllers'], function ($routes) {
		$routes->get('/', 'Buttons::index');
		$routes->add('edit/(:num)', 'Buttons::edit/$1');
	});

	//управление пользователями
	$routes->group('users', ['namespace' => 'Admin\Controllers'], function ($routes) {
		$routes->add('/', 'Users::index');
		$routes->add('/(:any)', 'Users::index/$1');
		$routes->post('checkbox_', 'Users::checkbox_');
		$routes->get('export', 'Users::export');
		$routes->add('create', 'Users::createUser');
		$routes->add('edit/(:num)', 'Users::edit/$1');
		$routes->add('activate/(:num)', 'Users::activate/$1');
		$routes->add('deactivate/(:num)', 'Users::deactivate/$1');
		$routes->add('delete/(:num)', 'Users::delete/$1');
		$routes->add('edit_group/(:num)', 'Users::editGroup/$1');
		$routes->add('create_group', 'Users::createGroup');
		$routes->post('users_', 'Users::users_');
		$routes->add('stat/(:any)', 'Users::stat/$1');
		$routes->add('/(:any)', 'Users::index/$1');
		$routes->get('reset', 'Users::reset');

	});

	//информация
	$routes->group('informations', ['namespace' => 'Admin\Controllers'], function ($routes) {
		$routes->get('/', 'Informations::index');
		$routes->get('displayPhpInfo', 'Informations::displayPhpInfo');
		$routes->add('exportDatabase', 'Informations::exportDatabase');
		$routes->post('sendEmailForTest', 'Informations::sendEmailForTest');
	});

});