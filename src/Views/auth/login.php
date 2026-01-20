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

    <form method="POST" action="/login">
        <?= csrf_field() ?>

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
            <?= component('button', [
                'type' => 'submit',
                'text' => 'Iniciar Sesión',
                'variant' => 'primary',
                'fullWidth' => true
            ]) ?>
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
