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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained();
            $table->foreignUuid('customer_id')->constrained();
            $table->string('plate_normalized', 7);
            $table->string('vin', 17)->nullable();
            $table->string('brand', 60)->nullable();
            $table->string('model', 80)->nullable();
            $table->smallInteger('year')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'customer_id']);
        });

        DB::statement('CREATE UNIQUE INDEX vehicles_tenant_plate_unique ON vehicles (tenant_id, plate_normalized) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
