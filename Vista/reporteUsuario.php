<?php $idRol = $_SESSION['id_rol']; // o el rol actual del usuario
$idModulo = 1;

if (isset($permisosUsuarioEntrar[$idRol][$idModulo]['consultar']) && $permisosUsuarioEntrar[$idRol][$idModulo]['consultar'] === true) { ?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Usuarios</title>
    <?php include 'header.php'; ?>
</head>

<body class="fondo"
    style=" height: 100vh; background-image: url(img/fondo.jpg); background-size: cover; background-position: center; background-repeat: no-repeat;">

    <?php include 'newnavbar.php'; ?>

    <div class="report-container" style="max-width: 1100px; margin: 40px auto; background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
        <h2 class="titulo-form" style="text-align:center">Reportes de Usuarios</h2>
        <div class="parameters-container" style="margin: 16px 0; background:#f8f9fa; border-radius: 10px; padding: 16px;">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="selectReporteUsuarios" class="form-label">Reporte</label>
                    <select id="selectReporteUsuarios" class="form-select">
                        <option value="roles" selected>Usuarios por Rol</option>
                        <option value="estatus">Usuarios por Estatus</option>
                        <option value="dominio">Usuarios por Dominio de Correo</option>
                        <option value="inicial_nombre">Usuarios por Inicial de Nombre</option>
                        <option value="inicial_apellido">Usuarios por Inicial de Apellido</option>
                        <option value="area_telefono">Usuarios por Prefijo Telefónico</option>
                    </select>
                </div>
                <div id="parametrosUsuarios" class="col-md-8 row g-3"></div>
                <div class="col-md-12" style="text-align:right;">
                    <button id="btnGenerarUsuarios" class="btn btn-primary">Generar Reporte</button>
                    <button id="descargarPDFUsuarios" class="btn btn-success">Descargar PDF</button>
                </div>
            </div>
        </div>

        <div id="reporteUsuarios" class="report-section" style="padding: 16px; border:1px solid #e0e0e0; border-radius: 10px;">
            <h3 id="tituloReporteUsuarios" class="titulo-form" style="font-size:20px;">Usuarios por Rol</h3>
            <div class="chart-container" style="display:flex; gap:20px; flex-wrap:wrap; align-items:center; justify-content:center;">
                <div class="chart-canvas" style="flex:1; min-width:300px; text-align:center;">
                    <canvas id="graficoUsuarios" width="380" height="280"></canvas>
                </div>
                <div class="chart-table" style="flex:2; min-width:380px;">
                    <div id="tablaUsuarios"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
    const reporteRolesPHP = <?= json_encode($reporteRoles ?? []) ?>;
    const usuariosTodos = <?= json_encode($usuariosTodos ?? []) ?>;

    let graficoUsuarios = null;

    function generarColores(n){
        return Array.from({length: Math.max(n,1)}, (_,i)=>`hsl(${Math.round((360/Math.max(n,1))*i)},70%,60%)`);
    }

    function agrupar(rows, key){
        const m = new Map();
        (rows||[]).forEach(r=>{
            const k = String(r[key] ?? '').trim() || 'Sin dato';
            m.set(k, (m.get(k)||0) + 1);
        });
        return Array.from(m.entries()).map(([label,value])=>({label,value})).sort((a,b)=>b.value-a.value);
    }

    function dominioCorreo(correo){
        if(!correo) return 'Sin dominio';
        const i = String(correo).indexOf('@');
        return i>0 ? String(correo).slice(i+1).toLowerCase() : 'Sin dominio';
    }

    function getParametrosUsuarios(){
        return {
            rol: document.getElementById('paramRol')?.value || '',
            estatus: document.getElementById('paramEstatus')?.value || '',
            dominio: document.getElementById('paramDominio')?.value || '',
            inicial: document.getElementById('paramInicial')?.value || '',
            area: document.getElementById('paramArea')?.value || '',
            topN: parseInt(document.getElementById('paramTopN')?.value||'0',10) || 0
        };
    }

    function buildParametrosUsuarios(){
        const tipo = document.getElementById('selectReporteUsuarios').value;
        const cont = document.getElementById('parametrosUsuarios');
        let html = '';
        if (tipo==='roles'){
            const roles = Array.from(new Set((reporteRolesPHP||[]).map(r=>r.nombre_rol))).sort();
            html += `<div class="col-md-4"><label>Rol</label><select id="paramRol" class="form-select"><option value="">Todos</option>${roles.map(r=>`<option value="${r}">${r}</option>`).join('')}</select></div>`;
        } else if (tipo==='estatus'){
            html += `<div class="col-md-3"><label>Estatus</label><select id="paramEstatus" class="form-select"><option value="">Todos</option><option value="habilitado">Habilitado</option><option value="deshabilitado">Deshabilitado</option></select></div>`;
        } else if (tipo==='dominio'){
            const dominios = Array.from(new Set((usuariosTodos||[]).map(u=>dominioCorreo(u.correo)))).sort();
            html += `<div class="col-md-4"><label>Dominio</label><select id="paramDominio" class="form-select"><option value="">Todos</option>${dominios.map(d=>`<option value="${d}">${d}</option>`).join('')}</select></div>`;
            html += `<div class="col-md-2"><label>Top</label><select id="paramTopN" class="form-select"><option value="0">Todos</option><option value="5">Top 5</option><option value="10">Top 10</option><option value="20">Top 20</option></select></div>`;
        } else if (tipo==='inicial_nombre' || tipo==='inicial_apellido'){
            const letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
            html += `<div class=\"col-md-3\"><label>Inicial</label><select id=\"paramInicial\" class=\"form-select\"><option value=\"\">Todas</option>${letras.map(l=>`<option value="${l}">${l}</option>`).join('')}</select></div>`;
        } else if (tipo==='area_telefono'){
            const areas = Array.from(new Set((usuariosTodos||[]).map(u=>String(u.telefono||'').slice(0,4)).filter(v=>v))).sort();
            html += `<div class=\"col-md-3\"><label>Prefijo</label><select id=\"paramArea\" class=\"form-select\"><option value=\"\">Todos</option>${areas.map(a=>`<option value="${a}">${a}</option>`).join('')}</select></div>`;
        }
        cont.innerHTML = html;
    }

    function renderUsuarios(datos, titulo, detalles){
        const canvas = document.getElementById('graficoUsuarios');
        const tabla = document.getElementById('tablaUsuarios');
        document.getElementById('tituloReporteUsuarios').textContent = titulo;
        if (!datos || datos.length===0){
            if (graficoUsuarios){ graficoUsuarios.destroy(); graficoUsuarios=null; }
            tabla.innerHTML = `<div class="alert alert-warning text-center">No hay datos</div>`;
            const ctx = canvas.getContext('2d'); ctx.clearRect(0,0,canvas.width,canvas.height);
            return;
        }
        const labels = datos.map(d=>d.label);
        const values = datos.map(d=>d.value);
        const colores = generarColores(labels.length);
        let tablaHtml = `<div class=\"table-responsive\"><table class=\"table table-bordered table-striped table-hover\"><thead><tr><th>Descripción</th><th>Cantidad</th></tr></thead><tbody>`;
        datos.forEach(d=>{ tablaHtml += `<tr><td>${d.label}</td><td>${d.value}</td></tr>`; });
        tablaHtml += `</tbody><tfoot><tr><th>Total</th><th>${values.reduce((a,b)=>a+b,0)}</th></tr></tfoot></table></div>`;

        // Si se proveen detalles (lista de usuarios), agregamos una tabla adicional
        if (detalles && detalles.length){
            tablaHtml += `<div class=\"mt-3\"></div>`;
            tablaHtml += `<div class=\"table-responsive\"><table class=\"table table-bordered table-striped table-hover\"><thead><tr><th>#</th><th>Nombres</th><th>Apellidos</th><th>Usuario</th><th>Rol</th><th>Estatus</th></tr></thead><tbody>`;
            detalles.forEach((u,idx)=>{
                const est = (u.estatus||'').toString();
                tablaHtml += `<tr><td>${idx+1}</td><td>${u.nombres||''}</td><td>${u.apellidos||''}</td><td>${u.username||''}</td><td>${u.nombre_rol||''}</td><td>${est}</td></tr>`;
            });
            tablaHtml += `</tbody></table></div>`;
        }
        tabla.innerHTML = tablaHtml;

        if (graficoUsuarios){ graficoUsuarios.destroy(); }
        graficoUsuarios = new Chart(canvas.getContext('2d'),{
            type:'bar',
            data:{ labels, datasets:[{ label: titulo, data: values, backgroundColor: colores }] },
            options:{ plugins:{ legend:{ display:true, position:'bottom' } }, scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } } }
        });
    }

    function generarUsuarios(){
        const tipo = document.getElementById('selectReporteUsuarios').value;
        const p = getParametrosUsuarios();
        if (tipo==='roles'){
            let rows = (reporteRolesPHP||[]).map(r=>({label: r.nombre_rol, value: Number(r.cantidad||0)}));
            if (p.rol){ const n=p.rol.toLowerCase(); rows = rows.filter(x=>x.label.toLowerCase()===n); }
            renderUsuarios(rows, 'Usuarios por Rol');
        } else if (tipo==='estatus'){
            let base = usuariosTodos || [];
            if (p.estatus){ base = base.filter(u=>String(u.estatus||'').toLowerCase()===p.estatus); }
            renderUsuarios(agrupar(base,'estatus'), 'Usuarios por Estatus');
        } else if (tipo==='dominio'){
            let base = (usuariosTodos||[]).map(u=>({ dominio: dominioCorreo(u.correo) }));
            let rows = agrupar(base.map(x=>({dominio:x.dominio})), 'dominio');
            if (p.dominio){ const n=p.dominio.toLowerCase(); rows = rows.filter(x=>x.label.toLowerCase()===n); }
            if (p.topN>0){ rows = rows.slice(0,p.topN); }
            renderUsuarios(rows, 'Usuarios por Dominio de Correo');
        } else if (tipo==='inicial_nombre'){
            const users = usuariosTodos || [];
            let base = users.map(u=>({ ini: String(u.nombres||'').trim().charAt(0).toUpperCase()||'?', u }));
            let detalles = [];
            if (p.inicial){
                base = base.filter(x=>x.ini===p.inicial.toUpperCase());
                detalles = base.map(x=>x.u);
            }
            const rows = agrupar(base.map(x=>({ini:x.ini})), 'ini').map(x=>({label:x.label, value:x.value}));
            renderUsuarios(rows, 'Usuarios por Inicial de Nombre', detalles);
        } else if (tipo==='inicial_apellido'){
            const users = usuariosTodos || [];
            let base = users.map(u=>({ ini: String(u.apellidos||'').trim().charAt(0).toUpperCase()||'?', u }));
            let detalles = [];
            if (p.inicial){
                base = base.filter(x=>x.ini===p.inicial.toUpperCase());
                detalles = base.map(x=>x.u);
            }
            const rows = agrupar(base.map(x=>({ini:x.ini})), 'ini').map(x=>({label:x.label, value:x.value}));
            renderUsuarios(rows, 'Usuarios por Inicial de Apellido', detalles);
        } else if (tipo==='area_telefono'){
            let base = (usuariosTodos||[]).map(u=>({ area: String(u.telefono||'').slice(0,4)||'N/A' }));
            if (p.area){ base = base.filter(x=>x.area===p.area); }
            const rows = agrupar(base.map(x=>({area:x.area})), 'area').map(x=>({label:x.label, value:x.value}));
            renderUsuarios(rows, 'Usuarios por Prefijo Telefónico');
        }
    }

    document.getElementById('selectReporteUsuarios').addEventListener('change', ()=>{ buildParametrosUsuarios(); });
    document.getElementById('btnGenerarUsuarios').addEventListener('click', (e)=>{ e.preventDefault(); generarUsuarios(); });
    document.getElementById('descargarPDFUsuarios').addEventListener('click', function () {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'portrait', unit: 'pt', format: 'a4' });
        const cont = document.querySelector('.report-container');
        html2canvas(cont).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pageWidth = doc.internal.pageSize.getWidth();
            const imgWidth = pageWidth - 40;
            const imgHeight = canvas.height * imgWidth / canvas.width;
            doc.addImage(imgData, 'PNG', 20, 20, imgWidth, imgHeight);
            doc.save('Reporte_Usuarios.pdf');
        });
    });

    buildParametrosUsuarios();
    generarUsuarios();
    </script>

    <?php include 'footer.php'; ?>
    <script src="public/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="public/js/jquery-3.7.1.min.js"></script>
    <script src="public/js/jquery.dataTables.min.js"></script>
    <script src="public/js/dataTables.bootstrap5.min.js"></script>
    <script src="public/js/datatable.js"></script>
</body>
</html>
<?php
} else {
    header("Location: ?pagina=acceso-denegado");
    exit;
}
?>