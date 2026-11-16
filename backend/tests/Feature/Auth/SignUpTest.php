<?php

declare(strict_types=1);

use App\Modules\Auth\Actions\SignUp;
use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Models\User;
use App\Modules\Tenancy\Enums\TenantStatus;
use App\Modules\Tenancy\Events\TenantCreated;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Event;

beforeEach(fn () => $this->withHeader('Origin', 'http://localhost:5173'));

$payload = fn (array $overrides = []) => [
    'workshop_name' => 'Auto Center Silva',
    'name' => 'Carlos Silva',
    'email' => 'Carlos@Silva.test',
    'password' => 'senha-forte-123',
    'accept_terms' => true,
    ...$overrides,
];

it('cria a oficina em trial com o dono e já deixa logado', function () use ($payload) {
    Event::fake([TenantCreated::class]);
    $this->freezeSecond();

    $this->postJson('/api/signup', $payload())
        ->assertCreated()
        ->assertJsonPath('data.role', 'owner')
        ->assertJsonPath('data.workshop', 'Auto Center Silva');

    $tenant = Tenant::sole();
    expect($tenant->status)->toBe(TenantStatus::Trialing)
        ->and($tenant->trial_ends_at?->equalTo(now()->addDays(14)))->toBeTrue();

    $user = User::sole();
    expect($user->tenant_id)->toBe($tenant->id)
        ->and($user->role)->toBe(UserRole::Owner)
        ->and($user->email)->toBe('carlos@silva.test')
        ->and($user->terms_accepted_at?->equalTo(now()))->toBeTrue()
        ->and($user->terms_version)->toBe(SignUp::TERMS_VERSION);

    Event::assertDispatched(TenantCreated::class, fn ($e) => $e->tenantId === $tenant->id);
    $this->assertAuthenticatedAs($user, 'web');
});

it('recusa e-mail já cadastrado, senha curta e campos faltando sem criar nada', function () use ($payload) {
    User::factory()->create(['email' => 'carlos@silva.test']);

    $this->postJson('/api/signup', $payload())->assertUnprocessable()->assertJsonValidationErrors('email');
    $this->postJson('/api/signup', $payload(['email' => 'novo@silva.test', 'password' => '123']))->assertUnprocessable()->assertJsonValidationErrors('password');
    $this->postJson('/api/signup', [])->assertUnprocessable()->assertJsonValidationErrors(['workshop_name', 'name', 'email', 'password', 'accept_terms']);
    $this->postJson('/api/signup', $payload(['email' => 'novo@silva.test', 'accept_terms' => false]))->assertUnprocessable()->assertJsonValidationErrors('accept_terms');

    expect(Tenant::count())->toBe(1);
});

it('limita cadastros por IP', function () use ($payload) {
    foreach (range(1, 5) as $i) {
        $this->postJson('/api/signup', $payload(['email' => "dono{$i}@oficina.test"]))->assertCreated();
        auth()->guard('web')->logout();
    }

    $this->postJson('/api/signup', $payload(['email' => 'dono6@oficina.test']))->assertTooManyRequests();
});
