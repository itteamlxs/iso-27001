<div class="bg-white rounded-2xl shadow-xl p-8 max-w-2xl mx-auto">
    <h2 class="text-2xl font-semibold text-gray-900 mb-2">Crear Cuenta</h2>
    <p class="text-gray-600 mb-6">Registra tu empresa y crea tu usuario administrador</p>

    <!-- Alertas -->
    <?php if (isset($errors['general'])): ?>
        <?= component('alert', ['type' => 'error', 'message' => $errors['general'][0]]) ?>
    <?php endif; ?>

    <form method="POST" action="/registro">
        <?= csrf_field() ?>

        <!-- Datos de Empresa -->
        <div class="mb-6 pb-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Datos de la Empresa</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?= component('form-input', [
                    'type' => 'text',
                    'name' => 'nombre',
                    'id' => 'nombre_empresa',
                    'label' => 'Nombre de la empresa',
                    'placeholder' => 'Mi Empresa S.A.',
                    'required' => true,
                    'value' => old('nombre'),
                    'error' => $errors['nombre'][0] ?? null
                ]) ?>

                <?= component('form-input', [
                    'type' => 'text',
                    'name' => 'ruc',
                    'id' => 'ruc',
                    'label' => 'RUC',
                    'placeholder' => '12345678901',
                    'required' => true,
                    'value' => old('ruc'),
                    'error' => $errors['ruc'][0] ?? null,
                    'attributes' => ['maxlength' => '11', 'pattern' => '[0-9]{11}']
                ]) ?>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?= component('form-input', [
                    'type' => 'text',
                    'name' => 'sector',
                    'id' => 'sector',
                    'label' => 'Sector',
                    'placeholder' => 'Tecnología, Retail, etc.',
                    'value' => old('sector'),
                    'error' => $errors['sector'][0] ?? null
                ]) ?>

                <?= component('form-input', [
                    'type' => 'tel',
                    'name' => 'telefono',
                    'id' => 'telefono',
                    'label' => 'Teléfono',
                    'placeholder' => '+51 999 999 999',
                    'value' => old('telefono'),
                    'error' => $errors['telefono'][0] ?? null
                ]) ?>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?= component('form-input', [
                    'type' => 'email',
                    'name' => 'email_empresa',
                    'id' => 'email_empresa',
                    'label' => 'Email de contacto',
                    'placeholder' => 'contacto@empresa.com',
                    'value' => old('email_empresa'),
                    'error' => $errors['email_empresa'][0] ?? null
                ]) ?>

                <?= component('form-input', [
                    'type' => 'text',
                    'name' => 'direccion',
                    'id' => 'direccion',
                    'label' => 'Dirección',
                    'placeholder' => 'Av. Principal 123',
                    'value' => old('direccion'),
                    'error' => $errors['direccion'][0] ?? null
                ]) ?>
            </div>
        </div>

        <!-- Datos de Usuario -->
        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Usuario Administrador</h3>

            <?= component('form-input', [
                'type' => 'text',
                'name' => 'nombre_usuario',
                'id' => 'nombre_usuario',
                'label' => 'Nombre completo',
                'placeholder' => 'Juan Pérez',
                'required' => true,
                'value' => old('nombre_usuario'),
                'error' => $errors['nombre_usuario'][0] ?? null
            ]) ?>

            <?= component('form-input', [
                'type' => 'email',
                'name' => 'email',
                'id' => 'email',
                'label' => 'Email',
                'placeholder' => 'juan.perez@empresa.com',
                'required' => true,
                'autocomplete' => 'email',
                'value' => old('email'),
                'error' => $errors['email'][0] ?? null
            ]) ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?= component('form-input', [
                    'type' => 'password',
                    'name' => 'password',
                    'id' => 'password',
                    'label' => 'Contraseña',
                    'placeholder' => '••••••••',
                    'required' => true,
                    'autocomplete' => 'new-password',
                    'error' => $errors['password'][0] ?? null
                ]) ?>

                <?= component('form-input', [
                    'type' => 'password',
                    'name' => 'password_confirmation',
                    'id' => 'password_confirmation',
                    'label' => 'Confirmar contraseña',
                    'placeholder' => '••••••••',
                    'required' => true,
                    'autocomplete' => 'new-password',
                    'error' => $errors['password_confirmation'][0] ?? null
                ]) ?>
            </div>

            <div class="mt-2 text-sm text-gray-600 bg-blue-50 border border-blue-200 rounded-lg p-3">
                <p class="font-medium text-blue-900 mb-1">La contraseña debe contener:</p>
                <ul class="list-disc list-inside space-y-1 text-blue-800">
                    <li>Mínimo 8 caracteres</li>
                    <li>Al menos una letra mayúscula</li>
                    <li>Al menos una letra minúscula</li>
                    <li>Al menos un número</li>
                    <li>Al menos un carácter especial (!@#$%^&*)</li>
                </ul>
            </div>
        </div>

        <div class="mt-6">
            <?= component('button', [
                'type' => 'submit',
                'text' => 'Crear Cuenta',
                'variant' => 'primary',
                'fullWidth' => true
            ]) ?>
        </div>
    </form>

    <!-- Link a login -->
    <div class="mt-6 text-center text-sm text-gray-600">
        ¿Ya tienes cuenta? 
        <a href="/login" class="text-blue-600 hover:text-blue-700 font-medium">
            Inicia sesión aquí
        </a>
    </div>
</div>
