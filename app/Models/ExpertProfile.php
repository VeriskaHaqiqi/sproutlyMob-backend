<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
#use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel; // tambah ini

class ExpertProfile extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'university',
        'years_of_experience',
        'description',
        'certificate',
        'diploma',
        'bank_name',
        'account_holder',
        'account_number',
        'session_fee',
        'session_duration',
        'instant_booking',
        'availability_status',
        'average_rating',
        'total_consultations',
    ];

    protected $casts = [
        'instant_booking' => 'boolean',
        'session_fee' => 'decimal:2',
        'average_rating' => 'decimal:2',
    ];

    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}