<?php

namespace Tests\Feature;

use App\Filament\Pages\EditAccount;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminSecurityPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creation_rejects_short_password_and_hashes_valid_password(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $data = ['name' => 'New Admin', 'email' => 'new@example.com', 'is_admin' => true];

        Livewire::test(ManageUsers::class)->callAction('create', [...$data, 'password' => 'short'])->assertHasActionErrors(['password']);
        $this->assertDatabaseMissing('users', ['email' => $data['email']]);
        Livewire::test(ManageUsers::class)->callAction('create', [...$data, 'password' => 'long-passphrase-123'])->assertHasNoActionErrors();
        $this->assertTrue(Hash::check('long-passphrase-123', User::where('email', $data['email'])->firstOrFail()->password));
    }

    public function test_account_edit_rejects_weak_replacement_but_allows_unchanged_password(): void
    {
        $user = User::factory()->admin()->create();
        $hash = $user->password;
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $action = TestAction::make('edit')->table($user);
        $data = ['name' => 'Edited', 'email' => $user->email, 'is_admin' => true];

        Livewire::test(ManageUsers::class)->callAction($action, [...$data, 'password' => 'short'])->assertHasActionErrors(['password']);
        Livewire::test(ManageUsers::class)->callAction($action, [...$data, 'password' => ''])->assertHasNoActionErrors();
        $this->assertSame($hash, $user->refresh()->password);
        $this->assertSame('Edited', $user->name);
    }

    public function test_self_service_password_uses_the_shared_policy(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(EditAccount::class)->fillForm(['password' => 'ten-chars!', 'passwordConfirmation' => 'ten-chars!', 'currentPassword' => 'password'])->call('save')->assertHasFormErrors(['password']);
        $this->assertTrue(Hash::check('password', $user->refresh()->password));
        Livewire::test(EditAccount::class)->fillForm(['password' => 'long-passphrase-123', 'passwordConfirmation' => 'long-passphrase-123', 'currentPassword' => 'password'])->call('save')->assertHasNoFormErrors();
        $this->assertTrue(Hash::check('long-passphrase-123', $user->refresh()->password));
    }

    public function test_production_requires_mfa_unless_explicitly_overridden(): void
    {
        $this->app->instance('env', 'production');
        config(['security.admin_mfa_required' => null]);
        $panel = Filament::getPanel('admin');
        $this->assertTrue($panel->isMultiFactorAuthenticationRequired());
        config(['security.admin_mfa_required' => false]);
        $this->assertFalse($panel->isMultiFactorAuthenticationRequired());
        $this->app->instance('env', 'testing');
        config(['security.admin_mfa_required' => null]);
        $this->assertFalse($panel->isMultiFactorAuthenticationRequired());
        config(['security.admin_mfa_required' => true]);
        $this->assertTrue($panel->isMultiFactorAuthenticationRequired());
    }
}
