#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
GPS数据上传脚本
将CSV文件中的杆塔信息上传到数据库
"""

import csv
import json
import urllib.request
import urllib.parse
import ssl

# API地址
API_URL = "https://markpole.ccxk.eu/phpapi/post.php"

# CSV文件路径
CSV_FILE = r"C:\Users\Administrator\Desktop\gps_result.txt"

def upload_data(data):
    """上传单条数据到API"""
    # 构建请求数据
    json_data = json.dumps(data, ensure_ascii=False).encode('utf-8')

    # 创建请求
    req = urllib.request.Request(
        API_URL,
        data=json_data,
        headers={
            'Content-Type': 'application/json',
            'User-Agent': 'Python GPS Uploader'
        }
    )

    # 跳过SSL验证（如果需要）
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE

    try:
        with urllib.request.urlopen(req, context=ctx) as response:
            result = response.read().decode('utf-8')
            return json.loads(result)
    except Exception as e:
        return {"state": "error", "data": str(e)}

def main():
    """主函数"""
    # 读取CSV文件
    with open(CSV_FILE, 'r', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        rows = list(reader)

    print(f"共读取到 {len(rows)} 条数据")

    success_count = 0
    error_count = 0

    for i, row in enumerate(rows, 1):
        # 构建上传数据
        data = {
            "polename": row['杆号'].strip(),
            "upperpole": row['上级电杆'].strip() if row['上级电杆'].strip() else '',
            "imgurl": row['图片名'].strip(),
            "latitude": row['纬度'].strip(),
            "longitude": row['经度'].strip()
        }

        print(f"[{i}/{len(rows)}] 上传: {data['polename']}...", end=" ")

        # 上传数据
        result = upload_data(data)

        if result.get('state') == 'success':
            print(f"✓ 成功 - {result.get('data')}")
            success_count += 1
        else:
            print(f"✗ 失败 - {result.get('data')}")
            error_count += 1

    # 打印统计
    print("\n" + "=" * 50)
    print(f"上传完成！")
    print(f"成功: {success_count} 条")
    print(f"失败: {error_count} 条")

if __name__ == "__main__":
    main()