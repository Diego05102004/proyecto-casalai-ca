<!-- Footer de la página -->
<footer class="footer-fijo text-center text-lg-start bg-light text-muted">
  <div class="text-center p-4" style="background-color: #f1f1f1;">
    © 2023 Copyright: CasaLai C.A/ Paula R - Braynt M - Simon F - Diego L - Gabriel M
  </div>
</footer>

<style>
html, body {
    height: 100%;
    margin: 0;
    padding: 0;
}

body {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.main-content {
    flex: 1;
    padding-bottom: 60px; /* Espacio para el footer */
}

.footer-fijo {
    width: 100%;
    background: #f8f9fa;
    text-align: center;
    padding: 10px 0;
    border-top: 1px solid #ddd;
    margin-top: auto; /* Empuja el footer al fondo con flexbox */
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const sidebar = document.querySelector(".sidebar");
  const body = document.querySelector("body");

  // Mostrar menú cuando el mouse está sobre él
  sidebar.addEventListener("mouseenter", function () {
    sidebar.classList.add("active");
  });

  // Ocultar menú cuando se da clic fuera de él
  body.addEventListener("click", function (event) {
    if (!sidebar.contains(event.target)) {
      sidebar.classList.remove("active");
    }
  });
});
</script>

<!-- Scripts consolidados (elimina duplicados) -->
<script src="public/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="public/js/jquery-3.7.1.min.js"></script>
<script src="public/js/jquery.dataTables.min.js"></script>
<script src="public/js/dataTables.bootstrap5.min.js"></script>
<script src="public/js/sweetalert2.js"></script>
<script src="javascript/tabla.js"></script>
<script src="javascript/new_menu.js"></script>
<script src="public/js/chart.js"></script>
<script src="public/js/html2canvas.min.js"></script>
<script src="public/js/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>