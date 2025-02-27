<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Account extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
    protected $fillable = [
        "username",
        "email",
        "password",
        "full_name",
        'position',
        'phone',
        'address',
        'birthday',
        'manager_id',
        'avatar',
        'role_id',
        'day_off',
        'attendance_at_home',
        'files',
        'identity_card',
        'temporary_address',
        'passport',
        'name_bank',
        'bank_number',
        'marital_status',
        'sex',
    ];

    public function isAdmin()
    {

        return $this->role_id == 1 || $this->role_id == 2;
    }

    public function isSeniorAdmin()
    {
        return $this->role_id == 2;
    }

    public function isSalesMember()
    {
        $salesDepartment = Department::where('name', 'Phòng sales')->first()->id;
        $accountDepartment = AccountDepartment::where('department_id', $salesDepartment)
            ->where('account_id', $this->id)
            ->exists();

        return $accountDepartment;
    }

    public function familyMembers()
    {
        return $this->hasMany(FamilyMember::class);
    }

    public function workHistories()
    {
        return $this->hasMany(WorkHistory::class);
    }

    public function educations()
    {
        return $this->hasMany(Education::class);
    }
}
