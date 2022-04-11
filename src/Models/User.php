<?php

namespace Qubiqx\QcommerceCore\Models;

use Spatie\Activitylog\LogOptions;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Spatie\Activitylog\Traits\LogsActivity;
use Qubiqx\QcommerceCore\Traits\HasDynamicRelation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasFactory;
    use Notifiable;
    use LogsActivity;
    use HasDynamicRelation;

    protected static $logFillable = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'role',
        'password',
        'password_reset_token',
        'password_reset_requested',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = [
        'name',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function canAccessFilament(): bool
    {
        return ($this->role === 'admin');
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function getNameAttribute()
    {
        if ($this->first_name || $this->last_name) {
            if ($this->first_name && $this->last_name) {
                return "$this->first_name $this->last_name";
            } elseif ($this->first_name) {
                return $this->first_name;
            } else {
                return $this->last_name;
            }
        } else {
            return $this->email;
        }
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($this->email)));
    }

//    public function orders()
//    {
//        return $this->hasMany(Order::class)->whereIn('status', ['paid', 'waiting_for_confirmation', 'partially_paid'])->orderBy('created_at', 'DESC');
//    }

//    public function lastOrder()
//    {
//        return $this->orders()->latest()->first();
//    }
}
