<?php
$url = 'http://localhost/school1/sppadmin/api.php';
$cookieFile = __DIR__ . '/cookie.txt';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'action' => 'login',
    'username' => 'admin',
    'password' => 'admin123'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
$body = curl_exec($ch);
echo "Login Body:\n$body\n";

// Now request list_modules
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $url . '?action=list_modules');
curl_setopt($ch2, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
$body2 = curl_exec($ch2);
$httpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

echo "HTTP Code: $httpCode\n";
echo "Response Body:\n$body2\n";
