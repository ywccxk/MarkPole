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
    
    // 确保上传目录存在
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if (!is_writable($uploadDir)) {
        echo "上传目录不可写！";
        exit;
    }
    
    // 检查文件上传
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
    
    // 获取并处理文件名
    $newFileName = $_POST['newFileName'] ?? $_FILES['image']['name'];
    $newFileName = preg_replace('/[^\w\-\.#]/u', '', $newFileName);
    $newFileName = basename($newFileName);
    
    if (empty($newFileName)) {
        echo "文件名无效！";
        exit;
    }
    
    // 移动上传文件
    $filePath = $uploadDir . $newFileName;
    
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
        echo "文件移动失败！";
        exit;
    }
    
    echo "文件上传成功！新文件名为：" . $newFileName;
    
    // 生成缩略图
    if (function_exists('imagecreatetruecolor')) {
        $imageInfo = @getimagesize($filePath);
        
        if ($imageInfo) {
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $imageType = $imageInfo[2];
            
            // 只处理宽度大于500px的图片
            if ($width > 500) {
                $thumbDir = $uploadDir . 'thumb/';
                
                if (!is_dir($thumbDir)) {
                    mkdir($thumbDir, 0755, true);
                }
                
                if (is_writable($thumbDir)) {
                    $thumbWidth = 500;
                    $thumbHeight = round($height * (500 / $width));
                    
                    // 创建源图像资源
                    $srcImg = null;
                    switch ($imageType) {
                        case IMAGETYPE_JPEG:
                            $srcImg = imagecreatefromjpeg($filePath);
                            break;
                        case IMAGETYPE_PNG:
                            $srcImg = imagecreatefrompng($filePath);
                            break;
                        case IMAGETYPE_GIF:
                            $srcImg = imagecreatefromgif($filePath);
                            break;
                    }
                    
                    if ($srcImg) {
                        // 创建缩略图
                        $thumbImg = imagecreatetruecolor($thumbWidth, $thumbHeight);
                        
                        // 处理透明通道
                        if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_GIF) {
                            imagealphablending($thumbImg, false);
                            imagesavealpha($thumbImg, true);
                        }
                        
                        // 缩放图片
                        imagecopyresampled($thumbImg, $srcImg, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
                        
                        // 保存缩略图
                        $thumbPath = $thumbDir . $newFileName;
                        
                        if ($imageType === IMAGETYPE_PNG) {
                            imagepng($thumbImg, $thumbPath);
                        } elseif ($imageType === IMAGETYPE_GIF) {
                            imagegif($thumbImg, $thumbPath);
                        } else {
                            imagejpeg($thumbImg, $thumbPath, 85);
                        }
                        
                        // 释放内存
                        imagedestroy($srcImg);
                        imagedestroy($thumbImg);
                        
                        echo " (缩略图已生成:{$thumbWidth}x{$thumbHeight})";
                    }
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "上传出错: " . $e->getMessage();
}
