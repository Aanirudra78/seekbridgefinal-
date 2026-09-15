<?php
// auth_footer.php - Closing tags for public/auth pages
?>
</div><!-- /.auth-main -->

<footer class="footer-bar">
  <div class="container">
    <div class="mb-1 fw-semibold text-navy"><i class="fa-solid fa-graduation-cap me-1"></i> TechNova</div>
    Academia-Industry Collaboration Portal &copy; <?php echo date('Y'); ?> — Bridging universities and enterprises. Made with professional care.
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/chart.umd.min.js"></script>
<script>
  // Navbar shadow on scroll
  window.addEventListener('scroll', function () {
    var nav = document.getElementById('publicNav');
    if (!nav) return;
    if (window.scrollY > 10) nav.classList.add('scrolled');
    else nav.classList.remove('scrolled');
  });
</script>
</body>
</html>