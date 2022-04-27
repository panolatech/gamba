<?php

namespace App\Jobs;

use App\Jobs\Job;

use App\Gamba\gambaCalc;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CalcBasicSupplies extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    
    protected $term;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($term)
    {
        $this->term = $term;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        gambaCalc::basic_supplies_subtraction($this->term);
    }
}
