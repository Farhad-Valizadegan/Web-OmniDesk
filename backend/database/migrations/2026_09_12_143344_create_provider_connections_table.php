<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_connections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('provider', 50);
            $table->string('provider_account_id')->nullable();
            $table->string('account_email')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamps();

            $table->unique(
                ['user_id', 'provider', 'provider_account_id'],
                'provider_connections_user_provider_account_unique'
            );

            $table->index(
                ['user_id', 'provider', 'status'],
                'provider_connections_user_provider_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_connections');
    }
};
