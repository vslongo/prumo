<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Uma assinatura por oficina. CPF/CNPJ não fica aqui: vai só para o Asaas (cliente de lá).
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->unique()->constrained();
            $table->string('plan', 20);
            $table->string('status', 20)->default('pending');
            $table->string('asaas_customer_id', 64)->nullable();
            $table->string('asaas_subscription_id', 64)->nullable()->unique();
            $table->string('invoice_url', 500)->nullable();
            $table->string('error', 500)->nullable();
            $table->date('paid_until')->nullable();
            $table->timestamp('past_due_since')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // Tabela de sistema (sem tenant_id): só garante que cada evento do Asaas é processado uma vez.
        Schema::create('billing_webhook_events', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->string('event', 60);
            $table->timestamp('processed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_webhook_events');
        Schema::dropIfExists('subscriptions');
    }
};
