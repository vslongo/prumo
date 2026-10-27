<?php

declare(strict_types=1);

use App\Modules\Auth\Models\User;

beforeEach(fn () => $this->withHeader('Origin', 'http://localhost:5173'));

it('faz login com e-mail e senha', function () {
    $user = User::factory()->create(['email' => 'dono@oficina.test']);

    $this->postJson('/api/login', ['email' => 'dono@oficina.test', 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

it('recusa senha errada e usuário inativo', function () {
    User::factory()->create(['email' => 'a@oficina.test']);
    User::factory()->create(['email' => 'b@oficina.test', 'is_active' => false]);

    $this->postJson('/api/login', ['email' => 'a@oficina.test', 'password' => 'errada'])->assertUnprocessable();
    $this->postJson('/api/login', ['email' => 'b@oficina.test', 'password' => 'password'])->assertUnprocessable();
});

it('exige autenticação nas rotas da oficina', function () {
    $this->getJson('/api/me')->assertUnauthorized();
    $this->getJson('/api/service-orders')->assertUnauthorized();
});
