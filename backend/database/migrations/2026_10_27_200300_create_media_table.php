<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained();
            $table->string('owner_type', 40);
            $table->uuid('owner_id');
            $table->string('r2_key');
            $table->string('mime', 50);
            $table->integer('size');
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->index(['tenant_id', 'owner_type', 'owner_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
