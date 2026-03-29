<?php
/**
 * 安装向导入口文件
 * 用于配置数据库和网站基本信息
 */

// 错误显示
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$error = '';
$success = false;

// 检查是否已安装
$configFile = __DIR__ . '/../config.php';
if (file_exists($configFile) && $step < 999 && $step != 4) {
    header('Location: ../index.php');
    exit;
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 1) {
        // 验证环境
        $checks = [];
        
        // 检查PHP版本
        $checks['php_version'] = version_compare(PHP_VERSION, '7.4.0', '>=');
        
        // 检查必要扩展
        $checks['pdo'] = extension_loaded('pdo');
        $checks['pdo_mysql'] = extension_loaded('pdo_mysql');
        $checks['mbstring'] = extension_loaded('mbstring');
        $checks['json'] = extension_loaded('json');
        $checks['gd'] = function_exists('imagecreatetruecolor');
        
        // 检查目录权限
        $checks['img_dir'] = is_writable(__DIR__ . '/../img');
        $checks['config_dir'] = is_writable(__DIR__ . '/..');
        
        $allPassed = !in_array(false, $checks, true);
        
        if ($allPassed) {
            header('Location: ?step=2');
            exit;
        } else {
            $error = '环境检查未通过，请确保所有要求都已满足';
        }
    } elseif ($step === 2) {
        // 保存数据库配置
        $db_host = trim($_POST['db_host'] ?? '');
        $db_port = trim($_POST['db_port'] ?? '3306');
        $db_user = trim($_POST['db_user'] ?? '');
        $db_pass = trim($_POST['db_pass'] ?? '');
        $db_name = trim($_POST['db_name'] ?? '');
        $db_prefix = trim($_POST['db_prefix'] ?? 'map_');
        
        // 验证输入
        if (empty($db_host) || empty($db_user) || empty($db_name)) {
            $error = '请填写完整的数据库配置信息';
        } else {
            // 测试数据库连接并创建表格
            try {
                $dsn = "mysql:host={$db_host};port={$db_port};charset=utf8mb4";
                $pdo = new PDO($dsn, $db_user, $db_pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                
                // 选择数据库（需要手动创建数据库）
                $pdo->exec("USE `{$db_name}`");
                
                // 记录创建结果
                $tableResults = [];
                
                // 创建数据表 - 主信息表
                $tableName = $db_prefix . 'info_table';
                try {
                    $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `PoleName` VARCHAR(100) NOT NULL UNIQUE,
                        `UpperPole` VARCHAR(100) DEFAULT '',
                        `DisconnectSwitch` VARCHAR(100) DEFAULT '',
                        `CircuitBreaker` VARCHAR(100) DEFAULT '',
                        `BareConductor` VARCHAR(100) DEFAULT '',
                        `longitude` DECIMAL(10, 6) DEFAULT 0,
                        `latitude` DECIMAL(10, 6) DEFAULT 0,
                        `Address` TEXT,
                        `Note` TEXT,
                        `ImgUrl` VARCHAR(255) DEFAULT '',
                        `CreateTime` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        `UpdateTime` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX `idx_polename` (`PoleName`),
                        INDEX `idx_upperpole` (`UpperPole`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                    $pdo->exec($sql);
                    $tableResults[] = ['name' => $tableName, 'status' => 'success', 'message' => '创建成功'];
                } catch (PDOException $e) {
                    $tableResults[] = ['name' => $tableName, 'status' => 'error', 'message' => $e->getMessage()];
                }
                
                // 检查是否有表创建失败
                $hasError = false;
                foreach ($tableResults as $result) {
                    if ($result['status'] === 'error') {
                        $hasError = true;
                        break;
                    }
                }
                
                if (!$hasError) {
                    // 保存配置到session
                    $_SESSION['db_config'] = [
                        'host' => $db_host,
                        'port' => $db_port,
                        'user' => $db_user,
                        'pass' => $db_pass,
                        'name' => $db_name,
                        'prefix' => $db_prefix
                    ];
                    $_SESSION['table_results'] = $tableResults;
                    
                    header('Location: ?step=3');
                    exit;
                } else {
                    $error = '部分数据表创建失败，请检查错误信息';
                }
                
            } catch (PDOException $e) {
                $error = '数据库连接失败: ' . $e->getMessage();
            }
        }
    } elseif ($step === 4) {
        // 数据库表管理页面 - 重建/修复表
        $db_host = trim($_POST['db_host'] ?? '');
        $db_port = trim($_POST['db_port'] ?? '3306');
        $db_user = trim($_POST['db_user'] ?? '');
        $db_pass = trim($_POST['db_pass'] ?? '');
        $db_name = trim($_POST['db_name'] ?? '');
        $db_prefix = trim($_POST['db_prefix'] ?? 'map_');
        
        $tableResults = [];
        $connectionSuccess = false;
        
        if (!empty($db_host) && !empty($db_user) && !empty($db_name)) {
            try {
                $dsn = "mysql:host={$db_host};port={$db_port};charset=utf8mb4";
                $pdo = new PDO($dsn, $db_user, $db_pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                
                $pdo->exec("USE `{$db_name}`");
                $connectionSuccess = true;
                
                // 重新创建数据表
                $tableName = $db_prefix . 'info_table';
                $action = $_POST['action'] ?? 'recreate';
                
                if ($action === 'recreate') {
                    // 删除旧表并重新创建
                    $pdo->exec("DROP TABLE IF EXISTS `{$tableName}`");
                }
                
                try {
                    $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `PoleName` VARCHAR(100) NOT NULL UNIQUE,
                        `UpperPole` VARCHAR(100) DEFAULT '',
                        `DisconnectSwitch` VARCHAR(100) DEFAULT '',
                        `CircuitBreaker` VARCHAR(100) DEFAULT '',
                        `BareConductor` VARCHAR(100) DEFAULT '',
                        `longitude` DECIMAL(10, 6) DEFAULT 0,
                        `latitude` DECIMAL(10, 6) DEFAULT 0,
                        `Address` TEXT,
                        `Note` TEXT,
                        `ImgUrl` VARCHAR(255) DEFAULT '',
                        `CreateTime` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        `UpdateTime` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX `idx_polename` (`PoleName`),
                        INDEX `idx_upperpole` (`UpperPole`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                    $pdo->exec($sql);
                    $tableResults[] = ['name' => $tableName, 'status' => 'success', 'message' => ($action === 'recreate' ? '重建成功' : '创建成功')];
                } catch (PDOException $e) {
                    $tableResults[] = ['name' => $tableName, 'status' => 'error', 'message' => $e->getMessage()];
                }
                
            } catch (PDOException $e) {
                $error = '数据库连接失败: ' . $e->getMessage();
            }
        }
    } elseif ($step === 3) {
        // 保存站点配置并完成安装
        $site_name = trim($_POST['site_name'] ?? '电力杆塔标记系统');
        $site_url = trim($_POST['site_url'] ?? '');
        $default_search = trim($_POST['default_search'] ?? '');
        $map_center_lng = trim($_POST['map_center_lng'] ?? '119.28188');
        $map_center_lat = trim($_POST['map_center_lat'] ?? '26.64158');
        $map_zoom = intval($_POST['map_zoom'] ?? 18);
        $tianditu_tk = trim($_POST['tianditu_tk'] ?? '');
        
        // 验证天地图Token
        if (empty($tianditu_tk)) {
            $error = '请填写天地图Token';
        }
        
        // 验证URL格式
        if (!empty($site_url)) {
            $site_url = rtrim($site_url, '/');
            if (!preg_match('/^https?:\/\/.+/', $site_url)) {
                $site_url = 'https://' . $site_url;
            }
        }
        
        if (!isset($_SESSION['db_config'])) {
            $error = '请先完成数据库配置';
        } else {
            // 获取session中的数据库配置
            $dbConfig = $_SESSION['db_config'];
            
            // 安全处理各配置项
            $dbHost = addslashes($dbConfig['host']);
            $dbPort = addslashes($dbConfig['port']);
            $dbUser = addslashes($dbConfig['user']);
            $dbPass = addslashes($dbConfig['pass']);
            $dbName = addslashes($dbConfig['name']);
            $dbPrefix = addslashes($dbConfig['prefix']);
            $siteNameSafe = addslashes($site_name);
            $siteUrlSafe = addslashes($site_url);
            $defaultSearchSafe = addslashes($default_search);
            $tiandituTkSafe = addslashes($tianditu_tk);
            
            // 生成配置文件
            $configContent = "<?php
/**
 * 配置文件 - 由安装程序自动生成
 * 生成时间: " . date('Y-m-d H:i:s') . "
 */

return [
    // 数据库配置
    'db' => [
        'host' => '{$dbHost}',
        'port' => '{$dbPort}',
        'user' => '{$dbUser}',
        'pass' => '{$dbPass}',
        'name' => '{$dbName}',
        'prefix' => '{$dbPrefix}',
        'charset' => 'utf8mb4'
    ],
    
    // 站点配置
    'site' => [
        'name' => '{$siteNameSafe}',
        'url' => '{$siteUrlSafe}',
        'default_search' => '{$defaultSearchSafe}'
    ],
    
    // 地图配置
    'map' => [
        'center_lng' => {$map_center_lng},
        'center_lat' => {$map_center_lat},
        'zoom' => {$map_zoom}
    ],
    
    // 天地图API配置
    'tianditu' => [
        'tk' => '{$tiandituTkSafe}'
    ],
    
    // 图片上传配置
    'upload' => [
        'max_size' => 10 * 1024 * 1024, // 10MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif'],
        'thumb_width' => 120,
        'thumb_height' => 135
    ]
];
";
            
            // 确保config目录存在
            $configDir = __DIR__ . '/../config';
            if (!is_dir($configDir)) {
                mkdir($configDir, 0755, true);
            }
            
            // 写入配置文件
            $configFile = __DIR__ . '/../config.php';
            if (file_put_contents($configFile, $configContent)) {
                // 创建img目录
                $imgDir = __DIR__ . '/../img';
                $imgThumbDir = __DIR__ . '/../img/thumb';
                if (!is_dir($imgDir)) mkdir($imgDir, 0755, true);
                if (!is_dir($imgThumbDir)) mkdir($imgThumbDir, 0755, true);
                
                // 清理session
                unset($_SESSION['db_config']);
                
                $success = true;
                $step = 999;
            } else {
                $error = '配置文件写入失败，请检查目录权限';
            }
        }
    }
}

// 渲染不同步骤的页面
switch ($step) {
    case 1:
        // 环境检查页面
        break;
    case 2:
        // 数据库配置页面
        break;
    case 3:
        // 站点配置页面
        break;
    case 999:
        // 安装完成页面
        break;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装向导 - 电力杆塔标记系统</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Microsoft YaHei", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .install-box {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 700px;
            overflow: hidden;
        }
        .install-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .install-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .install-header p {
            opacity: 0.9;
        }
        .step-bar {
            display: flex;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }
        .step-item {
            flex: 1;
            padding: 15px;
            text-align: center;
            color: #999;
            position: relative;
        }
        .step-item.active {
            color: #667eea;
            font-weight: bold;
        }
        .step-item.completed {
            color: #28a745;
        }
        .step-item:not(:last-child)::after {
            content: '→';
            position: absolute;
            right: -5px;
            top: 50%;
            transform: translateY(-50%);
            color: #ddd;
        }
        .install-body {
            padding: 40px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-group .hint {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        .form-row {
            display: flex;
            gap: 20px;
        }
        .form-row .form-group {
            flex: 1;
        }
        .btn {
            display: inline-block;
            padding: 14px 35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-block {
            width: 100%;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .alert-error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }
        .alert-success {
            background: #efe;
            border: 1px solid #cfc;
            color: #3c3;
        }
        .check-list {
            list-style: none;
        }
        .check-list li {
            padding: 12px 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .check-list .status {
            font-weight: bold;
        }
        .check-list .status.pass {
            color: #28a745;
        }
        .check-list .status.fail {
            color: #dc3545;
        }
        .success-icon {
            font-size: 80px;
            color: #28a745;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="install-box">
        <div class="install-header">
            <h1>⚡ 电力杆塔标记系统</h1>
            <p>安装向导 - 请按照步骤完成配置</p>
        </div>
        
        <div class="step-bar">
            <div class="step-item <?php echo $step >= 1 ? 'active' : ''; echo $step > 1 ? ' completed' : ''; ?>">
                1. 环境检查
            </div>
            <div class="step-item <?php echo $step >= 2 ? 'active' : ''; echo $step > 2 ? ' completed' : ''; ?>">
                2. 数据库配置
            </div>
            <div class="step-item <?php echo $step >= 3 ? 'active' : ''; echo $step > 3 ? ' completed' : ''; ?>">
                3. 站点配置
            </div>
            <div class="step-item <?php echo $step >= 999 ? 'active' : ''; ?>">
                4. 完成
            </div>
            <?php if ($step === 999): ?>
            <div class="step-item <?php echo $step === 4 ? 'active' : ''; ?>">
                <a href="?step=4" style="color: #667eea; text-decoration: none;">数据库管理</a>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="install-body">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['table_results']) && $step === 3): ?>
                <div class="alert alert-success">
                    <strong>数据库表创建结果：</strong>
                    <ul style="margin-top: 10px; padding-left: 20px;">
                        <?php foreach ($_SESSION['table_results'] as $result): ?>
                            <li>
                                <?php echo htmlspecialchars($result['name']); ?>: 
                                <?php if ($result['status'] === 'success'): ?>
                                    <span style="color: #28a745;">✓ <?php echo htmlspecialchars($result['message']); ?></span>
                                <?php else: ?>
                                    <span style="color: #dc3545;">✗ <?php echo htmlspecialchars($result['message']); ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($step === 4): ?>
                <h2 style="margin-bottom: 25px;">数据库表管理</h2>
                <p style="margin-bottom: 20px; color: #666;">管理数据库表的创建和修复：</p>
                
                <?php if ($connectionSuccess && !empty($tableResults)): ?>
                    <div class="alert alert-success">
                        <strong>操作结果：</strong>
                        <ul style="margin-top: 10px; padding-left: 20px;">
                            <?php foreach ($tableResults as $result): ?>
                                <li>
                                    <?php echo htmlspecialchars($result['name']); ?>: 
                                    <?php if ($result['status'] === 'success'): ?>
                                        <span style="color: #28a745;">✓ <?php echo htmlspecialchars($result['message']); ?></span>
                                    <?php else: ?>
                                        <span style="color: #dc3545;">✗ <?php echo htmlspecialchars($result['message']); ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="post">
                    <input type="hidden" name="action" value="recreate">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>数据库主机</label>
                            <input type="text" name="db_host" value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>端口</label>
                            <input type="text" name="db_port" value="<?php echo htmlspecialchars($_POST['db_port'] ?? '3306'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>数据库用户名</label>
                        <input type="text" name="db_user" value="<?php echo htmlspecialchars($_POST['db_user'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>数据库密码</label>
                        <input type="password" name="db_pass" value="<?php echo htmlspecialchars($_POST['db_pass'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>数据库名称</label>
                        <input type="text" name="db_name" value="<?php echo htmlspecialchars($_POST['db_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>表前缀</label>
                        <input type="text" name="db_prefix" value="<?php echo htmlspecialchars($_POST['db_prefix'] ?? 'map_'); ?>">
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" class="btn" style="flex: 1;">
                            重建数据表
                        </button>
                        <a href="?step=1" class="btn" style="flex: 1; text-align: center; text-decoration: none; background: #6c757d;">
                            重新安装
                        </a>
                    </div>
                </form>
                
            <?php elseif ($step === 1): ?>
                <h2 style="margin-bottom: 25px;">环境检查</h2>
                <p style="margin-bottom: 20px; color: #666;">请确保您的服务器满足以下要求：</p>
                
                <form method="post">
                    <ul class="check-list">
                        <li>
                            <span>PHP 版本 >= 7.4.0</span>
                            <span class="status <?php echo version_compare(PHP_VERSION, '7.4.0', '>=') ? 'pass' : 'fail'; ?>">
                                <?php echo PHP_VERSION; ?> 
                                <?php echo version_compare(PHP_VERSION, '7.4.0', '>=') ? '✓' : '✗'; ?>
                            </span>
                        </li>
                        <li>
                            <span>PDO 扩展</span>
                            <span class="status <?php echo extension_loaded('pdo') ? 'pass' : 'fail'; ?>">
                                <?php echo extension_loaded('pdo') ? '✓ 已启用' : '✗ 未启用'; ?>
                            </span>
                        </li>
                        <li>
                            <span>PDO MySQL 扩展</span>
                            <span class="status <?php echo extension_loaded('pdo_mysql') ? 'pass' : 'fail'; ?>">
                                <?php echo extension_loaded('pdo_mysql') ? '✓ 已启用' : '✗ 未启用'; ?>
                            </span>
                        </li>
                        <li>
                            <span>MBString 扩展</span>
                            <span class="status <?php echo extension_loaded('mbstring') ? 'pass' : 'fail'; ?>">
                                <?php echo extension_loaded('mbstring') ? '✓ 已启用' : '✗ 未启用'; ?>
                            </span>
                        </li>
                        <li>
                            <span>JSON 扩展</span>
                            <span class="status <?php echo extension_loaded('json') ? 'pass' : 'fail'; ?>">
                                <?php echo extension_loaded('json') ? '✓ 已启用' : '✗ 未启用'; ?>
                            </span>
                        </li>
                        <li>
                            <span>GD 扩展（图片处理）</span>
                            <span class="status <?php echo function_exists('imagecreatetruecolor') ? 'pass' : 'fail'; ?>">
                                <?php echo function_exists('imagecreatetruecolor') ? '✓ 已启用' : '✗ 未启用'; ?>
                            </span>
                        </li>
                        <li>
                            <span>img 目录可写</span>
                            <span class="status <?php echo is_writable(__DIR__ . '/../img') ? 'pass' : 'fail'; ?>">
                                <?php echo is_writable(__DIR__ . '/../img') ? '✓ 可写' : '✗ 不可写'; ?>
                            </span>
                        </li>
                        <li>
                            <span>根目录可写</span>
                            <span class="status <?php echo is_writable(__DIR__ . '/..') ? 'pass' : 'fail'; ?>">
                                <?php echo is_writable(__DIR__ . '/..') ? '✓ 可写' : '✗ 不可写'; ?>
                            </span>
                        </li>
                    </ul>
                    
                    <button type="submit" class="btn btn-block" style="margin-top: 30px;">
                        检查并继续 →
                    </button>
                </form>
                
            <?php elseif ($step === 2): ?>
                <h2 style="margin-bottom: 25px;">数据库配置</h2>
                <p style="margin-bottom: 20px; color: #666;">请填写您的MySQL数据库连接信息：</p>
                
                <form method="post">
                    <div class="form-row">
                        <div class="form-group">
                            <label>数据库主机</label>
                            <input type="text" name="db_host" value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>端口</label>
                            <input type="text" name="db_port" value="<?php echo htmlspecialchars($_POST['db_port'] ?? '3306'); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>数据库用户名</label>
                        <input type="text" name="db_user" value="<?php echo htmlspecialchars($_POST['db_user'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>数据库密码</label>
                        <input type="password" name="db_pass" value="<?php echo htmlspecialchars($_POST['db_pass'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>数据库名称</label>
                        <input type="text" name="db_name" value="<?php echo htmlspecialchars($_POST['db_name'] ?? ''); ?>" placeholder="请输入数据库名称" required>
                        <div class="hint">请确保数据库已存在</div>
                    </div>
                    
                    <div class="form-group">
                        <label>表前缀（可选）</label>
                        <input type="text" name="db_prefix" value="<?php echo htmlspecialchars($_POST['db_prefix'] ?? 'map_'); ?>">
                        <div class="hint">用于区分不同安装的数据表</div>
                    </div>
                    
                    <button type="submit" class="btn btn-block" style="margin-top: 20px;">
                        测试连接并继续 →
                    </button>
                </form>
                
            <?php elseif ($step === 3): ?>
                <h2 style="margin-bottom: 25px;">站点配置</h2>
                <p style="margin-bottom: 20px; color: #666;">请配置您的网站基本信息：</p>
                
                <form method="post">
                    <div class="form-group">
                        <label>网站名称</label>
                        <input type="text" name="site_name" value="<?php echo htmlspecialchars($_POST['site_name'] ?? '电力杆塔标记系统'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>网站域名</label>
                        <input type="text" name="site_url" value="<?php echo htmlspecialchars($_POST['site_url'] ?? ''); ?>" placeholder="例如: XX.ccxk.eu 或 https://XX.ccxk.eu">
                        <div class="hint">填写您的域名，用于图片等资源的URL生成</div>
                    </div>
                    
                    <div class="form-group">
                        <label>默认搜索关键词（选填）</label>
                        <input type="text" name="default_search" value="<?php echo htmlspecialchars($_POST['default_search'] ?? ''); ?>" placeholder="如：XX线路">
                        <div class="hint">页面加载时默认搜索的线路/杆塔名称，留空则不自动搜索</div>
                    </div>
                    
                    <h3 style="margin: 30px 0 20px; color: #333;">地图API配置</h3>
                    
                    <div class="form-group">
                        <label>天地图Token *</label>
                        <input type="text" name="tianditu_tk" value="<?php echo htmlspecialchars($_POST['tianditu_tk'] ?? ''); ?>" placeholder="请输入天地图API密钥" required>
                        <div class="hint">天地图API密钥，必填项，可在 <a href="https://www.tianditu.gov.cn/" target="_blank">天地图官网</a> 申请</div>
                    </div>
                    
                    <h3 style="margin: 30px 0 20px; color: #333;">地图默认配置</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>默认经度</label>
                            <input type="number" name="map_center_lng" value="<?php echo htmlspecialchars($_POST['map_center_lng'] ?? '119.28188'); ?>" step="any">
                        </div>
                        <div class="form-group">
                            <label>默认纬度</label>
                            <input type="number" name="map_center_lat" value="<?php echo htmlspecialchars($_POST['map_center_lat'] ?? '26.64158'); ?>" step="any">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>默认缩放级别</label>
                        <input type="number" name="map_zoom" value="<?php echo htmlspecialchars($_POST['map_zoom'] ?? '18'); ?>" min="1" max="18">
                        <div class="hint">范围 1-18，数值越大地图越详细</div>
                    </div>
                    
                    <button type="submit" class="btn btn-block" style="margin-top: 20px;">
                        完成安装 ✓
                    </button>
                </form>
                
            <?php elseif ($step === 999): ?>
                <div class="success-icon">🎉</div>
                <h2 style="text-align: center; margin-bottom: 20px; color: #28a745;">安装成功！</h2>
                <p style="text-align: center; margin-bottom: 30px; color: #666;">
                    电力杆塔标记系统已安装完成，您可以开始使用了。
                </p>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                    <h4 style="margin-bottom: 15px;">后续操作：</h4>
                    <ul style="padding-left: 20px; line-height: 2;">
                        <li>确保 img 目录有写入权限</li>
                        <li>如需HTTPS，请配置SSL证书</li>
                        <li>建议修改数据库密码加强安全</li>
                        <li>如天地图Token失效，请重新申请并修改 config.php 中的 tianditu.tk</li>
                    </ul>
                </div>
                
                <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                    <a href="../index.php" class="btn btn-block" style="text-align: center; text-decoration: none; flex: 1;">
                        访问网站 →
                    </a>
                    <a href="?step=4" class="btn btn-block" style="text-align: center; text-decoration: none; flex: 1; background: #17a2b8;">
                        数据库管理
                    </a>
                </div>
                
                <p style="text-align: center; margin-top: 20px; color: #999; font-size: 12px;">
                    如需重新安装，请删除 config.php 文件
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
