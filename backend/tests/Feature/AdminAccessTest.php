<?php

namespace Tests\Feature;

use App\Filament\Resources\Tuks\Pages\ManageTuks;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_open_admin_pages_or_livewire(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/admin')->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ManageTuks::class)->assertForbidden();
    }

    public function test_provisioning_grants_only_the_named_existing_account(): void
    {
        $admin = User::factory()->create(['email' => 'info@lspglobal.id']);
        $other = User::factory()->create();
        $this->artisan('admin:grant', ['email' => $admin->email])->assertSuccessful();
        $this->assertTrue($admin->refresh()->is_admin);
        $this->assertFalse($other->refresh()->is_admin);
        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->artisan('admin:grant', ['email' => 'missing@example.com'])->assertFailed();
    }

    public function test_access_is_revoked_for_an_already_authenticated_user(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();
        $this->actingAs($admin)->get('/admin')->assertOk();
        DB::table('users')->where('id', $admin->id)->update(['is_admin' => false]);
        $this->get('/admin')->assertForbidden();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ManageTuks::class)->assertForbidden();
    }

    public function test_last_admin_cannot_be_removed_through_the_model(): void
    {
        $admin = User::factory()->admin()->create();
        $this->expectException(ValidationException::class);
        $admin->delete();
    }

    public function test_database_prevents_removing_the_last_admin_after_another_revocation(): void
    {
        $first = User::factory()->admin()->create();
        $last = User::factory()->admin()->create();
        DB::table('users')->where('id', $first->id)->update(['is_admin' => false]);
        $this->expectException(QueryException::class);
        DB::table('users')->where('id', $last->id)->update(['is_admin' => false]);
    }

    public function test_public_mass_assignment_cannot_grant_admin_access(): void
    {
        $user = User::factory()->create();
        $user->fill(['is_admin' => true])->save();
        $this->assertFalse($user->refresh()->is_admin);
    }

    public function test_admin_can_manage_accounts_without_logging_passwords(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ManageUsers::class)
            ->callAction('create', ['name' => 'Admin Baru', 'email' => 'new@example.com', 'password' => 'secure-password', 'is_admin' => true])
            ->assertHasNoActionErrors();
        $user = User::query()->where('email', 'new@example.com')->firstOrFail();
        $this->assertTrue($user->is_admin);
        $this->assertStringNotContainsString('secure-password', DB::table('activity_log')->pluck('properties')->implode(''));
    }
}
