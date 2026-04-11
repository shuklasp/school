<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/school1/sppadmin/api.php?action=login");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['spp_user' => 'admin', 'spp_pswd' => 'admin123']));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookie.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookie.txt');
$loginRes = curl_exec($ch);
echo "Login HTTP: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "\n";
echo "Login Res: " . $loginRes . "\n\n";

curl_setopt($ch, CURLOPT_URL, "http://localhost/school1/sppadmin/api.php?action=list_modules");
curl_setopt($ch, CURLOPT_POST, false);
$modRes = curl_exec($ch);
echo "Mod HTTP: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "\n";
echo "Mod Res: " . $modRes . "\n";
curl_close($ch);
