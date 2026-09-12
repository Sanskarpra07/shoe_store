<?php
/**
 * --------------------------------------------------------------------------
 * ADMIN LAYOUT : FOOTER
 * --------------------------------------------------------------------------
 * Shared layout footer for every admin panel page.
 * It closes the wrapper divs opened by includes/header.php and loads the
 * SweetAlert2 + notify.js scripts that power toast messages and the
 * data-confirm delete prompts used across the admin pages.
 * --------------------------------------------------------------------------
 */
?>

<!-- Close main content + admin wrapper opened by the header -->
</div>
</div>

<!-- Global scripts: SweetAlert2 dialogs and the notify helper -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/notify.js"></script>
</body>
</html>
<?php // End of admin layout footer ?>