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
            $setting_id = $_GET['setting_id'];
            $result = $db->Execute("SELECT * FROM robot_setting_marquee WHERE setting_id = " . $setting_id);
            if ($result === false) {
                $resultAry['status'] = 'ng';
                $resultAry['msg'] .= $db->spySQL;
            } else {
                $resultAry['dataCount'] = $result->RecordCount(); // 資料筆數
                if ($result->RecordCount() > 0) {
                    while (!$result->EOF) {
                        $resultAry['data'][] = array(
                            'id' => (int)$result->fields['id'],
                            'content' => $result->fields['content'],
                            'display_order' => isset($result->fields['display_order']) ? $result->fields['display_order'] : 0
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
            $content = $_POST['content'];
            $setting_id = (int)$_POST['setting_id'];

            $result = $db->Execute("INSERT INTO robot_setting_marquee (setting_id, content) VALUES ($setting_id, '$content')");
            if ($result === false) {
                $resultAry['status'] = 'ng';
                $resultAry['msg'] .= $db->spySQL;
            } else {
                $resultAry['status'] = 'ok';
                $resultAry['data'] = null;
                $resultAry['msg'] .= $isDebugMode ? ($db->spySQL . PHP_EOL) : '';
            }
            break;
        }
    case 'PATCH': {
            // 處理傳入參數
            $patchData = file_get_contents("php://input");
            $requestData = json_decode($patchData, true);
            $setting_id = $requestData['setting_id'];
            $id = $requestData['id'];
            $content = $requestData['content'];

            $executeSql = "UPDATE robot_setting_marquee SET `content` = '$content' WHERE id = $id AND setting_id = $setting_id";
            $result = $db->Execute($executeSql);
            if ($result === false) {
                $resultAry['status'] = 'ng';
                $resultAry['msg'] .= $db->spySQL;
                // $resultAry['requestData'] = $requestData;
                // $resultAry['setting_id'] = $setting_id;
            } else {
                $resultAry['status'] = 'ok';
                $resultAry['data'] = null;
                $resultAry['msg'] .= $isDebugMode ? ($db->spySQL . PHP_EOL) : '';
            }
            break;
        }
    case 'DELETE': {
            $id = (int)$_GET['id'];
            $setting_id = (int)$_GET['setting_id'];
            $result = $db->Execute("DELETE FROM `robot_setting_marquee` WHERE id = $id AND setting_id = $setting_id");
            if ($result === false) {
                $resultAry['status'] = 'ng';
                $resultAry['msg'] .= $db->spySQL;
            } else {
                $resultAry['status'] = 'ok';
                $resultAry['msg'] .= $isDebugMode ? ($db->spySQL . PHP_EOL) : '';
            }
            break;
        }
}
echo json_encode($resultAry, JSON_UNESCAPED_UNICODE);
?>
