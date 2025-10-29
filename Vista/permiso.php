<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$idRol = $_SESSION['id_rol'] ?? 0; // rol por defecto en pruebas si no hay sesión
$idModulo = 17;

if (isset($permisosUsuario[$idRol][$idModulo]['consultar']) && $permisosUsuario[$idRol][$idModulo]['consultar'] === true) {?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Permisos</title>
    <?php include 'header.php'; ?>
    <style>
        .tabla-permisos-rol { display: none; }
        .tabla-permisos-rol.active { display: block; }
        .btn-seleccionar-todos {
            background: #27ae60; color: #fff; border: none; padding: 3px 8px;
            border-radius: 4px; cursor: pointer; font-size: 0.8rem;
        }
        .btn-seleccionar-todos:disabled { background: #ccc; cursor: not-allowed; }
        body {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
.main-content {
    flex: 1;
    padding-bottom: 60px; /* Espacio para el footer si es necesario */
}

        #selectorRol {
            font-family: arial;
            font-size: 14px;
            cursor: pointer;
            color: rgb(101, 162, 241);
            margin-left:10px;
            padding:5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        #selectorRol:focus {
            border-color: #5995fd;
            box-shadow: 0 0 0 2px rgba(89, 149, 253, 0.2);
        }
        .title-select {
            font-size: 1rem;
            color: #1976d2;
            font-weight: 500;
        }
    </style>
</head>

<body class="fondo" style="height:100vh; background-image:url(img/fondo.jpg); background-size:cover;">
<?php include 'newnavbar.php'; ?>

<div class="main-content">

<div style="display:flex; flex-direction:column; align-items:center; min-height:70vh; margin-bottom: 20px; margin-top: 20px;">
<form method="post" action=""
    style="background:rgba(255,255,255,0.97); padding:32px 24px; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,0.08); margin:30px 0; width:100%; max-width:1100px;">

    <h3 style="text-align:center; color: #1F66DF; padding: 20px;">Gestión de Permisos por Rol</h3>

    <!-- Selector de roles -->
    <div style="text-align:center; margin-bottom:18px;">
        <label for="selectorRol" class="title-select">Seleccionar Rol:</label>
        <select id="selectorRol">
            <?php
            $excluidos = ['administrador','superusuario', 'cliente'];
            $rolesSinSuper = array_filter($roles, function ($rol) use ($excluidos) {
                return !in_array(strtolower(str_replace(' ', '', $rol['nombre_rol'])), $excluidos);
            });
            $rolesSinSuper = array_values($rolesSinSuper);

            foreach ($rolesSinSuper as $i => $rol): ?>
                <option value="<?= $rol['id_rol'] ?>" <?= $i===0 ? 'selected' : '' ?>>
                    <?= htmlspecialchars($rol['nombre_rol']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Tablas visibles -->
    <div style="max-height:60vh; overflow-y:auto;">
        <?php 
        $acciones = ['ingresar', 'consultar', 'incluir', 'modificar', 'eliminar', 'generar reporte'];
        foreach ($rolesSinSuper as $i => $rol): ?>
            <div class="tabla-permisos-rol <?= $i===0 ? 'active' : '' ?>" id="tabla-rol-<?= $rol['id_rol'] ?>">
                <h4 style="text-align:center; color:#1f66df;"><?= htmlspecialchars($rol['nombre_rol']) ?></h4>
                <table border="1" cellpadding="6" style="margin:0 auto; min-width:450px; text-align:center;">
                    <thead>
                        <tr>
                            <th>Módulo</th>
                            <?php foreach ($acciones as $accion): ?>
                                <th><?= ucfirst($accion) ?></th>
                            <?php endforeach; ?>
                            <th>Todos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modulos_permiso as $modulo): ?>
                            <tr>
                                <td><?= htmlspecialchars($modulo['nombre_modulo']) ?></td>
                                <?php foreach ($acciones as $accion): ?>
                                    <td>
                                        <input type="checkbox" 
                                            class="permiso-<?= $accion ?>" 
                                            data-modulo="<?= $modulo['id_modulo'] ?>"
                                            name="permisos[<?= $rol['id_rol'] ?>][<?= $modulo['id_modulo'] ?>][<?= $accion ?>]"
                                            <?= isset($permisosActuales[$rol['id_rol']][$modulo['id_modulo']][$accion]) ? 'checked' : '' ?>>
                                    </td>
                                <?php endforeach; ?>
                                <td>
                                    <button type="button" 
                                        class="btn-seleccionar-todos btn-incluir"
                                        title="Marcar todos"
                                        data-modulo="<?= $modulo['id_modulo'] ?>">
                                        <img src="img/mouse-pointer-click.svg">
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- 🔹 Tablas ocultas para roles excluidos -->
    <div style="display:none;">
        <?php
        $rolesOcultos = array_filter($roles, function ($rol) use ($excluidos) {
            return in_array(strtolower(str_replace(' ', '', $rol['nombre_rol'])), $excluidos);
        });

        foreach ($rolesOcultos as $rol): ?>
            <?php foreach ($modulos_permiso as $modulo): ?>
                <?php foreach ($acciones as $accion): ?>
                    <input type="checkbox"
                        name="permisos[<?= $rol['id_rol'] ?>][<?= $modulo['id_modulo'] ?>][<?= $accion ?>]"
                        <?= isset($permisosActuales[$rol['id_rol']][$modulo['id_modulo']][$accion]) ? 'checked' : '' ?>>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>

    <div style="text-align:center; margin-top:15px;">
        <button type="submit" name="guardarPermisos" class="btn btn-primary">Guardar Permisos</button>
    </div>
</form>
</div>
                </div>
                
<?php include 'footer.php'; ?>

<script>
// Cambio de tabla por rol
document.getElementById('selectorRol').addEventListener('change', function () {
    document.querySelectorAll('.tabla-permisos-rol').forEach(tabla => tabla.classList.remove('active'));
    document.getElementById('tabla-rol-' + this.value).classList.add('active');
});

// Bloqueo por "Ingresar"
function actualizarPermisosIngresar() {
    document.querySelectorAll('.permiso-ingresar').forEach(chk => {
        const fila = chk.closest('tr');
        const otrosPermisos = fila.querySelectorAll(`input[type=checkbox]:not(.permiso-ingresar)`);
        const btnTodos = fila.querySelector('.btn-seleccionar-todos');

        if (!chk.checked) {
            otrosPermisos.forEach(cb => { cb.checked = false; cb.disabled = true; });
            btnTodos.disabled = true;
        } else {
            otrosPermisos.forEach(cb => cb.disabled = false);
            btnTodos.disabled = false;
        }
    });
}

actualizarPermisosIngresar();

document.querySelectorAll('.permiso-ingresar').forEach(chk => {
    chk.addEventListener('change', actualizarPermisosIngresar);
});

document.querySelectorAll('.btn-seleccionar-todos').forEach(btn => {
    btn.addEventListener('click', function () {
        const fila = this.closest('tr');
        fila.querySelectorAll(`input[type=checkbox]:not(.permiso-ingresar)`).forEach(cb => cb.checked = true);
    });
});
</script>

</body>
</html>

<?php
} else {
    header("Location: ?pagina=acceso-denegado");
    exit;
}
?>
