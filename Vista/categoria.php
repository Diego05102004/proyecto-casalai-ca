<?php $idRol = $_SESSION['id_rol']; // o el rol actual del usuario
$idModulo = 7;

if (isset($permisosUsuarioEntrar[$idRol][$idModulo]['consultar']) && $permisosUsuarioEntrar[$idRol][$idModulo]['consultar'] === true) { ?>

    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gestionar Categoria</title>
        <?php include 'header.php'; ?>
        <style>
            .caracteristica-item {
                display: flex;
                gap: 10px;
                margin-bottom: 10px;
                align-items: flex-end;
            }
            .caracteristica-item input,
            .caracteristica-item select {
                flex: 1;
            }
            .caracteristica-item .btn-eliminar {
                flex: 0 0 auto;
            }
        </style>
    </head>

    <body class="fondo"
        style=" height: 100vh; background-image: url(assets/img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

        <?php include 'NewNavBar.php'; ?>

        <div class="modal fade modal-registrar" id="registrarCategoriaModal" tabindex="-1" role="dialog"
            aria-labelledby="registrarCategoriaModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <form id="registrarCategoria" method="POST" novalidate>
                        <div class="modal-header">

                                            <button type="button" class="btn-ayuda-modal" title="Ayuda para Incluir Categoria" data-contexto="registrar">
                        <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="18" height="18">
                    </button>
                            <h5 class="titulo-form" id="registrarCategoriaModalLabel">Incluir Categoria</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="registrar">
                            <div class="envolver-form">
                                <label for="nombre_categoria">Nombre de la categoría*</label>
                                <input type="text" placeholder="Categoría" class="control-form" id="nombre_categoria"
                                    name="nombre_categoria" maxlength="20" required>
                                <span class="span-value" id="snombre_categoria"></span>
                            </div>
                            <div class="envolver-form">
                                <label>Características*</label>
                                <div id="caracteristicasContainer"></div>
                                <button type="button" class="btn btn-sm btn-primary mt-2" id="agregarCaracteristica">
                                    <i class="bi bi-plus-circle"></i> Agregar característica</button>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="boton-form btn-primary" type="submit">Registrar</button>
                            <button class="boton-reset btn-primary" type="reset">Limpiar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

<div class="contenedor-tabla">

    <div class="tabla-header" style="width: 75%;">
        <div class="ghost"></div>

        <h3>Lista de Categorias</h3>

        <div class="ghost"></div>
    </div>

    <table class="tablaConsultas" id="tablaConsultas">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($categorias as $categoria): ?>
                <tr data-id="<?php echo $categoria['id_categoria']; ?>">
                    <td>
                        <span class="campo-numeros">
                            <?php echo htmlspecialchars($categoria['id_categoria']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="campo-nombres">
                            <?php echo htmlspecialchars($categoria['nombre_categoria']); ?>
                        </span>
                    </td>
                    <td>
                        <ul>
                            <button class="btn-modificar"
                                id="btnModificarCategoria"
                                title="Modificar Categoria"
                                data-id="<?php echo $categoria['id_categoria']; ?>"
                                data-nombre="<?php echo htmlspecialchars($categoria['nombre_categoria']); ?>">
                                <img src="assets/img/pencil.svg">
                            </button>
                            <button class="btn-eliminar"
                                title="Eliminar Categoria"
                                data-id="<?php echo $categoria['id_categoria']; ?>">
                                <img src="assets/img/circle-x.svg">
                            </button>
                        </ul>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade modal-modificar" id="modificarCategoriaModal" tabindex="-1" role="dialog"
    aria-labelledby="modificarCategoriaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <form id="modificarCategoria" method="POST" novalidate>
                <div class="modal-header">
                                        <button type="button" class="btn-ayuda-modal" title="Ayuda para Modificar Categoria" data-contexto="modificar">
                        <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="18" height="18">
                    </button>
                    <h5 class="titulo-form" id="modificarCategoriaModalLabel">Modificar Categoría</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modificar_id_categoria" name="id_categoria">
                    <div class="form-group">
                        <label for="modificar_nombre_categoria">Nombre de la categoría</label>
                        <input type="text" class="form-control" id="modificar_nombre_categoria"
                            name="nombre_categoria" maxlength="20" required>
                        <span class="span-value-modal" id="smnombre_categoria"></span>
                    </div>
                    <div class="form-group">
                        <label>Características</label>
                        <div id="modificar_caracteristicasContainer"></div>
                        <button type="button" class="btn btn-sm btn-primary mt-2" id="modificar_agregarCaracteristica">
                            <i class="bi bi-plus-circle"></i> Agregar característica
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Modificar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="assets/javascript/categoria.js"></script>
<script src="assets/public/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/public/js/jquery-3.7.1.min.js"></script>
<script src="assets/public/bootstrap/js/sidebar.js"></script>
<script src="assets/public/js/jquery.dataTables.min.js"></script>
<script src="assets/public/js/dataTables.bootstrap5.min.js"></script>
<script src="assets/public/js/datatable.js"></script>

<script>
    /**
 * Protege todos los <select> cuyo id comience con "caracteristicas[".
 * Funciona con selects creados dinámicamente.
 * @param {number} interval - Intervalo de verificación (ms)
 */
function protegerSelectsDinamicos(interval = 1000) {

    const originales = new Map();

    // Función para registrar un nuevo select
    const registrarSelect = (select) => {
        if (!select || originales.has(select)) return;

        const opciones = [...select.options].map(opt => ({
            value: opt.value,
            text: opt.textContent
        }));

        originales.set(select, opciones);
    };

    // Registrar los selects existentes al cargar
    document.querySelectorAll("select[id^='caracteristicas[']").forEach(registrarSelect);

    // Observer para detectar nuevos selects
    const observer = new MutationObserver(() => {
        document.querySelectorAll("select[id^='caracteristicas[']").forEach(registrarSelect);
    });

    observer.observe(document.body, { childList: true, subtree: true });

    // Protección continua
    setInterval(() => {
        originales.forEach((opsOriginales, select) => {
            if (!document.body.contains(select)) {
                // El select ya no existe → eliminar de la protección
                originales.delete(select);
                return;
            }

            const opsActuales = [...select.options];

            const alterado =
                opsActuales.length !== opsOriginales.length ||
                opsActuales.some((o, i) =>
                    o.value !== opsOriginales[i].value ||
                    o.textContent !== opsOriginales[i].text
                );

            if (alterado) {
                // Restaurar
                select.innerHTML = "";
                opsOriginales.forEach(optData => {
                    const opt = document.createElement("option");
                    opt.value = optData.value;
                    opt.textContent = optData.text;
                    select.appendChild(opt);
                });

                console.warn(`⚠ Opciones del <select id="${select.id}"> fueron alteradas y se restauraron.`);
            }
        });
    }, interval);
}

document.addEventListener('DOMContentLoaded', () => {
  const contenedor = document.getElementById('caracteristicasContainer');
  const btnAgregar = document.getElementById('agregarCaracteristica');
    protegerSelectsDinamicos(800);


  let contador = 0;
  const maxCaracteristicas = 5;

  const crearInputCaracteristica = (id, puedeEliminar = true) => {
    const div = document.createElement('div');
    div.classList.add('caracteristica-item');
    div.dataset.index = id;

div.innerHTML = `
  <input type="text" name="caracteristicas[${id}][nombre]" placeholder="Nombre" class="form-control" maxlength="20" required> 
  <select id="caracteristicas[${id}]" name="caracteristicas[${id}][tipo]" class="form-select" required>
    <option value="" disable hidden>Tipo</option>
    <option value="int">Entero</option>
    <option value="float">Decimal</option>
    <option value="string">Texto</option>
  </select>
  <input type="number" name="caracteristicas[${id}][max]" placeholder="Máx. caracteres" class="form-control" min="1" max="255" style="display:none;" required>
  ${puedeEliminar ? `<button type="button" class="btn btn-danger btn-eliminar-caracteristicas">✖</button>` : ''}
`;

// Mostrar/ocultar el input de max solo si es tipo string
const selectTipo = div.querySelector('select[name="caracteristicas[' + id + '][tipo]"]');
const inputMax = div.querySelector('input[name="caracteristicas[' + id + '][max]"]');
selectTipo.addEventListener('change', function() {
  if (this.value === 'string') {
    inputMax.style.display = '';
    inputMax.required = true;
  } else {
    inputMax.style.display = 'none';
    inputMax.required = false;
    inputMax.value = '';
  }
});

    if (puedeEliminar) {
      div.querySelector('.btn-eliminar-caracteristicas').addEventListener('click', () => {
        contenedor.removeChild(div);
        // Recalcular total y estado del botón tras eliminar
        const total = contenedor.querySelectorAll('.caracteristica-item').length;
        btnAgregar.disabled = (total >= maxCaracteristicas);
      });
    }

    contenedor.appendChild(div);
  };

  // Agrega una característica inicial no eliminable
  crearInputCaracteristica(contador++, false);

  btnAgregar.addEventListener('click', () => {
    // Recalcular contador según elementos actuales para soportar resets/cierres
    contador = contenedor.querySelectorAll('.caracteristica-item').length;
    if (contador < maxCaracteristicas) {
      crearInputCaracteristica(contador++);
    }
    // Actualizar estado del botón según total actual
    const total = contenedor.querySelectorAll('.caracteristica-item').length;
    btnAgregar.disabled = (total >= maxCaracteristicas);
  });
});
</script>
    <button 
        class="btn-ayuda"
        title="Visualizar Ayuda"
        onclick="window.location.href='?pagina=ayuda'">
        <img src="assets/img/info-ayuda.svg" alt="Ayuda" width="20" height="20">
    </button>
    </body>

    </html>

    <?php
} else {
    header("Location: ?pagina=acceso-denegado");
    exit;
}
?>
