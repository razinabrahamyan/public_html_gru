<?php namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes(true);

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php'))
{
	require SYSTEMPATH . 'Config/Routes.php';
}

/**
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

/**
 * --------------------------------------------------------------------
 * Route Definitions
 * @docs https://codeigniter4.github.io/CodeIgniter4/incoming/routing.html
 * --------------------------------------------------------------------
 */


//дополнительные модули
$modules = [
	'panel',
	'sender', 
	'pays',
	'orders', 
	'products', 
	'balance',
	'mods',
	'yookassa',
	'promo',
	'wayforpay',
	'tronapi',
	'btc'
];

foreach ($modules as $modul) {
	if (file_exists(APPPATH . 'ThirdParty/'.$modul.'/Config/Routes.php')) {
		require_once APPPATH . 'ThirdParty/'.$modul.'/Config/Routes.php';
	}
}

/**
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need to it be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php'))
{
	require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
