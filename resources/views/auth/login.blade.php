{{-- resources/views/auth/login.blade.php --}}
@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="text-center mb-4">
            <img src="{{ asset('images/logo.png') }}" class="brand-logo mx-auto mb-1" alt="Angkringan Hotber">
            <h5 class="fw-bold mb-1">Admin Angkringan Hotber</h5>
            <p class="section-subtitle mb-0">Masuk menggunakan akun admin yang terdaftar</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger py-2 small">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="/login" method="POST">
            @csrf

            <div class="mb-3">
                <label class="form-label small fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" placeholder="Masukkan email terdaftar" required autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password terdaftar" required autocomplete="current-password">
                    <button type="button" class="btn btn-outline-secondary" id="togglePassword" aria-label="Lihat password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label small" for="remember">Ingat saya</label>
            </div>

            <button type="submit" class="btn btn-brand w-100">Masuk</button>
        </form>
    </div>
</div>

<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        var input = document.getElementById('password');
        var icon = this.querySelector('i');
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        icon.classList.toggle('bi-eye', !show);
        icon.classList.toggle('bi-eye-slash', show);
    });
</script>
@endsection
