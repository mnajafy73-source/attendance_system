import os

FOLDER = r"D:\RIC\New folder\hozor-jadid"
file_path = os.path.join(FOLDER, "Person.Txt")

if os.path.exists(file_path):
    print("فایل پیدا شد. محتویات:")
    with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
        for i, line in enumerate(f):
            if i >= 10:  # فقط ۱۰ خط اول رو نشون بده
                break
            print(line.strip())
else:
    print("فایل Person.Txt پیدا نشد.")