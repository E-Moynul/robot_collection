</main>

<footer class="site-footer">
    <p>&copy; <?= date('Y') ?> Robot Collection &middot; Web Application Development Lab Project</p>
</footer>

<script>
    // Mobile menu toggle
    var toggleBtn = document.getElementById('navToggle');
    var navLinks  = document.getElementById('navLinks');
    if (toggleBtn && navLinks) {
        toggleBtn.addEventListener('click', function () {
            navLinks.classList.toggle('open');
        });
    }
</script>

</body>
</html>
