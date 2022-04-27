<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Session;

use App\Http\Requests;

use App\Gamba\gambaFishbowl;

use App\Jobs\SyncAll;
use App\Jobs\SyncCustomers;
use App\Jobs\SyncProducts;
use App\Jobs\SyncParts;
use App\Jobs\SyncVendors;
use App\Jobs\SyncUoMs;
use App\Jobs\SyncVendorParts;

class SyncController extends Controller
{

	public function all() {
		$job = (new SyncAll())->onQueue('sync');
		$this->dispatch($job);
		return redirect("settings/fishbowl?view=all&alert=1");
	}

	public function parts() {
	    $job = (new SyncParts())->onQueue('sync');
	    $this->dispatch($job);
	    return redirect("settings/fishbowl?view=parts&alert=1");
	}

	public function uoms() {
	    $job = (new SyncUoMs())->onQueue('sync');
	    $this->dispatch($job);
	    return redirect("settings/fishbowl?view=uoms&alert=1");
	}

	public function vendors() {
	    $job = (new SyncVendors())->onQueue('sync');
	    $this->dispatch($job);
	    return redirect("settings/fishbowl?view=vendors&alert=1");
	}

	public function customers() {
		gambaFishbowl::truncate_log('customer.log');
		$job = (new SyncCustomers())->onQueue('sync');
		$this->dispatch($job);
		return redirect("settings/fishbowl?view=customers&alert=1");
	}

	public function products() {
	    gambaFishbowl::truncate_log('customer.log');
	    $job = (new SyncProducts())->onQueue('sync');
	    $this->dispatch($job);
	    return redirect("settings/fishbowl?view=products&alert=1");
	}

	public function vendorparts() {
		$job = (new SyncVendorParts())->onQueue('sync');
		$this->dispatch($job);
		return redirect("settings/fishbowl?view=vendorparts&alert=1");
	}

}
