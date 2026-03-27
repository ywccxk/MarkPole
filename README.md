# 电力杆塔标记系统

基于天地图API开发的电力杆塔位置标记与管理系统，支持在地图上标注杆塔位置、查看设备信息、显示线路走向等功能。
演示地址（非长期开放，有好想法的自觉提交分支）：https://markpole.ccxk.eu/
## 功能特点

- **地图标注**：在地图上标记杆塔位置，支持点击添加新标记点
- **杆塔搜索**：通过杆号搜索，快速定位目标杆塔
- **设备筛选**：一键筛选显示带有刀闸或开关的设备
- **线路显示**：根据上级杆关系自动绘制线路走向，裸导显示红色
- **图片上传**：支持上传杆塔照片，前端自动压缩处理
- **高德导航**：点击即可调用高德地图APP导航至目标杆塔
- **信息管理**：完整的杆塔信息管理（杆号、上级杆、刀闸、开关、裸导、经纬度、地址、备注）

## 技术栈

- **前端**：HTML + CSS + JavaScript
- **地图API**：天地图（Tianditu）
- **后端**：PHP + MySQL
- **图片处理**：前端Canvas压缩

## 目录结构

```
/
├── index.php              # 主入口页面
├── config.php             # 配置文件（安装后生成）
├── .htaccess              # Apache配置
├── .user.ini              # PHP配置
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
│   └── myAction.php       # 数据库操作类（备用）
├── install/
│   └── index.php          # 安装向导
└── img/                   # 图片存储目录
│   └── thumb/             # 缩略图目录
├── python/             # python脚本目录（获取无人机图片经纬度，上传数据）

```

## 安装部署

### 环境要求

- PHP >= 7.4
- MySQL >= 5.7
- PDO扩展
- mbstring扩展

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

## 常见问题

### 1. 地图不显示

- 检查天地图Token是否正确配置
- 检查网络连接是否正常

### 2. 数据库连接失败

- 确认数据库 credentials 正确
- 确认数据库用户有相应权限
- 检查防火墙设置

### 3. 图片上传失败

- 检查 `img/` 目录权限
- 检查PHP上传大小限制

### 4. 搜索不到数据

- 确认数据库中已有数据
- 检查搜索关键词是否正确

## 页面预览

系统主界面包含：
- 顶部搜索栏（搜索框 + 功能按钮）
- 地图显示区域
- 右侧滑出式表单（添加/编辑杆塔信息）

## 许可证

仅供学习交流使用，不授权商业使用

## 作者

By: 仰望、璀璨星空
