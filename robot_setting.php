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

$isDebugMode = true; // 是否偵錯模式
$resultAry = array('status' => 'ng', 'msg' => ''); // 回傳資料
$method = strtoupper($_SERVER['REQUEST_METHOD']); // 請求方法
$process = $_POST['process']; // 處理事項
$target = $_POST['target']; // 處理目標
$setting_id = $_POST['setting_id']; // 目標設定ID
$oldFileName = $_POST['oldFileName']; // 舊檔案名稱
$speakContent = $_POST['speakContent']; // 說話內容

// 回傳資料
$resultAry = array('status' => 'ng', 'msg' => '');
// 目前時間
$currentDateTime = date("Y-m-d H:i:s");
// 時間戳
$timestamp = time();

// 請求方法
$method = strtoupper($_SERVER['REQUEST_METHOD']);

if ($method === 'GET') {
    $setting_id = $_GET['setting_id'];
    $sql = "SELECT * FROM robot_setting WHERE id = " . $setting_id;
    $sqlResult = $db->Execute($sql);
    if ($sqlResult === false) {
        $resultAry['status'] = 'ng';
        $resultAry['msg'] .= 'failed to select';
    } else {
        $resultAry['status'] = 'ok';
        $resultAry['data'] = array(
            'id' => (int)$sqlResult->fields['id'],
            'title' => isset($sqlResult->fields['title']) ? $sqlResult->fields['title'] : '',
            'logo' => isset($sqlResult->fields['logo']) ? $sqlResult->fields['logo'] : '',
            'banner' => isset($sqlResult->fields['banner']) ? $sqlResult->fields['banner'] : '',
            'moving_play_type' => (int)$sqlResult->fields['moving_play_type'],
            'moving_play_source' => isset($sqlResult->fields['moving_play_source']) ? $sqlResult->fields['moving_play_source'] : '',
            'moving_play_music' => isset($sqlResult->fields['moving_play_music']) ? $sqlResult->fields['moving_play_music'] : '',
            'moving_speak_content' => isset($sqlResult->fields['moving_speak_content']) ? $sqlResult->fields['moving_speak_content'] : ''
        );
    }
} else if ($method === 'POST') {
    if ($process === 'upload_file') {
        // 基礎資料夾路徑
        $uploadBaseDir = '../../_system/' . $_SESSION['auth_code'] . '/robot_webview';
        // 是否準備資料夾OK
        $isPrepareDirOk = true;

        // 組成設定ID
        if (isset($setting_id)) {
            $uploadTargetDir = $uploadBaseDir . '/' . $setting_id;
            // 判斷資料夾是否存在，沒有就嘗試建立資料夾
            if (!file_exists($uploadTargetDir) && !mkdir($uploadTargetDir, 0777, true)) {
                $resultAry['status'] = 'ng';
                $resultAry['msg'] = 'failed to create folder';
                $isPrepareDirOk = false;
            }
        } else {
            $resultAry['status'] = 'ng';
            $resultAry['msg'] = 'setting_id not exist';
            $isPrepareDirOk = false;
        }

        if (isset($_FILES["file"]) && $_FILES["file"]["error"] == UPLOAD_ERR_OK) {
            // 上傳檔案欄位的檔名
            $file = $_FILES['file']['name'];
            //取得附屬檔名，並統一轉為小寫
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            // 上傳檔案的 MIME 類型
            $fileType = $_FILES['file']['type'];

            // 檔名: 加上當前時間戳與副檔名
            $fileName = $target . '_' . $timestamp . '.' . $ext;
            // 完整上傳路徑
            $targetFilePath = $uploadTargetDir . '/' . $fileName;

            if ($isPrepareDirOk) {
                if (isset($_FILES["file"]) && $_FILES["file"]["error"] == UPLOAD_ERR_OK) {
                    // 上傳檔案
                    if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
                        // 判斷是否是處理移動時的撥放圖/影片
                        if ($target === 'moving_play_source') {
                            $movingPlayType = 1;
                            if (strpos($fileType, "video") !== false) {
                                $movingPlayType = 2;
                            }
                            $sql = "UPDATE robot_setting SET `$target` = '$fileName', `moving_play_type` = $movingPlayType WHERE id = $setting_id";
                        } else {
                            $sql = "UPDATE robot_setting SET `$target` = '$fileName' WHERE id = $setting_id";
                        }
                        $sqlResult = $db->Execute($sql);
                        if ($sqlResult === false) {
                            $resultAry['status'] = 'ng';
                            $resultAry['msg'] .= 'failed to update db column: '.$target;
                        } else {
                            $resultAry['status'] = 'ok';
                            $resultAry['data'] = array(
                                'newFileName' => $fileName,
                                'fileType' => $movingPlayType
                            );
                        }
                        $resultAry['msg'] .= $isDebugMode ? ($db->spySQL) : '';
                    } else {
                        $resultAry['status'] = 'ng';
                        $resultAry['msg'] = 'failed to upload';
                        $resultAry['targetFilePath'] = $targetFilePath;
                    }
                    // 如果有傳舊檔案名稱，則要刪除檔案
                    if (isset($oldFileName)) {
                        $oldFilePath = $uploadTargetDir . '/' . $oldFileName;
                        if (file_exists($oldFilePath)) {
                            if (unlink($oldFilePath)) {
                                // 刪除成功...
                            }
                        }
                    }
                }
            }
        } else {
            $resultAry['status'] = 'ng';
            $resultAry['msg'] = 'file error';
        }
    } else if ($process === 'update_speak_content') {        
        $sql = "UPDATE robot_setting SET `moving_speak_content` = '$speakContent' WHERE id = $setting_id";
        $sqlResult = $db->Execute($sql);
        if ($sqlResult === false) {
            $resultAry['status'] = 'ng';
            $resultAry['msg'] .= 'failed to update db(moving_speak_content)';
        } else {
            $resultAry['status'] = 'ok';
            $resultAry['data'] = null;
        }
        $resultAry['msg'] .= $isDebugMode ? ($db->spySQL) : '';
    }
} else if ($method === 'PATCH') {
    // 基礎資料夾路徑
    $uploadBaseDir = '../../_system/' . $_SESSION['auth_code'] . '/robot_webview';
    $uploadTargetDir = $uploadBaseDir . '/' . $setting_id;

    // 處理傳入參數
    $patchData = file_get_contents("php://input");
    $requestData = json_decode($patchData, true);
    $setting_id = $requestData['setting_id']; // 設定ID
    $process = $requestData['process']; // 處理類型      
    $target =  $requestData['target']; // 處理目標         

    if ($process === "delete_file") {
         /**
         * 讀取檔名
         */
        $sql = "SELECT `$target` FROM robot_setting WHERE id = " . $setting_id;
        $sqlResult = $db->Execute($sql);
        if ($sqlResult === false) {
        // 
        } else {
            $targetFileName = $sqlResult->fields[$target];
            $fullTargetFilePath = $uploadTargetDir.'/'.$targetFileName;
            // 刪除檔案      
            if (file_exists($fullTargetFilePath)) {
                if (unlink($fullTargetFilePath)) {
                    // 刪除成功...
                }
            }       
        }
        /**
         * 清空記錄
         */
        $sql = "UPDATE robot_setting SET `$target` = '' WHERE id = $setting_id";
        $sqlResult = $db->Execute($sql);
        if ($sqlResult === false) {
            $resultAry['status'] = 'ng';
            $resultAry['msg'] = $isDebugMode ? ($db->spySQL) : '';
        } else {
            $resultAry['status'] = 'ok';
            $resultAry['data'] = null;
        }
    }   
}
echo json_encode($resultAry, JSON_UNESCAPED_UNICODE);
?>