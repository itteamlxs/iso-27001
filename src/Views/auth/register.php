<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="w-full lg:w-[95%]">
        <!-- Card Principal -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            
            <!-- Progress Steps -->
            <div class="bg-blue-50 px-12 py-6 border-b border-gray-200">
                <div class="flex items-center justify-between max-w-3xl mx-auto">
                    <!-- Step 1 -->
                    <div class="flex items-center flex-1">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-blue-600 text-white font-semibold step-indicator" data-step="1">
                            1
                        </div>
                        <div class="ml-3 hidden sm:block">
                            <p class="text-sm font-medium text-blue-600">Paso 1</p>
                            <p class="text-xs text-gray-600">Datos Empresa</p>
                        </div>
                    </div>
                    
                    <!-- Divider -->
                    <div class="flex-1 mx-4">
                        <div class="h-1 bg-gray-300 rounded-full step-divider"></div>
                    </div>
                    
                    <!-- Step 2 -->
                    <div class="flex items-center flex-1 justify-end">
                        <div class="mr-3 hidden sm:block text-right">
                            <p class="text-sm font-medium text-gray-400">Paso 2</p>
                            <p class="text-xs text-gray-400">Administrador</p>
                        </div>
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-300 text-gray-600 font-semibold step-indicator" data-step="2">
                            2
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" action="/registro" id="registroForm" class="p-12">
                <?= csrf_field() ?>

                <!-- STEP 1: Datos de Empresa -->
                <div class="form-step" data-step="1">
                    <div class="mb-6">
                        <div class="space-y-4">
                            <!-- Nombre Empresa -->
                            <div class="form-group">
                                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nombre de la Empresa <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                    </div>
                                    <input 
                                        type="text" 
                                        name="nombre" 
                                        id="nombre"
                                        value="<?= old('nombre') ?>"
                                        class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all <?= isset($errors['nombre']) ? 'border-red-500 ring-2 ring-red-200' : '' ?>"
                                        placeholder="Ej: Acme Corporation S.A."
                                        required
                                    >
                                </div>
                                <?php if (isset($errors['nombre'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?= $errors['nombre'][0] ?></p>
                                <?php endif; ?>
                            </div>

                            <!-- Grid 2 columnas: RUC y Sector -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- RUC -->
                                <div class="form-group">
                                    <label for="ruc" class="block text-sm font-medium text-gray-700 mb-2">
                                        RUC <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </div>
                                        <input 
                                            type="text" 
                                            name="ruc" 
                                            id="ruc"
                                            value="<?= old('ruc') ?>"
                                            maxlength="11"
                                            pattern="[0-9]{11}"
                                            class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all <?= isset($errors['ruc']) ? 'border-red-500 ring-2 ring-red-200' : '' ?>"
                                            placeholder="12345678901"
                                            required
                                        >
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">11 dígitos numéricos</p>
                                    <?php if (isset($errors['ruc'])): ?>
                                        <p class="mt-1 text-sm text-red-600"><?= $errors['ruc'][0] ?></p>
                                    <?php endif; ?>
                                </div>

                                <!-- Sector -->
                                <div class="form-group">
                                    <label for="sector" class="block text-sm font-medium text-gray-700 mb-2">
                                        Sector Industrial
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <input 
                                            type="text" 
                                            name="sector" 
                                            id="sector"
                                            value="<?= old('sector') ?>"
                                            class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all <?= isset($errors['sector']) ? 'border-red-500 ring-2 ring-red-200' : '' ?>"
                                            placeholder="Ej: Tecnología, Retail, Salud..."
                                        >
                                    </div>
                                    <?php if (isset($errors['sector'])): ?>
                                        <p class="mt-1 text-sm text-red-600"><?= $errors['sector'][0] ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Grid 2 columnas: Email y Teléfono -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Email Empresa -->
                                <div class="form-group">
                                    <label for="email_empresa" class="block text-sm font-medium text-gray-700 mb-2">
                                        Email Corporativo
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <input 
                                            type="email" 
                                            name="email_empresa" 
                                            id="email_empresa"
                                            value="<?= old('email_empresa') ?>"
                                            class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all <?= isset($errors['email_empresa']) ? 'border-red-500 ring-2 ring-red-200' : '' ?>"
                                            placeholder="contacto@empresa.com"
                                        >
                                    </div>
                                    <?php if (isset($errors['email_empresa'])): ?>
                                        <p class="mt-1 text-sm text-red-600"><?= $errors['email_empresa'][0] ?></p>
                                    <?php endif; ?>
                                </div>

                                <!-- Teléfono -->
                                <div class="form-group">
                                    <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2">
                                        Teléfono
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                            </svg>
                                        </div>
                                        <input 
                                            type="tel" 
                                            name="telefono" 
                                            id="telefono"
                                            value="<?= old('telefono') ?>"
                                            class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all <?= isset($errors['telefono']) ? 'border-red-500 ring-2 ring-red-200' : '' ?>"
                                            placeholder="+34 999 999 999"
                                        >
                                    </div>
                                    <?php if (isset($errors['telefono'])): ?>
                                        <p class="mt-1 text-sm text-red-600"><?= $errors['telefono'][0] ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Dirección -->
                            <div class="form-group">
                                <label for="direccion" class="block text-sm font-medium text-gray-700 mb-2">
                                    Dirección Fiscal
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                    </div>
                                    <input 
                                        type="text" 
                                        name="direccion" 
                                        id="direccion"
                                        value="<?= old('direccion') ?>"
                                        class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all <?= isset($errors['direccion']) ? 'border-red-500 ring-2 ring-red-200' : '' ?>"
                                        placeholder="Av. Principal 123, Oficina 456"
                                    >
                                </div>
                                <?php if (isset($errors['direccion'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?= $errors['direccion'][0] ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Botón Siguiente -->
                    <div class="flex justify-end pt-6 border-t border-gray-200">
                        <button 
                            type="button" 
                            id="nextStep"
                            class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-all transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Siguiente
                            <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- STEP 2: Datos de Usuario -->
                <div class="form-step hidden" data-step="2">
                    <div class="mb-6">
                        <div class="space-y-4">
                            <!-- Nombre Usuario -->
                            <div class="form-group">
                                <label for="nombre_usuario" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nombre Completo <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                    <input 
                                        type="text" 
                                        name="nombre_usuario" 
                                        id="nombre_usuario"
                                        value="<?= old('nombre_usuario') ?>"
                                        class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all <?= isset($errors['nombre_usuario']) ? 'border-red-500 ring-2 ring-red-200' : '' ?>"
                                        placeholder="Juan Pérez García"
                                        required
                                    >
                                </div>
                                <?php if (isset($errors['nombre_usuario'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?= $errors['nombre_usuario'][0] ?></p>
                                <?php endif; ?>
                            </div>

                            <!-- Email Usuario -->
                            <div class="form-group">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Email Personal <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                        </svg>
                                    </div>
                                    <input 
                                        type="email" 
                                        name="email" 
                                        id="email"
                                        value="<?= old('email') ?>"
                                        autocomplete="email"
                                        class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all <?= isset($errors['email']) ? 'border-red-500 ring-2 ring-red-200' : '' ?>"
                                        placeholder="juan.perez@empresa.com"
                                        required
                                    >
                                </div>
                                <?php if (isset($errors['email'])): ?>
                                    <p class="mt-1 text-sm text-red-600"><?= $errors['email'][0] ?></p>
                                <?php endif; ?>
                            </div>

                            <!-- Contraseñas -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Password -->
                                <div class="form-group">
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                        Contraseña <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                            </svg>
                                        </div>
                                        <input 
                                            type="password" 
                                            name="password" 
                                            id="password"
                                            autocomplete="new-password"
                                            class="block w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all <?= isset($errors['password']) ? 'border-red-500 ring-2 ring-red-200' : '' ?>"
                                            placeholder="••••••••"
                                            required
                                        >
                                        <button 
                                            type="button" 
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                            onclick="togglePassword('password')"
                                        >
                                            <svg class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <?php if (isset($errors['password'])): ?>
                                        <p class="mt-1 text-sm text-red-600"><?= $errors['password'][0] ?></p>
                                    <?php endif; ?>
                                </div>

                                <!-- Confirmar Password -->
                                <div class="form-group">
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                                        Confirmar Contraseña <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <input 
                                            type="password" 
                                            name="password_confirmation" 
                                            id="password_confirmation"
                                            autocomplete="new-password"
                                            class="block w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all <?= isset($errors['password_confirmation']) ? 'border-red-500 ring-2 ring-red-200' : '' ?>"
                                            placeholder="••••••••"
                                            required
                                        >
                                        <button 
                                            type="button" 
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center"
                                            onclick="togglePassword('password_confirmation')"
                                        >
                                            <svg class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <?php if (isset($errors['password_confirmation'])): ?>
                                        <p class="mt-1 text-sm text-red-600"><?= $errors['password_confirmation'][0] ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Password Strength Indicator -->
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <div class="text-sm">
                                        <p class="font-medium text-gray-900 mb-2">La contraseña debe contener:</p>
                                        <ul class="space-y-1 text-gray-600">
                                            <li class="flex items-center" id="req-length">
                                                <span class="requirement-icon mr-2">○</span>
                                                Mínimo 8 caracteres
                                            </li>
                                            <li class="flex items-center" id="req-uppercase">
                                                <span class="requirement-icon mr-2">○</span>
                                                Al menos una mayúscula (A-Z)
                                            </li>
                                            <li class="flex items-center" id="req-lowercase">
                                                <span class="requirement-icon mr-2">○</span>
                                                Al menos una minúscula (a-z)
                                            </li>
                                            <li class="flex items-center" id="req-number">
                                                <span class="requirement-icon mr-2">○</span>
                                                Al menos un número (0-9)
                                            </li>
                                            <li class="flex items-center" id="req-special">
                                                <span class="requirement-icon mr-2">○</span>
                                                Al menos un símbolo (!@#$%^&*)
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones Navegación -->
                    <div class="flex justify-between pt-6 border-t border-gray-200">
                        <button 
                            type="button" 
                            id="prevStep"
                            class="inline-flex items-center px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400"
                        >
                            <svg class="mr-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                            </svg>
                            Anterior
                        </button>

                        <button 
                            type="submit"
                            class="inline-flex items-center px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-lg transition-all transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <svg class="mr-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Crear Cuenta
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                ¿Ya tienes cuenta? 
                <a href="/login" class="text-blue-600 hover:text-blue-700 font-semibold hover:underline transition-all">
                    Inicia sesión aquí
                </a>
            </p>
        </div>
    </div>
</div>

<script>
// Multi-step form logic
document.addEventListener('DOMContentLoaded', function() {
    const nextBtn = document.getElementById('nextStep');
    const prevBtn = document.getElementById('prevStep');
    const steps = document.querySelectorAll('.form-step');
    const stepIndicators = document.querySelectorAll('.step-indicator');
    const stepDivider = document.querySelector('.step-divider');
    let currentStep = 1;

    function showStep(step) {
        steps.forEach(s => s.classList.add('hidden'));
        steps[step - 1].classList.remove('hidden');
        
        stepIndicators.forEach((indicator, index) => {
            if (index + 1 <= step) {
                indicator.classList.remove('bg-gray-300', 'text-gray-600');
                indicator.classList.add('bg-blue-600', 'text-white');
                indicator.previousElementSibling?.querySelectorAll('p')[0]?.classList.remove('text-gray-400');
                indicator.previousElementSibling?.querySelectorAll('p')[0]?.classList.add('text-blue-600');
                indicator.previousElementSibling?.querySelectorAll('p')[1]?.classList.remove('text-gray-400');
                indicator.previousElementSibling?.querySelectorAll('p')[1]?.classList.add('text-gray-600');
            } else {
                indicator.classList.remove('bg-blue-600', 'text-white');
                indicator.classList.add('bg-gray-300', 'text-gray-600');
            }
        });

        if (step === 2) {
            stepDivider.classList.remove('bg-gray-300');
            stepDivider.classList.add('bg-blue-600');
        } else {
            stepDivider.classList.remove('bg-blue-600');
            stepDivider.classList.add('bg-gray-300');
        }
    }

    function validateStep1() {
        const nombre = document.getElementById('nombre').value.trim();
        const ruc = document.getElementById('ruc').value.trim();
        
        if (!nombre) {
            alert('Por favor ingresa el nombre de la empresa');
            document.getElementById('nombre').focus();
            return false;
        }
        
        if (!ruc || ruc.length !== 11 || !/^\d+$/.test(ruc)) {
            alert('El RUC debe tener exactamente 11 dígitos numéricos');
            document.getElementById('ruc').focus();
            return false;
        }
        
        return true;
    }

    nextBtn?.addEventListener('click', function() {
        if (validateStep1()) {
            currentStep = 2;
            showStep(currentStep);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    prevBtn?.addEventListener('click', function() {
        currentStep = 1;
        showStep(currentStep);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Password visibility toggle
    window.togglePassword = function(fieldId) {
        const field = document.getElementById(fieldId);
        field.type = field.type === 'password' ? 'text' : 'password';
    };

    // Password strength validation
    const passwordField = document.getElementById('password');
    if (passwordField) {
        passwordField.addEventListener('input', function() {
            const password = this.value;
            
            const checks = {
                'req-length': password.length >= 8,
                'req-uppercase': /[A-Z]/.test(password),
                'req-lowercase': /[a-z]/.test(password),
                'req-number': /[0-9]/.test(password),
                'req-special': /[!@#$%^&*]/.test(password)
            };

            Object.keys(checks).forEach(id => {
                const element = document.getElementById(id);
                const icon = element.querySelector('.requirement-icon');
                
                if (checks[id]) {
                    element.classList.remove('text-gray-600');
                    element.classList.add('text-green-600', 'font-medium');
                    icon.textContent = '✓';
                } else {
                    element.classList.remove('text-green-600', 'font-medium');
                    element.classList.add('text-gray-600');
                    icon.textContent = '○';
                }
            });
        });
    }

    // RUC validation (only numbers)
    const rucField = document.getElementById('ruc');
    if (rucField) {
        rucField.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').substring(0, 11);
        });
    }
});
</script>
