<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function workShifts(): BelongsToMany
    {
        return $this->belongsToMany(WorkShift::class);
    }

    public function getStatusAttribute(): string
    {
        return $this->workShifts()->exists() ? 'working' : 'chilling';
    }

    protected $guarded = false;
}
