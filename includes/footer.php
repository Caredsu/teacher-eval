<?php
/**
 * Admin Footer
 */
?>

<style>
    .footer-minimal {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 1.5rem 0;
        margin-top: auto;
        text-align: center;
        width: 100%;
    }

    .footer-minimal p {
        margin: 0;
        color: #000000;
        font-size: 13px;
        font-weight: 500;
    }

    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    /* Main wrapper for pages - expands to push footer down */
    main, .main-content {
        flex: 1;
    }
</style>

<footer class="footer-minimal">
    <div class="container-fluid">
        <p>&copy; 2026 Fullbright College Inc. Teacher Evaluation System</p>
    </div>
</footer>
