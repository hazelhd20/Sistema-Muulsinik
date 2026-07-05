<div class="bg-surface-card border border-border rounded-xl p-7 space-y-5 shadow-sm">

    {{-- Heading --}}
    <div>
        <h2 class="text-h2 text-text-primary">Iniciar sesión</h2>
        <p class="text-body text-text-muted mt-1">Ingresa tus credenciales para continuar</p>
    </div>

    {{-- Global error --}}
    @if($errorMessage)
        <x-alert type="danger" :message="$errorMessage" />
    @endif

    <form wire:submit="authenticate" class="space-y-4">

        {{-- Email --}}
        <x-form-field label="Correo electrónico" :error="$errors->first('email')">
            <input
                wire:model="email"
                type="email"
                id="login-email"
                class="input"
                placeholder="tu@correo.com"
                autofocus
                autocomplete="email"
            >
        </x-form-field>

        {{-- Password --}}
        <x-form-field label="Contraseña" :error="$errors->first('password')">
            <input
                wire:model="password"
                type="password"
                id="login-password"
                class="input"
                placeholder="••••••••"
                autocomplete="current-password"
            >
        </x-form-field>

        {{-- Remember --}}
        <div class="flex items-center gap-2">
            <input wire:model="remember" type="checkbox" id="remember"
                class="rounded-[4px] border-border text-primary-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/30 focus-visible:border-primary-500 transition-all duration-200 w-4 h-4 cursor-pointer accent-primary-600">
            <label for="remember" class="text-small text-text-secondary cursor-pointer select-none">Recordarme</label>
        </div>

        {{-- Submit --}}
        <x-button type="submit" variant="primary" target="authenticate" class="w-full h-9">
            Iniciar sesión
        </x-button>

    </form>

</div>
