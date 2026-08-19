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
        Schema::table('doctor_files', function (Blueprint $table) {
            $table->json('processing_metrics')->nullable()->after('processed_at');
            $table->text('processing_log')->nullable()->after('processing_metrics');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctor_files', function (Blueprint $table) {
            $table->dropColumn(['processing_metrics', 'processing_log']);
        });
    }
};
