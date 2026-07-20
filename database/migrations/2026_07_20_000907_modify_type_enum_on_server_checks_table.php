<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_checks_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['cpu', 'ram', 'disk', 'uptime', 'updates']);
            $table->float('warning_threshold')->default(70);
            $table->float('critical_threshold')->default(90);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_alerted_at')->nullable();
            $table->timestamps();
        });

        DB::statement('INSERT INTO server_checks_new SELECT * FROM server_checks');

        Schema::drop('server_checks');

        Schema::rename('server_checks_new', 'server_checks');
    }

    public function down(): void
    {
        Schema::create('server_checks_old', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['cpu', 'ram', 'disk', 'uptime']);
            $table->float('warning_threshold')->default(70);
            $table->float('critical_threshold')->default(90);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_alerted_at')->nullable();
            $table->timestamps();
        });

        DB::statement("INSERT INTO server_checks_old SELECT * FROM server_checks WHERE type != 'updates'");

        Schema::drop('server_checks');

        Schema::rename('server_checks_old', 'server_checks');
    }
};
