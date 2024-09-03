<?php
session_start();
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

$isDebugMode = true; // 是否偵錯模式
$resultAry = array('status' => 'ng', 'msg' => ''); // 回傳資料
$currentDateTime = date("Y-m-d H:i:s"); // 目前時間
$timestamp = time(); // 時間戳

// 目標設定ID
$setting_id = $_POST['setting_id'];
// 基礎資料夾路徑
$uploadBaseDir = '../../_system/' . $_SESSION['auth_code'] . '/robot_webview';

// 設定檔資料
$settingData = array();

/**
 * 基礎設定
 */
$sql = "SELECT * FROM robot_setting WHERE id = " . $setting_id;
$sqlResult = $db->Execute($sql);
if ($sqlResult === false) {
    // 
} else {
    $settingData['id'] = (int)$sqlResult->fields['id'];
    $settingData['title'] = $sqlResult->fields['title'];
    $settingData['logo'] = $sqlResult->fields['logo'];
    $settingData['banner'] = $sqlResult->fields['banner'];
    $settingData['movingConfig'] = array(
        'moving_play_type' => (int)$sqlResult->fields['moving_play_type'],
        'moving_play_source' => isset($sqlResult->fields['moving_play_source']) ? $sqlResult->fields['moving_play_source'] : '',
        'moving_play_music' => isset($sqlResult->fields['moving_play_music']) ? $sqlResult->fields['moving_play_music'] : '',
        'moving_speak_content' => isset($sqlResult->fields['moving_speak_content']) ? $sqlResult->fields['moving_speak_content'] : ''
    );
}

/**
 * 按鈕
 */
$sqlResult = $db->Execute("SELECT * FROM robot_setting_btn WHERE setting_id = $setting_id ORDER BY dispaly_order");
if ($sqlResult === false) {
    // 
} else {
    if ($sqlResult->RecordCount() > 0) {
        while (!$sqlResult->EOF) {
            $settingData['buttonList'][] = array(
                'id' => (int)$sqlResult->fields['id'],
                'setting_id' => (int)$sqlResult->fields['setting_id'],
                'title' => $sqlResult->fields['title'],
                'is_visible' => (int)$sqlResult->fields['is_visible'],
                'bg_color' => isset($sqlResult->fields['bg_color']) ? $sqlResult->fields['bg_color'] : '',
                'is_goto' => (int)$sqlResult->fields['is_goto'],
                'location' => isset($sqlResult->fields['location']) ? $sqlResult->fields['location'] : '',
                'start_speak_content' => isset($sqlResult->fields['start_speak_content']) ? $sqlResult->fields['start_speak_content'] : '',
                'arrive_speak_content' => isset($sqlResult->fields['arrive_speak_content']) ? $sqlResult->fields['arrive_speak_content'] : '',
                'dispaly_order' => (int)$sqlResult->fields['dispaly_order']
            );
            $sqlResult->MoveNext();
        }
    }
}

/**
 * 跑馬燈
 */
$sqlResult = $db->Execute("SELECT * FROM robot_setting_marquee WHERE setting_id = " . $setting_id);
if ($sqlResult === false) {
   // 
} else {
    if ($sqlResult->RecordCount() > 0) {
        while (!$sqlResult->EOF) {
            $settingData['marqueeList'][] = array(
                'id' => (int)$sqlResult->fields['id'],
                'content' => $sqlResult->fields['content'],
                'display_order' => $sqlResult->fields['display_order']
            );
            $sqlResult->MoveNext();
        }
    }
}

/**
 * banner
 */
$sqlResult = $db->Execute("SELECT * FROM robot_setting_banner WHERE setting_id = $setting_id ORDER BY display_order");
if ($sqlResult === false) {
   //
} else {
    if ($sqlResult->RecordCount() > 0) {
        while (!$sqlResult->EOF) {
            $settingData['bannerList'][] = array(
                'id' => (int)$sqlResult->fields['id'],
                'setting_id' => (int)$sqlResult->fields['setting_id'],
                'file_name' => $sqlResult->fields['file_name'],
                'file_type' => (int)$sqlResult->fields['file_type'],
                'stay_time' => (int)$sqlResult->fields['stay_time'],
                'display_order' => (int)$sqlResult->fields['display_order']
            );
            $sqlResult->MoveNext();
        }
    }
}

/**
 * 處理寫入json檔案
 */
$settingDataString = json_encode($settingData, JSON_PRETTY_PRINT);
$filePath = $uploadBaseDir . '/' . $setting_id . '/' . 'settings.json';
if (file_put_contents($filePath, $settingDataString)) {
    $resultAry['status'] = 'ok';
    $resultAry['msg'] .= 'success update json';
    // $resultAry['settingDataString'] = $settingData;
} else {
    $resultAry['status'] = 'ng';
    $resultAry['msg'] .= 'failed to update json';
}

echo json_encode($resultAry, JSON_UNESCAPED_UNICODE);
?>
