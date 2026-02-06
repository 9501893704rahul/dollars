<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'profile_photo_path',
        'company_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the URL to the user's profile photo.
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        if ($this->profile_photo_path) {
            return str_starts_with($this->profile_photo_path, 'http')
                ? $this->profile_photo_path
                : asset('storage/' . $this->profile_photo_path);
        }

        // Get theme color from settings and remove '#' for API
        $themeColor = Setting::get('theme_color', '#842eb8');
        $backgroundColor = str_replace('#', '', $themeColor);

        // Return a default avatar placeholder
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=' . $backgroundColor . '&color=fff&size=128';
    }

    /**
     * Get the company this user belongs to (for owners and housekeepers).
     */
    public function company()
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    /**
     * Get the owners under this company.
     */
    public function owners()
    {
        return $this->hasMany(User::class, 'company_id')
                    ->whereHas('roles', fn($q) => $q->where('name', 'owner'));
    }

    /**
     * Get the housekeepers under this company.
     */
    public function companyHousekeepers()
    {
        return $this->hasMany(User::class, 'company_id')
                    ->whereHas('roles', fn($q) => $q->where('name', 'housekeeper'));
    }

    /**
     * Get all users under this company (owners and housekeepers).
     */
    public function companyUsers()
    {
        return $this->hasMany(User::class, 'company_id');
    }

    /**
     * Check if user is a company.
     */
    public function isCompany(): bool
    {
        return $this->hasRole('company');
    }

    /**
     * Get the company ID for this user (either self if company, or their company_id).
     */
    public function getEffectiveCompanyId(): ?int
    {
        if ($this->isCompany()) {
            return $this->id;
        }
        return $this->company_id;
    }
}
