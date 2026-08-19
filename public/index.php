<?php
/**
 * Front controller — everything routes through here.
 * Auto-detects the app location so the same file works both in local layout
 * (public/ beside app/) and on cPanel (public_html/ beside staffing-app/).
 */
$candidates = [
    __DIR__ . '/../app/bootstrap.php',                 // local: public/ inside project
    dirname(__DIR__) . '/staffing-app/app/bootstrap.php', // cPanel: ~/public_html + ~/staffing-app
];
foreach ($candidates as $bootstrap) {
    if (is_file($bootstrap)) {
        require $bootstrap;
        return;
    }
}
http_response_code(500);
echo 'Application bootstrap not found. Check DEPLOYMENT.md.';
