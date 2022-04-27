<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Gamba\gambaLogs;

class LogsController extends Controller
{
    public function enroll_calc_log(Request $array) {
    	gambaLogs::enroll_calc_log($array);
	}
}
