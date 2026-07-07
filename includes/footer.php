<?php
// ============================================================
//  NightERP Pro — Pied de page
//  Fichier : includes/footer.php
// ============================================================
?>
</main>

<div id="toast-container"></div>

<script src="<?= $root ?>assets/js/app.js"></script>
<script>
// Horloge topbar — placée ici pour s'exécuter après le DOM complet
(function tick() {
    var n  = new Date();
    var el = document.getElementById('tb-time');
    if (el) {
        el.textContent = n.toLocaleDateString('fr-FR', {
            weekday: 'long', day: 'numeric', month: 'long'
        }) + ' · ' + n.toLocaleTimeString('fr-FR', {
            hour: '2-digit', minute: '2-digit'
        });
    }
    setTimeout(tick, 1000);
})();
</script>
</body>
</html>
