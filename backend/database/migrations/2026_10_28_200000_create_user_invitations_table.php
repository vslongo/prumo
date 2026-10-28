<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained();
            $table->string('name');
            $table->string('role', 20);
            // Só o hash do token; o link vai para o funcionário e nunca é guardado.
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->foreignUuid('invited_by_id')->constrained('users');
            $table->foreignUuid('accepted_user_id')->nullable()->constrained('users');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'accepted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_invitations');
    }
};
