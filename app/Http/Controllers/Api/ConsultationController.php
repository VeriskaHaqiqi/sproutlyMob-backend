<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\ExpertProfile;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConsultationController extends Controller
{
    // ==================== CREATE CONSULTATION (USER) ====================
    public function store(Request $request)
    {
        if ($request->user()->role !== 'user') {
            return response()->json([
                'success' => false,
                'message' => 'Only users can create consultations',
            ], 403);
        }

        $request->validate([
            'expert_id' => 'required|exists:users,id',
            'topic'     => 'nullable|string|max:200',
        ]);

        // Ambil session fee dari expert profile
        $expertProfile = ExpertProfile::where('user_id', $request->expert_id)->first();

        if (!$expertProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Expert profile not found',
            ], 404);
        }

        $platformFee = 3000;
        $totalAmount = $expertProfile->session_fee + $platformFee;

        // Buat konsultasi
        $consultation = Consultation::create([
            'user_id'   => $request->user()->id,
            'expert_id' => $request->expert_id,
            'topic'     => $request->topic,
            'fee'       => $expertProfile->session_fee,
            'status'    => 'waiting_payment',
        ]);

        // Buat payment record
        $payment = Payment::create([
            'consultation_id' => $consultation->id,
            'amount'          => $expertProfile->session_fee,
            'platform_fee'    => $platformFee,
            'total_amount'    => $totalAmount,
            'payment_method'  => 'bank_transfer',
            'status'          => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Consultation created successfully',
            'data'    => [
                'consultation' => $consultation->load(['expert', 'user']),
                'payment'      => $payment,
                'expert_bank'  => [
                    'bank_name'      => $expertProfile->bank_name,
                    'account_holder' => $expertProfile->account_holder,
                    'account_number' => $expertProfile->account_number,
                ],
            ],
        ], 201);
    }

    // ==================== UPLOAD PAYMENT PROOF (USER) ====================
    public function uploadPaymentProof(Request $request, $consultationId)
    {
        $request->validate([
            'payment_proof' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $consultation = Consultation::where('id', $consultationId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$consultation) {
            return response()->json([
                'success' => false,
                'message' => 'Consultation not found',
            ], 404);
        }

        if ($consultation->status !== 'waiting_payment') {
            return response()->json([
                'success' => false,
                'message' => 'Payment already submitted',
            ], 400);
        }

        // Upload bukti pembayaran
        $path = $request->file('payment_proof')
            ->store('payments/proofs', 'public');

        // Update payment
        $payment = Payment::where('consultation_id', $consultationId)->first();
        $payment->update([
            'payment_proof' => $path,
            'status'        => 'pending',
        ]);

        // Update status konsultasi
        $consultation->update(['status' => 'waiting_verification']);

        return response()->json([
            'success' => true,
            'message' => 'Payment proof uploaded successfully',
            'data'    => [
                'consultation' => $consultation,
                'payment'      => $payment,
            ],
        ]);
    }

    // ==================== VERIFY PAYMENT (EXPERT) ====================
    public function verifyPayment(Request $request, $consultationId)
    {
        if ($request->user()->role !== 'expert') {
            return response()->json([
                'success' => false,
                'message' => 'Only experts can verify payments',
            ], 403);
        }

        $request->validate([
            'action'         => 'required|in:verify,reject',
            'rejection_note' => 'nullable|string',
        ]);

        $consultation = Consultation::where('id', $consultationId)
            ->where('expert_id', $request->user()->id)
            ->first();

        if (!$consultation) {
            return response()->json([
                'success' => false,
                'message' => 'Consultation not found',
            ], 404);
        }

        if ($consultation->status !== 'waiting_verification') {
            return response()->json([
                'success' => false,
                'message' => 'Consultation is not waiting for verification',
            ], 400);
        }

        $payment = Payment::where('consultation_id', $consultationId)->first();

        if ($request->action === 'verify') {

            $payment->update(['status' => 'verified']);

            // Ambil durasi sesi dari expert profile
            $expertProfile = ExpertProfile::where('user_id', $consultation->expert_id)->first();
            $sessionDuration = $expertProfile->session_duration ?? 60;

            $consultation->update([
                'status'           => 'active',
                'started_at'       => now(),
                'scheduled_end_at' => now()->addMinutes($sessionDuration),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment verified, consultation is now active',
                'data'    => $consultation->load(['user', 'expert', 'payment']),
            ]);

        } else {

            $payment->update([
                'status'         => 'rejected',
                'rejection_note' => $request->rejection_note,
            ]);
            $consultation->update(['status' => 'rejected']);

            return response()->json([
                'success' => true,
                'message' => 'Payment rejected',
                'data'    => $consultation->load(['user', 'expert', 'payment']),
            ]);
        }
    }

    // ==================== END CONSULTATION (EXPERT) ====================
    public function endConsultation(Request $request, $consultationId)
    {
        if ($request->user()->role !== 'expert') {
            return response()->json([
                'success' => false,
                'message' => 'Only experts can end consultations',
            ], 403);
        }

        $consultation = Consultation::where('id', $consultationId)
            ->where('expert_id', $request->user()->id)
            ->first();

        if (!$consultation) {
            return response()->json([
                'success' => false,
                'message' => 'Consultation not found',
            ], 404);
        }

        if ($consultation->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Consultation is not active',
            ], 400);
        }

        $consultation->update([
            'status'   => 'completed',
            'ended_at' => now(),
        ]);

        // Update total konsultasi expert
        $expertProfile = ExpertProfile::where('user_id', $request->user()->id)->first();
        $expertProfile->increment('total_consultations');

        // Tentukan cara end consultation
        $endedBy = 'expert';
        if ($consultation->scheduled_end_at && now()->gte($consultation->scheduled_end_at)) {
            $endedBy = 'system';
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Consultation ended successfully',
            'ended_by' => $endedBy,
            'data'     => $consultation->load(['user', 'expert', 'payment']),
        ]);
    }

    // ==================== GET USER CONSULTATIONS ====================
    public function userConsultations(Request $request)
    {
        $status = $request->status;

        $query = Consultation::with(['expert', 'expert.expertProfile', 'payment'])
            ->where('user_id', $request->user()->id);

        if ($status) {
            $query->where('status', $status);
        }

        $consultations = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $consultations,
        ]);
    }

    // ==================== GET EXPERT CONSULTATIONS ====================
    public function expertConsultations(Request $request)
    {
        if ($request->user()->role !== 'expert') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $status = $request->status;

        $query = Consultation::with(['user', 'payment'])
            ->where('expert_id', $request->user()->id);

        if ($status) {
            $query->where('status', $status);
        }

        $consultations = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $consultations,
        ]);
    }

    // ==================== GET SINGLE CONSULTATION ====================
    public function show(Request $request, $consultationId)
    {
        $consultation = Consultation::with([
            'user',
            'expert',
            'expert.expertProfile',
            'payment',
            'rating',
        ])->where(function ($q) use ($request) {
            $q->where('user_id', $request->user()->id)
              ->orWhere('expert_id', $request->user()->id);
        })->find($consultationId);

        if (!$consultation) {
            return response()->json([
                'success' => false,
                'message' => 'Consultation not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $consultation,
        ]);
    }

    // ==================== GET PAYMENT DETAIL ====================
    public function paymentDetail(Request $request, $consultationId)
    {
        $consultation = Consultation::with(['expert', 'expert.expertProfile', 'payment'])
            ->where('user_id', $request->user()->id)
            ->find($consultationId);

        if (!$consultation) {
            return response()->json([
                'success' => false,
                'message' => 'Consultation not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $consultation,
        ]);
    }

    // ==================== GET PAYMENT HISTORY ====================
    public function paymentHistory(Request $request)
    {
        $consultations = Consultation::with(['expert', 'payment'])
            ->where('user_id', $request->user()->id)
            ->whereHas('payment')
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $consultations,
        ]);
    }
}