<?php
/**
 * 提交/更新杆塔信息API
 */

require_once './Db.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");

$output = array();

// 获取JSON数据
$jsonStr = file_get_contents('php://input');
$json = json_decode($jsonStr, true);

// 验证JSON数据
if (empty($json) || !isset($json["polename"]) || empty($json["polename"])) {
    $output = array(
        'data' => "提交数据不完整，杆号不能为空",
        'state' => 'error',
        'code' => -201,
    );
    exit(json_encode($output, JSON_UNESCAPED_UNICODE));
}

// 检查数据长度
if (Db::utf8Strlen($jsonStr) > 2000) {
    $output = array(
        'data' => "提交数据过长",
        'state' => 'error',
        'code' => -201,
    );
    exit(json_encode($output, JSON_UNESCAPED_UNICODE));
}

try {
    $db = Db::getInstance();
    $prefix = $db->getPrefix();
    $pdo = $db->getPdo();
    
    // 获取数据库表的实际字段
    $columnsSql = "SHOW COLUMNS FROM {$prefix}info_table";
    $columnsResult = $db->query($columnsSql);
    
    $dbColumns = [];
    if ($columnsResult) {
        foreach ($columnsResult as $column) {
            $dbColumns[] = $column['Field'];
        }
    }
    
    // 字段映射（前端字段名 -> 数据库字段名）
    $fields = [
        'polename' => 'PoleName',
        'upperpole' => 'UpperPole',
        'disconnectswitch' => 'DisconnectSwitch',
        'circuitbreaker' => 'CircuitBreaker',
        'bareconductor' => 'BareConductor',
        'longitude' => 'longitude',
        'latitude' => 'latitude',
        'address' => 'Address',
        'remark' => 'Note',
        'imgurl' => 'ImgUrl'
    ];
    
    // 准备数据
    $data = [];
    foreach ($fields as $jsonKey => $dbField) {
        if (in_array($dbField, $dbColumns)) {
            $data[$dbField] = $json[$jsonKey] ?? '';
        }
    }
    
    // 检查记录是否存在
    $checkSql = "SELECT COUNT(1) as cnt FROM {$prefix}info_table WHERE PoleName = :polename";
    $checkResult = $db->queryOne($checkSql, ['polename' => $data['PoleName']]);
    
    if ($checkResult && $checkResult['cnt'] > 0) {
        // 更新记录
        $updateFields = [];
        $updateParams = [];
        
        foreach ($data as $dbField => $value) {
            if ($dbField !== 'PoleName') {
                $updateFields[] = "{$dbField} = :{$dbField}";
                $updateParams[$dbField] = $value;
            }
        }
        
        $updateParams['PoleName'] = $data['PoleName'];
        
        $sql = "UPDATE {$prefix}info_table SET " . implode(', ', $updateFields) . " WHERE PoleName = :PoleName";
        $result = $db->execute($sql, $updateParams);
        
        if ($result) {
            $output = array(
                'data' => '记录更新成功',
                'state' => 'success',
                'code' => 200,
            );
        } else {
            $output = array(
                'data' => '记录更新失败',
                'state' => 'error',
                'code' => -201,
            );
        }
    } else {
        // 插入新记录
        $insertFields = array_keys($data);
        $placeholders = ':' . implode(', :', $insertFields);
        
        $sql = "INSERT INTO {$prefix}info_table (" . implode(', ', $insertFields) . ") VALUES ({$placeholders})";
        $result = $db->execute($sql, $data);
        
        if ($result) {
            $output = array(
                'data' => '新记录插入成功',
                'state' => 'success',
                'code' => 200,
            );
        } else {
            $output = array(
                'data' => '新记录插入失败',
                'state' => 'error',
                'code' => -201,
            );
        }
    }
    
    exit(json_encode($output, JSON_UNESCAPED_UNICODE));
    
} catch (Exception $e) {
    $output = array(
        'data' => "操作失败: " . $e->getMessage(),
        'state' => 'error',
        'code' => -201,
    );
    exit(json_encode($output, JSON_UNESCAPED_UNICODE));
}
