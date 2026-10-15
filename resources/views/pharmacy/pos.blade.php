<x-layout :title="$editingInvoice ? 'Edit Sale '.$editingInvoice->invoice_number : 'Pharmacy POS'">
    <div class="container-fluid pharmacy-pos">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">{{ $editingInvoice ? 'Edit sale '.$editingInvoice->invoice_number : 'Pharmacy Point of Sale' }}</h1>
                <p class="text-muted mb-0">{{ $editingInvoice ? 'Only unpaid sales can be edited. All inventory changes are audited.' : 'Scan or search medicines, select a patient, and complete a sale.' }}</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a class="btn btn-outline-primary" href="{{ route('pharmacy.sales.index') }}"><i class="bi bi-clock-history me-1"></i>Sales history</a>
                <a class="btn btn-outline-primary" href="{{ route('pharmacy.reports.daily') }}"><i class="bi bi-clipboard-data me-1"></i>Daily report</a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>{{ $editingInvoice ? 'Sale could not be updated.' : 'Sale could not be completed.' }}</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-lg-6">
                <div class="card border-warning h-100">
                    <div class="card-header bg-warning-subtle fw-semibold"><i class="bi bi-exclamation-triangle me-2"></i>Low stock</div>
                    <div class="card-body py-2">
                        @forelse ($lowStock as $medicine)
                            <div class="d-flex justify-content-between border-bottom py-1">
                                <span>{{ $medicine->name }} <small class="text-muted">({{ $medicine->medicine_code }})</small></span>
                                <span class="badge text-bg-warning">{{ $medicine->available_stock ?? 0 }} available</span>
                            </div>
                        @empty
                            <span class="text-muted">No low-stock medicines.</span>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-info h-100">
                    <div class="card-header bg-info-subtle fw-semibold"><i class="bi bi-calendar2-event me-2"></i>Expiring within 90 days</div>
                    <div class="card-body py-2">
                        @forelse ($expiringBatches as $batch)
                            <div class="d-flex justify-content-between border-bottom py-1 gap-2">
                                <span>{{ $batch->medicine?->name }} <small class="text-muted">{{ $batch->batch_number }}</small></span>
                                <span class="text-nowrap">{{ $batch->expiry_date?->format('d M Y') }} · {{ $batch->quantity_available }} units</span>
                            </div>
                        @empty
                            <span class="text-muted">No batches approaching expiry.</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="pos-grid">
            <section class="card shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-3">Choose medicine</h2>
                    <div class="row g-2">
                        <div class="col-sm-8">
                            <label class="visually-hidden" for="medicine-search">Search or scan barcode</label>
                            <input id="medicine-search" class="form-control" type="search" autocomplete="off" placeholder="Scan barcode or search name / code">
                        </div>
                        <div class="col-sm-4">
                            <label class="visually-hidden" for="medicine-category">Category</label>
                            <select id="medicine-category" class="form-select">
                                <option value="">All categories</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}">{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="medicine-search-message" class="small text-muted mb-2" aria-live="polite">Loading medicines…</div>
                    <div id="medicine-results" class="medicine-results"></div>
                </div>
            </section>

            <section class="card shadow-sm">
                <form id="sale-form" method="POST" action="{{ $editingInvoice ? route('pharmacy.sales.update', $editingInvoice) : route('pharmacy.sales.store') }}">
                    @csrf
                    @if ($editingInvoice)
                        @method('PUT')
                    @else
                        <input type="hidden" name="pos_token" value="{{ old('pos_token', $transactionToken) }}">
                    @endif
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0">Sale cart</h2>
                        <button id="clear-cart" class="btn btn-sm btn-outline-secondary" type="button">Clear cart</button>
                    </div>
                    <div class="card-body">
                        <label for="patient-search" class="form-label fw-semibold">Patient <span class="text-muted fw-normal">(optional)</span></label>
                        <div class="input-group">
                            <input id="patient-search" class="form-control" type="search" autocomplete="off" value="{{ $oldPatient ? trim($oldPatient->first_name.' '.$oldPatient->last_name) : '' }}" placeholder="Search patient name or patient number">
                            <button id="clear-patient" class="btn btn-outline-secondary" type="button">Clear</button>
                        </div>
                        <input type="hidden" name="patient_id" id="patient_id" value="{{ old('patient_id', $editingInvoice?->patient_id) }}">
                        <div id="patient-results" class="list-group mt-1 mb-2"></div>
                        <div id="selected-patient" class="form-text mb-3" aria-live="polite">
                            @if ($oldPatient)
                                Selected: {{ trim($oldPatient->first_name.' '.$oldPatient->last_name) }} · {{ $oldPatient->patient_number }}.
                            @else
                                Walk-in / over-the-counter sale. Select a patient only if you want to link one.
                            @endif
                        </div>

                        <div id="cart-empty" class="empty-cart text-center text-muted py-4">No medicines added yet.</div>
                        <div id="cart-items" class="cart-items"></div>

                        <hr>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label" for="discount">Discount amount</label>
                                <input id="discount" name="discount" type="number" min="0" step="0.01" class="form-control" value="{{ old('discount', $editingInvoice?->discount ?? '0.00') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="tax_percent">Tax (%)</label>
                                <input id="tax_percent" name="tax_percent" type="number" min="0" max="999.99" step="0.01" class="form-control" value="{{ old('tax_percent', $editingInvoice?->tax_percent ?? '0.00') }}">
                            </div>
                        </div>

                        <div class="financial-summary mt-3">
                            <div><span>Total medicine quantity</span><strong id="quantity-display">0</strong></div>
                            <div><span>Subtotal</span><strong id="subtotal-display">$0.00</strong></div>
                            <div><span>Discount</span><strong id="discount-display">− $0.00</strong></div>
                            <div><span>Tax</span><strong id="tax-display">$0.00</strong></div>
                            <div class="grand-total"><span>Grand total</span><strong id="total-display">$0.00</strong></div>
                            <div><span>Balance due</span><strong id="due-display">$0.00</strong></div>
                        </div>

                        @if ($editingInvoice)
                            <input id="amount_paid" type="hidden" value="0">
                            <button id="pay-full" type="button" class="d-none">Receive full amount</button>
                        @else
                        <div class="row g-2 mt-2">
                            <div class="col-sm-6">
                                <label class="form-label" for="amount_paid">Amount received</label>
                                <input id="amount_paid" name="amount_paid" type="number" min="0" step="0.01" class="form-control" value="{{ old('amount_paid', '0.00') }}">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label" for="payment_method">Payment method</label>
                                <select id="payment_method" name="payment_method" class="form-select">
                                    <option value="">Not paid / select method</option>
                                    @foreach (['Cash', 'Card', 'Bank Transfer', 'Mobile Money', 'Insurance', 'Cheque'] as $method)
                                        <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ $method }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <button id="pay-full" type="button" class="btn btn-link btn-sm px-0">Receive full amount</button>
                        @endif

                        <button id="complete-sale" type="submit" class="btn btn-primary btn-lg w-100 mt-2">
                            <i class="bi bi-check2-circle me-2"></i>{{ $editingInvoice ? 'Save Sale Changes' : 'Complete Sale' }}
                        </button>
                        <div class="form-text text-center mt-2">Prices, stock, discounts, tax and balance are rechecked by the server.</div>
                    </div>
                </form>
            </section>
        </div>

    </div>

    <style>
        .pharmacy-pos .pos-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(360px, 0.85fr);
            gap: 1rem;
            align-items: start;
        }
        .medicine-results { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: .75rem; }
        .medicine-card { border: 1px solid #dee2e6; border-radius: .65rem; padding: .85rem; min-width: 0; }
        .medicine-card h3 { font-size: .95rem; margin-bottom: .15rem; overflow-wrap: anywhere; }
        .medicine-card .medicine-code { color: #6c757d; font-size: .78rem; }
        .medicine-card .medicine-extra { color: #6c757d; min-height: 1.25rem; font-size: .8rem; }
        .cart-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .5rem; padding: .75rem 0; border-bottom: 1px solid #e9ecef; }
        .cart-row-title { font-weight: 600; overflow-wrap: anywhere; }
        .cart-controls { display: flex; gap: .25rem; align-items: center; }
        .cart-controls input { width: 4.25rem; text-align: center; }
        .financial-summary { display: grid; gap: .4rem; }
        .financial-summary > div { display: flex; justify-content: space-between; gap: 1rem; }
        .financial-summary .grand-total { border-top: 1px solid #ced4da; padding-top: .55rem; font-size: 1.15rem; font-weight: 700; }
        @media (max-width: 900px) { .pharmacy-pos .pos-grid { grid-template-columns: 1fr; } }
        @media (max-width: 575px) { .medicine-results { grid-template-columns: 1fr; } }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('medicine-search');
            const patientSearch = document.getElementById('patient-search');
            const patientResults = document.getElementById('patient-results');
            const patientIdInput = document.getElementById('patient_id');
            const selectedPatient = document.getElementById('selected-patient');
            const clearPatientButton = document.getElementById('clear-patient');
            const categoryInput = document.getElementById('medicine-category');
            const results = document.getElementById('medicine-results');
            const message = document.getElementById('medicine-search-message');
            const cartNode = document.getElementById('cart-items');
            const emptyCart = document.getElementById('cart-empty');
            const form = document.getElementById('sale-form');
            const cart = new Map();
            let availableMedicines = new Map();
            let searchTimer;
            let patientSearchTimer;
            const initialCart = @json($initialCart);
            const reservedStock = @json($reservedStock);

            const money = (amount) => '$' + (Number(amount) || 0).toFixed(2);
            const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            }[character]));

            const showMessage = (text, isError = false) => {
                message.textContent = text;
                message.classList.toggle('text-danger', isError);
            };

            const renderResults = (medicines) => {
                const resultsById = new Map(medicines.map((medicine) => {
                    const id = Number(medicine.id);
                    return [id, { ...medicine, stock: Number(medicine.stock) + Number(reservedStock[id] || 0) }];
                }));
                initialCart.forEach((medicine) => {
                    if (!resultsById.has(Number(medicine.id))) {
                        resultsById.set(Number(medicine.id), medicine);
                    }
                });
                const visibleMedicines = [...resultsById.values()];
                availableMedicines = new Map(visibleMedicines.map((medicine) => [Number(medicine.id), medicine]));
                if (!visibleMedicines.length) {
                    results.replaceChildren();
                    showMessage('No active medicine with sellable stock matched this search.');
                    return;
                }

                showMessage(`${visibleMedicines.length} medicine${visibleMedicines.length === 1 ? '' : 's'} found.`);
                results.innerHTML = visibleMedicines.map((medicine) => {
                    const nextBatch = medicine.batches[0];
                    const batchText = nextBatch ? `${escapeHtml(nextBatch.number)} · expires ${escapeHtml(nextBatch.expiry_date || 'not recorded')}` : 'Batch not available';
                    return `<article class="medicine-card">
                        <div class="d-flex justify-content-between gap-2">
                            <h3>${escapeHtml(medicine.name)}</h3>
                            <strong class="text-nowrap">${money(medicine.unit_price)}</strong>
                        </div>
                        <div class="medicine-code">${escapeHtml(medicine.code)}${medicine.strength ? ' · ' + escapeHtml(medicine.strength) : ''}</div>
                        <div class="medicine-extra">Unit/form: ${escapeHtml(medicine.dosage_form)}${medicine.strength ? ' · ' + escapeHtml(medicine.strength) : ''}${medicine.generic_name ? ' · ' + escapeHtml(medicine.generic_name) : ''}</div>
                        <div class="small text-muted mb-2">${batchText}</div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge text-bg-light border">${Number(medicine.stock)} in stock</span>
                            <button class="btn btn-sm btn-outline-primary" type="button" data-add-medicine="${Number(medicine.id)}">Add</button>
                        </div>
                    </article>`;
                }).join('');
            };

            const searchMedicines = async (exactScan = false) => {
                const query = searchInput.value.trim();
                const params = new URLSearchParams();
                if (query) params.set('q', query);
                if (categoryInput.value) params.set('category', categoryInput.value);
                showMessage('Searching…');

                try {
                    const response = await fetch(`{{ route('pharmacy.medicines') }}?${params}`, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin'
                    });
                    if (!response.ok) throw new Error('Medicine search failed. Refresh the page and try again.');
                    const medicines = await response.json();
                    renderResults(medicines);

                    if (exactScan && query) {
                        const exact = medicines.find((medicine) => medicine.code.toLowerCase() === query.toLowerCase());
                        if (exact) {
                            addMedicine(exact.id);
                            searchInput.value = '';
                            await searchMedicines();
                        }
                    }
                } catch (error) {
                    showMessage(error.message, true);
                }
            };

            const searchPatients = async () => {
                const query = patientSearch.value.trim();
                patientIdInput.value = '';
                selectedPatient.textContent = 'Walk-in / over-the-counter sale. Select a patient only if you want to link one.';
                if (!query) {
                    patientResults.replaceChildren();
                    return;
                }
                try {
                    const params = new URLSearchParams({ q: query });
                    const response = await fetch(`{{ route('pharmacy.patients') }}?${params}`, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin'
                    });
                    if (!response.ok) throw new Error('Patient search failed. Refresh the page and try again.');
                    const patients = await response.json();
                    patientResults.innerHTML = patients.map((patient) => `<button type="button" class="list-group-item list-group-item-action" data-patient-id="${Number(patient.id)}" data-patient-label="${escapeHtml(patient.name)} · ${escapeHtml(patient.number)}">${escapeHtml(patient.name)} <small class="text-muted">${escapeHtml(patient.number)}</small></button>`).join('');
                    if (!patients.length) patientResults.innerHTML = '<div class="list-group-item text-muted">No active patient found.</div>';
                } catch (error) {
                    patientResults.innerHTML = `<div class="list-group-item text-danger">${escapeHtml(error.message)}</div>`;
                }
            };

            const renderCart = () => {
                emptyCart.classList.toggle('d-none', cart.size > 0);
                cartNode.innerHTML = [...cart.values()].map((item) => `<div class="cart-row">
                    <div>
                        <div class="cart-row-title">${escapeHtml(item.name)}</div>
                        <div class="small text-muted">${escapeHtml(item.code)}${item.dosage_form ? ' · ' + escapeHtml(item.dosage_form) : ''} · ${Number(item.stock)} in stock</div>
                        <div class="small text-muted">${money(item.unit_price)} × ${item.quantity} = ${money(Number(item.unit_price) * item.quantity)}</div>
                    </div>
                    <div class="cart-controls">
                        <button type="button" class="btn btn-sm btn-outline-secondary" aria-label="Decrease quantity" data-cart-action="decrease" data-id="${item.id}">−</button>
                        <input class="form-control form-control-sm" type="number" min="1" max="${item.stock}" value="${item.quantity}" aria-label="Quantity for ${escapeHtml(item.name)}" data-cart-action="quantity" data-id="${item.id}">
                        <button type="button" class="btn btn-sm btn-outline-secondary" aria-label="Increase quantity" data-cart-action="increase" data-id="${item.id}">+</button>
                        <button type="button" class="btn btn-sm btn-outline-danger" aria-label="Remove ${escapeHtml(item.name)}" data-cart-action="remove" data-id="${item.id}"><i class="bi bi-trash"></i></button>
                    </div>
                </div>`).join('');
                updateTotals();
            };

            const addMedicine = (id) => {
                const medicine = availableMedicines.get(Number(id));
                if (!medicine) return;
                const current = cart.get(Number(id));
                const quantity = (current?.quantity || 0) + 1;
                if (quantity > Number(medicine.stock)) {
                    showMessage(`Only ${medicine.stock} units of ${medicine.name} are available.`, true);
                    return;
                }
                cart.set(Number(id), { ...medicine, quantity });
                renderCart();
            };

            const updateTotals = () => {
                const subtotal = [...cart.values()].reduce((sum, item) => sum + (Number(item.unit_price) * item.quantity), 0);
                const totalQuantity = [...cart.values()].reduce((sum, item) => sum + item.quantity, 0);
                const discount = Math.min(Math.max(0, Number(document.getElementById('discount').value) || 0), subtotal);
                const taxPercent = Math.max(0, Number(document.getElementById('tax_percent').value) || 0);
                const tax = Math.round(((subtotal - discount) * taxPercent) + Number.EPSILON) / 100;
                const total = Math.max(0, subtotal - discount + tax);
                const paid = Math.min(Math.max(0, Number(document.getElementById('amount_paid').value) || 0), total);
                document.getElementById('quantity-display').textContent = String(totalQuantity);
                document.getElementById('subtotal-display').textContent = money(subtotal);
                document.getElementById('discount-display').textContent = '− ' + money(discount);
                document.getElementById('tax-display').textContent = money(tax);
                document.getElementById('total-display').textContent = money(total);
                document.getElementById('due-display').textContent = money(total - paid);
            };

            results.addEventListener('click', (event) => {
                const button = event.target.closest('[data-add-medicine]');
                if (button) addMedicine(button.dataset.addMedicine);
            });
            patientResults.addEventListener('click', (event) => {
                const button = event.target.closest('[data-patient-id]');
                if (!button) return;
                patientIdInput.value = button.dataset.patientId;
                selectedPatient.textContent = `Selected: ${button.dataset.patientLabel}`;
                patientResults.replaceChildren();
            });
            clearPatientButton.addEventListener('click', () => {
                patientSearch.value = '';
                patientIdInput.value = '';
                patientResults.replaceChildren();
                selectedPatient.textContent = 'Walk-in / over-the-counter sale. Select a patient only if you want to link one.';
            });

            cartNode.addEventListener('click', (event) => {
                const button = event.target.closest('[data-cart-action]');
                if (!button) return;
                const id = Number(button.dataset.id);
                const item = cart.get(id);
                if (!item) return;
                if (button.dataset.cartAction === 'remove') cart.delete(id);
                if (button.dataset.cartAction === 'increase' && item.quantity < item.stock) item.quantity++;
                if (button.dataset.cartAction === 'decrease') {
                    item.quantity--;
                    if (item.quantity < 1) cart.delete(id);
                }
                renderCart();
            });

            cartNode.addEventListener('change', (event) => {
                if (event.target.dataset.cartAction !== 'quantity') return;
                const item = cart.get(Number(event.target.dataset.id));
                if (!item) return;
                item.quantity = Math.min(item.stock, Math.max(1, Number.parseInt(event.target.value, 10) || 1));
                renderCart();
            });

            searchInput.addEventListener('input', () => {
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(() => searchMedicines(), 250);
            });
            searchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    searchMedicines(true);
                }
            });
            patientSearch.addEventListener('input', () => {
                window.clearTimeout(patientSearchTimer);
                patientSearchTimer = window.setTimeout(searchPatients, 250);
            });
            categoryInput.addEventListener('change', () => searchMedicines());
            document.getElementById('discount').addEventListener('input', updateTotals);
            document.getElementById('tax_percent').addEventListener('input', updateTotals);
            document.getElementById('amount_paid').addEventListener('input', updateTotals);
            document.getElementById('pay-full').addEventListener('click', () => {
                const total = Number(document.getElementById('total-display').textContent.replace('$', '')) || 0;
                document.getElementById('amount_paid').value = total.toFixed(2);
                updateTotals();
            });
            document.getElementById('clear-cart').addEventListener('click', () => {
                cart.clear();
                renderCart();
            });
            form.addEventListener('submit', (event) => {
                if (cart.size === 0) {
                    event.preventDefault();
                    showMessage('Add at least one medicine before completing the sale.', true);
                    return;
                }

                form.querySelectorAll('[data-generated-cart-item]').forEach((input) => input.remove());
                [...cart.values()].forEach((item, index) => {
                    [['medicine_id', item.id], ['quantity', item.quantity]].forEach(([key, value]) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = `items[${index}][${key}]`;
                        input.value = value;
                        input.dataset.generatedCartItem = 'true';
                        form.append(input);
                    });
                });
                document.getElementById('complete-sale').disabled = true;
            });

            initialCart.forEach((item) => {
                cart.set(Number(item.id), item);
                availableMedicines.set(Number(item.id), item);
            });
            renderCart();
            searchMedicines();
        });
    </script>
</x-layout>
