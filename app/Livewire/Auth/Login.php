<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

class Login extends Component
{
    #[Rule('required|email')]
    public string $email = '';

    #[Rule('required|min:6')]
    public string $password = '';

    public bool $remember = false;
    public string $errorMessage = '';

    public function authenticate(): void
    {
        $this->validate();
        $this->errorMessage = '';

        $credentials = [
            'email' => $this->email,
            'password' => $this->password,
            'active' => true,
        ];

        if (!Auth::attempt($credentials, $this->remember)) {
            $this->errorMessage = 'Las credenciales proporcionadas no son correctas o la cuenta está desactivada.';
            return;
        }

        session()->regenerate();
        $this->redirectIntended(url('/dashboard'));
    }

    #[Layout('components.layouts.guest')]
    #[Title('Iniciar Sesión')]
    public function render()
    {
        return view('livewire.auth.login');
    }
}
