<?php
/** Integração de leitura com o WordPress instalado, sem filtros de prévia. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 404 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
$slug = basename( dirname( __DIR__ ) );
if ( get_option( 'stylesheet' ) !== $slug || get_option( 'template' ) !== $slug || wp_get_theme()->parent() ) {
    fwrite( STDERR, "FAIL: WordPress must activate FAFAR Institucional as an independent theme.\n" );
    exit( 1 );
}
echo "PASS: installed WordPress loads FAFAR Institucional independently.\n";
