<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // NFS-e emitida na Sefin Nacional (ADR 0010). Sem soft delete: nota emitida nunca é apagada.
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained();
            $table->foreignUuid('service_order_id')->constrained();
            $table->foreignUuid('requested_by')->constrained('users');
            $table->string('status', 20)->default('pending');
            $table->string('environment', 20);
            // Série + número da DPS: reservados antes do envio e reaproveitados nas novas tentativas.
            $table->string('dps_series', 5);
            $table->unsignedBigInteger('dps_number');
            $table->string('dps_id', 45);
            $table->string('access_key', 50)->nullable();
            $table->string('nfse_number', 20)->nullable();
            $table->integer('amount_cents');
            $table->boolean('include_products');
            $table->string('xml_key')->nullable();
            $table->string('pdf_key')->nullable();
            $table->string('error_code', 20)->nullable();
            $table->string('error_message', 1000)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->string('cancel_reason', 255)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'dps_series', 'dps_number']);
            $table->index(['tenant_id', 'service_order_id']);
            $table->index(['tenant_id', 'status']);
        });

        // No máximo uma nota não cancelada por OS.
        DB::statement("create unique index invoices_one_active_per_order on invoices (service_order_id) where status <> 'canceled'");
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
