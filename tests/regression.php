<?php
// Isolated regression tests: no WordPress database, network or real email.
define('ABSPATH', __DIR__);
define('MINUTE_IN_SECONDS', 60);
class Response extends Exception { public $payload; public $status; public function __construct($payload, $status) { $this->payload=$payload; $this->status=$status; } }
function add_action(...$args) {}
function add_shortcode(...$args) {}
function wp_send_json_error($data, $status) { throw new Response(array('success'=>false,'data'=>$data),$status); }
function wp_send_json_success($data) { throw new Response(array('success'=>true,'data'=>$data),200); }
function wp_unslash($v) { return stripslashes($v); }
function sanitize_text_field($v) { return trim(strip_tags($v)); }
function wp_verify_nonce($v,$a) { return $v==='valid' && $a==='woofreequote'; }
function is_email($v) { return is_string($v) && filter_var($v,FILTER_VALIDATE_EMAIL); }
function wc_get_product($id) { return $id===12 ? $GLOBALS['product'] : false; }
function wc_get_products($args) { $GLOBALS['query']=$args; return (object)array('products'=>array($GLOBALS['product']),'max_num_pages'=>3); }
function post_password_required($id) { return $GLOBALS['protected']; }
function get_option($key) { return $GLOBALS['recipient']; }
function get_transient($key) { return $GLOBALS['transients'][$key] ?? false; }
function set_transient($key,$v,$ttl) { $GLOBALS['transients'][$key]=$v; $GLOBALS['ttl']=$ttl; }
function wp_salt($key) { return 'test-secret'; }
function wc_price($v) { return '<span>&#36;'.number_format($v,2).'</span>'; }
function wp_strip_all_tags($v) { return strip_tags($v); }
function wp_mail(...$args) { $GLOBALS['mail'][]=$args; return $GLOBALS['mail_ok']; }
function wp_enqueue_style(...$args) { $GLOBALS['styles'][]=$args; }
function wp_enqueue_script(...$args) { $GLOBALS['scripts'][]=$args; }
function plugin_dir_url($file) { return 'https://example.test/plugin/'; }
function admin_url($path) { return 'https://example.test/'.$path; }
function wp_unique_id($prefix) { static $i=0; return $prefix.++$i; }
function wp_print_styles($handle) {}
function wp_create_nonce($action) { return 'valid'; }
function esc_attr($s) { return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
function esc_html($s) { return esc_attr($s); }
function esc_url($s) { return esc_attr($s); }
function add_query_arg($key,$v) { return 'https://example.test/?'.$key.'='.$v; }
class Product {
    public $status='publish', $type='simple', $visibility='visible', $visible=true, $price='10.50', $name='<img src=x onerror=alert(1)> Product';
    function get_status() { return $this->status; }
    function is_type($v) { return $this->type===$v; }
    function get_catalog_visibility() { return $this->visibility; }
    function is_visible() { return $this->visible; }
    function get_price() { return $this->price; }
    function get_id() { return 12; }
    function get_name() { return $this->name; }
}
require dirname(__DIR__).'/cotizador_hc_.php';
function reset_case() {
    $_SERVER=array('REQUEST_METHOD'=>'POST','REMOTE_ADDR'=>'192.0.2.1');
    $_POST=array('nonce'=>'valid','name'=>'Cliente','email'=>'client@example.test','product'=>'12','quantity'=>'2','website'=>'');
    $_GET=array();
    $GLOBALS['product']=new Product(); $GLOBALS['mail']=array(); $GLOBALS['mail_ok']=true;
    $GLOBALS['recipient']='store@example.test'; $GLOBALS['protected']=false; $GLOBALS['transients']=array();
}
function request() { try { cotizador_online_handle_request(); } catch(Response $r) { return $r; } throw new Exception('No response'); }
$count=0;
function check($condition,$label) { global $count; if (!$condition) { throw new Exception('FAIL: '.$label); } ++$count; }
reset_case(); $r=request(); check($r->status===200,'valid request'); check(count($GLOBALS['mail'])===1,'one email');
check($r->payload['data']['total_price']==='$21.00','server calculates total');
check(strpos($GLOBALS['mail'][0][2],'<span>')===false,'plain text email');
check(in_array('Reply-To: client@example.test',$GLOBALS['mail'][0][3],true),'reply to client');
$cases=array(
 'invalid nonce'=>array('nonce','expired',403), 'array nonce'=>array('nonce',array(),400),
 'empty name'=>array('name',' ',400), 'array name'=>array('name',array('x'),400),
 'invalid email'=>array('email','invalid',400), 'header injection'=>array('email',"x@example.test\r\nBcc: attacker@example.test",400),
 'zero'=>array('quantity','0',400), 'negative'=>array('quantity','-1',400), 'fraction'=>array('quantity','1.5',400),
 'oversized'=>array('quantity','10001',400), 'array quantity'=>array('quantity',array(),400),
 'invalid product'=>array('product','12abc',400), 'missing product'=>array('product','99',422),
 'honeypot'=>array('website','spam',400), 'array honeypot'=>array('website',array(),400)
);
foreach($cases as $label=>$case) { reset_case(); $_POST[$case[0]]=$case[1]; $r=request(); check($r->status===$case[2] && !$GLOBALS['mail'],$label); }
foreach(array('status'=>'draft','type'=>'variable','visibility'=>'hidden','visible'=>false,'price'=>'') as $key=>$value) {
 reset_case(); $GLOBALS['product']->$key=$value; check(request()->status===422 && !$GLOBALS['mail'],'reject '.$key);
}
reset_case(); $GLOBALS['protected']=true; check(request()->status===422,'password protected');
reset_case(); $_SERVER['REQUEST_METHOD']='GET'; check(request()->status===405,'method');
reset_case(); unset($_POST['email']); check(request()->status===400,'missing field');
reset_case(); $GLOBALS['recipient']=''; check(request()->status===503 && !$GLOBALS['mail'],'bad recipient');
reset_case(); $GLOBALS['mail_ok']=false; check(request()->status===502,'mail failure is not success');
reset_case(); $_POST['total_price']='0.01'; check(request()->payload['data']['total_price']==='$21.00','ignore submitted price');
reset_case(); for($i=0;$i<5;$i++) { check(request()->status===200,'within limit'); }
check(request()->status===429 && count($GLOBALS['mail'])===5,'limit enforced'); check($GLOBALS['ttl']===600,'ten minute window');
reset_case(); $GLOBALS['product']->price='0'; check(request()->status===200,'zero price accepted');
reset_case(); $html=cotizador_online_form_shortcode();
check(strpos($html,'<img src=x')===false && strpos($html,'&lt;img')!==false,'product HTML escaped');
check($GLOBALS['query']['limit']===20 && $GLOBALS['query']['paginate']===true,'bounded catalogue');
check(strpos($html,'Más productos')!==false,'next page');
check(strpos($html,'name="nonce"')!==false,'nonce rendered');
check(strpos($html,'aria-live="polite"')!==false,'live status');
$_GET['wfq_page']='2'; $html2=cotizador_online_form_shortcode();
check($GLOBALS['query']['page']===2 && strpos($html2,'Productos anteriores')!==false,'second page');
check(strpos($html,'wfq-1-name')!==false && strpos($html2,'wfq-2-name')!==false,'unique form ids');
echo "$count checks passed\n";
