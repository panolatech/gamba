<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Gamba\gambaTerm;

class SettingsTermsController extends Controller
{

	public function index(Request $array)
	{
		$content = gambaTerm::view_terms($array['r']);
		return view('app.settings.home', ['content' => $content]);
	}

	public function updateYears(Request $array)
	{
		$url = url('/');
		$result = gambaTerm::year_update($array);
		return redirect("{$url}/settings/terms?r=$result");
	}

	public function deleteYear(Request $array)
	{
		$url = url('/');
		$result = gambaTerm::year_delete($array);
		return redirect("{$url}/settings/terms?r=$result");
	}
}
