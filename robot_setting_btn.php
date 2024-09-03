<?php
session_start();
/**前置定義**/
define('moduleName','robot_setting_btn');
include('../checkin.inc-server.php');
include("adodb/adodb.inc.php");
include('common/lang.inc_'.$_SESSION['lang'].'.php');  
include('common/stringlib_'.$_SESSION['lang'].'.php');
include('common/timelib.php');   
include('common/form_tools_'.$_SESSION['lang'].'.php');
include('common/loglib_'.$_SESSION['lang'].'.php');
include_once($_SESSION['dbConnectFile']);
// echo $_SESSION['dbConnectFile'];

// 是否偵錯模式
$isDebugMode = true;
// 回傳資料
$resultAry = array('status' => 'ng', 'msg' => '');
// 請求方法
$method = strtoupper($_SERVER['REQUEST_METHOD']);
switch($method) {
    /**
     * 取得按鈕列表
     */
    case 'GET': {
        $setting_id = $_GET['setting_id'];
        $result = $db->Execute("SELECT * FROM robot_setting_btn WHERE setting_id = $setting_id ORDER BY dispaly_order");
        if ($result === false) {
            $resultAry['status'] = 'ng';
            $resultAry['msg'] .= $db->spySQL;
        } else {
            $resultAry['dataCount'] = $result->RecordCount(); // 資料筆數
            if ($result->RecordCount() > 0) {
                while (!$result->EOF) {
                    $resultAry['data'][] = array(
                        'id' => (int)$result->fields['id'],
                        'setting_id' => (int)$result->fields['setting_id'],
                        'title' => $result->fields['title'],
                        'is_visible' => (int)$result->fields['is_visible'],
                        'bg_color' => $result->fields['bg_color'],
                        'is_goto' => (int)$result->fields['is_goto'],
                        'location' => $result->fields['location'],
                        'start_speak_content' => $result->fields['start_speak_content'],
                        'arrive_speak_content' => $result->fields['arrive_speak_content'],
                        'dispaly_order' => (int)$result->fields['dispaly_order']
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
        $setting_id = $_POST['setting_id'];
        $title = $_POST['title'];
        $is_visible = $_POST['is_visible'];
        $bg_color = $_POST['bg_color'];
        $is_goto = $_POST['is_goto'];
        $location = $_POST['location'];
        $start_speak_content = $_POST['start_speak_content'];
        $arrive_speak_content = $_POST['arrive_speak_content'];
        $dispaly_order = $_POST['dispaly_order'];

        $result = $db->Execute("INSERT INTO robot_setting_btn (setting_id, title, is_visible, bg_color, is_goto, `location`, start_speak_content, arrive_speak_content, dispaly_order) VALUES ($setting_id, '$title', $is_visible, '$bg_color', $is_goto, '$location', '$start_speak_content', '$arrive_speak_content', $dispaly_order)");
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
        $process = $requestData['process']; // 處理類型       
        $setting_id = $requestData['setting_id']; // 設定ID

        if ($process === 'column') {
            /* 處理特定欄位更新 */           
            $id = $requestData['id'];
            $title = $requestData['title'];
            $is_visible = $requestData['is_visible'];
            $bg_color = $requestData['bg_color'];
            $is_goto = $requestData['is_goto'];      
            $location = $requestData['location'];
            $start_speak_content = $requestData['start_speak_content'];
            $arrive_speak_content = $requestData['arrive_speak_content'];     

            $columnName = $requestData['columnName']; // 欄位名稱
            if (isset($columnName)) {
                // 處理要更新的數值
                $valueSql = null;
                if (in_array($columnName, array('is_visible', 'is_goto'))) {
                    $valueSql = (int)$requestData['value'];
                } else {
                    $valueSql = "'".$requestData['value']."'";
                }
                $executeSql = "UPDATE robot_setting_btn SET $columnName = $valueSql WHERE id = $id AND setting_id = $setting_id";
            } else {
                $executeSql = "UPDATE robot_setting_btn SET title='$title', is_visible=$is_visible, bg_color='$bg_color', is_goto=$is_goto, `location`='$location', start_speak_content='$start_speak_content', arrive_speak_content='$arrive_speak_content' WHERE id = $id AND setting_id = $setting_id";
            } 

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
        } else if ($process === 'order') {
            /* 處理所有按鈕的順序欄位更新 */            
            $resultAry['data'] = null;
            $resultAry['msg'] = '';            
            $orderSetting = $requestData['orderSetting'];
            try {
                foreach ($orderSetting as $setting) {
                    $id = $setting['id']; // 按鈕ID
                    $newOrder = $setting['newOrder']; // 新順序
                    $executeSql = "UPDATE robot_setting_btn SET `dispaly_order` = $newOrder WHERE id = $id AND setting_id = $setting_id";
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
        $id = $_GET['id'];
        $setting_id = $_GET['setting_id'];
        $result = $db->Execute("DELETE FROM robot_setting_btn WHERE id = $id AND setting_id = $setting_id");
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