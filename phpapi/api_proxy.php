<?php
/**
 * 天地图API代理
 * 用于隐藏API Token，客户端通过此脚本加载天地图JS API
 */

require_once './Db.php';
header("Content-Type: application/javascript; charset=utf-8");
header("Access-Control-Allow-Origin: *");

// 设置缓存（API JS文件很少变化，可以缓存较长时间）
header("Cache-Control: public, max-age=3600"); // 缓存1小时

try {
    $db = Db::getInstance();
    $tiandituTk = $db->getTiandituTk();
    
    if (empty($tiandituTk)) {
        echo "console.error('天地图Token未配置，请检查config.php');";
        exit;
    }
    
    // 天地图API地址 - 尝试多个服务器
    $apiUrls = [
        "http://api.tianditu.gov.cn/api?v=4.0&tk=" . $tiandituTk,
        "https://api.tianditu.gov.cn/api?v=4.0&tk=" . $tiandituTk
    ];
    
    $jsContent = false;
    $lastError = '';
    
    foreach ($apiUrls as $apiUrl) {
        // 设置请求上下文
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'header' => "Referer: https://www.tianditu.gov.cn/\r\n"
            ]
        ]);
        
        // 获取API JS内容
        $jsContent = @file_get_contents($apiUrl, false, $context);
        
        if ($jsContent !== false && strpos($jsContent, 'T') !== false) {
            break; // 成功获取
        }
        
        $lastError = error_get_last()['message'] ?? 'Unknown error';
    }
    
    if ($jsContent === false) {
        echo "console.error('天地图API加载失败: " . addslashes($lastError) . "');";
        exit;
    }
    
    // 输出JS内容
    echo $jsContent;
    
} catch (Exception $e) {
    echo "console.error('天地图API错误: " . addslashes($e->getMessage()) . "');";
}
