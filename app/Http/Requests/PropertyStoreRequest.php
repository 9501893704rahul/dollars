<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropertyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'owner']) ?? false;
    }

    public function rules(): array
    {
        $user = $this->user();
        $isAdmin = $user?->hasRole('admin');
        $userId = $user?->id;

        return [
            'name'         => ['required', 'string', 'max:255'],
            'address'      => ['nullable', 'string', 'max:255'],
            'latitude'     => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'    => ['nullable', 'numeric', 'between:-180,180'],
            'geo_radius_m' => ['nullable', 'integer', 'min:50'],
            'photo'        => ['nullable', 'image', 'max:5120'], // 5MB

            'owner_id'     => $isAdmin
                ? ['required', 'integer', Rule::exists('users', 'id')]
                : ['required', 'integer', Rule::in([$userId])], // For owners, must be their own ID

            'attach'       => ['nullable', Rule::in(['none', 'rooms'])],
        ];
    }

    /**
     * If the requester is an owner, force owner_id to themselves (defense-in-depth),
     * so even if someone tampers with the form, it won't assign to another user.
     */
    protected function prepareForValidation(): void
    {
        if ($this->user()?->hasRole('owner')) {
            $this->merge([
                'owner_id' => $this->user()->id,
            ]);
        }
    }
}
