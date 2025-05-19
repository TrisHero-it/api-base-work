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
        'gender',
        'status',
        'BHXH',
        'tax_policy',
        'tax_reduced',
        'tax_code',
        'insurance_policy',
        'start_trial_date',
        'start_work_date',
        'personal_documents',
        'contract_file',
        'quit_work',
        'salary_scale',
        'personal_email',
        'place_of_registration',
        'is_active',
        'end_work_date',
        'is_active',
        'staff_type',
        'work_from_home',
    ];

    protected $casts = [
        'personal_documents' => 'array',
    ];

    public function isAdmin()
    {

        return $this->role_id == 2 || $this->role_id == 3;
    }

    public function contractActive()
    {
        return $this->hasOne(Contract::class, 'account_id', 'id')->where('active', true);
    }

    public function isSeniorAdmin()
    {
        return $this->role_id == 3;
    }

    public function isSalesMember()
    {
        $salesDepartment = Department::where('name', 'Phòng Sale')->first()->id;
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

    public function jobPosition()
    {
        return $this->hasMany(JobPosition::class, 'account_id', 'id')->orderBy('id', 'desc');
    }

    public function jobPositionActive()
    {
        return $this->hasOne(JobPosition::class, 'account_id', 'id')->where('status', 'active');
    }

    public function leaveHistory()
    {
        return $this->hasMany(LeaveHistory::class);
    }

    public function salary()
    {
        return $this->hasOne(Salary::class, 'account_id', 'id');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function dayoffAccount()
    {
        return $this->hasOne(DayoffAccount::class, 'account_id', 'id');
    }

    public function department()
    {
        return $this->belongsToMany(Department::class, 'account_departments', 'account_id', 'department_id');
    }

    public function workAtHome()
    {
        return $this->attendance_at_home == true;
    }

    public function dayOff()
    {
        return $this->hasOne(DayoffAccount::class, 'account_id', 'id');
    }
}
