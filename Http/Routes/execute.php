<?php

	// Timer
	Route::get('timer/fbsync', function() {
		//exec(php_path . " " . Site_path . "execute_php sync all > /dev/null &");
		$job = (new App\Jobs\SyncAll())->onQueue('sync');
		$this->dispatch($job);
	});