<?php
/**
 * Footer Global Aplikasi SM Sport Center
 */
?>
        </div> <!-- End of container-fluid -->
    </div> <!-- End of #content -->
</div> <!-- End of .wrapper -->

<!-- Bootstrap 5 Bundle JS with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Sidebar Collapse Script -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const sidebarCollapseBtn = document.getElementById("sidebarCollapse");
        const sidebar = document.getElementById("sidebar");
        
        if (sidebarCollapseBtn && sidebar) {
            sidebarCollapseBtn.addEventListener("click", function () {
                sidebar.classList.toggle("active");
            });
        }
    });
</script>
</body>
</html>
<?php endif; ?>
