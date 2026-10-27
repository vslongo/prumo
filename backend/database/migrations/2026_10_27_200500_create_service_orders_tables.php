<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained();
            $table->unsignedInteger('number');
            $table->string('origin', 20);
            // um orçamento gera no máximo uma OS
            $table->foreignUuid('quote_id')->nullable()->unique()->constrained();
            $table->foreignUuid('customer_id')->constrained();
            $table->foreignUuid('vehicle_id')->constrained();
            $table->foreignUuid('opened_by')->constrained('users');
            $table->string('status', 20)->default('open');
            $table->integer('mileage_in')->nullable();
            $table->integer('mileage_out')->nullable();
            $table->text('notes')->nullable();
            $table->integer('total_cents')->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'number']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'vehicle_id']);
        });

        Schema::create('service_order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained();
            $table->foreignUuid('service_order_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->foreignUuid('quote_item_id')->nullable()->constrained();
            $table->string('kind', 20);
            $table->string('description');
            $table->foreignUuid('service_type_id')->nullable()->constrained();
            $table->foreignUuid('technician_id')->constrained('users');
            $table->decimal('quantity', 10, 3);
            $table->integer('unit_price_cents');
            $table->integer('total_cents');
            $table->jsonb('fields')->default('{}');
            $table->jsonb('fields_schema_snapshot')->default('[]');
            $table->timestamp('done_at')->nullable();
            $table->foreignUuid('done_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['tenant_id', 'service_order_id']);
            $table->index(['tenant_id', 'technician_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_order_items');
        Schema::dropIfExists('service_orders');
    }
};
