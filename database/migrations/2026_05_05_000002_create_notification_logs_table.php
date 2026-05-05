<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 100)->index();
            $table->string('scope_key', 150)->index();
            $table->date('sent_date')->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['type', 'scope_key', 'sent_date'], 'notification_logs_unique_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
