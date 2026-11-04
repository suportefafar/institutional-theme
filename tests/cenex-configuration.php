<?php
/** Public installation must never grant privileges to hardcoded user IDs. */
function add_action(...$args) {}
function add_filter(...$args) {}
function get_option($key,$default=false) { return $default; }
function get_post($id) { return (object)['post_author'=>701]; }
$names=['CENEX_DELEGATED_USER_ID','CENEX_TARGET_USER_ID','CENEX_DELEGATION_EXPIRES_AT'];$original=[];
foreach($names as $name){$original[$name]=getenv($name);putenv($name);}
try {
 require dirname(__DIR__).'/inc/modules/cenex-permissions.php';
 if(fafar_cenex_delegation_enabled())throw new RuntimeException('Delegation enabled without explicit configuration.');
 $unchanged=custom_cenex_user_permissions(['read'=>true],['edit_posts'],['edit_posts'],(object)['ID'=>0]);
 if($unchanged!==['read'=>true])throw new RuntimeException('Unconfigured delegation grants privileges.');
 $GLOBALS['cenex_delegated_user_id']=700;$GLOBALS['cenex_target_user_id']=701;$GLOBALS['cenex_delegation_expires_at']=time()+3600;
 $allowed=custom_cenex_user_permissions([],['edit_others_posts'],['edit_post',700,50],(object)['ID'=>700]);
 if(empty($allowed['edit_others_posts']))throw new RuntimeException('Configured owner delegation broken.');
 $GLOBALS['cenex_delegation_expires_at']=time()-1;
 if(custom_cenex_user_permissions([],['edit_posts'],['edit_posts'],(object)['ID'=>700])!==[])throw new RuntimeException('Expired delegation grants privileges.');
} finally {foreach($original as $name=>$value)putenv(false===$value?$name:$name.'='.$value);}
echo "PASS: delegation disabled by default; explicit configuration and expiration enforced.\n";
