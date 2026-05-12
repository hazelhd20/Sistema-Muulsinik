<div class="bg-surface-card border border-border rounded-xl p-7 space-y-5 shadow-sm">

    {{-- Heading --}}
    <div>
        <h2 class="text-h2 text-text-primary">Iniciar Sesión</h2>
        <p class="text-body text-text-muted mt-1">Ingresa tus credenciales para continuar</p>
    </div>

    {{-- Global error --}}
    @if($errorMessage)
        <div class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-small flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            <span>{{ $errorMessage }}</span>
        </div>
    @endif

    <form wire:submit="authenticate" class="space-y-4">

        {{-- Email --}}
        <div>
            <label for="login-email" class="label">Correo electrónico</label>
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
                <p class="mt-1.5 text-xs-fluid text-danger">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="login-password" class="label">Contraseña</label>
            <input
                wire:model="password"
                type="password"
                id="login-password"
                class="input"
                placeholder="••••••••"
                autocomplete="current-password"
            >
            @error('password')
                <p class="mt-1.5 text-xs-fluid text-danger">{{ $message }}</p>
            @enderror
        </div>

        {{-- Remember --}}
        <div class="flex items-center gap-2">
            <input wire:model="remember" type="checkbox" id="remember"
                class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 cursor-pointer">
            <label for="remember" class="text-body text-text-secondary cursor-pointer">Recordarme</label>
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn-primary w-full h-9 relative" wire:loading.attr="disabled">
            <span wire:loading.class="opacity-0" wire:target="authenticate" class="transition-opacity">
                Iniciar Sesión
            </span>
            <span wire:loading wire:target="authenticate"
                class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </span>
        </button>

    </form>

</div>
