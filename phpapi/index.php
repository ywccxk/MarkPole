<?php
/**
 * 搜索杆塔信息API
 */

require_once './Db.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");

$output = array();
$title = @$_GET['title'] ? $_GET['title'] : '';

if (empty($title)) {
    $output = array(
        'data' => "搜索关键词不能为空",
        'state' => 'error',
        'code' => -201,
    );
    exit(json_encode($output, JSON_UNESCAPED_UNICODE));
}

// 检查搜索词长度
if (Db::utf8Strlen($title) > 100) {
    $output = array(
        'data' => "搜索关键词过长",
        'state' => 'error',
        'code' => -201,
    );
    exit(json_encode($output, JSON_UNESCAPED_UNICODE));
}

try {
    // 获取数据库实例
    $db = Db::getInstance();
    $prefix = $db->getPrefix();
    
    // 使用预处理语句防止SQL注入
    $sql = "SELECT * FROM {$prefix}info_table WHERE PoleName LIKE CONCAT('%', :keyword, '%')";
    $arr = $db->query($sql, ['keyword' => $title]);
    
    if ($arr === null || empty($arr)) {
        $output = array(
            'data' => "未找到相关数据",
            'state' => 'error',
            'code' => -201,
        );
        exit(json_encode($output, JSON_UNESCAPED_UNICODE));
    }
    
    // 输出数据
    $output = array(
        'data' => $arr,
        'state' => 'success',
        'code' => 200,
    );
    exit(json_encode($output, JSON_UNESCAPED_UNICODE));
    
} catch (Exception $e) {
    $output = array(
        'data' => "查询出错: " . $e->getMessage(),
        'state' => 'error',
        'code' => -201,
    );
    exit(json_encode($output, JSON_UNESCAPED_UNICODE));
}
