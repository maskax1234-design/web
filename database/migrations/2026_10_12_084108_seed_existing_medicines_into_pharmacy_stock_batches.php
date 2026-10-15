<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('medicines')
            ->select(['id', 'quantity', 'expiry_date'])
            ->orderBy('id')
            ->chunkById(500, function ($medicines): void {
                foreach ($medicines as $medicine) {
                    $batchId = DB::table('pharmacy_stock_batches')->insertGetId([
                        'medicine_id' => $medicine->id,
                        'batch_number' => 'OPENING-'.$medicine->id,
                        'quantity_received' => $medicine->quantity,
                        'quantity_available' => $medicine->quantity,
                        'expiry_date' => $medicine->expiry_date,
                        'received_by' => null,
                        'notes' => 'Opening stock imported from the existing medicine quantity.',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    if ((int) $medicine->quantity > 0) {
                        DB::table('pharmacy_stock_movements')->insert([
                            'medicine_id' => $medicine->id,
                            'batch_id' => $batchId,
                            'movement_type' => 'OPENING',
                            'quantity' => $medicine->quantity,
                            'notes' => 'Opening stock migration.',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Opening batches are the basis of the inventory audit trail and may now contain sale movements.
    }
};
