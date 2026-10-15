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
        Schema::table('pharmacy_stock_movements', function (Blueprint $table): void {
            $table->timestamp('reversed_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pharmacy_stock_movements', function (Blueprint $table): void {
            $table->dropIndex(['reversed_at']);
            $table->dropColumn('reversed_at');
        });
    }
};
