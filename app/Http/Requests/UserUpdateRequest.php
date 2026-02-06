<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->route('user');
        $authUser = $this->user();

        // Users can update their own profile
        if ($authUser->id === $user->id) {
            return true;
        }

        // Admin can update anyone
        if ($authUser->hasRole('admin')) {
            return true;
        }

        // Owner can only update their assigned housekeepers
        if ($authUser->hasRole('owner') && !$authUser->hasRole('admin')) {
            if (!$user->hasRole('housekeeper')) {
                return false;
            }
            // Check if housekeeper is assigned to this owner
            return \Illuminate\Support\Facades\DB::table('cleaning_sessions')
                ->where('owner_id', $authUser->id)
                ->where('housekeeper_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');
        $authUser = $this->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'profile_photo' => ['nullable', 'image', 'max:5120'], // 5MB
            'remove_profile_photo' => ['sometimes', 'boolean'],
        ];

        // Admin can change any role
        if ($authUser->hasRole('admin') && $authUser->id !== $user->id) {
            $rules['role'] = ['nullable', Rule::in(['admin', 'owner', 'housekeeper'])];
        }

        // Owner can assign housekeeper role to their assigned housekeepers
        if ($authUser->hasRole('owner') && !$authUser->hasRole('admin') && $authUser->id !== $user->id) {
            if ($user->hasRole('housekeeper')) {
                $isAssigned = \Illuminate\Support\Facades\DB::table('cleaning_sessions')
                    ->where('owner_id', $authUser->id)
                    ->where('housekeeper_id', $user->id)
                    ->exists();
                if ($isAssigned) {
                    $rules['role'] = ['nullable', Rule::in(['housekeeper'])];
                }
            }
        }

        // Password is optional on update
        if ($this->filled('password')) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $rules;
    }
}
