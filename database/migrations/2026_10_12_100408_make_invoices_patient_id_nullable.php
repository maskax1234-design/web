<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->foreignId('patient_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('invoices')->whereNull('patient_id')->exists()) {
            throw new RuntimeException('Cannot require a patient for every invoice while walk-in pharmacy sales exist.');
        }

        Schema::table('invoices', function (Blueprint $table): void {
            $table->foreignId('patient_id')->nullable(false)->change();
        });
    }
};
