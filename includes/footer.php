    <footer>
        <p>&copy; <?php echo date('Y'); ?> Attendft. All rights reserved.</p>
        <p>Contact us at: <a href="mailto:ab24367010@ga.ttc.ac.jp">ab24367010@ga.ttc.ac.jp</a></p>
    </footer>

    <script src="<?php echo baseUrl('assets/js/theme.js'); ?>" defer></script>
    <?php if (isset($includeRealTime) && $includeRealTime): ?>
    <script src="<?php echo baseUrl('assets/js/real-time.js'); ?>" defer></script>
    <?php endif; ?>
</body>
</html>
