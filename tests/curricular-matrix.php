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
	if ( 'biomedicina-20131' === $key ) {
		matrix_check( 'MOF034, QUI208, BIG028' === $codes['ACT046']['requisitos'], 'Biomedicine multiline prerequisites must remain complete.' );
		matrix_check( 'ACT607, ACT045, ACT601' === $codes['ACT053']['requisitos'], 'The 2013 training prerequisites must use the 2013 version.' );
	}
	if ( 'biomedicina-20162' === $key ) {
		matrix_check( 'ACT010, ACT045, ACT601' === $codes['ACT053']['requisitos'], 'The 2016 training prerequisites must use the 2016 version.' );
	}
	if ( 0 === strpos( $key, 'biomedicina-' ) ) {
		matrix_check( 11 === $last_period, 'Historical biomedicine matrices must include period 11.' );
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
matrix_check( 6 === substr_count( $html, 'class="curriculum-set"' ), 'All six biomedicine matrices must render.' );
matrix_check( false !== strpos( $html, 'N-20262.pdf' ), 'Biomedicine official document changed.' );
matrix_check( false !== strpos( $html, 'id="curriculum-version"' ), 'Biomedicine needs a version selector.' );
matrix_check( false === strpos( $html, 'id="curriculum-course"' ), 'Biomedicine has only the evening shift.' );
matrix_check( false !== strpos( $html, '<option value="11">11º período</option>' ), 'Biomedicine period 11 must be selectable.' );
foreach ( array( '20262', '20189', '20181', '20162', '20131', '20101X' ) as $version ) {
	matrix_check( false !== strpos( $html, '<option value="' . $version . '">N-' . $version . '</option>' ), "Missing biomedicine version: $version" );
}
matrix_check( false !== strpos( $html, 'Matriz-Curricular-BiomedicinaN-20189.pdf' ), 'The 20189 version must use its official PDF.' );
matrix_check( false !== strpos( $html, 'curriculum-bibliography' ), 'Biomedicine bibliography must remain available.' );
preg_match_all( '/<section class="curriculum-set".*?(?=<section class="curriculum-set"|<p class="curriculum-empty")/s', $html, $sets );
foreach ( array_slice( $sets[0], 1 ) as $historical_set ) {
	matrix_check( false === strpos( $historical_set, 'curriculum-bibliography' ), 'Current biomedicine bibliography must not be attributed to historical versions.' );
}
echo "PASS: curriculum data, historical hours and prerequisites, six versions per course, and official documents.\n";
