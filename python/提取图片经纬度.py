from PIL import Image
from PIL.ExifTags import TAGS, GPSTAGS
import os

def get_exif_data(image_path):
    """提取图片的EXIF元数据"""
    try:
        image = Image.open(image_path)
        exif_data = image._getexif()
        if not exif_data:
            return None
        
        exif = {}
        for tag_id, value in exif_data.items():
            tag = TAGS.get(tag_id, tag_id)
            if tag == 'GPSInfo':
                gps_data = {}
                for gps_tag_id, gps_value in value.items():
                    gps_tag = GPSTAGS.get(gps_tag_id, gps_tag_id)
                    gps_data[gps_tag] = gps_value
                exif[tag] = gps_data
            else:
                exif[tag] = value
        return exif
    except Exception as e:
        print(f"读取{image_path}的EXIF失败：{str(e)}")
        return None

def convert_to_degrees(value):
    """
    辅助函数：将EXIF中的分数元组转换为十进制度数
    :param value: EXIF中的GPS坐标值（元组格式：(分子, 分母) 或 单个数字）
    :return: 十进制浮点数
    """
    if isinstance(value, tuple):
        # 如果是元组，执行 分子/分母
        return float(value[0]) / float(value[1])
    return float(value)  # 兼容直接是数字的情况

def convert_gps_to_decimal(gps_info):
    """将GPS的度分秒(DMS)格式转换为十进制经纬度（修复分数元组问题）"""
    try:
        # 1. 提取度分秒（先通过辅助函数转为浮点数）
        lat_deg = convert_to_degrees(gps_info['GPSLatitude'][0])
        lat_min = convert_to_degrees(gps_info['GPSLatitude'][1])
        lat_sec = convert_to_degrees(gps_info['GPSLatitude'][2])
        lat_ref = gps_info['GPSLatitudeRef']
        
        lon_deg = convert_to_degrees(gps_info['GPSLongitude'][0])
        lon_min = convert_to_degrees(gps_info['GPSLongitude'][1])
        lon_sec = convert_to_degrees(gps_info['GPSLongitude'][2])
        lon_ref = gps_info['GPSLongitudeRef']
        
        # 2. 度分秒转十进制公式：度 + 分/60 + 秒/3600
        latitude = lat_deg + lat_min / 60 + lat_sec / 3600
        if lat_ref == 'S':
            latitude = -latitude
        
        longitude = lon_deg + lon_min / 60 + lon_sec / 3600
        if lon_ref == 'W':
            longitude = -longitude
        
        return round(latitude, 6), round(longitude, 6)
    except KeyError:
        return None, None
    except Exception as e:
        print(f"转换经纬度失败：{str(e)}")
        return None, None

def extract_gps_to_txt(image_paths, output_txt="gps_result.txt"):
    """提取图片经纬度并写入文本文件"""
    with open(output_txt, 'w', encoding='utf-8') as f:
        f.write("图片名,纬度,经度\n")
    
    for img_path in image_paths:
        if not os.path.exists(img_path):
            print(f"文件不存在：{img_path}")
            continue
        
        img_name = os.path.basename(img_path)
        exif_data = get_exif_data(img_path)
        
        if not exif_data or 'GPSInfo' not in exif_data:
            result = f"{img_name},无GPS信息,无GPS信息"
        else:
            lat, lon = convert_gps_to_decimal(exif_data['GPSInfo'])
            if lat and lon:
                result = f"{img_name},{lat},{lon}"
            else:
                result = f"{img_name},GPS信息不完整,GPS信息不完整"
        
        with open(output_txt, 'a', encoding='utf-8') as f:
            f.write(result + "\n")
        print(f"处理完成：{img_name} -> {result}")

if __name__ == "__main__":
    # 配置参数（示例：处理文件夹下所有图片）
    img_folder = "./images"  # 替换为你的图片文件夹路径
    image_paths = [
        os.path.join(img_folder, fname)
        for fname in os.listdir(img_folder)
        if fname.lower().endswith(('.jpg', '.jpeg', '.png', '.tiff', '.bmp'))
    ]
    
    output_txt = "gps_result.txt"
    extract_gps_to_txt(image_paths, output_txt)
    print(f"\n所有图片处理完成！结果已写入：{output_txt}")
