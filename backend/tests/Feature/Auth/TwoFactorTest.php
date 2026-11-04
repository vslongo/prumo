<?php

declare(strict_types=1);

use App\Modules\Auth\Actions\ConfirmTwoFactor;
use App\Modules\Auth\Actions\EnableTwoFactor;
use App\Modules\Auth\Actions\ResolveTwoFactorRequirement;
use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Support\Totp;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

function currentCode(string $secret, int $offset = 0): string
{
    return Totp::code($secret, intdiv(time(), Totp::PERIOD) + $offset);
}

/** Ativa o 2FA pela API e devolve [segredo, códigos de recuperação]. */
function enableTwoFactor(): array
{
    $secret = test()->postJson('/api/two-factor')->assertOk()->json('data.secret');
    $codes = test()->postJson('/api/two-factor/confirm', ['code' => currentCode($secret, -1)])->assertOk()->json('data.recovery_codes');

    return [$secret, $codes];
}

/** Dono com 2FA já ativo e sem sessão: [usuário, segredo, códigos de recuperação]. */
function userWithTwoFactor(): array
{
    test()->withHeader('Origin', 'http://localhost:5173'); // sessão de SPA (statefulApi), como no LoginTest
    $user = User::factory()->for(Tenant::factory())->role(UserRole::Owner)->create();
    $secret = app(EnableTwoFactor::class)->handle($user)['secret'];
    $codes = app(ConfirmTwoFactor::class)->handle($user, currentCode($secret, -1));

    return [$user->fresh(), $secret, $codes];
}

it('ativa em duas etapas e guarda segredo e códigos só criptografados', function () {
    $user = actingAsRole(UserRole::Technician);

    $start = $this->postJson('/api/two-factor')->assertOk();
    expect($start->json('data.otpauth_url'))->toStartWith('otpauth://totp/Prumo%3A')->toContain('secret='.$start->json('data.secret'));

    $this->postJson('/api/two-factor/confirm', ['code' => '000000'])->assertUnprocessable()->assertJsonValidationErrors('code');
    $codes = $this->postJson('/api/two-factor/confirm', ['code' => currentCode($start->json('data.secret'))])->assertOk()->json('data.recovery_codes');

    expect($codes)->toHaveCount(8);
    $row = DB::table('users')->where('id', $user->id)->first();
    expect($row->two_factor_secret)->not->toContain($start->json('data.secret'))
        ->and($row->two_factor_recovery_codes)->not->toContain($codes[0])
        ->and($row->two_factor_confirmed_at)->not->toBeNull();
    $this->getJson('/api/me')->assertJsonPath('data.two_factor.enabled', true);
    $this->postJson('/api/two-factor')->assertUnprocessable();
});

it('login com 2FA pede o código antes de abrir a sessão', function () {
    [$user, $secret] = userWithTwoFactor();

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])->assertOk()->assertExactJson(['two_factor' => true]);
    $this->getJson('/api/me')->assertUnauthorized();

    $this->postJson('/api/login/two-factor', ['code' => '000000'])->assertUnprocessable();
    $this->postJson('/api/login/two-factor', ['code' => currentCode($secret)])->assertOk()->assertJsonPath('data.id', $user->id);
    $this->getJson('/api/me')->assertOk();
});

it('não aceita o mesmo código duas vezes', function () {
    [$user, $secret] = userWithTwoFactor();
    $code = currentCode($secret);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);
    $this->postJson('/api/login/two-factor', ['code' => $code])->assertOk();
    $this->postJson('/api/logout');
    app('auth')->forgetGuards();

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);
    $this->postJson('/api/login/two-factor', ['code' => $code])->assertUnprocessable();
});

it('código de recuperação entra uma vez só', function () {
    [$user, , $codes] = userWithTwoFactor();

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);
    $this->postJson('/api/login/two-factor', ['recovery_code' => strtoupper($codes[0])])->assertOk();
    $this->postJson('/api/logout');
    app('auth')->forgetGuards();

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);
    $this->postJson('/api/login/two-factor', ['recovery_code' => $codes[0]])->assertUnprocessable();
    expect($user->fresh()->two_factor_recovery_codes)->toHaveCount(7);
});

it('desafio sem senha antes ou vencido não entra', function () {
    [$user, $secret] = userWithTwoFactor();

    $this->postJson('/api/login/two-factor', ['code' => currentCode($secret)])->assertUnprocessable();

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);
    $this->travel(6)->minutes();
    $this->postJson('/api/login/two-factor', ['code' => currentCode($secret)])->assertUnprocessable();
    $this->getJson('/api/me')->assertUnauthorized();
});

it('desativa só com a senha certa', function () {
    $user = actingAsRole();
    enableTwoFactor();

    $this->deleteJson('/api/two-factor', ['password' => 'errada'])->assertUnprocessable()->assertJsonValidationErrors('password');
    $this->deleteJson('/api/two-factor', ['password' => 'password'])->assertNoContent();
    expect($user->fresh()->two_factor_confirmed_at)->toBeNull()->and($user->fresh()->two_factor_secret)->toBeNull();
});

it('dono tem 7 dias; depois só consegue ativar o 2FA', function () {
    $owner = actingAsRole(UserRole::Owner);

    $this->getJson('/api/customers')->assertOk();
    $this->getJson('/api/me')->assertJsonPath('data.two_factor.blocked', false)
        ->assertJsonPath('data.two_factor.required_from', $owner->created_at->addDays(ResolveTwoFactorRequirement::GRACE_DAYS)->toIso8601String());

    $this->travel(ResolveTwoFactorRequirement::GRACE_DAYS)->days();
    $this->travel(1)->minutes();

    $this->getJson('/api/customers')->assertForbidden()->assertJsonPath('code', 'two_factor_required');
    $this->getJson('/api/me')->assertOk()->assertJsonPath('data.two_factor.blocked', true);

    enableTwoFactor();
    $this->getJson('/api/customers')->assertOk();
});

it('outros papéis nunca são bloqueados', function (UserRole $role) {
    actingAsRole($role);
    $this->travel(30)->days();

    $this->getJson('/api/customers')->assertOk();
    $this->getJson('/api/me')->assertJsonPath('data.two_factor.required_from', null);
})->with([UserRole::Admin, UserRole::Technician, UserRole::Front]);

it('limita tentativas do código no login', function () {
    [$user] = userWithTwoFactor();
    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password']);

    foreach (range(1, 5) as $_) {
        $this->postJson('/api/login/two-factor', ['code' => '000000'])->assertUnprocessable();
    }
    $this->postJson('/api/login/two-factor', ['code' => '000000'])->assertTooManyRequests();
});

it('ativar não mexe em usuário de outro tenant', function () {
    actingAsRole();
    $other = User::factory()->for(Tenant::factory())->create();

    enableTwoFactor();

    expect($other->fresh()->two_factor_secret)->toBeNull();
    Sanctum::actingAs($other);
    $this->getJson('/api/me')->assertJsonPath('data.two_factor.enabled', false);
});
