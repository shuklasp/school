<?php
// Simple script to test the API directly via HTTP
$url = 'http://localhost/school1/sppadmin/api.php';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'action' => 'login',
    'username' => 'admin',
    'password' => 'admin123'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, 1);
$result = curl_exec($ch);
list($headers, $body) = explode("\r\n\r\n", $result, 2);

// Extract cookie
preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headers, $matches);
$cookies = [];
foreach($matches[1] as $item) {
    if (strpos($item, 'PHPSESSID') !== false) {
        $cookies[] = $item;
    }
}
$cookieStr = implode('; ', $cookies);

// Now request list_modules
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $url . '?action=list_modules');
curl_setopt($ch2, CURLOPT_COOKIE, $cookieStr);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
$body2 = curl_exec($ch2);
$httpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

echo "HTTP Code: $httpCode\n";
echo "Response Body:\n$body2\n";
