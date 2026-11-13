<?php
define( 'ARRAY_A', 'ARRAY_A' );
function add_filter() {}
function add_action() {}
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_url( $value ) { return esc_html( $value ); }
function sanitize_email( $value ) { return $value; }

class FacultyRoomsDatabase {
    public $last_error = '';
    public $room_query = '';
    public $room_ids = array();

    public function suppress_errors( $value = true ) { return false; }
    public function prepare( $query, ...$args ) {
        $this->room_ids = is_array( $args[0] ) ? $args[0] : $args;
        return $query;
    }
    public function get_row( $query ) {
        return (object) array( 'option_value' => serialize( array( 'alm' => array( 'name' => 'ALM' ) ) ) );
    }
    public function get_results( $query, $format = null ) {
        if ( strpos( $query, 'wp_fafar_cf7crud_submissions' ) !== false ) {
            $this->room_query = $query;
            return array( array( 'id' => 'room-A', 'data' => '{"number":"4101 <script>"}' ) );
        }
        return array(
            (object) array( 'display_name' => 'Docente A', 'user_email' => 'a@example.test', 'workplace_extension' => '1234', 'workplace_place' => 'room-A', 'capabilities' => serialize( array( 'alm' => true ) ) ),
            (object) array( 'display_name' => 'Docente B', 'user_email' => 'b@example.test', 'workplace_extension' => '', 'workplace_place' => null, 'capabilities' => serialize( array( 'alm' => true ) ) ),
        );
    }
}

$wpdb = new FacultyRoomsDatabase();
require dirname( __DIR__ ) . '/inc/dynamic-pages.php';
$args = array( 'meta_query' => array( array( 'key' => 'public_servant_bond_category', 'value' => 'DOCENTE' ) ) );
ob_start();
get_people_from_intranet( $args );
$html = ob_get_clean();
if ( strpos( $html, '<th scope="col">Sala</th>' ) === false || strpos( $html, '4101 &lt;script&gt;' ) === false || strpos( $html, '<script>' ) !== false ) {
    throw new RuntimeException( 'Faculty room column was not rendered and escaped.' );
}
if ( $wpdb->room_ids !== array( 'room-A' ) || strpos( $wpdb->room_query, "object_name='place'" ) === false ) {
    throw new RuntimeException( 'Faculty rooms were not resolved in one filtered query.' );
}
if ( ! isset( fafar_dynamic_routes()['corpo-docente-fas'] ) || isset( fafar_dynamic_routes()['corpo-docente-2'] ) ) {
    throw new RuntimeException( 'FAS faculty route does not use the requested slug.' );
}
ob_start();
people_content_handler( array() );
$admin_html = ob_get_clean();
if ( strpos( $admin_html, '<th scope="col">Sala</th>' ) !== false || strpos( $admin_html, 'colspan="4"' ) === false ) {
    throw new RuntimeException( 'Non-faculty contact tables changed.' );
}
echo "PASS: faculty rooms, escaping, FAS route, and non-faculty table.\n";
