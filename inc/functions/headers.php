<?php
add_action('send_headers', function () {
    $nonce = kb_get_csp_nonce();

    $csp = "default-src 'self'; "
         . "base-uri 'self'; "
         . "form-action 'self'; "
         . "script-src 'self' 'nonce-$nonce'; "
         . "style-src-elem 'self' 'nonce-$nonce'; "
         . "style-src-attr 'unsafe-inline'; "
         . "object-src 'none'; "
         . "frame-ancestors 'self'; "
         . "upgrade-insecure-requests";
    header("Content-Security-Policy: $csp");
});