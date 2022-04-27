<?php

namespace App\Jobs;

use App\Jobs\Job;

use App\Gamba\gambaLogs;

use App\Models\Parts;
use App\Models\ViewPartCosts;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class JoinPartCosts extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {
        gambaLogs::truncate_log('joinpartcosts.log');
        $parts = ViewPartCosts::where('part_desc', '!=', "")->get();
        $i = 0;
        foreach($parts as $key => $value) {
            if($value['cost1'] != "0.00" && $value['cost1'] != "") {
                $update = Parts::where('number', $value['part_num'])->update(['fbcost' => $value['cost1'], 'updated' => date("Y-m-d H:i:s")]);
                gambaLogs::data_log("Join: {$value['part_num']} | {$value['cost']} to {$value['cost1']}", 'joinpartcosts.log');
                $i++;
            }
        }
        gambaLogs::data_log("{$i} Parts Joined.", 'joinpartcosts.log');
    }
}
