<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('app_settings')->where('key', 'courriers.sla')->exists();

        if ($exists) {
            return;
        }

        DB::table('app_settings')->insert([
            'key' => 'courriers.sla',
            'value' => json_encode([
                'task_reminder_days_before' => (array) config('courriers.sla.task_reminder_days_before', [3, 1, 0]),
                'imputation_reminder_days_before' => (array) config('courriers.sla.imputation_reminder_days_before', [3, 1, 0]),
                'task_escalation_after_overdue_days' => (int) config('courriers.sla.task_escalation_after_overdue_days', 2),
                'imputation_escalation_after_overdue_days' => (int) config('courriers.sla.imputation_escalation_after_overdue_days', 1),
                'enable_task_escalation' => (bool) config('courriers.sla.enable_task_escalation', true),
                'enable_imputation_escalation' => (bool) config('courriers.sla.enable_imputation_escalation', true),
                'send_overdue_daily' => (bool) config('courriers.sla.send_overdue_daily', true),
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('app_settings')->where('key', 'courriers.sla')->delete();
    }
};
