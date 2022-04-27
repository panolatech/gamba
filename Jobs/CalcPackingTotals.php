<?php

namespace App\Jobs;

use App\Jobs\Job;

use App\Gamba\gambaPacking;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CalcPackingTotals extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;
    
    protected $term;
    protected $packing_id;
    protected $qtshort;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($term, $packing_id, $qtshort)
    {
        $this->term = $term;
        $this->packing_id = $packing_id;
        $this->qtshort = $qtshort;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        gambaPacking::packing_totals_calc_all($this->term, $this->packing_id, $this->qtshort);
    }
}
