@php
    $roles = ['Admin', 'Doctor', 'Nurse', 'Receptionist', 'Accountant', 'Lab Technician', 'Pharmacist', 'Pharmacy Technician', 'Cashier'];
    if (! auth()->user()->isAdmin()) {
        $roles = array_values(array_filter($roles, fn (string $role): bool => strtolower($role) !== 'admin'));
    }
    $statuses = ['Active', 'Inactive', 'Suspended'];
@endphp

<x-form.input name="name" label="Full Name" :value="$record?->name" :required="true" />
<x-form.input name="email" label="Email" type="email" :value="$record?->email" :required="true" />
<x-form.input name="phone" label="Phone" type="tel" :value="$record?->phone" />
<x-form.select name="role" label="Role" :options="$roles" :value="$record?->role" :required="true" />
<x-form.select name="status" label="Status" :options="$statuses" :value="$record?->status" :required="true" />

@php
    $selectedModules = old('module_access', $record?->module_access ?? []);
    $selectedModules = is_array($selectedModules) ? $selectedModules : [];
@endphp
<div class="col-12 mt-2">
    <fieldset class="border rounded p-3">
        <legend class="float-none w-auto px-2 fs-6 fw-semibold">Module Access Permissions</legend>
        <p class="text-muted small">Users can create, edit, and remove records in their assigned modules. Non-administrators can only grant modules they already have and cannot assign the Admin role.</p>
        @foreach ($moduleGroups as $group => $modules)
            <div class="mb-3">
                <h3 class="h6">{{ $group }}</h3>
                <div class="row">
                    @foreach ($modules as $module => $details)
                        <div class="col-sm-6 col-lg-4 form-check mb-2">
                            <input
                                class="form-check-input ms-0 me-2"
                                type="checkbox"
                                name="module_access[]"
                                id="module-{{ $module }}"
                                value="{{ $module }}"
                                @checked(in_array($module, $selectedModules, true))
                            >
                            <label class="form-check-label" for="module-{{ $module }}">{{ $details['label'] }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
        @error('module_access')
            <div class="text-danger small">{{ $message }}</div>
        @enderror
        @error('module_access.*')
            <div class="text-danger small">{{ $message }}</div>
        @enderror
    </fieldset>
</div>

<div class="col-md-6 mb-3">
    <label class="form-label" for="field-password">
        Password
        @if (! $record)
            <span class="text-danger">*</span>
        @else
            <span class="text-muted small">(leave blank to keep current)</span>
        @endif
    </label>
    <input
        type="password"
        name="password"
        id="field-password"
        value=""
        @required(! $record)
        autocomplete="new-password"
        class="form-control @error('password') is-invalid @enderror"
    >
    @error('password')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>