<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $roles = [
            'Nhân viên',
            'Quản lí',
            'Admin',
        ];

        if ($this->quit_work == true) {
            $role = 'Vô hiệu hoá';
        } else {
            $role = $roles[$this->role - 1];
        }
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'role' => $role,
            'phone' => $this->phone,
            'address' => $this->address,
            'avatar' => $this->avatar,
            'birthday' => $this->birthday,
            'gender' => $this->gender,
            'day_off' => $this->day_off,
            'kpi' => $this->kpi,
            'basic_salary' => $this->basic_salary,
            'travel_allowance' => $this->travel_allowance,
            'eat_allowance' => $this->eat_allowance,
            'position' => $this->position,
            'department_name' => $this->department_name,
            'url_contract' => $this->url_contract,
            'name_contract' => $this->name_contract,
            'type_contract' => $this->category__contract_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

        ];
    }
}
