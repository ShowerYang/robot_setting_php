<?php
session_start();

// 是否開發模式
$isDevMode = false;
if ($isDevMode) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);

    $_SESSION['auth_code'] = 'demo1';
    $_SESSION['lang'] = "zh-TW";
    $_SESSION["charset"] = "utf-8";
    $_SESSION['user_account'] = "demo1";
    $_SESSION['user_name'] = "張三";
    // 允許跨網域訪問的特定 IP 列表
    $allowedIPs = array('192.168.1.999');
    // 檢查請求的 IP 是否在允許的列表中
    $clientIP = $_SERVER['REMOTE_ADDR'];
    if (in_array($clientIP, $allowedIPs)) {
        // 如果是允許的 IP，設定跨網域標頭
        header('Access-Control-Allow-Origin: *'); // 或者指定允許的網域，例如：header('Access-Control-Allow-Origin: http://example.com');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Access-Control-Max-Age: 86400'); // 快取跨網域標頭的秒數，可根據實際需求調整
    }
}

/**前置定義**/
define('moduleName', 'robot_setting_btn');
include('../checkin.inc-server.php');
include("adodb/adodb.inc.php");
include('common/lang.inc_' . $_SESSION['lang'] . '.php');
include('common/stringlib_' . $_SESSION['lang'] . '.php');
include('common/timelib.php');
include('common/form_tools_' . $_SESSION['lang'] . '.php');
include('common/loglib_' . $_SESSION['lang'] . '.php');
include_once($_SESSION['dbConnectFile']);
// echo $_SESSION['dbConnectFile'];

// 是否偵錯模式
$isDebugMode = true;
// 回傳資料
$resultAry = array('status' => 'ng', 'msg' => '');
// 請求方法
$method = strtoupper($_SERVER['REQUEST_METHOD']);
switch ($method) {
        /**
     * 取得按鈕列表
     */
    case 'GET': {
        $serialNumber = $_GET['serialNumber'];
        $result = $db->Execute("SELECT * FROM robot_amr WHERE serialNumber = '$serialNumber'");
        if ($result === false) {
            $resultAry['status'] = 'ng';
            $resultAry['msg'] .= $db->spySQL;
        } else {
            $resultAry['data'] = array(
                'id' => (int)$result->fields['id'],
                'mac' => $result->fields['mac'],
                'serialNumber' => $result->fields['serialNumber'],
                'locations' => $result->fields['locations'],
                'setting_id' => (int)$result->fields['setting_id']
            );
            $resultAry['status'] = 'ok';
            $resultAry['msg'] .= $isDebugMode ? ($db->spySQL . PHP_EOL) : '';
        }
        break;
    }
}
echo json_encode($resultAry, JSON_UNESCAPED_UNICODE);
?>
