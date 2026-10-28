<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Log de WhatsApp enviado pelo sistema. Sem soft delete (CLAUDE.md §9): é registro do que saiu. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained();
            $table->foreignUuid('customer_id')->nullable()->constrained();
            $table->string('template', 60);
            $table->string('status', 20);
            $table->text('to'); // telefone E.164, criptografado
            $table->jsonb('params');
            // De qual número saiu (ADR 0003: hoje o do Prumo; no futuro o da oficina).
            $table->string('sender_phone_number_id', 40);
            $table->string('subject_type', 40);
            $table->uuid('subject_id');
            // Evita mandar a mesma mensagem duas vezes se o evento chegar repetido.
            $table->string('idempotency_key', 120)->unique();
            $table->string('provider_message_id', 120)->nullable()->unique();
            $table->string('error', 500)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'subject_type', 'subject_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
