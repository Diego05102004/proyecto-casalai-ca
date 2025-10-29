<?php $idRol = $_SESSION['id_rol']; // o el rol actual del usuario
$idModulo = 11;

if (isset($permisosUsuario[$idRol][$idModulo]['consultar']) && $permisosUsuario[$idRol][$idModulo]['consultar'] === true) { ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito de Compras</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="public/bootstrap/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <?php include 'header.php'; ?>
<style>
.tabladeConsultas {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.tabladeConsultas thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.tabladeConsultas thead th {
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 16px 12px;
    border: none;
    text-align: center;
}

.tabladeConsultas thead th:first-child {
    text-align: left;
    padding-left: 20px;
}

.tabladeConsultas thead th:last-child {
    padding-right: 20px;
}

.tabladeConsultas tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #f1f5f9;
}

.tabladeConsultas tbody tr:hover {
    background-color: #f8fafc;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.tabladeConsultas tbody td {
    padding: 16px 12px;
    text-align: center;
    color: #475569;
    font-size: 0.95rem;
    border: none;
}

.tabladeConsultas tbody td:first-child {
    text-align: left;
    padding-left: 20px;
    font-weight: 500;
    color: #1e293b;
}

.tabladeConsultas tbody td:last-child {
    padding-right: 20px;
}

/* Estilo para la fila del total */
.tabladeConsultas tbody tr.table-active {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    font-weight: 700;
}

.tabladeConsultas tbody tr.table-active td {
    color: #1e293b;
    font-size: 1rem;
    border-top: 2px solid #cbd5e1;
}

.tabladeConsultas tbody tr.table-active td:nth-child(4) {
    color: #059669;
    font-size: 1.1rem;
}

/* Estilo para cuando no hay productos */
.tabladeConsultas tbody tr:last-child td {
    padding: 40px 20px;
    color: #64748b;
    font-style: italic;
}

.tabladeConsultas tbody tr:last-child td i {
    color: #94a3b8;
    margin-bottom: 8px;
}

/* Estilo para los botones de cantidad */
.cantidad-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    max-width: 140px;
    margin: 0 auto;
}

.btn-decrement, .btn-increment {
    width: 32px;
    height: 32px;
    border: 1px solid #d1d5db;
    background: white;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #6b7280;
}

.btn-decrement:hover, .btn-increment:hover {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
    transform: scale(1.05);
}
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 4px;
            margin-right: 15px;
            border: 1px solid #dee2e6;
        }

.cantidad-wrapper .form-control.cantidad {
    width: 60px;
    text-align: center;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 6px;
    font-weight: 600;
    background: #f9fafb;
}

/* Estilo para el botón eliminar */
.btn-eliminar {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    border: none;
    border-radius: 8px;
    padding: 8px 12px;
    color: white;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);
}

.btn-eliminar:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3);
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
}

/* Estilo para los subtotales */
.subtotal {
    font-weight: 600;
    color: #059669;
}

/* Responsive */
@media (max-width: 768px) {
    .tabladeConsultas {
        font-size: 0.85rem;
    }
    
    .tabladeConsultas thead th,
    .tabladeConsultas tbody td {
        padding: 12px 8px;
    }
    
    .tabladeConsultas thead th:first-child,
    .tabladeConsultas tbody td:first-child {
        padding-left: 12px;
    }
    
    .tabladeConsultas thead th:last-child,
    .tabladeConsultas tbody td:last-child {
        padding-right: 12px;
    }
    
    .cantidad-wrapper {
        max-width: 120px;
    }
}
</style>
</head>
<body  class="fondo" style=" height: 100vh; background-image: url(img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

<?php include 'newnavbar.php'; ?>

<div class="contenedor-tabla">

    <div class="tabla-header">
        <div class="ghost"></div>
        
        <h3>PRODUCTOS EN EL CARRITO</h3>

        <div class="ghost"></div>
    </div>

    <table class="tabladeConsultas tabla">
        <thead>
            <tr>
                <th>Imagen y Serial</th>
                <th>Nombre del Producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($carritos)): ?>
                <?php 
                $total = 0;
                foreach ($carritos as $carrito): 
                    $total += $carrito['subtotal'];
                ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <?php if (!empty($carrito['imagen'])): ?>
                                    <img src="<?= htmlspecialchars($carrito['imagen']) ?>" class="product-image"
                                        alt="<?= htmlspecialchars($carrito['nombre']) ?>"
                                        onerror="this.src='img/placeholder-product.png'">
                                <?php else: ?>
                                    <div class="product-image img-placeholder">
                                        <i class="bi bi-image"></i>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($carrito['nombre'].' Modelo '.$carrito['nombre_modelo'].' de la Marca '.$carrito['nombre_marca']); ?></td>
                        <td>
                            <div class="cantidad-wrapper">
                                <button type="button" class="btn-decrement" data-id-carrito-detalle="<?php echo htmlspecialchars($carrito['id_carrito_detalle']); ?>"><i class="bi bi-dash"></i></button>
                                <input type="number"
                                    class="form-control cantidad"
                                    value="<?php echo htmlspecialchars($carrito['cantidad']); ?>"
                                    min="1"
                                    readonly
                                    data-id-carrito-detalle="<?php echo htmlspecialchars($carrito['id_carrito_detalle']); ?>"
                                    data-id-producto="<?php echo htmlspecialchars($carrito['id_producto']); ?>">
                                <button type="button" class="btn-increment" data-id-carrito-detalle="<?php echo htmlspecialchars($carrito['id_carrito_detalle']); ?>"><i class="bi bi-plus"></i></button>
                            </div>
                        </td>
                        
                        <td><?php echo number_format($carrito['precio']*$data['monitors']['bcv']['price'], 2); ?> BS</td>
                        <td class="subtotal"><?php echo number_format($carrito['subtotal']*$data['monitors']['bcv']['price'], 2); ?> BS</td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm btn-eliminar" 
                                    data-id="<?php echo htmlspecialchars($carrito['id_carrito_detalle']); ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="table-active">
                    <td colspan="4" class="text-end fw-bold">TOTAL:</td>
                    <td class="fw-bold"><?php echo number_format($total*$data['monitors']['bcv']['price'], 2); ?> BS</td>
                    <td></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <i class="bi bi-cart-x" style="font-size: 2rem;"></i>
                        <p class="mt-2">No hay productos en el carrito.</p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
            
    <!-- Botones de acción -->
    <div class="d-flex justify-content-between mt-3">
        <?php if (!empty($carritos)): ?>
            <button type="button" class="btn btn-danger" id="eliminar-todo-carrito">
                <i class="bi bi-trash"></i> Eliminar Todo el Carrito
            </button>
            <button type="button" class="btn btn-success" id="registrar-compra">
                <i class="bi bi-check-circle"></i> Prefacturar
            </button>
        <?php endif; ?>
    </div>
</div>

    <script src="public/bootstrap/js/sidebar.js"></script>
    <script src="public/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/jquery-3.7.1.min.js"></script>
    <script src="public/js/jquery.dataTables.min.js"></script>
    <script src="public/js/dataTables.bootstrap5.min.js"></script>
    <script src="public/js/datatable.js"></script>
    <script src="javascript/sweetalert2.all.min.js"></script>
    <script src="javascript/carrito.js"></script>

    <script>
        $(document).on('click', '.btn-increment', function () {
            let input = $(this).siblings('input.cantidad');
            let val = parseInt(input.val()) || 1;
            input.val(val + 1).trigger('change');
        });

        $(document).on('click', '.btn-decrement', function () {
            let input = $(this).siblings('input.cantidad');
            let val = parseInt(input.val()) || 1;
            if (val > 1) {
                input.val(val - 1).trigger('change');
            }
        });
    </script>

<?php include 'footer.php'; ?>
</body>
</html>

<?php
} else {
    header("Location: ?pagina=acceso-denegado");
    exit;
}
?>