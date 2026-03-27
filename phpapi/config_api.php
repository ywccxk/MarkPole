<?php
/**
 * 获取网站配置API
 * 供Android客户端获取配置信息
 */

require_once './Db.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");

try {
    $db = Db::getInstance();
    
    $mapConfig = $db->getMapConfig();
    $siteConfig = $db->getSiteConfig();
    $tiandituTk = $db->getTiandituTk();
    
    $output = [
        'data' => [
            'map' => $mapConfig,
            'site' => $siteConfig,
            'tianditu' => [
                'tk' => $tiandituTk
            ]
        ],
        'state' => 'success',
        'code' => 200
    ];
    
    exit(json_encode($output, JSON_UNESCAPED_UNICODE));
    
} catch (Exception $e) {
    $output = [
        'data' => null,
        'state' => 'error',
        'code' => -201,
        'message' => $e->getMessage()
    ];
    exit(json_encode($output, JSON_UNESCAPED_UNICODE));
}
