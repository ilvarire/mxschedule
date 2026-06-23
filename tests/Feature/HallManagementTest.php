<?php

use App\Models\Hall;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function hallManagementAdmin(): User
{
    $permissions = collect(['manage_halls', 'manage_systems'])
        ->map(fn (string $name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));

    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $role->syncPermissions($permissions);

    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('bulk system creation shows a validation error when hall capacity would be exceeded', function () {
    $admin = hallManagementAdmin();
    $hall = Hall::create([
        'name' => 'Capacity Hall',
        'code' => 'CH',
        'capacity' => 50,
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($admin)
        ->from(route('admin.halls.show', $hall))
        ->post(route('admin.systems.bulk-create', $hall), [
            'count' => 60,
            'prefix' => 'CH',
        ]);

    $response
        ->assertRedirect(route('admin.halls.show', $hall))
        ->assertSessionHasErrors('count');

    expect($hall->systems()->count())->toBe(0);
});

test('saving hall edits updates the hall instead of deleting it', function () {
    $admin = hallManagementAdmin();
    $hall = Hall::create([
        'name' => 'Old Hall',
        'code' => 'OH',
        'location' => 'Old Block',
        'capacity' => 50,
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($admin)
        ->put(route('admin.halls.update', $hall), [
            'name' => 'Updated Hall',
            'code' => 'UH',
            'location' => 'New Block',
            'capacity' => 75,
            'is_active' => '1',
        ]);

    $response
        ->assertRedirect(route('admin.halls.show', $hall))
        ->assertSessionHas('success', 'Hall updated successfully.');

    expect($hall->refresh()->name)->toBe('Updated Hall')
        ->and($hall->code)->toBe('UH')
        ->and($hall->capacity)->toBe(75);
});
