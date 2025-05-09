<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Check if user is owner/manager
     * 
     * @return bool
     */
    public function isOwner()
    {
        return $this->role === 'owner';
    }

    /**
     * Check if user is delivery personnel
     * 
     * @return bool
     */
    public function isDelivery()
    {
        return $this->role === 'delivery';
    }

    /**
     * Check if user is helper
     * 
     * @return bool
     */
    public function isHelper()
    {
        return $this->role === 'helper';
    }

    /**
     * Get orders created by this user
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get deliveries assigned to this user
     */
    public function deliveries()
    {
        return $this->hasMany(Order::class, 'delivery_user_id');
    }
}