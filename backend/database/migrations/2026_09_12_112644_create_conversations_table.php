<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('provider', 50);
            $table->string('provider_conversation_id')->nullable();
            $table->string('subject')->nullable();
            $table->string('status', 30)->default('open');
            $table->boolean('is_read')->default(false);
            $table->timestamp('last_message_at')->nullable();
            $table->json('labels')->nullable();
            $table->timestamps();

            $table->unique([
                'user_id',
                'provider',
                'provider_conversation_id',
            ]);

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'last_message_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
