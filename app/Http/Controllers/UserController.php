<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $this->assertOwnerOrAdmin();

        $auth = $request->user();
        $role = (string) $request->query('role', '');
        $q    = (string) $request->query('q', '');

        $users = User::query()
            ->with('roles')
            ->when($auth->hasRole('owner') && ! $auth->hasRole('admin'), function ($qry) use ($auth) {
                $qry->where(function ($q) use ($auth) {
                    $q->where('users.id', $auth->id)
                        ->orWhere(function ($qq) use ($auth) {
                            $qq->whereHas('roles', fn($r) => $r->where('name', 'housekeeper'))
                                ->whereExists(function ($sub) use ($auth) {
                                    $sub->selectRaw(1)
                                        ->from('cleaning_sessions as cs')
                                        ->whereColumn('cs.housekeeper_id', 'users.id')
                                        ->where('cs.owner_id', $auth->id);
                                });
                        });
                });
            })
            ->when($q !== '', fn($qry) => $qry->where(function ($q2) use ($q) {
                $q2->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            }))
            ->when($role !== '', fn($qry) => $qry->whereHas('roles', fn($r) => $r->where('name', $role)))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function assignRole(Request $request, User $user)
    {
        $this->assertOwnerOrAdmin();

        $auth = $request->user();

        $data = $request->validate([
            'role' => ['required', Rule::in(['admin', 'owner', 'housekeeper'])],
        ]);

        // Admin can assign anything.
        if ($auth->hasRole('owner') && ! $auth->hasRole('admin')) {
            // Owners may ONLY assign housekeeper role.
            if ($data['role'] !== 'housekeeper') {
                abort(403, 'Owners can only assign the housekeeper role.');
            }
            // Owners cannot modify privileged users.
            if ($user->hasAnyRole(['admin', 'owner'])) {
                abort(403, 'Cannot modify admin/owner users.');
            }
            // If you want to restrict to their own HKs only, enforce exists() below:
            $isAssignedToOwner = DB::table('cleaning_sessions')
                ->where('owner_id', $auth->id)
                ->where('housekeeper_id', $user->id)
                ->exists();
            abort_unless($isAssignedToOwner, 403, 'Not your housekeeper.');
        }

        // If you want single-role model: use syncRoles([$data['role']]);
        $user->assignRole($data['role']);

        return back()->with('ok', 'Role assigned.');
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        // Admin and owner can create users
        $user = auth()->user();
        abort_unless($user && ($user->hasRole('admin') || $user->hasRole('owner')), 403, 'You do not have permission to create users.');

        return view('users.create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(UserStoreRequest $request)
    {
        $data = $request->validated();
        $authUser = $request->user();

        // Enforce: Owners can only create housekeepers
        if ($authUser->hasRole('owner') && !$authUser->hasRole('admin')) {
            if ($data['role'] !== 'housekeeper') {
                abort(403, 'Owners can only create housekeeper users.');
            }
        }

        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            $data['profile_photo_path'] = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        // Create user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'phone_number' => $data['phone_number'] ?? null,
            'profile_photo_path' => $data['profile_photo_path'] ?? null,
        ]);

        // Assign role
        $user->assignRole($data['role']);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $authUser = auth()->user();

        // Users can edit their own profile
        if ($authUser->id === $user->id) {
            return view('users.edit', compact('user'));
        }

        // Admin can edit anyone
        if ($authUser->hasRole('admin')) {
            return view('users.edit', compact('user'));
        }

        // Owner can only edit their assigned housekeepers
        if ($authUser->hasRole('owner') && !$authUser->hasRole('admin')) {
            if (!$user->hasRole('housekeeper')) {
                abort(403, 'You can only edit housekeepers assigned to you.');
            }
            $isAssigned = DB::table('cleaning_sessions')
                ->where('owner_id', $authUser->id)
                ->where('housekeeper_id', $user->id)
                ->exists();
            abort_unless($isAssigned, 403, 'This housekeeper is not assigned to you.');
        } else {
            abort(403, 'You do not have permission to edit this user.');
        }

        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UserUpdateRequest $request, User $user)
    {
        $data = $request->validated();

        // Handle profile photo removal
        if ($request->boolean('remove_profile_photo') && $user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $data['profile_photo_path'] = null;
        }

        // Handle profile photo upload
        if ($request->hasFile('profile_photo')) {
            // Delete old photo if exists
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            $data['profile_photo_path'] = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        // Update password if provided
        if (isset($data['password'])) {
            $data['password'] = $data['password'];
        } else {
            unset($data['password']);
        }

        // Update user
        $user->update($data);

        // Update role if admin/owner and role is provided
        $authUser = auth()->user();
        if ($authUser->id !== $user->id && isset($data['role'])) {
            // Admin can assign any role
            if ($authUser->hasRole('admin')) {
                $user->syncRoles([$data['role']]);
            }
            // Owner can only assign housekeeper role to their assigned housekeepers
            elseif ($authUser->hasRole('owner') && !$authUser->hasRole('admin')) {
                if ($data['role'] !== 'housekeeper') {
                    abort(403, 'Owners can only assign the housekeeper role.');
                }
                if (!$user->hasRole('housekeeper')) {
                    abort(403, 'You can only assign roles to housekeepers assigned to you.');
                }
                $isAssigned = DB::table('cleaning_sessions')
                    ->where('owner_id', $authUser->id)
                    ->where('housekeeper_id', $user->id)
                    ->exists();
                abort_unless($isAssigned, 403, 'This housekeeper is not assigned to you.');
                $user->syncRoles([$data['role']]);
            }
        }

        $redirectRoute = $authUser->id === $user->id
            ? route('profile.edit')
            : route('users.index');

        return redirect($redirectRoute)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        $authUser = auth()->user();

        // Cannot delete yourself
        abort_if($authUser->id === $user->id, 403, 'You cannot delete your own account.');

        // Only admin can delete users
        abort_unless($authUser->hasRole('admin'), 403, 'Only administrators can delete users.');

        // Delete profile photo if exists
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    private function assertOwnerOrAdmin(): void
    {
        $u = auth()->user();
        abort_unless($u && $u->hasAnyRole(['admin', 'owner']), 403);
    }
}
