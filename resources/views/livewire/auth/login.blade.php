<div class="bg-surface-card border border-border rounded-xl p-7 space-y-5 shadow-sm">

    {{-- Heading --}}
    <div>
        <h2 class="text-h2 text-text-primary">Iniciar Sesión</h2>
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
                class="w-4 h-4 rounded border-gray-300 accent-primary-600 focus:ring-primary-500 cursor-pointer">
            <label for="remember" class="text-body text-text-secondary cursor-pointer">Recordarme</label>
        </div>

        {{-- Submit --}}
        <x-button type="submit" variant="primary" target="authenticate" class="w-full h-9">
            Iniciar Sesión
        </x-button>

    </form>

</div>
