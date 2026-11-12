<?php
define('ABSPATH', __DIR__);
function add_action(...$args) {}
function add_shortcode(...$args) {}
function wp_send_json_error($data, $status) { if($status!==503) { exit(1); } throw new RuntimeException('expected'); }
require dirname(__DIR__).'/cotizador_hc_.php';
if(strpos(cotizador_online_form_shortcode(),'no está disponible')===false) { exit(1); }
$_SERVER['REQUEST_METHOD']='POST';
try { cotizador_online_handle_request(); exit(1); } catch(RuntimeException $e) { echo "WooCommerce missing: safe response\n"; }
