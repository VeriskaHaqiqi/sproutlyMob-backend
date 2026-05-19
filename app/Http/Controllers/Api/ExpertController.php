<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpertProfile;
use App\Models\ExpertSchedule;
use App\Models\Specialization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpertController extends Controller
{
    // ==================== GET ALL EXPERTS ====================
    public function index(Request $request)
    {
        $query = User::with(['expertProfile', 'specializations'])
            ->where('role', 'expert');

        // Filter available now
        if ($request->available_now == 'true') {
            $query->whereHas('expertProfile', function($q) {
                $q->where('availability_status', 'available');
            });
        }

        // Filter top rated
        if ($request->top_rated == 'true') {
            $query->whereHas('expertProfile', function($q) {
                $q->orderBy('average_rating', 'desc');
            });
        }

        // Search by name or specialization
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhereHas('specializations', function($q) use ($request) {
                      $q->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        $experts = $query->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $experts,
        ]);
    }

    // ==================== GET SINGLE EXPERT ====================
    public function show($id)
    {
        $expert = User::with([
            'expertProfile',
            'specializations',
            'schedules',
            'ratingsReceived.user',
        ])->where('role', 'expert')->find($id);

        if (!$expert) {
            return response()->json([
                'success' => false,
                'message' => 'Expert not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $expert,
        ]);
    }

    // ==================== UPDATE EXPERT PROFILE ====================
    public function updateProfile(Request $request)
    {
        if ($request->user()->role !== 'expert') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'university'          => 'nullable|string|max:150',
            'years_of_experience' => 'nullable|integer|min:0',
            'description'         => 'nullable|string',
            'bank_name'           => 'nullable|string|max:100',
            'account_holder'      => 'nullable|string|max:100',
            'account_number'      => 'nullable|string|max:50',
            'session_fee'         => 'nullable|numeric|min:0',
            'session_duration'    => 'nullable|integer|min:15',
            'instant_booking'     => 'nullable|boolean',
            'availability_status' => 'nullable|in:available,unavailable',
        ]);

        $profile = ExpertProfile::where('user_id', $request->user()->id)->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Expert profile not found',
            ], 404);
        }

        $profile->update($request->only([
            'university',
            'years_of_experience',
            'description',
            'bank_name',
            'account_holder',
            'account_number',
            'session_fee',
            'session_duration',
            'instant_booking',
            'availability_status',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Expert profile updated successfully',
            'data'    => $profile,
        ]);
    }

    // ==================== UPDATE AVAILABILITY STATUS ====================
    public function updateAvailability(Request $request)
    {
        if ($request->user()->role !== 'expert') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'availability_status' => 'required|in:available,unavailable',
        ]);

        $profile = ExpertProfile::where('user_id', $request->user()->id)->first();
        $profile->update(['availability_status' => $request->availability_status]);

        return response()->json([
            'success' => true,
            'message' => 'Availability updated',
            'data'    => $profile,
        ]);
    }

    // ==================== MANAGE SCHEDULES ====================
    public function getSchedules(Request $request)
    {
        if ($request->user()->role !== 'expert') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $schedules = ExpertSchedule::where('expert_id', $request->user()->id)
            ->orderByRaw("FIELD(day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')")
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $schedules,
        ]);
    }

    public function saveSchedules(Request $request)
    {
        if ($request->user()->role !== 'expert') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'schedules'              => 'required|array',
            'schedules.*.day'        => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time'   => 'required|date_format:H:i|after:schedules.*.start_time',
            'schedules.*.is_active'  => 'nullable|boolean',
        ]);

        // Hapus jadwal lama, ganti dengan yang baru
        ExpertSchedule::where('expert_id', $request->user()->id)->delete();

        $schedules = collect($request->schedules)->map(function($schedule) use ($request) {
            return [
                'expert_id'  => $request->user()->id,
                'day'        => $schedule['day'],
                'start_time' => $schedule['start_time'],
                'end_time'   => $schedule['end_time'],
                'is_active'  => $schedule['is_active'] ?? true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        ExpertSchedule::insert($schedules->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Schedules saved successfully',
            'data'    => ExpertSchedule::where('expert_id', $request->user()->id)->get(),
        ]);
    }

    // ==================== MANAGE SPECIALIZATIONS ====================
    public function getSpecializations(Request $request)
    {
        $specializations = Specialization::where('expert_id', $request->user()->id)->get();

        return response()->json([
            'success' => true,
            'data'    => $specializations,
        ]);
    }

    public function saveSpecializations(Request $request)
    {
        if ($request->user()->role !== 'expert') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'specializations'   => 'required|array',
            'specializations.*' => 'required|string|max:100',
        ]);

        // Hapus spesialisasi lama, ganti baru
        Specialization::where('expert_id', $request->user()->id)->delete();

        $specializations = collect($request->specializations)->map(function($name) use ($request) {
            return [
                'expert_id'  => $request->user()->id,
                'name'       => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        Specialization::insert($specializations->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Specializations saved successfully',
            'data'    => Specialization::where('expert_id', $request->user()->id)->get(),
        ]);
    }

    // ==================== SET SESSION FEE ====================
    public function setFee(Request $request)
    {
        if ($request->user()->role !== 'expert') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'session_fee'      => 'required|numeric|min:0',
            'session_duration' => 'nullable|integer|min:15',
            'instant_booking'  => 'nullable|boolean',
        ]);

        $profile = ExpertProfile::where('user_id', $request->user()->id)->first();
        $profile->update([
            'session_fee'      => $request->session_fee,
            'session_duration' => $request->session_duration ?? $profile->session_duration,
            'instant_booking'  => $request->instant_booking ?? $profile->instant_booking,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Session fee updated successfully',
            'data'    => $profile,
        ]);
    }

    // ==================== GET EXPERT INCOME HISTORY ====================
    public function incomeHistory(Request $request)
    {
        if ($request->user()->role !== 'expert') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $consultations = \App\Models\Consultation::with(['user', 'payment'])
            ->where('expert_id', $request->user()->id)
            ->where('status', 'completed')
            ->latest()
            ->paginate(10);

        $totalIncome = \App\Models\Consultation::where('expert_id', $request->user()->id)
            ->where('status', 'completed')
            ->whereHas('payment', function($q) {
                $q->where('status', 'verified');
            })
            ->sum('fee');

        return response()->json([
            'success' => true,
            'data'    => [
                'total_income'    => $totalIncome,
                'consultations'   => $consultations,
            ],
        ]);
    }
}