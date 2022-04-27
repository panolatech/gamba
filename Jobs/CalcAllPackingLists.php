<?php

namespace App\Jobs;

use App\Jobs\Job;

use App\Gamba\gambaCalc;
use App\Gamba\gambaLogs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CalcAllPackingLists extends Job implements ShouldQueue {
	
    use InteractsWithQueue, SerializesModels;
    
    protected $term;
    protected $camp;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($term, $camp)
    {
        $this->term = $term;
        $this->camp = $camp;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        gambaCalc::calculate_all($this->term, $this->camp);
//     	gambaLogs::truncate_log('enroll_calc.log');
//     	gambaLogs::data_log("I did it.", 'enroll_calc.log');
    }
    
}
