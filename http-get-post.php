<?php

// identifikace Business Application
define('BAID', 'demo');
// klientsky certifikat slouzici pro autentizaci
define('CERT', 'demo.pem');
// certifikat autority RapidSSL, ktera vydala serverovy certifikat
define('CACERT', 'geotrust.crt');
// URL adresa HTTP GET/POST API (LinuxBox.cz SMS brana)
define('URL', 'https://www.ipsms.cz:8443/smsconnector/getpost/GP');


function smsgw($action = 'ping', $data = null) {
	$postData = array(
		'baID'   => BAID,
		'action' => $action
	);
	if (isset($data)) {
		$postData = array_merge($postData, $data);
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, URL);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_CAINFO, CACERT);

	curl_setopt($ch, CURLOPT_SSLCERT, CERT);

	$response = curl_exec($ch);

	$output = array();
	$arr = explode("\n", $response);
	foreach ($arr as &$line) {
		$line = trim($line);
		if (!empty($line)) {
			list($key, $value) = explode('=', $line, 2);
			$output[$key] = $value;
		}
	}

	curl_close($ch);
	
	return $output;
}

echo "<h1>Demo application for HTTP GET/POST API</h1>\n";
echo "<p><a href=\"http://www.linuxbox.cz/ipsms\">http://www.linuxbox.cz/ipsms</a></p>\n";

// ping - vyzkouseni funkcnosti (spravne zadane parametry a aktivni ucet)
$result = smsgw('ping');
echo "<h2>ping</h2>\n";
echo "<pre>\n";
print_r($result);
echo "</pre>\n";
if ($result['code'] !== 'ISUC_000') {
	echo "<strong>ERROR: " . $result['description'] . "</strong>\n";
	exit();
}

// send - poslani SMS
$data = array(
	// cilove cislo
	'toNumber'       => '+420734468595',
	// text SMS
	'text'           => 'Server LinuxBox je určen společnostem libovolné velikosti s různorodými potřebami, kterým se dokáže snadno přizpůsobit. Jedná se o komplexní řešení s vysokou úrovní servisní podpory, která je jednou z klíčových filozofií společnosti LinuxBox.cz.',
	// standardni SMS
	'intruder'       => false,
	// s dorucenkou
	'deliveryReport' => true,
	// delsi SMS se rozdeli na vice casti
	'multipart'      => true
);
$result = smsgw('send', $data);
echo "<h2>send</h2>\n";
echo "<pre>\n";
print_r($result);
echo "</pre>\n";
if ($result['code'] !== 'ISUC_001') {
	echo "<strong>ERROR: " . $result['description'] . "</strong>\n";
	exit();
}

while (true) {
	// receive - precteni dorucenky nebo prichozi SMS
	$result = smsgw('receive');
	if (empty($result)) {
		break;
	}
	echo "<h2>receive</h2>\n";
	echo "<pre>\n";
	print_r($result);
	echo "</pre>\n";
	if ($result['selector'] === 'Response') {
		// dorucenka
		if ($result['code'] === 'ISUC_005') {
			echo "<strong>Zprava byla dorucena - ID: " . $result['msgID'] . "</strong>\n";
		}
	} else if ($result['selector'] === 'TextSms') {
		// prichozi zprava
		echo "<strong>Prichozi SMS od " . $result['fromNumber'] .
			", text zpravy: " . $result['text'] . "</strong>\n";
	}
			
	// confirm - potvrzeni, ze byla zprava systemem zpracovana
	// a muze se pri pristim volani receive zobrazit dalsi zprava v poradi 
	$data = array(
		'refMsgID' => $result['msgID'],
		'refBaID'  => $result['baID']
	);
	$result = smsgw('confirm', $data);
	echo "<h2>confirm</h2>\n";
	echo "<pre>\n";
	print_r($result);
	echo "</pre>\n";
	if ($result['code'] !== 'ISUC_002') {
		echo "<strong>ERROR: " . $result['description'] . "</strong>\n";
		exit();
	}
}
