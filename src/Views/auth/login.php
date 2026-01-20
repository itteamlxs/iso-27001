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

    <form method="POST" action="/login" id="loginForm">
        <?= csrf_field() ?>

        <!-- Step 1: Email -->
        <div id="emailStep">
            <?= component('form-input', [
                'type' => 'email',
                'name' => 'email',
                'id' => 'email',
                'label' => 'Correo electrónico',
                'placeholder' => 'usuario@empresa.com',
                'required' => true,
                'autocomplete' => 'email',
                'value' => old('email'),
                'error' => $errors['email'][0] ?? null
            ]) ?>

            <div class="mt-6">
                <button 
                    type="button" 
                    id="nextBtn"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    Siguiente
                </button>
            </div>
        </div>

        <!-- Step 2: Password -->
        <div id="passwordStep" class="hidden">
            <div class="mb-4 pb-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">
                        <span id="emailDisplay" class="font-medium text-gray-900"></span>
                    </span>
                    <button 
                        type="button" 
                        id="backBtn"
                        class="text-sm text-blue-600 hover:text-blue-700 font-medium"
                    >
                        Cambiar
                    </button>
                </div>
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

            <div class="mt-6">
                <button 
                    type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    Iniciar Sesión
                </button>
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
document.addEventListener('DOMContentLoaded', function() {
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const nextBtn = document.getElementById('nextBtn');
    const backBtn = document.getElementById('backBtn');
    const emailStep = document.getElementById('emailStep');
    const passwordStep = document.getElementById('passwordStep');
    const emailDisplay = document.getElementById('emailDisplay');

    nextBtn?.addEventListener('click', function() {
        const email = emailInput.value.trim();

        if (!email || !isValidEmail(email)) {
            emailInput.focus();
            emailInput.classList.add('border-red-500');
            return;
        }

        emailInput.classList.remove('border-red-500');
        emailStep.classList.add('hidden');
        passwordStep.classList.remove('hidden');
        emailDisplay.textContent = email;
        passwordInput.focus();
    });

    backBtn?.addEventListener('click', function() {
        passwordStep.classList.add('hidden');
        emailStep.classList.remove('hidden');
        emailInput.focus();
    });

    emailInput?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            nextBtn.click();
        }
    });

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
});
</script>
