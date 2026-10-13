<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AdminMfaTest extends TestCase
{
    use RefreshDatabase;

    public function test_optional_enrollment_encrypts_secrets_and_does_not_log_them(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $provider = AppAuthentication::make()->recoverable();
        $this->assertFalse($provider->isEnabled($user));
        $this->get('/admin')->assertOk();
        $this->get('/admin/account')->assertOk();
        $this->get('/admin/profile')->assertOk()->assertSee('Simpan Tentang Kami');
        $secret = $provider->generateSecret();
        $provider->saveSecret($user, $secret);
        $this->assertTrue($provider->isEnabled($user->fresh()));
        $this->assertNotSame($secret, $user->fresh()->getRawOriginal('app_authentication_secret'));
        $this->assertArrayNotHasKey('app_authentication_secret', $user->toArray());
        $this->assertStringNotContainsString($secret, Activity::all()->toJson());
        $code = $provider->getCurrentCode($user);
        $this->assertTrue($provider->verifyCode($code, $secret, shouldPreventCodeReuse: true));
        $this->assertFalse($provider->verifyCode($code, $secret, shouldPreventCodeReuse: true));
    }

    public function test_recovery_codes_are_encrypted_hashed_and_consumed_once(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $provider = AppAuthentication::make()->recoverable()->recoveryCodeCount(2);
        $codes = $provider->generateRecoveryCodes();
        $provider->saveRecoveryCodes($user, $codes);
        $this->assertStringNotContainsString($codes[0], $user->fresh()->getRawOriginal('app_authentication_recovery_codes'));
        $this->assertArrayNotHasKey('app_authentication_recovery_codes', $user->toArray());
        $this->assertTrue($provider->verifyRecoveryCode($codes[0], $user));
        $this->assertFalse($provider->verifyRecoveryCode($codes[0], $user));
        $this->assertCount(1, $provider->getRecoveryCodes($user->fresh()));
        $this->assertStringNotContainsString($codes[0], Activity::all()->toJson());
    }
}
