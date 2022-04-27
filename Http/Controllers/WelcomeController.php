<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;

class WelcomeController extends Controller
{

    /**
     * 
     */
    public function index()
    {
		$hide_login_checkbox = "";
	    return view('app.index', ['hide_login_checkbox' => ""]);
//         return view('welcome');
    }
}
