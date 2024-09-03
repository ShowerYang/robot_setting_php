<?php
// 将错误报告级别设置为显示所有错误
error_reporting(E_ALL);
// 将错误显示设置为开启
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *'); // 或者指定允許的網域，例如：header('Access-Control-Allow-Origin: http://example.com');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Max-Age: 86400'); // 快取跨網域標頭的秒數，可根據實際需求調整
// header('Content-Type: application/json');

$auth_sid = $_GET['auth_sid']; // 授權代碼
$setting_id = $_GET['setting_id']; // 設定ID
$serial_no = $_GET['serial_no']; // 機器人序列號

// 回傳資料
$resultAry = array('status' => 'ng', 'msg' => ''); 

/**
 * 檢查參數是否存在
 */
if (isset($auth_sid) && isset($setting_id) && isset($serial_no)) {
    // 基礎資料夾路徑
    $fileBasePath = '../../_system/' . $auth_sid . '/robot_webview' . '/' . $setting_id;
    $jsonPath = $fileBasePath . '/settings.json';

    // 檢查json檔是否存在
    if (file_exists($jsonPath)) {
        $jsonFileContent = file_get_contents($jsonPath);
        $jsonData = json_decode($jsonFileContent, true);

        $zip = new ZipArchive();
        $zipFileName = 'resource_'.$serial_no.'.zip';
        $zipFullPath = $fileBasePath . '/' . $zipFileName;
        // 打开 ZIP 文件，如果无法创建，则输出错误信息并退出
        if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            // 无法创建 ZIP 文件
            // echo "无法创建 ZIP 文件";
            $resultAry['status'] = 'ng';
            $resultAry['msg'] = '無法建立zip檔';
        } else {
            // 要添加到 ZIP 文件中的文件列表
            $filesToAdd = array();

            // 加入json檔
            array_push($filesToAdd, $jsonPath);
            // 處理其他檔案
            foreach ($jsonData as $key => $value) {
                switch ($key) {               
                    case "logo": {
                        array_push($filesToAdd, $fileBasePath . "/" . $value);
                        break;
                    }
                    case "bannerList": {
                        foreach ($value as $banner) {
                            array_push($filesToAdd, $fileBasePath . "/" . "banner/" . $banner["file_name"]);
                        }
                        break;
                    }
                }
            }    
            // 将每个文件添加到 ZIP 文件中
            foreach ($filesToAdd as $file) {
                // 检查文件是否存在
                if (file_exists($file)) {
                    // 将文件添加到 ZIP 文件中，并指定文件在 ZIP 中的名称（可选）
                    $zip->addFile($file, basename($file));
                } else {
                    // echo "文件 '$file' 不存在\n";
                }
            }    
            // 关闭 ZIP 文件
            $zip->close();

            $resultAry['status'] = 'ok';
            $data = new stdClass;
            $data->name = $zipFileName;
            $resultAry['data'] = $data;
            $resultAry['msg'] = '';
        }
    }
} else {
    $resultAry['status'] = 'ng';
    $resultAry['msg'] = '參數不完整';
}
echo json_encode($resultAry, JSON_UNESCAPED_UNICODE);
?>
