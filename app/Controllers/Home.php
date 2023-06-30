<?php namespace App\Controllers;

class Home extends BaseController
{
	public function index()
	{	
		//если не нужно на главной заглушку
		return redirect()->to(base_url('/admin'));
		// return view('welcome_message');
	}

	//--------------------------------------------------------------------

}
