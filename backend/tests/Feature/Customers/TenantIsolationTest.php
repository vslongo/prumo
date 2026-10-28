<?php

declare(strict_types=1);

use App\Modules\Customers\Models\Customer;

it('não lê clientes de outro tenant', function () {
    actingAsRole();
    $other = inOtherTenant(fn () => Customer::factory()->create(['name' => 'Cliente da outra']));

    $this->getJson("/api/customers/{$other->id}")->assertNotFound();
    $this->getJson('/api/customers')->assertJsonCount(0, 'data');
    $this->getJson('/api/customers?q=outra')->assertJsonCount(0, 'data');
});

it('ignora tenant_id enviado no cadastro', function () {
    $me = actingAsRole();
    $otherTenantId = inOtherTenant(fn () => Customer::factory()->create()->tenant_id);

    $id = $this->postJson('/api/customers', ['name' => 'Ana', 'tenant_id' => $otherTenantId])->assertCreated()->json('data.id');

    expect(Customer::findOrFail($id)->tenant_id)->toBe($me->tenant_id);
});
