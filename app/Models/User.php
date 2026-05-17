<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'gender',
        'password',
        'role',
        'profile_photo',
        'google_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Relasi ke expert_profiles
    public function expertProfile()
    {
        return $this->hasOne(ExpertProfile::class);
    }

    // Relasi ke articles (sebagai penulis)
    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    // Relasi ke bookmarked_articles
    public function bookmarkedArticles()
    {
        return $this->hasMany(BookmarkedArticle::class);
    }

    // Relasi ke consultations (sebagai user)
    public function consultationsAsUser()
    {
        return $this->hasMany(Consultation::class, 'user_id');
    }

    // Relasi ke consultations (sebagai expert)
    public function consultationsAsExpert()
    {
        return $this->hasMany(Consultation::class, 'expert_id');
    }

    // Relasi ke ratings (sebagai user yang memberi rating)
    public function ratingsGiven()
    {
        return $this->hasMany(Rating::class, 'user_id');
    }

    // Relasi ke ratings (sebagai expert yang dinilai)
    public function ratingsReceived()
    {
        return $this->hasMany(Rating::class, 'expert_id');
    }

    // Relasi ke expert_schedules
    public function schedules()
    {
        return $this->hasMany(ExpertSchedule::class, 'expert_id');
    }

    // Relasi ke specializations
    public function specializations()
    {
        return $this->hasMany(Specialization::class, 'expert_id');
    }

    // Helper: cek apakah user adalah expert
    public function isExpert()
    {
        return $this->role === 'expert';
    }
}