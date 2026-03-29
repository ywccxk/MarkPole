import os
from PIL import Image

# 配置参数
TARGET_WIDTH = 2000
TARGET_DIR = "thumb"
QUALITY = 95  # 高质量压缩

# 获取当前目录
current_dir = r"C:\Users\Administrator\Desktop\img"
thumb_dir = os.path.join(current_dir, TARGET_DIR)

# 创建thumb文件夹
if not os.path.exists(thumb_dir):
    os.makedirs(thumb_dir)
    print(f"已创建文件夹: {thumb_dir}")

# 获取所有图片文件
image_extensions = ('.jpg', '.jpeg', '.JPG', '.JPEG')
image_files = [f for f in os.listdir(current_dir) if f.endswith(image_extensions)]

print(f"找到 {len(image_files)} 张图片，开始压缩...\n")

for filename in image_files:
    img_path = os.path.join(current_dir, filename)
    
    try:
        with Image.open(img_path) as img:
            # 获取原始尺寸
            original_width, original_height = img.size
            
            # 计算新的高度（保持宽高比）
            new_height = int(TARGET_WIDTH * original_height / original_width)
            
            # 调整大小
            resized_img = img.resize((TARGET_WIDTH, new_height), Image.Resampling.LANCZOS)
            
            # 保存到thumb文件夹
            output_path = os.path.join(thumb_dir, filename)
            resized_img.save(output_path, 'JPEG', quality=QUALITY, optimize=True)
            
            original_size = os.path.getsize(img_path) / 1024  # KB
            new_size = os.path.getsize(output_path) / 1024    # KB
            ratio = (1 - new_size / original_size) * 100
            
            print(f"✓ {filename}: {original_width}x{original_height} → {TARGET_WIDTH}x{new_height}, "
                  f"{original_size:.1f}KB → {new_size:.1f}KB (压缩 {ratio:.1f}%)")
            
    except Exception as e:
        print(f"✗ {filename}: 处理失败 - {e}")

print(f"\n完成！压缩后的图片保存在: {thumb_dir}")
