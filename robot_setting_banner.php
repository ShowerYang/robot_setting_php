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
// echo $_SESSION['dbConnectFile'];

// 基礎資料夾路徑
$uploadBaseDir = '../../_system/' . $_SESSION['auth_code'] . '/robot_webview';
// 是否準備資料夾OK
$isPrepareDirOk = true;

// 目前時間
$currentDateTime = date("Y-m-d H:i:s");
// 時間戳
$timestamp = time();

// 是否偵錯模式
$isDebugMode = true;
// 回傳資料
$resultAry = array('status' => 'ng', 'msg' => '');
// 請求方法
$method = strtoupper($_SERVER['REQUEST_METHOD']);
switch ($method) {
    case 'GET': {
            $setting_id = $_GET['setting_id'];
            $result = $db->Execute("SELECT * FROM robot_setting_banner WHERE setting_id = $setting_id ORDER BY display_order");
            if ($result === false) {
                $resultAry['status'] = 'ng';
                $resultAry['msg'] .= $db->spySQL;
            } else {
                $resultAry['dataCount'] = $result->RecordCount(); // 資料筆數
                $resultAry['data'] = array();
                if ($result->RecordCount() > 0) {
                    while (!$result->EOF) {
                        $resultAry['data'][] = array(
                            'id' => (int)$result->fields['id'],
                            'setting_id' => (int)$result->fields['setting_id'],
                            'file_name' => $result->fields['file_name'],
                            'file_type' => (int)$result->fields['file_type'],
                            'stay_time' => (int)$result->fields['stay_time'],
                            'display_order' => (int)$result->fields['display_order']
                        );
                        $result->MoveNext();
                    }
                }
                $resultAry['status'] = 'ok';
                $resultAry['msg'] .= $isDebugMode ? ($db->spySQL . PHP_EOL) : '';
            }
            break;
        }
    case 'POST': {
            $target = 'banner';
            $setting_id = $_POST['setting_id']; // 目標設定ID
            $id = (int)$_POST['id']; // 項目ID
            $stay_time = $_POST['stay_time']; // 停留時間
            $display_order = $_POST['display_order']; // 停留時間
            $oldFileName = $_POST['oldFileName']; // 舊檔案名稱

            // 組成設定ID
            if (isset($setting_id)) {
                $uploadTargetDir = $uploadBaseDir . '/' . $setting_id . '/banner';
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

            $hasFile = false; // 是否有檔案
            $isFileUploadOk = false; // 是否檔案上傳成功
            if (isset($_FILES["file"])) {
                $hasFile = true;
                if ($_FILES["file"]["error"] == UPLOAD_ERR_OK) {
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
                        // 上傳檔案
                        if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
                            $isFileUploadOk = true;
                        }
                        // 如果有傳入舊檔案名稱，則要刪除檔案
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
            }

            // 要執行
            $sql = "";
            // 有上傳檔案
            if ($hasFile) {
                // 且上傳成功
                if ($isFileUploadOk) {
                    $file_type = 1;
                    if (strpos($fileType, "video") !== false) {
                        $file_type = 2;
                    }

                    if ($id > 0) {
                        // 修改
                        $sql = "UPDATE robot_setting_banner SET file_name = '$fileName', file_type = $file_type WHERE id = $id AND setting_id = $setting_id";
                    } else {
                        // 新增
                        $sql = "INSERT robot_setting_banner (setting_id, file_name, file_type, stay_time, display_order) VALUES ($setting_id, '$fileName', $file_type, $stay_time, $display_order)";
                    }
                } else {
                    $resultAry['status'] = 'ng';
                    $resultAry['msg'] = 'failed to upload file';
                }
            } 

            if ($sql !== '') {
                $sqlResult = $db->Execute($sql);
                if ($sqlResult === false) {
                    $resultAry['status'] = 'ng';
                    $resultAry['msg'] .= 'failed to Execute: ' . $isDebugMode ? ($db->spySQL) : '';
                } else {
                    $resultAry['status'] = 'ok';
                    $resultAry['data'] = array();
                }
            }
            break;
        }
    case 'PATCH': {
        // 處理傳入參數
        $patchData = file_get_contents("php://input");
        $requestData = json_decode($patchData, true);
        $id = (int)$requestData['id']; // 項目ID
        $setting_id = $requestData['setting_id']; // 目標設定ID
        $action = $requestData['action']; // 處理動作
        $stay_time = $requestData['stay_time']; // 停留時間
        $orderSetting = $requestData['orderSetting']; // 停留時間

        if ($action === 'stay_time') {
            $sql = "UPDATE robot_setting_banner SET `stay_time` = $stay_time WHERE id = $id AND setting_id = $setting_id";
            $sqlResult = $db->Execute($sql);     
            if ($sqlResult === false) {
                $resultAry['status'] = 'ng';
                $resultAry['msg'] .= 'failed to Execute: ' . $isDebugMode ? ($db->spySQL) : '';
            } else {
                $resultAry['status'] = 'ok';
                $resultAry['data'] = array();
            }

        } else if ($action === "display_order") {
            try {
                foreach ($orderSetting as $setting) {
                    $id = $setting['id']; // 按鈕ID
                    $newOrder = $setting['newOrder']; // 新順序
                    $executeSql = "UPDATE robot_setting_banner SET `display_order` = $newOrder WHERE id = $id AND setting_id = $setting_id";
                    $db->Execute($executeSql);                    
                }
                $resultAry['status'] = 'ok';
            } catch(Exception $e) {
                $resultAry['status'] = 'ng';
                $resultAry['msg'] .= $db->spySQL;
            }
        }
        break;
    }
    case 'DELETE': {
        $id = (int)$_GET['id'];
        $setting_id = (int)$_GET['setting_id'];
        $uploadTargetDir = $uploadBaseDir . '/' . $setting_id . '/banner';

        $sqlFileName = $db->Execute("SELECT file_name FROM robot_setting_banner WHERE id = $id AND setting_id = $setting_id");
        if ($sqlFileName !== false) {
            $targetFileName = $sqlFileName->fields['file_name'];
            if (isset($targetFileName)) {
                $targetFilePath = $uploadTargetDir . '/' . $targetFileName;
                if (file_exists($targetFilePath)) {
                    if (unlink($targetFilePath)) {
                        // 刪除成功...
                    }
                }
            }
        } 

        $result = $db->Execute("DELETE FROM robot_setting_banner WHERE id = $id AND setting_id = $setting_id");
        if ($result === false) {
            $resultAry['status'] = 'ng';
            $resultAry['msg'] = $db->spySQL;
        } else {
            $resultAry['status'] = 'ok';
            $resultAry['msg'] .= $isDebugMode ? ($db->spySQL . PHP_EOL) : '';
        }
        break;
    }
}
echo json_encode($resultAry, JSON_UNESCAPED_UNICODE);
?>