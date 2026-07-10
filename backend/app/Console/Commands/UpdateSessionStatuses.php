<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ActivitySession;

class UpdateSessionStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-session-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    /*  public function handle()
    {
        $now = now();

        // 1. تحويل المقبولة لـ جارية (كما هي)
        ActivitySession::where('status', 'approved')
            ->where('date_time', '<=', $now)
            ->update(['status' => 'ongoing']);

        // العملية هنا: إذا كان (الآن) أكبر من أو يساوي (وقت البدء + المدة)
        ActivitySession::where('status', 'ongoing')
            ->whereRaw("DATE_ADD(date_time, INTERVAL duration MINUTE) <= ?", [$now])
            ->update(['status' => 'completed']);

        $this->info('تم تحديث الحالات بدقة بناءً على مدة كل جلسة.');
    }*/
    public function handle()
    {
        $now = now();

        // تحويل الجلسة من approved إلى ongoing أثناء وقتها
        ActivitySession::where('status', 'approved')
            ->where('date_time', '<=', $now)
            ->whereRaw("DATE_ADD(date_time, INTERVAL duration MINUTE) > ?", [$now])
            ->update([
                'status' => 'ongoing'
            ]);


        // تحويل الجلسة إلى completed بعد انتهاء المدة
        ActivitySession::whereIn('status', ['approved', 'ongoing'])
            ->whereRaw("DATE_ADD(date_time, INTERVAL duration MINUTE) <= ?", [$now])
            ->update([
                'status' => 'completed'
            ]);


        $this->info('تم تحديث حالات الجلسات.');
    }
}
