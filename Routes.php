<?php
/**
 * Routes.php — BabiSoft Framework
 * API REST complète — tous modules
 */

header('Content-Type: application/json; charset=utf-8');

// ══════════════════════════════════════════════════════════════
//  HOME
// ══════════════════════════════════════════════════════════════
App::route('GET', '/', function () {
    header('location: public/index.html');
});

?>
