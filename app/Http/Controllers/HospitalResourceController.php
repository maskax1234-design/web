<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

abstract class HospitalResourceController extends Controller
{
    /** @var class-string<Model> */
    protected string $model;

    protected string $title;

    /**
     * Validation rules keyed by field name.
     *
     * @var array<string, array<int, string>>
     */
    protected array $rules;

    /**
     * Relationships eager loaded for the index and show pages.
     *
     * @var array<int, string>
     */
    protected array $with = [];

    public function index(): View
    {
        return view($this->viewPath('index'), [
            'records' => $this->model::query()
                ->with($this->with)
                ->latest()
                ->paginate(12),
            'title' => $this->title,
            'route' => $this->routeName(),
        ]);
    }

    public function create(): View
    {
        return view($this->viewPath('create'), array_merge([
            'title' => $this->title,
            'route' => $this->routeName(),
            'record' => null,
            'selectOptions' => $this->selectOptions(),
        ], $this->formData()));
    }

    public function store(Request $request): RedirectResponse
    {
        $record = $this->model::create($request->validate($this->rules));

        return redirect()
            ->route($this->routeName().'.show',
                $record)
            ->with('success',
                $this->title.' created.');
    }

    public function show(string $record): View
    {
        $model = $this->find($record);

        return view($this->viewPath('show'), array_merge([
            'record' => $model,
            'title' => $this->title,
            'route' => $this->routeName(),
        ], $this->showData($model)));
    }

    public function edit(string $record): View
    {
        return view($this->viewPath('edit'), array_merge([
            'title' => $this->title,
            'route' => $this->routeName(),
            'record' => $this->find($record),
            'selectOptions' => $this->selectOptions(),
        ], $this->formData()));
    }

    public function update(Request $request, string $record): RedirectResponse
    {
        $model = $this->find($record);
        $model->update($request->validate($this->updateRules()));

        return redirect()
            ->route($this->routeName().'.show', $model)
            ->with('success', $this->title.' updated.');
    }

    public function destroy(string $record): RedirectResponse
    {
        $this->find($record)->delete();

        return redirect()
            ->route($this->routeName().'.index')
            ->with('success', $this->title.' removed.');
    }

    protected function find(string $id): Model
    {
        return $this->model::query()
            ->with($this->with)
            ->findOrFail($id);
    }

    protected function routeName(): string
    {
        return str($this->title)->plural()->kebab()->toString();
    }

    protected function viewPath(string $view): string
    {
        return $this->routeName().'.'.$view;
    }

    /**
     * Rules applied when updating an existing record.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function updateRules(): array
    {
        return $this->rules;
    }

    /**
     * Extra data handed to the create and edit forms.
     *
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return [];
    }

    /**
     * Extra data handed to the show page.
     *
     * @return array<string, mixed>
     */
    protected function showData(Model $record): array
    {
        return [];
    }

    /**
     * Foreign key dropdown options, limited to the fields this resource validates.
     *
     * @return array<string, Collection<int, string>>
     */
    protected function selectOptions(): array
    {
        $options = [];

        foreach (array_keys($this->rules) as $field) {
            if ($values = $this->optionsFor($field)) {
                $options[$field] = $values;
            }
        }

        return $options;
    }

    /**
     * @return Collection<int, string>|null
     */
    protected function optionsFor(string $field): ?Collection
    {
        return match ($field) {
            'patient_id' => Patient::query()
                ->orderBy('first_name')
                ->get()
                ->mapWithKeys(fn (Patient $patient): array => [$patient->id => $patient->first_name.' '.$patient->last_name.' ('.$patient->patient_number.')']),
            'doctor_id' => Doctor::query()
                ->orderBy('first_name')
                ->get()
                ->mapWithKeys(fn (Doctor $doctor): array => [$doctor->id => 'Dr. '.$doctor->first_name.' '.$doctor->last_name]),
            'department_id' => Department::query()->orderBy('name')->pluck('name', 'id'),
            'appointment_id' => Appointment::query()
                ->orderByDesc('appointment_date')
                ->get()
                ->mapWithKeys(fn (Appointment $appointment): array => [$appointment->id => $appointment->appointment_number]),
            'invoice_id' => Invoice::query()->orderByDesc('invoice_date')->pluck('invoice_number', 'id'),
            'prescription_id' => Prescription::query()->orderByDesc('prescription_date')->get()->mapWithKeys(fn (Prescription $prescription): array => [$prescription->id => $prescription->prescription_number.' - '.$prescription->patient?->first_name.' '.$prescription->patient?->last_name]),
            'received_by' => User::query()->orderBy('name')->pluck('name', 'id'),
            default => null,
        };
    }
}
