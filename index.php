<?php
/**
 * 主入口文件 - 动态加载配置
 */

require_once __DIR__ . '/phpapi/Db.php';

try {
    $db = Db::getInstance();
    $siteConfig = $db->getSiteConfig();
    $mapConfig = $db->getMapConfig();
    $tiandituTk = $db->getTiandituTk();
    $imgBaseUrl = $db->getImgBaseUrl();
    
    $defaultSearch = $siteConfig['default_search'] ?? '';
    $siteName = $siteConfig['name'] ?? '电力杆塔标记系统';
    $centerLng = $mapConfig['center_lng'] ?? 119.28188;
    $centerLat = $mapConfig['center_lat'] ?? 26.64158;
    $zoom = $mapConfig['zoom'] ?? 18;
    
} catch (Exception $e) {
    // 如果获取配置失败，使用默认值
    $defaultSearch = '';
    $siteName = '电力杆塔标记';
    $centerLng = 119.28188;
    $centerLat = 26.64158;
    $zoom = 18;
    $tiandituTk = '';
    $imgBaseUrl = '';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <title><?php echo htmlspecialchars($siteName); ?></title>
    <link rel="icon" href="./ccxk.ico"/>
    <script type="text/javascript" src="http://api.tianditu.gov.cn/api?v=4.0&tk=<?php echo htmlspecialchars($tiandituTk); ?>"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/jsqr@latest/dist/jsQR.js"></script>
    <link rel="stylesheet" href="./css/map.css">
    <style>
        /* 页面样式调整 */
        body { margin: 0; padding: 0; }
    </style>
</head>
<body onLoad="onLoad()">
    <div class="search-container">
        <input type="text" id="keyWord" value="<?php echo htmlspecialchars($defaultSearch); ?>" />
        <input type="button" id="gps" onClick="gpsbuttonClicked()" value="定位" />
        <input type="button" id="device" onClick="devicebuttonClicked()" value="设备" />
        <input type="button" id="line" onClick="showlinebuttonClicked()" value="线路" />
        <input type="button" id="search" onClick="searchbuttonClicked()" value="搜索" />
    </div>
    
    <div id="mapDiv"></div>

    <div id="form-container">
        <form id="myForm" method="post">
            <div class="form-group">
                <label for="polename">杆号:</label>
                <input type="text" id="polename" name="polename">
            </div>
            
            <div class="form-group">
                <label for="upperpole">上级杆:</label>
                <input type="text" id="upperpole" name="upperpole">
            </div>
          
            <div class="form-group">
                <label for="disconnectswitch">刀闸:</label>
                <input type="text" id="disconnectswitch" name="disconnectswitch">
            </div>
            
            <div class="form-group">
                <label for="circuitbreaker">开关:</label>
                <input type="text" id="circuitbreaker" name="circuitbreaker" maxlength="12">
            </div>
            
            <div class="form-group">
                <label for="bareconductor">裸导:</label>
                <input type="tel" id="bareconductor" name="bareconductor">
            </div>
            
            <div class="form-group">
                <label for="longitude">经度:</label>
                <input type="number" id="longitude" name="longitude" step="any">
            </div>
            
            <div class="form-group">
                <label for="latitude">纬度:</label>
                <input type="number" id="latitude" name="latitude" step="any">
            </div>
            
            <div class="form-group">
                <label for="address">地址:</label>
                <textarea id="address" name="address" placeholder="请输入详细地址..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="remark">备注:</label>
                <textarea id="remark" name="remark" placeholder="请输入备注信息..."></textarea>
            </div>
            <button id="navButton" onclick="openAmapApp()">高德导航</button>
            <input type="file" id="fileInput" accept="image/*" />
            <progress id="uploadProgress" value="0" max="100"></progress>
            <input type="submit" value="提交">
        </form>
    </div>
</body>

<script>
    // 全局配置 - 从PHP传入
    var CONFIG = {
        centerLng: <?php echo $centerLng; ?>,
        centerLat: <?php echo $centerLat; ?>,
        zoom: <?php echo $zoom; ?>,
        imgBaseUrl: '<?php echo addslashes($imgBaseUrl); ?>',
        tiandituTk: '<?php echo addslashes($tiandituTk); ?>'
    };
</script>

<script type="text/javascript" src="./js/mapapi.js"></script>
</html>
