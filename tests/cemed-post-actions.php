<?php
/** Regression checks for Cemed public post edit/delete actions. */
define( 'ABSPATH', dirname( __DIR__ ) . '/' );

class WP_Error {
	private $code;
	private $message;
	public function __construct( $code, $message ) { $this->code = $code; $this->message = $message; }
	public function get_error_code() { return $this->code; }
	public function get_error_message() { return $this->message; }
}
class Redirected extends RuntimeException {
	public $url;
	public $status;
	public function __construct( $url, $status ) { parent::__construct( 'redirect' ); $this->url = $url; $this->status = $status; }
}

function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function absint( $value ) { return abs( (int) $value ); }
function wp_unslash( $value ) { return $value; }
function is_user_logged_in() { return true; }
function is_admin() { return false; }
function get_post( $id ) { return $GLOBALS['posts'][ $id ] ?? null; }
function get_post_meta( $id, $key, $single ) { return $GLOBALS['meta'][ $id ][ $key ] ?? ''; }
function current_user_can( $capability, $id ) { return 'delete_post' === $capability && $GLOBALS['can_delete']; }
function wp_verify_nonce( $nonce, $action ) { return $nonce === 'valid' && $action === 'fafar_cemed_delete_public_post_10_20'; }
function wp_trash_post( $id ) { $GLOBALS['trashed'][] = $id; return $GLOBALS['posts'][ $id ] ?? false; }
function wp_untrash_post( $id ) { $GLOBALS['untrashed'][] = $id; return true; }
function delete_post_meta( $id, $key ) {
	if ( ! array_key_exists( $key, $GLOBALS['meta'][ $id ] ?? array() ) ) { return false; }
	unset( $GLOBALS['meta'][ $id ][ $key ] );
	return true;
}
function update_post_meta( $id, $key, $value ) { $GLOBALS['meta'][ $id ][ $key ] = $value; return true; }
function current_time( $type ) { return '2026-11-05 10:00:00'; }
function get_current_user_id() { return 7; }
function add_query_arg( $args, $url ) { return $url . '?' . http_build_query( $args ); }
function wp_create_nonce( $action ) { return 'confirmation'; }
function get_permalink() { return 'https://example.test/gerenciar-perguntas-cemed/'; }
function wp_safe_redirect( $url, $status ) { throw new Redirected( $url, $status ); }

function check( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
}
function reset_fixture() {
	$GLOBALS['posts'] = array(
		10 => (object) array( 'ID' => 10, 'post_type' => 'cemed_pergunta', 'post_status' => 'publish' ),
		20 => (object) array( 'ID' => 20, 'post_type' => 'post', 'post_status' => 'publish' ),
		21 => (object) array( 'ID' => 21, 'post_type' => 'post', 'post_status' => 'publish' ),
	);
	$GLOBALS['meta'] = array(
		10 => array( '_cemed_public_post_id' => 20, '_cemed_feedback_token' => 'token', '_cemed_status' => 'respondida' ),
		20 => array( '_cemed_question_id' => 10 ),
	);
	$GLOBALS['can_delete'] = true;
	$GLOBALS['trashed'] = array();
	$GLOBALS['untrashed'] = array();
	$_SERVER['REQUEST_METHOD'] = 'POST';
	$_POST = array(
		'fafar_cemed_manage_action' => 'delete_public_post',
		'cemed_question_id' => '10',
		'cemed_public_post_id' => '20',
		'cemed_page_number' => '2',
		'fafar_cemed_delete_nonce' => 'valid',
	);
}

require dirname( __DIR__ ) . '/inc/modules/cemed.php';

reset_fixture();
try {
	fafar_cemed_delete_public_post();
	check( false, 'Successful deletion did not redirect.' );
} catch ( Redirected $redirect ) {
	check( 303 === $redirect->status, 'Deletion did not use a 303 redirect.' );
	check( str_contains( $redirect->url, 'excluida=10' ) && str_contains( $redirect->url, 'pagina=2' ), 'Deletion confirmation redirect is incomplete.' );
}
check( array( 20 ) === $GLOBALS['trashed'], 'Associated public post was not moved to trash.' );
check( ! isset( $GLOBALS['meta'][10]['_cemed_public_post_id'] ), 'Question still points to deleted post.' );
check( ! isset( $GLOBALS['meta'][10]['_cemed_feedback_token'] ), 'Old feedback token was not removed.' );
check( ! isset( $GLOBALS['meta'][20]['_cemed_question_id'] ), 'Deleted post still points to the question.' );
check( 'em_analise' === $GLOBALS['meta'][10]['_cemed_status'], 'Question status was not reset for republication.' );

reset_fixture();
$_POST['cemed_public_post_id'] = '21';
$result = fafar_cemed_delete_public_post();
check( $result instanceof WP_Error && 'invalid_post' === $result->get_error_code(), 'Mismatched post association was accepted.' );
check( array() === $GLOBALS['trashed'], 'Unassociated post was moved to trash.' );

reset_fixture();
$GLOBALS['can_delete'] = false;
$result = fafar_cemed_delete_public_post();
check( $result instanceof WP_Error && 'forbidden' === $result->get_error_code(), 'Deletion without capability was accepted.' );

reset_fixture();
$_POST['fafar_cemed_delete_nonce'] = 'expired';
$result = fafar_cemed_delete_public_post();
check( $result instanceof WP_Error && 'nonce' === $result->get_error_code(), 'Deletion with invalid nonce was accepted.' );

$template = file_get_contents( dirname( __DIR__ ) . '/page-gerenciar-perguntas-cemed.php' );
check( str_contains( $template, 'get_edit_post_link' ) && str_contains( $template, '>Editar post</a>' ), 'Edit link is missing from the management page.' );
check( str_contains( $template, '>Excluir post</button>' ), 'Delete button is missing from the management page.' );

echo "PASS: Cemed edit link and recoverable, authorized, nonce-protected post deletion.\n";
