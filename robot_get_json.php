<?php
header('Access-Control-Allow-Origin: *'); // 或者指定允許的網域，例如：header('Access-Control-Allow-Origin: http://example.com');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 86400'); // 快取跨網域標頭的秒數，可根據實際需求調整
// header('Content-Type: application/json');

// 授權代碼
$auth_code = $_GET['auth_code'];
$setting_id =  $_GET['setting_id'];
// 基礎資料夾路徑
$uploadBaseDir = '../../_system/' . $auth_code . '/robot_webview' . '/' . $setting_id ;
$jsonPath = $uploadBaseDir . '/settings.json';
// echo $jsonPath;
if (file_exists($jsonPath)) {
    $jsonData = file_get_contents($jsonPath);
    echo $jsonData;
}
?>
