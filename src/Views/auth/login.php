<div class="bg-white rounded-2xl shadow-xl p-8">
    <h2 class="text-xl font-semibold text-gray-900 mb-6">Iniciar Sesión</h2>

    <!-- Alertas -->
    <?php if (isset($errors['general'])): ?>
        <?= component('alert', ['type' => 'error', 'message' => $errors['general'][0]]) ?>
    <?php endif; ?>

    <?php if (\App\Core\Session::getFlash('error')): ?>
        <?= component('alert', ['type' => 'error', 'message' => \App\Core\Session::getFlash('error')]) ?>
    <?php endif; ?>

    <?php if (\App\Core\Session::getFlash('success')): ?>
        <?= component('alert', ['type' => 'success', 'message' => \App\Core\Session::getFlash('success')]) ?>
    <?php endif; ?>

    <form id="loginForm" method="POST" action="/login">
        <?= csrf_field() ?>

        <!-- Step 1: Email -->
        <div id="step1">
            <?= component('form-input', [
                'type' => 'email',
                'name' => 'email',
                'id' => 'email',
                'label' => 'Correo electrónico',
                'placeholder' => 'usuario@empresa.com',
                'required' => true,
                'autocomplete' => 'email',
                'value' => old('email')
            ]) ?>

            <div class="mt-4">
                <?= component('button', [
                    'type' => 'button',
                    'id' => 'btnContinue',
                    'text' => 'Continuar',
                    'variant' => 'primary',
                    'fullWidth' => true
                ]) ?>
            </div>
        </div>

        <!-- Step 2: Password -->
        <div id="step2" class="hidden">
            <div class="flex items-center justify-between mb-4 p-3 bg-green-50 rounded-lg">
                <div class="flex items-center text-sm text-gray-700">
                    <svg class="w-5 h-5 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span id="emailDisplay" class="font-medium"></span>
                </div>
                <button 
                    type="button" 
                    id="btnChangeEmail"
                    class="text-sm text-blue-600 hover:text-blue-700 font-medium focus:outline-none"
                >
                    Cambiar
                </button>
            </div>

            <?= component('form-input', [
                'type' => 'password',
                'name' => 'password',
                'id' => 'password',
                'label' => 'Contraseña',
                'placeholder' => '••••••••',
                'required' => true,
                'autocomplete' => 'current-password',
                'error' => $errors['password'][0] ?? null
            ]) ?>

            <div class="mt-4">
                <?= component('button', [
                    'type' => 'submit',
                    'text' => 'Iniciar Sesión',
                    'variant' => 'primary',
                    'fullWidth' => true
                ]) ?>
            </div>
        </div>
    </form>

    <!-- Link a registro -->
    <div class="mt-6 text-center text-sm text-gray-600">
        ¿No tienes cuenta? 
        <a href="/registro" class="text-blue-600 hover:text-blue-700 font-medium">
            Regístrate aquí
        </a>
    </div>
</div>

<script>
    const emailInput = document.getElementById('email');
    const btnContinue = document.getElementById('btnContinue');
    const btnChangeEmail = document.getElementById('btnChangeEmail');
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const emailDisplay = document.getElementById('emailDisplay');
    const passwordInput = document.getElementById('password');

    btnContinue.addEventListener('click', async function() {
        const email = emailInput.value.trim();
        
        if (!email || !isValidEmail(email)) {
            showToast('Por favor ingrese un email válido', 'error');
            emailInput.focus();
            return;
        }

        btnContinue.disabled = true;
        btnContinue.textContent = 'Verificando...';

        try {
            const response = await fetch('/api/check-email', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: JSON.stringify({ email })
            });

            if (response.status === 429) {
                showToast('Demasiados intentos. Espere unos minutos.', 'error');
                return;
            }

            const data = await response.json();

            if (data.exists) {
                emailDisplay.textContent = email;
                step1.classList.add('hidden');
                step2.classList.remove('hidden');
                passwordInput.focus();
            } else {
                showToast('No existe una cuenta con este correo', 'error');
                emailInput.focus();
            }
        } catch (error) {
            showToast('Error al verificar el email. Intente nuevamente.', 'error');
        } finally {
            btnContinue.disabled = false;
            btnContinue.textContent = 'Continuar';
        }
    });

    btnChangeEmail.addEventListener('click', function() {
        step2.classList.add('hidden');
        step1.classList.remove('hidden');
        passwordInput.value = '';
        emailInput.focus();
    });

    emailInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            btnContinue.click();
        }
    });

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function showToast(message, type) {
        const existing = document.querySelector('.toast-notification');
        if (existing) existing.remove();

        const colors = {
            'error': 'bg-red-50 border-red-500 text-red-700',
            'success': 'bg-green-50 border-green-500 text-green-700'
        };
        
        const toast = document.createElement('div');
        toast.className = `toast-notification fixed top-4 right-4 p-4 ${colors[type]} border-l-4 rounded shadow-lg z-50 max-w-md animate-fade-in`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('animate-fade-out');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
</script>

<style>
    @keyframes fade-in {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fade-out {
        from { opacity: 1; }
        to { opacity: 0; }
    }
    .animate-fade-in { animation: fade-in 0.3s ease-out; }
    .animate-fade-out { animation: fade-out 0.3s ease-out; }
</style>
