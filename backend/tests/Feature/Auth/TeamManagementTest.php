<?php

declare(strict_types=1);

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\UserInvitation;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

function colleague(User $of, UserRole $role = UserRole::Technician): User
{
    return User::factory()->create(['tenant_id' => $of->tenant_id, 'role' => $role]);
}

it('dono desativa funcionário: some das sessões, não entra mais e reativa depois', function () {
    $owner = actingAsRole();
    $tech = colleague($owner);
    DB::table('sessions')->insert(['id' => 'sessao-do-tecnico', 'user_id' => $tech->id, 'payload' => '', 'last_activity' => time()]);

    $this->postJson("/api/users/{$tech->id}/deactivate")->assertOk()->assertJsonPath('data.is_active', false);

    expect($tech->refresh()->is_active)->toBeFalse()
        ->and(DB::table('sessions')->where('user_id', $tech->id)->exists())->toBeFalse();

    $this->postJson("/api/users/{$tech->id}/reactivate")->assertOk()->assertJsonPath('data.is_active', true);
    expect($tech->refresh()->is_active)->toBeTrue();
});

it('desativado com sessão aberta recebe 401 em qualquer rota da oficina', function () {
    $tech = actingAsRole(UserRole::Technician);
    $tech->forceFill(['is_active' => false])->save();

    $this->getJson('/api/me')->assertUnauthorized();
    $this->getJson('/api/service-orders')->assertUnauthorized();
});

it('dono troca o papel; ninguém vira dono', function () {
    $owner = actingAsRole();
    $tech = colleague($owner);

    $this->patchJson("/api/users/{$tech->id}/role", ['role' => 'admin'])->assertOk()->assertJsonPath('data.role', 'admin');
    $this->patchJson("/api/users/{$tech->id}/role", ['role' => 'owner'])->assertUnprocessable()->assertJsonValidationErrors('role');
    expect($tech->refresh()->role)->toBe(UserRole::Admin);
});

it('ninguém mexe no dono nem em si mesmo; técnico e balcão não gerenciam', function () {
    $admin = actingAsRole(UserRole::Admin);
    $owner = colleague($admin, UserRole::Owner);
    $tech = colleague($admin);

    $this->postJson("/api/users/{$owner->id}/deactivate")->assertForbidden();
    $this->patchJson("/api/users/{$owner->id}/role", ['role' => 'front'])->assertForbidden();
    $this->postJson("/api/users/{$admin->id}/deactivate")->assertForbidden();

    actingAsRole(UserRole::Technician, $admin->tenant);
    $this->postJson("/api/users/{$tech->id}/deactivate")->assertForbidden();
    $this->getJson('/api/user-invitations')->assertForbidden();

    expect($owner->refresh()->is_active)->toBeTrue();
});

it('só o dono gerencia, promove ou convida admin', function () {
    $owner = actingAsRole();
    $otherAdmin = colleague($owner, UserRole::Admin);
    $tech = colleague($owner);

    actingAsRole(UserRole::Admin, $owner->tenant);
    $this->postJson("/api/users/{$otherAdmin->id}/deactivate")->assertForbidden();
    $this->patchJson("/api/users/{$otherAdmin->id}/role", ['role' => 'technician'])->assertForbidden();
    $this->patchJson("/api/users/{$tech->id}/role", ['role' => 'admin'])->assertForbidden();
    $this->postJson('/api/user-invitations', ['name' => 'X', 'role' => 'admin'])->assertForbidden();
    $this->patchJson("/api/users/{$tech->id}/role", ['role' => 'front'])->assertOk();

    Sanctum::actingAs($owner);
    $this->patchJson("/api/users/{$otherAdmin->id}/role", ['role' => 'technician'])->assertOk();
    $this->postJson('/api/user-invitations', ['name' => 'Novo admin', 'role' => 'admin'])->assertCreated();
    expect($otherAdmin->refresh()->role)->toBe(UserRole::Technician);
});

it('não gerencia usuário de outra oficina (404)', function () {
    actingAsRole();
    $outsider = inOtherTenant(fn () => User::factory()->create(['tenant_id' => TenantContext::id()]));

    $this->postJson("/api/users/{$outsider->id}/deactivate")->assertNotFound();
    $this->patchJson("/api/users/{$outsider->id}/role", ['role' => 'admin'])->assertNotFound();
    expect($outsider->refresh()->is_active)->toBeTrue();
});

it('lista convites pendentes e cancela: o link deixa de valer', function () {
    actingAsRole();
    $token = basename((string) $this->postJson('/api/user-invitations', ['name' => 'Zé', 'role' => 'technician'])->json('data.link'));
    $id = UserInvitation::sole()->id;

    $this->getJson('/api/user-invitations')->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Zé')
        ->assertJsonPath('data.0.link', fn (string $link) => str_ends_with($link, "/convite/{$token}"));

    // Token cru só criptografado no banco; o JSON do model não o expõe.
    $raw = DB::table('user_invitations')->value('token');
    expect($raw)->not->toContain($token)
        ->and(UserInvitation::sole()->toArray())->not->toHaveKeys(['token', 'token_hash']);

    $this->deleteJson("/api/user-invitations/{$id}")->assertNoContent();
    $this->getJson('/api/user-invitations')->assertJsonCount(0, 'data');
    $this->getJson("/api/public/invitations/{$token}")->assertJsonPath('data.usable', false);
});

it('não cancela convite de outra oficina', function () {
    $owner = actingAsRole();
    $foreign = inOtherTenant(fn () => UserInvitation::create([
        'name' => 'De fora', 'role' => 'technician', 'token_hash' => str_repeat('c', 64),
        'expires_at' => now()->addDay(), 'invited_by_id' => User::factory()->create(['tenant_id' => TenantContext::id()])->id,
    ]));

    $this->deleteJson("/api/user-invitations/{$foreign->id}")->assertNotFound();
    expect($owner->tenant_id)->not->toBe($foreign->tenant_id);
});
