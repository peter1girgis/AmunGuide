<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Sensitive fields (password, remember_token) are deliberately
     * excluded here as a second line of defence, even though they are
     * already listed in $hidden on the User model.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'role'              => $this->role,
            'phone'             => $this->phone,
            'address'           => $this->address,
            'national_id'       => $this->national_id,
            'profile_image'     => $this->profile_image,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at'        => $this->created_at->toIso8601String(),
            'updated_at'        => $this->updated_at->toIso8601String(),

            // password and remember_token are intentionally omitted.
        ];
    }
}
