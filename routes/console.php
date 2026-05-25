<?php

use Illuminate\Support\Facades\Schedule;
use App\Models\Consultation;
use App\Models\ExpertProfile;

// Auto end consultation ketika waktu sesi habis
Schedule::call(function () {
    $expiredConsultations = Consultation::where('status', 'active')
        ->where('scheduled_end_at', '<=', now())
        ->get();

    foreach ($expiredConsultations as $consultation) {
        $consultation->update([
            'status'   => 'completed',
            'ended_at' => now(),
        ]);

        ExpertProfile::where('user_id', $consultation->expert_id)
            ->increment('total_consultations');
    }
})->everyMinute();