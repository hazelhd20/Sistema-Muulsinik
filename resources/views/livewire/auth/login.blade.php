<div class="space-y-6">
    {{-- Logo y título --}}
    <div class="text-center">
        <img src="{{ asset('images/logo_muulsinik.svg') }}" alt="Muulsinik ERP" class="mx-auto h-32 mb-4">
    </div>

    {{-- Card de login --}}
    <div class="card" style="padding: 2rem;">
        <h2 class="text-lg font-semibold text-text-primary mb-1">Iniciar Sesión</h2>
        <p class="text-sm text-text-muted mb-6">Ingresa tus credenciales para continuar</p>

        {{-- Error global --}}
        @if($errorMessage)
            <div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
                <span>{{ $errorMessage }}</span>
            </div>
        @endif

        <form wire:submit="authenticate" class="space-y-4">
            {{-- Email --}}
            <div>
                <label for="login-email" class="block text-sm font-medium text-text-primary mb-1.5">Correo electrónico</label>
                <input
                    wire:model="email"
                    type="email"
                    id="login-email"
                    class="input"
                    placeholder="tu@correo.com"
                    autofocus
                    autocomplete="email"
                >
                @error('email')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="login-password" class="block text-sm font-medium text-text-primary mb-1.5">Contraseña</label>
                <input
                    wire:model="password"
                    type="password"
                    id="login-password"
                    class="input"
                    placeholder="••••••••"
                    autocomplete="current-password"
                >
                @error('password')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            {{-- Remember + Forgot --}}
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input wire:model="remember" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <span class="text-sm text-text-secondary">Recordarme</span>
                </label>
            </div>

            {{-- Submit --}}
            <button type="submit" class="btn-primary w-full relative" wire:loading.attr="disabled">
                <span wire:loading.class="opacity-0" wire:target="authenticate" class="transition-opacity">Iniciar Sesión</span>
                <span wire:loading wire:target="authenticate" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </span>
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-text-muted">
        Muulsinik ERP v1.0 &copy; {{ date('Y') }}
    </p>
</div>
