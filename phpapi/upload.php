<?php
/**
 * 图片上传API
 */

require_once './Db.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: text/plain; charset=utf-8");

try {
    // 获取配置
    $db = Db::getInstance();
    $config = $db->config['upload'] ?? [];
    
    // 设置上传目录
    $uploadDir = __DIR__ . '/../img/';
    
    // 确保上传目录存在并且可写
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            echo "上传目录创建失败！";
            exit;
        }
    }
    
    if (!is_writable($uploadDir)) {
        echo "上传目录不可写！";
        exit;
    }
    
    // 检查是否有文件被上传
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo "没有文件被上传或上传出错！";
        exit;
    }
    
    // 验证文件类型
    $allowedTypes = $config['allowed_types'] ?? ['jpg', 'jpeg', 'png', 'gif'];
    $fileExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    
    if (!in_array($fileExt, $allowedTypes)) {
        echo "不支持的文件类型！仅支持: " . implode(', ', $allowedTypes);
        exit;
    }
    
    // 验证文件大小
    $maxSize = $config['max_size'] ?? (10 * 1024 * 1024);
    if ($_FILES['image']['size'] > $maxSize) {
        echo "文件大小超过限制！最大允许: " . ($maxSize / 1024 / 1024) . "MB";
        exit;
    }
    
    // 获取客户端发送的新文件名
    $newFileName = $_POST['newFileName'] ?? $_FILES['image']['name'];
    
    // 安全检查：只允许特定扩展名
    $newFileName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $newFileName);
    $newFileName = basename($newFileName);
    
    // 完整的文件路径
    $filePath = $uploadDir . $newFileName;
    
    // 移动上传的文件到指定目录
    if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
        // 文件上传成功
        echo "文件上传成功！新文件名为：" . $newFileName;
        
        // 生成缩略图（可选功能）
        $thumbDir = $uploadDir . 'thumb/';
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }
        
        // 如果是图片文件，可以在这里添加缩略图生成逻辑
        // 目前前端已经做了压缩处理，这里暂时保留原图
        
    } else {
        echo "文件移动失败！";
    }
    
} catch (Exception $e) {
    echo "上传出错: " . $e->getMessage();
}