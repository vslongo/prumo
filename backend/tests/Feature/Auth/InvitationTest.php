<?php

declare(strict_types=1);

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Events\UserInvited;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\UserInvitation;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Support\Facades\Event;

beforeEach(fn () => $this->withHeader('Origin', 'http://localhost:5173'));

/** Dono convida e devolve o token cru do link. */
function inviteAs(UserRole $role = UserRole::Technician, string $name = 'Zé Mecânico'): string
{
    $link = test()->postJson('/api/user-invitations', ['name' => $name, 'role' => $role->value])
        ->assertCreated()
        ->json('data.link');

    return basename((string) $link);
}

function acceptPayload(array $overrides = []): array
{
    return ['name' => 'Zé Mecânico', 'email' => 'Ze@Oficina.test', 'password' => 'senha-forte-123', ...$overrides];
}

it('dono convida técnico e o link vem com wa.me pronto', function () {
    Event::fake([UserInvited::class]);
    actingAsRole();

    $this->postJson('/api/user-invitations', ['name' => 'Zé', 'role' => 'technician'])
        ->assertCreated()
        ->assertJsonPath('data.whatsapp_url', fn (string $url) => str_starts_with($url, 'https://wa.me/?text='));

    $invitation = UserInvitation::sole();
    expect($invitation->role)->toBe(UserRole::Technician)
        ->and($invitation->tenant_id)->toBe(TenantContext::id());
    Event::assertDispatched(UserInvited::class);
});

it('técnico e balcão não convidam; ninguém convida outro dono', function () {
    actingAsRole(UserRole::Technician);
    $this->postJson('/api/user-invitations', ['name' => 'X', 'role' => 'front'])->assertForbidden();

    actingAsRole(UserRole::Front);
    $this->postJson('/api/user-invitations', ['name' => 'X', 'role' => 'front'])->assertForbidden();

    actingAsRole();
    $this->postJson('/api/user-invitations', ['name' => 'X', 'role' => 'owner'])->assertUnprocessable()->assertJsonValidationErrors('role');
});

it('mostra o convite sem id interno e aceita criando o usuário na oficina do convite', function () {
    $owner = actingAsRole();
    $token = inviteAs();
    auth()->forgetGuards();
    TenantContext::set(null);

    $this->getJson("/api/public/invitations/{$token}")
        ->assertOk()
        ->assertExactJson(['data' => ['name' => 'Zé Mecânico', 'role' => 'technician', 'workshop' => $owner->tenant->name, 'usable' => true]]);

    $this->postJson("/api/public/invitations/{$token}/accept", acceptPayload())
        ->assertCreated()
        ->assertJsonPath('data.role', 'technician');

    $user = User::query()->where('email', 'ze@oficina.test')->sole();
    expect($user->tenant_id)->toBe($owner->tenant_id)
        ->and($user->role)->toBe(UserRole::Technician);
    $this->assertAuthenticatedAs($user, 'web');
});

it('convite é de uso único e expira', function () {
    actingAsRole();
    $token = inviteAs();
    $expired = inviteAs(name: 'Atrasado');
    UserInvitation::query()->where('name', 'Atrasado')->update(['expires_at' => now()->subMinute()]);
    auth()->forgetGuards();

    $this->postJson("/api/public/invitations/{$token}/accept", acceptPayload())->assertCreated();
    auth()->guard('web')->logout();

    $this->postJson("/api/public/invitations/{$token}/accept", acceptPayload(['email' => 'outro@oficina.test']))
        ->assertUnprocessable()->assertJsonValidationErrors('invitation');
    $this->postJson("/api/public/invitations/{$expired}/accept", acceptPayload(['email' => 'atrasado@oficina.test']))
        ->assertUnprocessable()->assertJsonValidationErrors('invitation');
    $this->getJson("/api/public/invitations/{$expired}")->assertJsonPath('data.usable', false);
});

it('recusa token inválido e e-mail já cadastrado', function () {
    actingAsRole();
    $token = inviteAs();
    User::factory()->create(['email' => 'ze@oficina.test']);
    auth()->forgetGuards();

    $this->getJson('/api/public/invitations/'.str_repeat('a', 64))->assertNotFound();
    $this->getJson('/api/public/invitations/curto')->assertNotFound();
    $this->postJson("/api/public/invitations/{$token}/accept", acceptPayload())->assertUnprocessable()->assertJsonValidationErrors('email');
});
