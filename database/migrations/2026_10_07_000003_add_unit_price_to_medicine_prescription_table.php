<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('medicine_prescription', 'unit_price')) {
            Schema::table('medicine_prescription', function (Blueprint $table): void {
                $table->decimal('unit_price', 12, 2)->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('medicine_prescription', 'unit_price')) {
            Schema::table('medicine_prescription', function (Blueprint $table): void {
                $table->dropColumn('unit_price');
            });
        }
    }
};