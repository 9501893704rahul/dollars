<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ManageSessionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PropertyDuplicateController;
use App\Http\Controllers\PropertyAssignmentsApiController;
use App\Http\Controllers\PropertyRoomAttachController;
use App\Http\Controllers\PropertyRoomController;
use App\Http\Controllers\RoomTaskOrderController;
use App\Http\Controllers\PropertyRoomOrderController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomSuggestionController;
use App\Http\Controllers\RoomTaskAttachController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskMediaController;
use App\Http\Controllers\TaskSuggestionController;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Setup route to seed database (run once)
Route::get('/setup-database', function () {
    try {
        \Artisan::call('db:seed', ['--force' => true]);
        return 'Database seeded successfully! You can now login with:<br>
        Admin: admin@example.com / password<br>
        Owner: owner@example.com / password<br>
        Housekeeper: housekeeper@example.com / password';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

// Run pending migrations (for production updates)
Route::get('/run-migrations', function () {
    try {
        \Artisan::call('migrate', ['--force' => true]);
        $output = \Artisan::output();
        return '<pre>Migrations completed successfully!<br><br>' . nl2br(htmlspecialchars($output)) . '</pre>';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

Route::middleware('auth')->get('/dashboard', DashboardController::class)->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');



    Route::resource('rooms', RoomController::class)->except(['show', 'create']);

    Route::post('/rooms/{room}/tasks/attach', [RoomTaskAttachController::class, 'store'])
        ->name('rooms.tasks.attach');

    Route::post(
        '/rooms/bulk-attach-tasks',
        [RoomController::class, 'bulkAttachTasks']
    )->name('rooms.bulk-attach-tasks');

    // Room-specific task management (standalone rooms, not under properties)
    Route::get('/rooms/{room}/tasks', [RoomController::class, 'tasks'])->name('rooms.tasks.index');
    Route::patch('/rooms/{room}/tasks', [RoomTaskOrderController::class, 'updateForRoom'])->name('rooms.tasks.order');
    Route::post('/rooms/{room}/tasks', [RoomController::class, 'storeTask'])->name('rooms.tasks.store');
    Route::post('/rooms/{room}/tasks/bulk', [RoomController::class, 'bulkStoreTask'])->name('rooms.tasks.bulk-store');
    Route::get('/rooms/{room}/tasks/{task}/edit', [RoomController::class, 'editTask'])->name('rooms.tasks.edit');
    Route::put('/rooms/{room}/tasks/{task}', [RoomController::class, 'updateTask'])->name('rooms.tasks.update');
    Route::delete('/rooms/{room}/tasks/{task}', [RoomController::class, 'detachTask'])->name('rooms.tasks.detach');


    Route::resource('tasks', TaskController::class)->except('show');

    Route::get('/activity', [ActivityController::class, 'index'])
        ->name('activity.index');


    Route::resource('properties', PropertyController::class)->except('show');

    // Property assigned rooms/tasks (AJAX)
    Route::get('/api/properties/{property}/assigned-rooms', [PropertyAssignmentsApiController::class, 'rooms'])
        ->name('api.properties.assigned-rooms');
    Route::get('/api/properties/{property}/assigned-property-tasks', [PropertyAssignmentsApiController::class, 'propertyTasks'])
        ->name('api.properties.assigned-property-tasks');

    // Rooms and tasks (nested under property)
    Route::prefix('properties')->name('properties.')->group(function () {
        Route::post('{property}/duplicate', [PropertyDuplicateController::class, 'store'])->name('duplicate');

        Route::get('{property}/rooms', [PropertyController::class, 'rooms'])->name('rooms.index');
        Route::put('{property}/rooms/{room}', [PropertyController::class, 'updateRoom'])->name('rooms.update');
        Route::delete('{property}/rooms/{room}', [PropertyController::class, 'destroyRoom'])->name('rooms.destroy');
        Route::patch('{property}/rooms/order', [PropertyRoomOrderController::class, 'update'])
            ->name('rooms.order');

        Route::post('{property}/rooms/attach', [PropertyRoomAttachController::class, 'store'])
            ->name('rooms.attach');

        Route::post('{property}/rooms', [PropertyRoomController::class, 'store'])->name('rooms.store');

        Route::get('{property}/rooms/{room}/tasks', [PropertyController::class, 'tasks'])->name('tasks.index');
        Route::patch('{property}/rooms/{room}/tasks', [RoomTaskOrderController::class, 'update'])->name('tasks.order');
        Route::post('{property}/rooms/{room}/tasks', [PropertyController::class, 'storeTask'])->name('tasks.store');
        Route::post('{property}/rooms/{room}/tasks/bulk', [PropertyController::class, 'bulkStoreTask'])->name('tasks.bulk-store');
        Route::put('{property}/rooms/{room}/tasks/{task}', [PropertyController::class, 'updateTask'])->name('tasks.update');
        Route::delete('{property}/rooms/{room}/tasks/{task}', [PropertyController::class, 'detachTask'])->name('tasks.detach');

        // Property-level tasks (not room-specific)
        Route::get('{property}/property-tasks', [PropertyController::class, 'propertyTasks'])->name('property-tasks.index');
        Route::post('{property}/property-tasks', [PropertyController::class, 'storePropertyTask'])->name('property-tasks.store');
        Route::put('{property}/property-tasks/{task}', [PropertyController::class, 'updatePropertyTask'])->name('property-tasks.update');
        Route::delete('{property}/property-tasks/{task}', [PropertyController::class, 'detachPropertyTask'])->name('property-tasks.detach');

        Route::post('/tasks/{task}/media',               [TaskMediaController::class, 'store'])->name('tasks.media.store');
        Route::delete('/tasks/{task}/media/{media}',     [TaskMediaController::class, 'destroy'])->name('tasks.media.destroy');
    });

    // Users management (admin and owner only, housekeepers cannot access)
    Route::middleware('role:admin|owner')->group(function () {
        Route::get('users', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');
        Route::post('users/{user}/assign-role', [\App\Http\Controllers\UserController::class, 'assignRole'])->name('users.assignRole');
        Route::get('users/create', [\App\Http\Controllers\UserController::class, 'create'])->name('users.create');
        Route::post('users', [\App\Http\Controllers\UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [\App\Http\Controllers\UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [\App\Http\Controllers\UserController::class, 'update'])->name('users.update');
    });

    // User deletion (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::delete('users/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('users.destroy');

        // Settings (admin only)
        Route::get('settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [\App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');
    });

    // Sessions (housekeeper)
    Route::get('/sessions', [\App\Http\Controllers\SessionController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/{session}', [\App\Http\Controllers\SessionController::class, 'show'])->name('sessions.show');
    Route::get('/api/sessions/{session}/data', [\App\Http\Controllers\SessionController::class, 'getData'])->name('sessions.data');
    Route::post('/sessions/{session}/start', [\App\Http\Controllers\SessionController::class, 'start'])->name('sessions.start');
    Route::post('/sessions/{session}/complete', [\App\Http\Controllers\SessionController::class, 'complete'])->name('sessions.complete');

    Route::post('/sessions/{session}/rooms/{room}/tasks/{task}/toggle', [ChecklistController::class, 'toggle'])
        ->name('checklist.toggle');

    Route::post('/sessions/{session}/rooms/{room}/tasks/{task}/note', [ChecklistController::class, 'note'])
        ->name('checklist.note');

    Route::post('/sessions/{session}/rooms/{room}/tasks/{task}/photo', [ChecklistController::class, 'taskPhoto'])
        ->name('checklist.task-photo');

    // Property-level task checklist actions (no room)
    Route::post('/sessions/{session}/property-tasks/{task}/toggle', [ChecklistController::class, 'togglePropertyTask'])
        ->name('checklist.property-task.toggle');

    Route::post('/sessions/{session}/property-tasks/{task}/note', [ChecklistController::class, 'notePropertyTask'])
        ->name('checklist.property-task.note');

    Route::post('/sessions/{session}/property-tasks/{task}/photo', [ChecklistController::class, 'propertyTaskPhoto'])
        ->name('checklist.property-task.photo');

    Route::post('/sessions/{session}/rooms/{room}/photos', [\App\Http\Controllers\PhotoController::class, 'store'])->name('photos.store');
    Route::delete('/api/sessions/{session}/photos/{photo}', [\App\Http\Controllers\PhotoController::class, 'destroy'])->name('photos.destroy');

    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

    // Completion Reports
    Route::get('/sessions/{session}/report', [ReportController::class, 'show'])->name('reports.show');
    Route::get('/sessions/{session}/report/download', [ReportController::class, 'download'])->name('reports.download');
    Route::post('/sessions/{session}/report/email', [ReportController::class, 'email'])->name('reports.email');
});

Route::middleware(['auth', 'role:owner|admin'])
    ->prefix('manage')->name('manage.')
    ->group(function () {
        Route::get('sessions',        [ManageSessionController::class, 'index'])->name('sessions.index');
        Route::get('sessions/create', [ManageSessionController::class, 'create'])->name('sessions.create');
        Route::post('sessions',       [ManageSessionController::class, 'store'])->name('sessions.store');
        Route::get('sessions/{session}/edit', [ManageSessionController::class, 'edit'])->name('sessions.edit');
        Route::put('sessions/{session}',      [ManageSessionController::class, 'update'])->name('sessions.update');
        Route::delete('sessions/{session}',   [ManageSessionController::class, 'destroy'])->name('sessions.destroy');
    });


Route::middleware(['auth'])->group(function () {
    // Autocomplete suggestions for room names
    Route::get('/rooms/suggest', [RoomSuggestionController::class, 'index'])
        ->name('rooms.suggest');

    Route::get('/tasks/suggest', [TaskSuggestionController::class, 'index'])
        ->name('tasks.suggest');
});







require __DIR__ . '/auth.php';
