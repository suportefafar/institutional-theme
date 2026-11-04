<?php
function add_action(...$args) {}
function add_filter(...$args) {}
function esc_html($s) { return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
function esc_attr($s) { return esc_html($s); }
function esc_url($s) { return preg_match('~^https?://~i',$s) ? esc_attr($s) : ''; }
function home_url($p) { return 'https://example.test'.$p; }
function sanitize_text_field($s) { return strip_tags($s); }
function wp_unslash($s) { return $s; }
class DynamicDatabase {
 public $last_error='';public $suppressed=false;public $fail=false;public $bound=[];
 function suppress_errors($value=true) { $old=$this->suppressed;$this->suppressed=$value;return $old; }
 function prepare($sql,...$values) { $this->bound=$values;return $sql; }
 function get_results($sql) {
  $this->last_error=$this->fail?'private database error':'';
  if($this->fail) { return null; }
  if(strpos($sql,'FROM tipo_ementas')!==false) { return [(object)['id'=>3,'descricao'=>'2026 <script>version</script>']]; }
  return [(object)['cod_disciplina'=>'FAR101','nome'=>'Farmacologia <img src=x onerror=alert(1)>','arquivo_ementa'=>'javascript:alert(1)']];
 }
 function get_row($sql) { $this->last_error=$this->fail?'private database error':'';return null; }
}
function check($condition,$message) { if(!$condition) throw new RuntimeException($message); }
function download_page() { ob_start();baixar_ementas_content_handler();return ob_get_clean(); }
$wpdb=new DynamicDatabase();
require dirname(__DIR__).'/inc/dynamic-pages.php';
$html=download_page();
check(strpos($html,'data-filter-row')!==false,'Available syllabus not rendered.');
check(strpos($html,'<script>')===false && strpos($html,'<img')===false && strpos($html,'href="javascript:')===false,'Stored syllabus content not escaped.');
check($wpdb->bound===[3],'Version not passed as a query parameter.');
check(!$wpdb->suppressed,'Error state leaked.');
$wpdb->fail=true;$html=download_page();
check(strpos($html,'temporariamente indisponíveis')!==false && strpos($html,' disabled')!==false,'Unavailable state not communicated.');
check(strpos($html,'private database error')===false && strpos($html,'data-filter-row')===false,'Database error or partial results leaked.');
check(!$wpdb->suppressed,'Error state leaked on unavailable download.');
$_GET['codigo']='FAR101';ob_start();validar_ementas_content_handler();$html=ob_get_clean();
check(strpos($html,'temporariamente indisponível')!==false && strpos($html,'private database error')===false,'Validation missing-table response broken.');
check(count(fafar_dynamic_routes())===17,'Dynamic route coverage changed.');
echo "PASS: dynamic downloads, escaped data/URLs, bound versions, unavailable states and all 17 routes.\n";
