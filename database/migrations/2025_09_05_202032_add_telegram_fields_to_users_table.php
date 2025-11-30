<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTelegramFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('telegram_id')->nullable();
            $table->string('telegram_username')->nullable();
            $table->string('telegram_first_name')->nullable();
            $table->string('telegram_last_name')->nullable();
            $table->boolean('is_bot')->default(false);
            $table->boolean('is_premium')->default(false);
            $table->boolean('heh')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('telegram_id');
            $table->dropColumn('telegram_username');
            $table->dropColumn('telegram_first_name');
            $table->dropColumn('telegram_last_name');
            $table->dropColumn('is_bot');
            $table->dropColumn('is_premium');
            $table->dropColumn('is_premium');
        });
    }
};
