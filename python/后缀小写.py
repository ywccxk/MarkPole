import os

def rename_file_extensions_to_lower():
    """
    将【当前文件夹】下所有文件的后缀名改为小写
    例如：FILE.PDF → file.pdf、IMAGE.JPG → image.jpg
    """
    # 直接使用当前文件夹（无需修改路径）
    folder_path = os.getcwd()

    # 遍历当前文件夹所有内容
    for filename in os.listdir(folder_path):
        old_path = os.path.join(folder_path, filename)

        # 只处理文件，跳过文件夹
        if os.path.isfile(old_path):
            # 拆分文件名和后缀
            name, ext = os.path.splitext(filename)

            # 如果有后缀，并且不是小写，就修改
            if ext and ext != ext.lower():
                new_filename = name + ext.lower()
                new_path = os.path.join(folder_path, new_filename)

                # 执行重命名
                os.rename(old_path, new_path)
                print(f"已修改：{filename}  →  {new_filename}")

if __name__ == "__main__":
    rename_file_extensions_to_lower()
    print("\n✅ 当前文件夹所有文件后缀已全部改为小写！")