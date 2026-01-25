<?php
// ===================================================
// Footer partial
// - Shown on every page that includes header + footer
// - Contains site-wide footer + floating chatbot
// ===================================================
?>
    <footer class="mt-5 border-top bg-light">
      <div class="container py-3 d-flex flex-column flex-md-row justify-content-between align-items-center small text-muted">

        <!-- Left side: copyright + project note -->
        <div>
          &copy; <?= date("Y"); ?> Koro High School. All rights reserved.
          <span class="d-block d-md-inline">
            &nbsp;Student Enrollment System – IS413 Project.
          </span>
        </div>

        <!-- Right side: quick footer links -->
        <div class="mt-2 mt-md-0">
          <!-- Social link (replace with real FB page later) -->
          <a href="https://www.facebook.com/" target="_blank" class="text-decoration-none me-3">
            Facebook
          </a>

          <!-- Internal links (you can create these pages later) -->
          <a href="about.php" class="text-decoration-none me-3">
            About
          </a>
        </div>
      </div>
    </footer>

    <?php
    // Floating chatbot – included on every page
    // __DIR__ = /partials, so we go one level up to project root
    include __DIR__ . "/../chat_widget.php";
    ?>

    <!-- Local Bootstrap JS (for navbar, dropdowns, etc.) -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>


  </body>
</html>
