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
        if (! Schema::hasColumn('prescriptions', 'appointment_id')) {
            Schema::table('prescriptions', function (Blueprint $table): void {
                $table->foreignId('appointment_id')->nullable()->after('doctor_id')->constrained()->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('prescriptions', 'appointment_id')) {
            Schema::table('prescriptions', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('appointment_id');
            });
        }
    }
};
