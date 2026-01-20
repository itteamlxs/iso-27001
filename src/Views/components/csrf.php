<?php
/**
 * CSRF Token Component
 * Genera y renderiza token CSRF para protección de formularios
 */

use App\Core\Session;

// Generar token si no existe
if (!Session::has('_token')) {
    Session::put('_token', generate_token(32));
}

$token = Session::get('_token');
?>
<input type="hidden" name="_token" value="<?= htmlspecialchars($token) ?>">

