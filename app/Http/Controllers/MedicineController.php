<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Services\PharmacyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicineController extends HospitalResourceController
{
    public function __construct(private readonly PharmacyService $pharmacy) {}

    protected string $model = Medicine::class;

    protected string $title = 'Medicine';

    protected array $with = ['prescriptions'];

    protected array $rules = [
        'name' => ['required', 'string'],
        'generic_name' => ['nullable', 'string'],
        'category' => ['nullable', 'string'],
        'manufacturer' => ['nullable', 'string'],
        'dosage_form' => ['required', 'string'],
        'strength' => ['nullable', 'string'],
        'quantity' => ['required', 'integer', 'min:0', 'max:4294967295'],
        'unit_price' => ['required', 'numeric', 'min:0'],
        'expiry_date' => ['required', 'date'],
        'status' => ['required', 'string'],
    ];

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules);
        $user = $request->user();

        $medicine = DB::transaction(function () use ($validated, $user): Medicine {
            $medicine = Medicine::create($validated);
            $this->pharmacy->adjustQuantity($medicine, (int) $validated['quantity'], $user);

            return $medicine;
        }, 3);

        return redirect()->route('medicines.show', $medicine)->with('success', 'Medicine created.');
    }

    public function update(Request $request, string $record): RedirectResponse
    {
        $validated = $request->validate($this->rules);
        $user = $request->user();

        $medicine = DB::transaction(function () use ($validated, $record, $user): Medicine {
            $medicine = Medicine::query()->lockForUpdate()->findOrFail($record);
            $medicine->fill($validated);
            $medicine->save();
            $this->pharmacy->adjustQuantity($medicine, (int) $validated['quantity'], $user);

            return $medicine;
        }, 3);

        return redirect()->route('medicines.show', $medicine)->with('success', 'Medicine updated.');
    }

    public function destroy(string $record): RedirectResponse
    {
        $medicine = Medicine::query()->findOrFail($record);

        if ($medicine->stockMovements()->exists()) {
            return back()->with('error', 'This medicine has inventory history and cannot be deleted. Set its status to Inactive instead.');
        }

        DB::transaction(function () use ($medicine): void {
            $medicine->stockBatches()->delete();
            $medicine->delete();
        });

        return redirect()->route('medicines.index')->with('success', 'Medicine removed.');
    }
}
