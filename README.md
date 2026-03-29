# 电力杆塔标记系统

基于天地图API开发的电力杆塔位置标记与管理系统，支持在地图上标注杆塔位置、查看设备信息、显示线路走向等功能。
演示地址（非长期开放，有好想法的自觉提交分支）：https://markpole.ccxk.eu/

## 功能特点

- **地图标注**：在地图上标记杆塔位置，支持点击添加新标记点
- **杆塔搜索**：通过杆号搜索，快速定位目标杆塔
- **设备筛选**：一键筛选显示带有刀闸或开关的设备
- **线路显示**：根据上级杆关系自动绘制线路走向，裸导线路显示红色
- **图片上传**：支持上传杆塔照片，前端自动压缩处理（宽度限制2000px，USM锐化提升清晰度）
- **图片管理**：自动生成缩略图，支持原图/缩略图智能切换
- **高德导航**：点击即可调用高德地图APP导航至目标杆塔
- **信息管理**：完整的杆塔信息管理（杆号、上级杆、刀闸、开关、裸导、经纬度、地址、备注）
- **实时更新**：表单提交后前端即时更新标记点，无需刷新页面

## 技术栈

- **前端**：HTML + CSS + JavaScript
- **地图API**：天地图（Tianditu WMTS + JavaScript API）
- **后端**：PHP + MySQL
- **图片处理**：前端Canvas压缩 + 后端GD库缩略图生成
- **前端库**：jsQR（二维码识别，待扩展）

## 目录结构

```
/
├── index.php              # 主入口页面
├── config.php             # 配置文件（安装后自动生成）
├── ccxk.ico               # 网站图标
├── README.md              # 说明文档
├── css/
│   └── map.css            # 地图样式
├── js/
│   └── mapapi.js          # 地图交互逻辑
├── phpapi/
│   ├── Db.php             # 数据库操作类
│   ├── index.php          # 搜索API
│   ├── post.php           # 提交/更新API
│   ├── upload.php         # 图片上传API
│   ├── config_api.php     # 配置获取API
│   └── api_proxy.php      # 天地图API代理（隐藏JS API Token）
├── install/
│   └── index.php          # 安装向导
├── img/                   # 图片存储目录
│   └── thumb/             # 缩略图目录
└── python/                # Python工具脚本
    ├── 上传数据.py        # GPS数据批量上传脚本
    ├── 后缀小写.py        # 文件名批量处理脚本
    ├── 图片压缩.py        # 图片压缩脚本
    └── 提取图片经纬度.py  # 无人机图片EXIF信息提取
```

## 安全说明

### Token保护机制

天地图Token由后端代理，不直接体现在html源码。但因天地图API，请求时仍会在Network中体现Token，建议部署后前往天地图APIKEY设置域名白名单：

**API代理保护：**

- **API代理** (`api_proxy.php`) - 加载天地图JavaScript API
  - 工作流程：前端 → `./phpapi/api_proxy.php` → 天地图API
  - 效果：HTML源代码中的 `<script src="...">` 不包含Token

**Token存储位置：**
- Token在PHP安装时存储在服务器端 `config.php` 配置文件中

**说明：**
- 地图瓦片请求（WMTS）直接使用天地图服务，Token会在网络请求中传输
- 开发者工具的Network面板中可能看到Token（浏览器层面的正常行为）

## 安装部署

### 环境要求

- PHP >= 7.4
- MySQL >= 5.7
- PDO扩展
- mbstring扩展
- GD扩展（图片处理，用于生成缩略图）

### 安装步骤

1. **上传文件**

   将项目文件上传到Web服务器目录（如 `/var/www/html/markpole.ccxk.eu`）

2. **设置目录权限**

   确保以下目录可写：
   - `img/` 目录
   - 项目根目录

3. **访问安装向导**

   在浏览器中访问：`http://your-domain/install/`

4. **配置数据库**

   - 填写MySQL数据库连接信息
   - 填写站点名称、域名
   - 填写天地图API Token（必填）

5. **完成安装**

   安装程序会自动创建数据库表和配置文件

### 天地图Token申请

1. 访问 [天地图官网](https://www.tianditu.gov.cn/)
2. 注册账号
3. 进入开发者控制台
4. 创建应用获取Token

## 配置说明

安装完成后，配置信息保存在 `config.php` 中：

```php
return [
    // 数据库配置
    'db' => [
        'host' => 'localhost',
        'port' => '3306',
        'user' => 'your_username',
        'pass' => 'your_password',
        'name' => 'your_database',
        'prefix' => 'map_',
    ],

    // 站点配置
    'site' => [
        'name' => '电力杆塔标记系统',
        'url' => 'https://your-domain.com',
        'default_search' => ''
    ],

    // 地图配置
    'map' => [
        'center_lng' => 119.28188,  // 默认经度
        'center_lat' => 26.64158,  // 默认纬度
        'zoom' => 18               // 默认缩放级别
    ],

    // 天地图配置
    'tianditu' => [
        'tk' => 'your_token'
    ],

    // 上传配置
    'upload' => [
        'max_size' => 10485760,  // 10MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif']
    ]
];
```

## 使用说明

### 地图操作

- **缩放**：鼠标滚轮或使用地图控件
- **平移**：拖拽地图
- **点击地图**：点击空白处打开表单，可添加新杆塔

### 按钮功能

| 按钮 | 功能 |
|------|------|
| 定位 | 获取当前位置并在地图上标记 |
| 设备 | 切换显示带有刀闸/开关的设备 |
| 线路 | 显示/隐藏线路走向 |
| 搜索 | 根据关键词搜索杆塔 |

### 表单字段

| 字段 | 说明 |
|------|------|
| 杆号 | 杆塔唯一标识（必填） |
| 上级杆 | 上级杆塔编号（用于线路关联） |
| 刀闸 | 刀闸编号 |
| 开关 | 开关编号 |
| 裸导 | 裸导线规格 |
| 经度 | GPS经度坐标 |
| 纬度 | GPS纬度坐标 |
| 地址 | 详细地址 |
| 备注 | 备注信息 |

### 图片上传

1. 点击杆塔标记打开信息窗口
2. 点击信息窗口中的字段区域打开表单
3. 点击"选择图片"按钮上传照片
4. 支持JPEG、PNG、GIF格式
5. 前端会自动压缩处理

### 高德导航

在表单中填写经纬度后，点击"高德导航"按钮可打开高德地图APP进行导航。

## API接口

### 搜索杆塔

```
GET /phpapi/index.php?title=关键词
```

### 提交/更新杆塔

```
POST /phpapi/post.php
Content-Type: application/json

{
    "polename": "杆号",
    "upperpole": "上级杆",
    "disconnectswitch": "刀闸",
    "circuitbreaker": "开关",
    "bareconductor": "裸导",
    "longitude": "经度",
    "latitude": "纬度",
    "address": "地址",
    "remark": "备注",
    "imgurl": "图片名.jpg"
}
```

### 上传图片

```
POST /phpapi/upload.php
Content-Type: multipart/form-data

Fields:
- image: 图片文件
- newFileName: 新文件名
```

### 获取配置

```
GET /phpapi/config_api.php
```

## 数据库表结构

系统安装后会自动创建 `map_info_table` 数据表，结构如下：

```sql
CREATE TABLE `map_info_table` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `PoleName` VARCHAR(100) NOT NULL UNIQUE,      -- 杆号（唯一标识）
    `UpperPole` VARCHAR(100) DEFAULT '',            -- 上级杆（线路关联）
    `DisconnectSwitch` VARCHAR(100) DEFAULT '',    -- 刀闸编号
    `CircuitBreaker` VARCHAR(100) DEFAULT '',       -- 开关编号
    `BareConductor` VARCHAR(100) DEFAULT '',        -- 裸导线规格
    `longitude` DECIMAL(10, 6) DEFAULT 0,           -- 经度坐标
    `latitude` DECIMAL(10, 6) DEFAULT 0,            -- 纬度坐标
    `Address` TEXT,                                  -- 详细地址
    `Note` TEXT,                                     -- 备注信息
    `ImgUrl` VARCHAR(255) DEFAULT '',               -- 图片文件名
    `CreateTime` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `UpdateTime` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_polename` (`PoleName`),
    INDEX `idx_upperpole` (`UpperPole`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 常见问题

### 1. 地图不显示

- 检查天地图Token是否正确配置
- 检查网络连接是否正常
- 查看浏览器控制台是否有错误信息

### 2. 数据库连接失败

- 确认数据库 credentials 正确
- 确认数据库用户有相应权限
- 检查防火墙设置
- 确认数据库已创建

### 3. 图片上传失败

- 检查 `img/` 目录权限（需可写）
- 检查PHP上传大小限制（php.ini中upload_max_filesize）
- 确认GD扩展已启用

### 4. 搜索不到数据

- 确认数据库中已有数据
- 检查搜索关键词是否正确
- 尝试模糊搜索（如搜索"01"可匹配"001"、"010"等）

### 5. 线路不显示

- 确认上级杆字段已正确填写
- 检查上下级杆的经纬度是否有效

### 6. 天地图API加载失败

- 检查服务器网络能否访问 tianditu.gov.cn
- 确认Token是否已过期
- 尝试重新申请新的Token

### 7. 图片显示问题

- 确认 img/ 目录权限正确
- 如使用CDN，确认域名配置正确
- 检查浏览器控制台是否有404错误

## 页面预览

系统主界面包含：
- 顶部搜索栏（搜索框 + 功能按钮）
- 地图显示区域
- 右侧滑出式表单（添加/编辑杆塔信息）

## 许可证

仅供学习交流使用，不授权商业使用

## 作者

By: 仰望、璀璨星空