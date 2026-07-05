<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateExpiredCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-expired-campaigns';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // البحث عن الحملات التي انتهى تاريخها (أصغر من اليوم) وما زالت active
        $count = \App\Models\DonationCampaign::where('end_date', '<', now())
            ->where('status', 'active')
            ->update(['status' => 'completed']);

        $this->info("تم تحديث $count حملة إلى حالة مكتملة.");
    }
}
