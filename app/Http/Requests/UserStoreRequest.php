<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        // Admin and owner can create users
        return $user && ($user->hasRole('admin') || $user->hasRole('owner'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $authUser = $this->user();
        $roleRules = ['required', Rule::in(['admin', 'owner', 'housekeeper'])];

        // Owners can only create housekeepers
        if ($authUser->hasRole('owner') && !$authUser->hasRole('admin')) {
            $roleRules = ['required', Rule::in(['housekeeper'])];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'profile_photo' => ['nullable', 'image', 'max:5120'], // 5MB
            'role' => $roleRules,
        ];
    }
}
