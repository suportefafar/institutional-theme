<?php
/** Componentes compartilhados pelas páginas geradas pelo tema. */
function fafar_dynamic_query( $query, $single = false ) {
    global $wpdb;
    $previous = $wpdb->suppress_errors();
    try {
        $result = $single ? $wpdb->get_row( $query ) : $wpdb->get_results( $query );
        return $wpdb->last_error ? false : $result;
    } finally {
        $wpdb->suppress_errors( $previous );
    }
}

function fafar_dynamic_notice( $message, $type = 'info' ) {
    return '<p class="dynamic-notice dynamic-notice-' . esc_attr( $type ) . '" role="status">' . esc_html( $message ) . '</p>';
}

function fafar_dynamic_table( $headings, $rows, $label, $body_id, $class = '' ) {
    echo '<div class="table-responsive dynamic-table-scroll" role="region" aria-label="' . esc_attr( $label ) . '" tabindex="0"><table class="table dynamic-table ' . esc_attr( $class ) . '"><thead><tr>';
    foreach ( $headings as $heading ) { echo '<th scope="col">' . esc_html( $heading ) . '</th>'; }
    echo '</tr></thead><tbody id="' . esc_attr( $body_id ) . '">' . $rows;
    echo '<tr data-filter-empty' . ( $rows ? ' hidden' : '' ) . '><td colspan="' . count( $headings ) . '" class="dynamic-empty">Nenhum resultado encontrado.</td></tr></tbody></table></div>';
}

function fafar_dynamic_routes() {
    return array(
        'mapa-de-salas' => 'mapa_salas_content_handler',
        'mapa-de-salas-2' => 'mapa_salas_content_handler',
        'ementas' => 'ementas_content_handler',
        'baixar-ementas' => 'baixar_ementas_content_handler',
        'baixar-ementas-biomedicina' => 'baixar_ementas_content_handler',
        'validarementa' => 'validar_ementas_content_handler',
        'emitir-certificados' => 'fafar_dynamic_certificates_maintenance',
        'tecnicos-administrativos' => 'tecnicos_administrativos_content_handler',
        'corpo-administrativo-act' => 'corpo_administrativo_act_content_handler',
        'corpo-administrativo-alm' => 'corpo_administrativo_alm_content_handler',
        'corpo-administrativo-fas' => 'corpo_administrativo_fas_content_handler',
        'corpo-administrativo-pfa' => 'corpo_administrativo_pfa_content_handler',
        'corpo-docente-act' => 'corpo_docente_act_content_handler',
        'corpo-docente-alm' => 'corpo_docente_alm_content_handler',
        'corpo-docente-2' => 'corpo_docente_fas_content_handler',
        'corpo-docente-pfa' => 'corpo_docente_pfa_content_handler',
        'corpo-docente' => 'corpo_docente_all_content_handler',
    );
}

function fafar_dynamic_certificates_maintenance() {
    echo '<p class="dynamic-intro">Consulte os certificados de participação nas atividades da Faculdade.</p>';
    echo fafar_dynamic_notice( 'A emissão de certificados está em manutenção. Tente novamente mais tarde.' );
}

// Os formulários passam a ser renderizados pelo tema; o conteúdo legado fica preservado no banco.
add_filter( 'the_content', function ( $content ) {
    return in_the_loop() && is_main_query() && is_page( array( 'ementas', 'validarementa' ) ) ? '' : $content;
}, 8 );

add_action( 'wp_enqueue_scripts', function () {
    if ( is_page( array_keys( fafar_dynamic_routes() ) ) ) {
        wp_enqueue_script( 'fafar-dynamic-pages', get_stylesheet_directory_uri() . '/assets/js/dynamic-pages.js', array(), filemtime( get_stylesheet_directory() . '/assets/js/dynamic-pages.js' ), true );
    }
} );
