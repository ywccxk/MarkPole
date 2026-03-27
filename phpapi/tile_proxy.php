<?php
/**
 * 地图瓦片代理
 * 用于隐藏天地图Token，客户端通过此脚本请求地图瓦片
 */

require_once './Db.php';
header("Content-Type: image/png");
header("Access-Control-Allow-Origin: *");
header("Cache-Control: public, max-age=86400"); // 缓存一天

try {
    $db = Db::getInstance();
    $tiandituTk = $db->getTiandituTk();
    
    if (empty($tiandituTk)) {
        http_response_code(500);
        exit('Token not configured');
    }
    
    // 获取请求参数
    $x = isset($_GET['x']) ? intval($_GET['x']) : 0;
    $y = isset($_GET['y']) ? intval($_GET['y']) : 0;
    $z = isset($_GET['z']) ? intval($_GET['z']) : 0;
    
    // 验证参数范围
    if ($z < 1 || $z > 18) {
        http_response_code(400);
        exit('Invalid zoom level');
    }
    
    // 天地图影像底图（img_w）和矢量图（vec_w）
    // 可根据需要选择图层类型
    $layerType = isset($_GET['layer']) ? $_GET['layer'] : 'img';
    
    // 常用的图层类型
    // img_w: 影像底图
    // vec_w: 矢量地图
    // cia_w: 影像标注
    // cva_w: 矢量标注
    $validLayers = ['img', 'vec', 'cia', 'cva'];
    if (!in_array($layerType, $validLayers)) {
        $layerType = 'img';
    }
    
    // 构建天地图瓦片URL
    $tileUrl = "http://t0.tianditu.gov.cn/{$layerType}_w/wmts?" .
        "SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0" .
        "&LAYER={$layerType}&STYLE=default&TILEMATRIXSET=w&FORMAT=tiles" .
        "&TILEMATRIX={$z}&TILEROW={$y}&TILECOL={$x}&tk=" . $tiandituTk;
    
    // 设置上下文，跳过SSL验证（如果是HTTPS）
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ],
        'http' => [
            'timeout' => 10,
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);
    
    // 请求瓦片
    $imageData = @file_get_contents($tileUrl, false, $context);
    
    if ($imageData === false) {
        // 如果t0失败，尝试t1
        $tileUrl = str_replace('t0.tianditu.gov.cn', 't1.tianditu.gov.cn', $tileUrl);
        $imageData = @file_get_contents($tileUrl, false, $context);
    }
    
    if ($imageData === false) {
        // 尝试t2
        $tileUrl = str_replace('t1.tianditu.gov.cn', 't2.tianditu.gov.cn', $tileUrl);
        $imageData = @file_get_contents($tileUrl, false, $context);
    }
    
    if ($imageData === false) {
        http_response_code(502);
        exit('Failed to fetch tile');
    }
    
    // 输出图片
    echo $imageData;
    
} catch (Exception $e) {
    http_response_code(500);
    exit($e->getMessage());
}
