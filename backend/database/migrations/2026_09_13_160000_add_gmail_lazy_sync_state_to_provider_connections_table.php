<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provider_connections', function (Blueprint $table): void {
            $table->text('gmail_sync_page_token')->nullable();
            $table->string('gmail_sync_snapshot_history_id')->nullable();
            $table->boolean('gmail_sync_completed')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('provider_connections', function (Blueprint $table): void {
            $table->dropColumn([
                'gmail_sync_page_token',
                'gmail_sync_snapshot_history_id',
                'gmail_sync_completed',
            ]);
        });
    }
};
