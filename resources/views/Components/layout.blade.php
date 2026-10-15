@props(['title' => 'Haldoor Hospital Management System'])
<!doctype html>
<html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title }}</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="app-shell d-flex min-vh-100">
        <x-sidebar />
        <main class="app-main flex-grow-1">
            <header class="app-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                <strong>Haldoor Hospital Management System</strong>
                <form action="{{ route('logout') }}" method="POST">@csrf<button class="btn btn-sm btn-outline-secondary">Sign out</button></form>
            </header>
            <section class="container-fluid p-4">
                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
                {{ $slot }}
            </section>
        </main>
    </div>

    <style>
        .app-shell {
            min-height: 100vh;
        }

        .app-main {
            min-width: 0;
        }

        .app-header {
            position: sticky;
            top: 0;
            z-index: 20;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
