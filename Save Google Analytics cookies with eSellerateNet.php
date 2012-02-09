<?php
// script generates JavaScript code that sets Google Analytics cookies on eSellerate.net

require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );
global $wpdb;

$IP = $_SERVER['REMOTE_ADDR'];

$Data = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."cookies WHERE ga_ip='$IP' LIMIT 0, 1", ARRAY_A);

$jscode = '';
foreach ($Data as $Item) {
	$utma = $Item['ga_utma'];
	$utmb = $Item['ga_utmb'];
	$utmc = $Item['ga_utmc'];
	$utmx = $Item['ga_utmx'];
	$utmz = $Item['ga_utmz'];
	$utmv = $Item['ga_utmv'];
	$utmk = $Item['ga_utmk'];

	$jscode  = "setCookie('__utma', '".htmlspecialchars($utma)."', 30, '/', document.domain, false); \n";
	if ($utmb != "") $jscode .= "setCookie('__utmb', '".htmlspecialchars($utmb)."', 30, '/', document.domain, false); \n";
	if ($utmc != "") $jscode .= "setCookie('__utmc', '".htmlspecialchars($utmc)."', 30, '/', document.domain, false); \n";
	if ($utmx != "") $jscode .= "setCookie('__utmx', '".htmlspecialchars($utmx)."', 30, '/', document.domain, false); \n";
	$jscode .= "setCookie('__utmz', '".htmlspecialchars($utmz)."', 30, '/', document.domain, false); \n";
	if ($utmv != "") $jscode .= "setCookie('__utmv', '".htmlspecialchars($utmv)."', 30, '/', document.domain, false); \n";
	if ($utmk != "") $jscode .= "setCookie('__utmk', '".htmlspecialchars($utmk)."', 30, '/', document.domain, false); \n";


}

$Data = $wpdb->query("DELETE FROM ".$wpdb->prefix."cookies WHERE ga_ip='$IP'");

if ($jscode != '') {
	$jscode .= "deleteCookie('__utma', 'esellerate.net');\n";
	$jscode .= "deleteCookie('__utmb', 'esellerate.net');\n";
	$jscode .= "deleteCookie('__utmc', 'esellerate.net');\n";
	$jscode .= "deleteCookie('__utmx', 'esellerate.net');\n";
	$jscode .= "deleteCookie('__utmz', 'esellerate.net');\n";
	$jscode .= "deleteCookie('__utmv', 'esellerate.net');\n";
	$jscode .= "deleteCookie('__utmk', 'esellerate.net');\n";
}

header('Content-type: text/javascript');
?>

function setCookie(name, value, expires, path, domain, secure){
	document.cookie=name+'='+unescape(value||'')+
		(expires?';expires='+new Date(+new Date()+expires*864e5).toGMTString():'')+
		(path?';path='+path:'')+
		(domain?';host='+domain:'')+
		(secure?';secure':'');
}

function deleteCookie(name, domain) {
    document.cookie = encodeURIComponent(name)+'=deleted'+
    	';expires='+new Date(0).toUTCString()+
    	';path=/'+
    	(domain?';domain='+domain:'')+
    	';secure=false';
}

<?php echo $jscode; ?>
