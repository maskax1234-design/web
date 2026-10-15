<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Medicine;
use App\Models\Prescription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrescriptionController extends HospitalResourceController
{
    protected string $model = Prescription::class;

    protected string $title = 'Prescription';

    protected array $with = ['patient', 'doctor', 'medicines'];

    protected array $rules = [
        'patient_id' => ['required', 'exists:patients,id'],
        'doctor_id' => ['required', 'exists:doctors,id'],
        'appointment_id' => ['nullable', 'exists:appointments,id'],
        'prescription_date' => ['required', 'date'],
        'instructions' => ['nullable', 'string'],
        'status' => ['required', 'string'],
    ];

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return [
            'medicines' => Medicine::query()
                ->where('quantity', '>=', 1)
                ->orderBy('name')
                ->get(),
            'appointments' => Appointment::query()
                ->with(['patient', 'doctor'])
                ->orderByDesc('appointment_date')
                ->get()
                ->mapWithKeys(fn (Appointment $appointment): array => [
                    $appointment->id => [
                        'patient_id' => $appointment->patient_id,
                        'doctor_id' => $appointment->doctor_id,
                        'patient_name' => trim(($appointment->patient?->first_name ?? '').' '.($appointment->patient?->last_name ?? '')),
                        'doctor_name' => trim(('Dr. '.($appointment->doctor?->first_name ?? '')).' '.($appointment->doctor?->last_name ?? '')),
                    ],
                ]),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());

        $prescription = DB::transaction(function () use ($validated): Prescription {
            $prescription = Prescription::create(Arr::except($validated, ['medicine_ids', 'medicine_details']));
            $this->syncMedicines($prescription, $validated['medicine_ids'], $validated['medicine_details']);

            return $prescription;
        });

        return redirect()->route('prescriptions.show', $prescription)->with('success', 'Prescription created.');
    }

    public function update(Request $request, string $record): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());
        $prescription = $this->find($record);

        DB::transaction(function () use ($prescription, $validated): void {
            $prescription->update(Arr::except($validated, ['medicine_ids', 'medicine_details']));
            $this->syncMedicines($prescription, $validated['medicine_ids'], $validated['medicine_details']);
        });

        return redirect()->route('prescriptions.show', $prescription)->with('success', 'Prescription updated.');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function validationRules(): array
    {
        return array_merge($this->rules, [
            'medicine_ids' => ['required', 'array', 'min:1'],
            'medicine_ids.*' => ['integer', 'exists:medicines,id'],
            'medicine_details' => ['required', 'array'],
            'medicine_details.*.dosage' => ['required', 'string'],
            'medicine_details.*.frequency' => ['required', 'string'],
            'medicine_details.*.duration' => ['required', 'string'],
            'medicine_details.*.quantity' => ['required', 'integer', 'min:1'],
            'medicine_details.*.instructions' => ['nullable', 'string'],
        ]);
    }

    /**
     * @param  array<int, int|string>  $medicineIds
     * @param  array<int, array{dosage: string, frequency: string, duration: string, quantity: int, instructions?: string|null}>  $medicineDetails
     */
    private function syncMedicines(Prescription $prescription, array $medicineIds, array $medicineDetails): void
    {
        $existingQuantities = [];

        foreach ($prescription->medicines()->get() as $existingMedicine) {
            $existingQuantities[$existingMedicine->id] = (int) ($existingMedicine->pivot->quantity ?? 0);
        }

        foreach ($existingQuantities as $medicineId => $previousQuantity) {
            if (! in_array($medicineId, $medicineIds, true)) {
                $medicine = Medicine::findOrFail($medicineId);
                $medicine->quantity += $previousQuantity;
                $medicine->save();
            }
        }

        $pivotData = [];

        foreach ($medicineIds as $medicineId) {
            $medicine = Medicine::findOrFail($medicineId);
            $details = $medicineDetails[$medicineId] ?? [];
            $quantity = (int) ($details['quantity'] ?? 1);
            $unitPrice = (float) ($medicine->unit_price ?? 0);
            $previousQuantity = $existingQuantities[$medicineId] ?? 0;
            $delta = $quantity - $previousQuantity;

            if ($quantity <= 0) {
                throw new \InvalidArgumentException('Prescription item quantity must be greater than 0.');
            }

            if ($unitPrice < 0) {
                throw new \InvalidArgumentException('Medicine unit price cannot be negative.');
            }

            if ($delta > 0 && $medicine->quantity < $delta) {
                throw ValidationException::withMessages([
                    'medicine_details.'.$medicineId.'.quantity' => 'Not enough stock available for '.$medicine->name.'.',
                ]);
            }

            if ($delta > 0) {
                $medicine->quantity -= $delta;
            } elseif ($delta < 0) {
                $medicine->quantity += abs($delta);
            }

            $medicine->save();

            $total = $quantity * $unitPrice;

            $pivotData[$medicineId] = [
                'dosage' => $details['dosage'] ?? '',
                'frequency' => $details['frequency'] ?? '',
                'duration' => $details['duration'] ?? '',
                'quantity' => $quantity,
                'instructions' => $details['instructions'] ?? null,
                'unit_price' => $unitPrice,
                'total' => $total,
            ];
        }

        $prescription->medicines()->sync($pivotData);
    }
}
