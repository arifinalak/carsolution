</main>
<?php $mainJsVersion = (string) @filemtime(__DIR__ . '/../assets/js/main.js'); ?>
<script src="/assets/js/main.js?v=<?= esc($mainJsVersion !== '' ? $mainJsVersion : '1') ?>"></script>
</body>
</html>
