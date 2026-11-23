<?php
/** Run with: php tests/curricular-matrix.php */
define( 'ABSPATH', __DIR__ );
function get_stylesheet_directory() { return dirname( __DIR__ ); }
function is_page( $slug ) { return $slug === $GLOBALS['matrix_test_page']; }
function get_header() {}
function get_footer() {}
function get_permalink() { return '/documentos/'; }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_url( $value ) { return esc_html( $value ); }
function matrix_check( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
}
function matrix_render( $page ) {
	$GLOBALS['matrix_test_page'] = $page;
	ob_start();
	require dirname( __DIR__ ) . '/template-matriz-curricular.php';
	return ob_get_clean();
}

$data = json_decode( file_get_contents( dirname( __DIR__ ) . '/data/matrizes-curriculares.json' ), true, 512, JSON_THROW_ON_ERROR );
foreach ( $data as $key => $entries ) {
	$codes = array();
	$last_period = 0;
	foreach ( $entries as $entry ) {
		matrix_check( $entry['periodo'] >= $last_period, "Unsorted periods: $key" );
		matrix_check( ! isset( $codes[ $entry['codigo'] ] ), "Duplicate activity: $key" );
		matrix_check( $entry['total'] === $entry['teorica'] + $entry['pratica'], "Invalid hours: $key" );
		matrix_check( ',' !== substr( $entry['requisitos'], -1 ), "Truncated prerequisites: $key" );
		$last_period = $entry['periodo'];
		$codes[ $entry['codigo'] ] = $entry;
	}
	if ( 'farmacia-noturno-2016' === $key ) {
		matrix_check( 13 === $codes['FAF076']['periodo'], 'The 2016 evening internship must remain in period 13.' );
	}
	if ( in_array( $key, array( 'farmacia-diurno-2018', 'farmacia-noturno-2018' ), true ) ) {
		matrix_check( 'PFA029, PFA608, PFA031, ALM027, ACT005, FAS013' === $codes['FAF094']['requisitos'], 'Multiline prerequisites must be imported completely.' );
		matrix_check( 75 === $codes['MOF142']['total'], 'Historical hours must come from the historical document.' );
	}
}

$html = matrix_render( 'da-matriz-curricular-far' );
matrix_check( 6 === substr_count( $html, 'class="curriculum-set"' ), 'All six pharmacy matrices must render.' );
foreach ( array( 'D-20252', 'N-20252', 'D-20181', 'N-20181', 'D-20161X', 'N-20161X' ) as $version ) {
	matrix_check( false !== strpos( $html, $version ), "Missing official document: $version" );
}
matrix_check( false !== strpos( $html, 'id="curriculum-version"' ), 'Pharmacy needs a version selector.' );
matrix_check( false !== strpos( $html, '<option value="13">13º período</option>' ), 'Period 13 must be selectable.' );
matrix_check( 4 === substr_count( $html, 'Representação curricular ·' ), 'The four historical representations must remain accessible.' );
preg_match_all( '/<section class="curriculum-set".*?(?=<section class="curriculum-set"|<p class="curriculum-empty")/s', $html, $sets );
foreach ( array_slice( $sets[0], 2 ) as $historical_set ) {
	matrix_check( false === strpos( $historical_set, 'curriculum-bibliography' ), 'Current bibliography must not be attributed to historical versions.' );
}

$html = matrix_render( 'da-matriz-curricular-bio' );
matrix_check( 1 === substr_count( $html, 'class="curriculum-set"' ), 'Biomedicine must keep its current matrix.' );
matrix_check( false !== strpos( $html, 'N-20262.pdf' ), 'Biomedicine official document changed.' );
matrix_check( false === strpos( $html, 'id="curriculum-version"' ), 'Biomedicine must not display pharmacy versions.' );
matrix_check( false !== strpos( $html, 'curriculum-bibliography' ), 'Biomedicine bibliography must remain available.' );
echo "PASS: curriculum data, historical hours and prerequisites, six pharmacy versions, official documents, and biomedicine.\n";
