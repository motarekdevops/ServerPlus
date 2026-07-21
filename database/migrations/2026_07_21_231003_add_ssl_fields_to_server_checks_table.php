<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('server_checks', function (Blueprint $table) {
            $table->string('domain')->nullable()->after('type');
            $table->timestamp('ssl_issued_at')->nullable()->after('domain');
            $table->timestamp('ssl_expires_at')->nullable()->after('ssl_issued_at');
            $table->timestamp('ssl_last_renewed_at')->nullable()->after('ssl_expires_at');
            $table->date('domain_registration_expires_at')->nullable()->after('ssl_last_renewed_at');
            $table->integer('alert_days_before')->default(15)->after('domain_registration_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('server_checks', function (Blueprint $table) {
            $table->dropColumn([
                'domain',
                'ssl_issued_at',
                'ssl_expires_at',
                'ssl_last_renewed_at',
                'domain_registration_expires_at',
                'alert_days_before',
            ]);
        });
    }
};
