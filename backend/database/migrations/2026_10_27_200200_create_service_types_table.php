<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // null = tipo padrão do sistema, somente leitura para as oficinas
            $table->foreignUuid('tenant_id')->nullable()->constrained();
            $table->string('name');
            $table->jsonb('fields_schema')->default('[]');
            $table->integer('default_price_cents')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_types');
    }
};
