<?php

declare(strict_types=1);

use App\Modules\Auth\Models\User;
use App\Modules\Auth\Notifications\ResetPasswordNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

beforeEach(fn () => $this->withHeader('Origin', 'http://localhost:5173'));

it('manda o link por e-mail, pela fila, apontando para o app e sem o e-mail na URL', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'dono@oficina.test']);

    $this->postJson('/api/password/forgot', ['email' => ' Dono@Oficina.test '])->assertNoContent();

    Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $n) use ($user) {
        $mail = $n->toMail($user);

        return $n instanceof ShouldQueue
            && str_starts_with($n->url(), 'http://localhost:5173/redefinir-senha/')
            && ! str_contains($n->url(), 'oficina.test')
            && $mail->actionUrl === $n->url()
            && $mail->subject === 'Criar uma senha nova no Prumo';
    });
});

it('responde igual para e-mail sem cadastro e para usuário desativado, sem mandar nada', function () {
    Notification::fake();
    User::factory()->create(['email' => 'saiu@oficina.test', 'is_active' => false]);

    $this->postJson('/api/password/forgot', ['email' => 'ninguem@oficina.test'])->assertNoContent();
    $this->postJson('/api/password/forgot', ['email' => 'saiu@oficina.test'])->assertNoContent();

    Notification::assertNothingSent();
});

it('troca a senha com o token, derruba sessões e tokens e não abre sessão', function () {
    $user = User::factory()->create(['email' => 'dono@oficina.test']);
    $user->createToken('app');
    DB::table('sessions')->insert(['id' => 'sessao-antiga', 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->getTimestamp()]);
    $token = Password::createToken($user);

    $this->postJson('/api/password/reset', ['email' => 'dono@oficina.test', 'token' => $token, 'password' => 'senha-nova-123'])->assertNoContent();

    expect(Hash::check('senha-nova-123', $user->refresh()->password))->toBeTrue()
        ->and($user->tokens()->count())->toBe(0)
        ->and(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
    $this->assertGuest('web');

    // Uso único: o mesmo token não vale de novo.
    $this->postJson('/api/password/reset', ['email' => 'dono@oficina.test', 'token' => $token, 'password' => 'outra-senha-123'])
        ->assertUnprocessable()->assertJsonValidationErrors('token');
});

it('recusa token errado, vencido, e-mail de outra conta, usuário desativado e senha curta', function () {
    $user = User::factory()->create(['email' => 'dono@oficina.test']);
    $other = User::factory()->create(['email' => 'outro@oficina.test']);
    $token = Password::createToken($user);
    $reset = fn (array $overrides = []) => $this->postJson('/api/password/reset', ['email' => 'dono@oficina.test', 'token' => $token, 'password' => 'senha-nova-123', ...$overrides]);

    $reset(['token' => 'token-errado'])->assertUnprocessable()->assertJsonValidationErrors('token');
    $reset(['email' => $other->email])->assertUnprocessable()->assertJsonValidationErrors('token');
    $reset(['password' => '123'])->assertUnprocessable()->assertJsonValidationErrors('password');

    $user->forceFill(['is_active' => false])->save();
    $reset()->assertUnprocessable()->assertJsonValidationErrors('token');
    $user->forceFill(['is_active' => true])->save();

    $this->travel(61)->minutes();
    $reset()->assertUnprocessable()->assertJsonValidationErrors('token');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

it('limita pedidos por IP', function () {
    Notification::fake();
    foreach (range(1, 5) as $i) {
        $this->postJson('/api/password/forgot', ['email' => "a{$i}@oficina.test"])->assertNoContent();
    }

    $this->postJson('/api/password/forgot', ['email' => 'a6@oficina.test'])->assertTooManyRequests();
});
