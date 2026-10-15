<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class AppointmentController extends HospitalResourceController
{
    protected string $model = Appointment::class;

    protected string $title = 'Appointment';

    protected array $with = ['patient', 'doctor', 'department'];

    protected array $rules = [
        'patient_id' => ['required', 'exists:patients,id'],
        'doctor_id' => ['required', 'exists:doctors,id'],
        'department_id' => ['required', 'exists:departments,id'],
        'appointment_date' => ['required', 'date'],
        'appointment_time' => ['required'],
        'reason' => ['required', 'string'],
        'status' => ['required', 'string'],
        'notes' => ['nullable', 'string'],
    ];

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules);

        if ($this->hasSchedulingConflict((int) $validated['doctor_id'], $validated['appointment_date'], $validated['appointment_time'])) {
            return back()->withInput()->with('error', 'This doctor is already booked during this time. Each appointment takes 25 minutes. Please choose another time.');
        }

        $appointment = Appointment::create($validated);

        return redirect()->route('appointments.show', $appointment)->with('success', 'Appointment created.');
    }

    public function update(Request $request, string $record): RedirectResponse
    {
        $validated = $request->validate($this->rules);
        $appointment = $this->find($record);

        if ($this->hasSchedulingConflict((int) $validated['doctor_id'], $validated['appointment_date'], $validated['appointment_time'], $appointment)) {
            return back()->withInput()->with('error', 'This doctor is already booked during this time. Each appointment takes 25 minutes. Please choose another time.');
        }

        $appointment->update($validated);

        return redirect()->route('appointments.show', $appointment)->with('success', 'Appointment updated.');
    }

    private function hasSchedulingConflict(int $doctorId, string $appointmentDate, string $appointmentTime, ?Appointment $ignoreAppointment = null): bool
    {
        $newStart = Date::parse($appointmentDate.' '.$appointmentTime);
        $newEnd = $newStart->copy()->addMinutes(25);

        $query = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $appointmentDate);

        if ($ignoreAppointment) {
            $query->whereKeyNot($ignoreAppointment->id);
        }

        foreach ($query->get() as $existingAppointment) {
            $existingStart = Date::parse($existingAppointment->appointment_date->toDateString().' '.$existingAppointment->appointment_time);
            $existingEnd = $existingStart->copy()->addMinutes(25);

            if ($newStart->lt($existingEnd) && $newEnd->gt($existingStart)) {
                return true;
            }
        }

        return false;
    }
}
