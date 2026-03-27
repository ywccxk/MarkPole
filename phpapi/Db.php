<?php
/**
 * 数据库操作类
 * 使用配置文件中的数据库连接信息
 */

class Db {
    private static $instance = null;
    private $pdo = null;
    private $config = null;
    
    private function __construct() {
        $this->loadConfig();
        $this->connect();
    }
    
    /**
     * 获取单例实例
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 加载配置文件
     */
    private function loadConfig() {
        $configFile = __DIR__ . '/../config.php';
        
        if (!file_exists($configFile)) {
            // 如果配置文件不存在，尝试重定向到安装程序
            if (php_sapi_name() !== 'cli') {
                header('Location: ./install/index.php');
                exit;
            }
            throw new Exception('配置文件不存在，请先运行安装程序');
        }
        
        $this->config = require $configFile;
        
        if (!isset($this->config['db'])) {
            throw new Exception('配置文件格式错误');
        }
    }
    
    /**
     * 连接数据库
     */
    private function connect() {
        $db = $this->config['db'];
        
        $dsn = sprintf(
            "mysql:host=%s;port=%s;dbname=%s;charset=%s",
            $db['host'],
            $db['port'],
            $db['name'],
            $db['charset'] ?? 'utf8mb4'
        );
        
        try {
            $this->pdo = new PDO($dsn, $db['user'], $db['pass']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            error_log('数据库连接失败: ' . $e->getMessage());
            throw new Exception('数据库连接失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取PDO对象
     */
    public function getPdo() {
        return $this->pdo;
    }
    
    /**
     * 获取表前缀
     */
    public function getPrefix() {
        return $this->config['db']['prefix'] ?? 'map_';
    }
    
    /**
     * 获取站点配置
     */
    public function getSiteConfig() {
        return $this->config['site'] ?? [];
    }
    
    /**
     * 获取地图配置
     */
    public function getMapConfig() {
        return $this->config['map'] ?? [
            'center_lng' => 119.28188,
            'center_lat' => 26.64158,
            'zoom' => 18
        ];
    }
    
    /**
     * 获取图片URL基础路径
     */
    public function getImgBaseUrl() {
        $site = $this->getSiteConfig();
        if (!empty($site['url'])) {
            return $site['url'];
        }
        // 如果未配置域名，使用相对路径
        return '';
    }
    
    /**
     * 获取天地图Token
     */
    public function getTiandituTk() {
        return $this->config['tianditu']['tk'] ?? '';
    }
    
    /**
     * 执行查询并返回所有结果
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('查询失败: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 执行查询并返回单条结果
     */
    public function queryOne($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('查询失败: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 执行插入/更新/删除操作
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log('执行失败: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 获取最后插入的ID
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * 关闭连接
     */
    public function close() {
        $this->pdo = null;
    }
    
    // 禁止克隆
    private function __clone() {}
    
    // 禁止反序列化
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
