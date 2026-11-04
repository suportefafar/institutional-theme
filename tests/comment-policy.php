<?php
/** php tests/comment-policy.php */
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
function add_action( ...$args ) {}
function add_filter( ...$args ) {}
function absint( $id ) { return abs( (int) $id ); }
function get_post( $id ) { return $GLOBALS['posts'][ $id ] ?? null; }
function get_post_type( $id ) { return get_post( $id )->post_type ?? null; }
function get_post_meta( $id, $key, $single ) { return $GLOBALS['meta'][ $id ][ $key ] ?? ''; }
function has_category( $slug, $id ) { return in_array( $id, array( 3, 4, 5 ), true ); }
function is_singular( $type ) { return $GLOBALS['queried_id'] !== 4; }
function get_queried_object_id() { return $GLOBALS['queried_id']; }
function get_the_ID() { return get_queried_object_id(); }
function wp_unslash( $value ) { return $value; }
function check( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } }
$GLOBALS['posts'] = array();
foreach ( array( 1 => 'post', 2 => 'post', 3 => 'post', 4 => 'page', 5 => 'post', 6 => 'cemed_pergunta' ) as $id => $type ) {
    $GLOBALS['posts'][ $id ] = (object) array( 'ID' => $id, 'post_type' => $type, 'post_status' => 5 === $id ? 'draft' : 'publish' );
}
$GLOBALS['meta'] = array( 2 => array( '_cemed_question_id' => 6 ) );
require dirname( __DIR__ ) . '/inc/modules/cemed.php';
foreach ( array( 0 => false, 1 => false, 2 => true, 3 => true, 4 => false, 5 => false, 6 => false ) as $id => $allowed ) {
    check( fafar_cemed_keep_answer_comments_open( true, $id ) === $allowed, 'Stored open status bypassed policy: ' . $id );
    check( fafar_cemed_keep_answer_comments_open( false, $id ) === $allowed, 'Cemed exception broken: ' . $id );
}
$GLOBALS['queried_id'] = 2;
check( '0' === fafar_cemed_allow_guest_answer_comments( false ), 'Cemed guests blocked.' );
$GLOBALS['queried_id'] = 1;
check( false === fafar_cemed_allow_guest_answer_comments( false ), 'Non-Cemed guests enabled.' );
foreach ( array( 1, 4 ) as $id ) {
    $GLOBALS['queried_id'] = $id;
    ob_start(); require dirname( __DIR__ ) . '/comments.php'; $html = ob_get_clean();
    check( '' === trim( $html ), 'Non-Cemed comments template was rendered.' );
}
echo "PASS: comments only on published Cemed posts; pages, drafts and other posts blocked even when stored as open; old comments hidden.\n";
