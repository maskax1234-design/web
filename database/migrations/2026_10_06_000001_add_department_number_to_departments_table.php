<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table): void {
            $table->string('department_number')->nullable()->unique()->after('id');
        });

        $departments = DB::table('departments')->whereNull('department_number')->get();

        foreach ($departments as $department) {
            $number = 'DEP-'.str_pad((string) ((int) ($department->id ?? 0)), 4, '0', STR_PAD_LEFT);

            if (DB::table('departments')->where('department_number', $number)->exists()) {
                $number = 'DEP-'.str_pad((string) ((DB::table('departments')->count() + 1)), 4, '0', STR_PAD_LEFT);
            }

            DB::table('departments')
                ->where('id', $department->id)
                ->update(['department_number' => $number]);
        }
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table): void {
            $table->dropUnique(['department_number']);
            $table->dropColumn('department_number');
        });
    }
};