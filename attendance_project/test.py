import pypxlib

file_path = r"D:\RIC\New folder\hozor-jadid\p140506.DB"
table = pypxlib.Table(file_path)
print("اسم ستون‌ها:", table.fields)
for i, record in enumerate(table):
    if i >= 3:
        break
    print(record)
table.close()