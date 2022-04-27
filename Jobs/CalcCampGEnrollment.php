<?php

namespace App\Jobs;

use App\Jobs\Job;

use App\Gamba\gambaLogs;
use App\Gamba\gambaCalc;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CalcCampGEnrollment extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    
    protected $term;
    protected $grade;
    protected $camp;

    /**
     * Create a new job instance.
     *
     * @return void
     */
	public function __construct($term, $grade, $camp)
    {
        $this->term = $term;
        $this->grade = $grade;
        $this->camp = $camp;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        gambaLogs::truncate_log('enroll_calc.log');
		gambaLogs::data_log("Action: Exec() Calc CG Enrollment | Term: {$this->term} | Grade: {$this->grade} | Camp: {$this->camp}", 'enroll_calc.log');
		
		gambaCalc::calculate_from_cg_enrollment($this->term, $this->grade, $this->camp);
    }
}
