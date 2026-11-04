<?php
/** php tests/intranet-map.php — no real database or network. */
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function esc_html($s) { return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }
function esc_attr($s) { return esc_html($s); }
function get_stylesheet_directory_uri() { return '/theme'; }
define( 'ARRAY_A', 'ARRAY_A' );
class MapDatabase {
    public $last_error = '';
    public $suppressed = false;
    public $fail = false;
    public $queries = array();
    public $bound = array();
    function suppress_errors( $value = true ) { $old = $this->suppressed; $this->suppressed = $value; return $old; }
    function prepare( $sql, $values ) { $this->bound = $values; return $sql; }
    function get_results( $sql, $format ) {
        $this->queries[] = $sql;
        $this->last_error = $this->fail ? 'database unavailable' : '';
        if ( $this->fail ) { return null; }
        if ( strpos( $sql, "'reservation'" ) !== false ) {
            return array( array( 'data' => json_encode( array( 'date' => date( 'Y-m-d' ), 'class_subject' => array( 'subject-A1' ), 'place' => array( 'room-B2' ), 'weekdays' => array( 1, 3 ), 'start_time' => '08:00', 'end_time' => '10:00' ) ) ) );
        }
        return array(
            array( 'id' => 'subject-A1', 'data' => json_encode( array( 'code' => 'FAR101', 'name_of_subject' => 'Farmacologia', 'group' => 'A' ) ) ),
            array( 'id' => 'room-B2', 'data' => json_encode( array( 'number' => '101', 'block' => 'B', 'floor' => '1' ) ) ),
        );
    }
}
function check( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } }
function render_map() { ob_start(); mapa_salas_content_handler(); return ob_get_clean(); }
require dirname( __DIR__ ) . '/inc/dynamic-pages.php';
$wpdb = new MapDatabase();
$original = getenv( 'INTRANET_DB_NAME' );
try {
    putenv( 'INTRANET_DB_NAME' );
    check( 'intranet' === fafar_intranet_database_name(), 'Stack default not resolved without the legacy constant.' );
    $html = render_map();
    check( strpos( $html, 'FAR101 Farmacologia (A)' ) !== false, 'Reservation lost.' );
    check( $wpdb->bound === array( 'subject-A1', 'room-B2' ), 'Alphanumeric references not bound in the batch lookup.' );
    check( false === $wpdb->suppressed, 'Database error state not restored.' );
    putenv( 'INTRANET_DB_NAME=custom_intranet' );
    check( 'custom_intranet' === fafar_intranet_database_name(), 'Configured name ignored.' );
    putenv( 'INTRANET_DB_NAME=bad`name' );
    $before = count( $wpdb->queries );
    check( strpos( render_map(), 'temporariamente indisponíveis' ) !== false && count( $wpdb->queries ) === $before, 'Unsafe identifier reached a query.' );
    putenv( 'INTRANET_DB_NAME=intranet' );
    $wpdb->fail = true;
    $html = render_map();
    check( strpos( $html, 'temporariamente indisponíveis' ) !== false && strpos( $html, 'database unavailable' ) === false, 'Query failure leaked or broke the page.' );
    check( false === $wpdb->suppressed, 'Error suppression leaked after a failed query.' );
} finally {
    putenv( false === $original ? 'INTRANET_DB_NAME' : 'INTRANET_DB_NAME=' . $original );
}
echo "PASS: intranet map without legacy constant, configurable schema, bound references, safe unavailable state and restored database error state.\n";
