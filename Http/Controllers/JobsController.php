<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Jobs\ExportParts;
use App\Jobs\ExportProducts;

class JobsController extends Controller
{

    public function exportParts(Request $array) {
    	$url = url('/');
    	$job = (new ExportParts())->onQueue('export');
    	$this->dispatch($job);
		return redirect("{$url}/parts/parts_log");
    }

    public function exportProducts(Request $array) {
    	$url = url('/');
    	$job = (new ExportProducts())->onQueue('export');
    	$this->dispatch($job);
		return redirect("{$url}/parts/products_log");
    }
}
