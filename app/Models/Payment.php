<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
#use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel; // tambah ini

class Payment extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'consultation_id',
        'amount',
        'platform_fee',
        'total_amount',
        'payment_method',
        'payment_proof',
        'status',
        'rejection_note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // Relasi ke consultation
    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }
}