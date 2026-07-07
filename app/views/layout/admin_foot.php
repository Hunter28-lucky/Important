            </div> <!-- /content-body -->
        </main> <!-- /main-content -->
    </div> <!-- /admin-layout -->

    <!-- Mobile Navigation Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    sidebar.classList.toggle('active');
                });

                // Dismiss sidebar when clicking outside on mobile
                document.addEventListener('click', function(e) {
                    if (sidebar.classList.contains('active') && !sidebar.contains(e.target) && e.target !== toggleBtn) {
                        sidebar.classList.remove('active');
                    }
                });
            }
        });
    </script>
</body>
</html>
