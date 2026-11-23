@extends('layouts.app')

@section('title', 'Iniciar sesión')

@section('content')
    <div class="mx-auto flex min-h-[70vh] max-w-sm flex-col justify-center">
        <div class="mb-8 text-center">
            <span aria-hidden="true" class="mx-auto mb-4 block size-9 rounded-xl bg-accent-600 shadow-sm dark:bg-accent-500"></span>
            <p class="text-sm font-medium tracking-tight muted">{{ config('app.name') }}</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">Iniciar sesión</h1>
        </div>

        <div class="surface p-6 shadow-sm">
            <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="label">Correo electrónico</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                           @error('email') aria-invalid="true" @enderror
                           class="field">
                    @error('email')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="label">Contraseña</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           @error('password') aria-invalid="true" @enderror
                           class="field">
                    @error('password')
                        <p class="field-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn-primary w-full">Entrar</button>
            </form>
        </div>
    </div>
@endsection
