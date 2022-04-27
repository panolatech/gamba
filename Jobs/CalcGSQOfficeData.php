<?php

namespace App\Jobs;

use App\Jobs\Job;

use App\Gamba\gambaEnroll;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CalcGSQOfficeData extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    
    protected $sheet_id;
    protected $term;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($sheet_id, $term)
    {
        $this->sheet_id = $sheet_id;
        $this->term = $term;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        gambaEnroll::gsq_sumofficedata($this->sheet_id, $this->term);
    }
}
