<?php namespace Config;

require_once SYSTEMPATH . 'Config/AutoloadConfig.php';

/**
 * -------------------------------------------------------------------
 * AUTO-LOADER
 * -------------------------------------------------------------------
 * This file defines the namespaces and class maps so the Autoloader
 * can find the files as needed.
 */
class Autoload extends \CodeIgniter\Config\AutoloadConfig
{
	public $psr4 = [];

	public $classmap = [];

	//--------------------------------------------------------------------

	/**
	 * Collects the application-specific autoload settings and merges
	 * them with the framework's required settings.
	 *
	 * NOTE: If you use an identical key in $psr4 or $classmap, then
	 * the values in this file will overwrite the framework's values.
	 */
	public function __construct()
	{
		parent::__construct();

		/**
		 * -------------------------------------------------------------------
		 * Namespaces
		 * -------------------------------------------------------------------
		 * This maps the locations of any namespaces in your application
		 * to their location on the file system. These are used by the
		 * Autoloader to locate files the first time they have been instantiated.
		 *
		 * The '/app' and '/system' directories are already mapped for
		 * you. You may change the name of the 'App' namespace if you wish,
		 * but this should be done prior to creating any namespaced classes,
		 * else you will need to modify all of those classes for this to work.
		 *
		 * DO NOT change the name of the CodeIgniter namespace or your application
		 * WILL break. *
		 * Prototype:
		 *
		 *   $Config['psr4'] = [
		 *       'CodeIgniter' => SYSPATH
		 *   `];
		 */
		$psr4 = [
			'App'         => APPPATH,                // To ensure filters, etc still found,
			APP_NAMESPACE => APPPATH,                // For custom namespace
			'Config'      => APPPATH . 'Config',
			'IonAuth' 	  => APPPATH . 'ThirdParty/Ion-auth',
			'Admin'   	  => APPPATH . 'ThirdParty/panel',
			'Telegram'    => APPPATH . 'ThirdParty/telegram',
			'Language'    => APPPATH . 'ThirdParty/language',
			'Aff'    	  => APPPATH . 'ThirdParty/aff',
			'Balance'     => APPPATH . 'ThirdParty/balance',
			'Btc'     	  => APPPATH . 'ThirdParty/btc',
			'Products'    => APPPATH . 'ThirdParty/products',
			'Orders'      => APPPATH . 'ThirdParty/orders',
			'Pays'        => APPPATH . 'ThirdParty/pays',
			'Sender'      => APPPATH . 'ThirdParty/sender',
			'Yandex'      => APPPATH . 'ThirdParty/yandex',
			'Qiwi'        => APPPATH . 'ThirdParty/qiwi',
			'Notify'      => APPPATH . 'ThirdParty/notify',
			'Course'      => APPPATH . 'ThirdParty/courses',
			'Blockio'     => APPPATH . 'ThirdParty/blockio',
			'Promo'       => APPPATH . 'ThirdParty/promo',
			'Freekassa'   => APPPATH . 'ThirdParty/freekassa',
			'Mods'        => APPPATH . 'ThirdParty/mods',
			'Yookassa'    => APPPATH . 'ThirdParty/yookassa',
			'Stat'        => APPPATH . 'ThirdParty/stat',
			'Wayforpay'   => APPPATH . 'ThirdParty/wayforpay',
			'Yandexgeo'   => APPPATH . 'ThirdParty/yandexgeo',
			'Tronapi' 		=> APPPATH . 'ThirdParty/tronapi',
		];

		/**
		 * -------------------------------------------------------------------
		 * Class Map
		 * -------------------------------------------------------------------
		 * The class map provides a map of class names and their exact
		 * location on the drive. Classes loaded in this manner will have
		 * slightly faster performance because they will not have to be
		 * searched for within one or more directories as they would if they
		 * were being autoloaded through a namespace.
		 *
		 * Prototype:
		 *
		 *   $Config['classmap'] = [
		 *       'MyClass'   => '/path/to/class/file.php'
		 *   ];
		 */
		$classmap = [];

		//--------------------------------------------------------------------
		// Do Not Edit Below This Line
		//--------------------------------------------------------------------

		$this->psr4     = array_merge($this->psr4, $psr4);
		$this->classmap = array_merge($this->classmap, $classmap);

		unset($psr4, $classmap);
	}

	//--------------------------------------------------------------------

}
