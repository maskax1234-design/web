<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <title>Sign in | MediCore</title>
    </head>
    <body class="bg-light">
        <main class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <div class="text-center mb-4">
                        <h1 class="h2 text-primary">Haldoor Hospital Management System</h1>
                        <p class="text-muted">Welcome back</p>
                    </div>
                    <div class="card shadow-sm p-4">
                        <h2 class="h4 mb-3">Sign in</h2>
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            @if($errors->any())
                                <div class="alert alert-danger">{{ $errors->first() }}</div>
                            @endif
                            <button class="btn btn-primary w-100">Sign in</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
