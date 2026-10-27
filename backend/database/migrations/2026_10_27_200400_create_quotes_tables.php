<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained();
            $table->foreignUuid('customer_id')->constrained();
            $table->foreignUuid('vehicle_id')->constrained();
            $table->foreignUuid('created_by')->constrained('users');
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->integer('mileage')->nullable();
            $table->text('notes')->nullable();
            $table->integer('total_cents')->default(0);
            $table->string('token_hash', 64)->nullable()->unique();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'vehicle_id']);
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained();
            $table->foreignUuid('quote_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('kind', 20);
            $table->string('description');
            $table->foreignUuid('service_type_id')->nullable()->constrained();
            $table->foreignUuid('technician_id')->constrained('users');
            $table->decimal('quantity', 10, 3);
            $table->integer('unit_price_cents');
            $table->integer('total_cents');
            $table->jsonb('fields')->default('{}');
            $table->jsonb('fields_schema_snapshot')->default('[]');
            $table->timestamps();

            $table->index(['tenant_id', 'quote_id']);
        });

        // Registro imutável da aprovação: sem update, sem delete, sem soft delete.
        // Sem timestamps de propósito: `approved_at` é o único carimbo e nunca muda.
        Schema::create('quote_approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained();
            $table->foreignUuid('quote_id')->unique()->constrained();
            $table->unsignedInteger('quote_version');
            $table->string('signer_name');
            $table->foreignUuid('signature_media_id')->constrained('media');
            $table->string('signature_key');
            $table->jsonb('approved_item_ids');
            $table->string('ip', 45);
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('approved_at');

            $table->index(['tenant_id', 'quote_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_approvals');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
    }
};
