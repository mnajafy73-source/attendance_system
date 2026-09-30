import mysql.connector
import os

# مسیر فایل متنی
FILE_PATH = r"D:\RIC\New folder\hozor-jadid\list personal.txt"

# تنظیمات دیتابیس
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'hozozmohsen'
}

def parse_line(line):
    line = line.strip()
    if not line or 'گزارش' in line or 'بخش:' in line or 'شماره' in line or 'تاریخ' in line or 'صفحه' in line or 'گروه کاری' in line:
        return None
    
    parts = line.split()
    if len(parts) < 5:
        return None
        
    try:
        work_group = parts[0]
        dept = parts[1]
        name = " ".join(parts[2:-2])
        pcode = parts[-2]
        return pcode, name, dept, work_group
    except Exception:
        return None

def main():
    print("شروع وارد کردن اطلاعات پرسنل...")
    
    if not os.path.exists(FILE_PATH):
        print(f"❌ فایل پیدا نشد: {FILE_PATH}")
        return

    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        print("اتصال به MySQL برقرار شد.")
    except Exception as e:
        print(f"❌ خطا در اتصال به MySQL: {e}")
        return
    
    # پاک کردن جدول قدیمی و ساخت جدول جدید
    print("پاک کردن جدول قدیمی personnel...")
    cursor.execute("DROP TABLE IF EXISTS personnel")
    
    print("ساخت جدول جدید personnel...")
    cursor.execute("""
        CREATE TABLE personnel (
            PCode VARCHAR(50),
            Name VARCHAR(255),
            Dept VARCHAR(100),
            WorkGroup VARCHAR(100)
        )
    """)
    
    try:
        with open(FILE_PATH, 'r', encoding='cp1256') as f:
            count = 0
            for line in f:
                result = parse_line(line)
                if result:
                    pcode, name, dept, work_group = result
                    cursor.execute(
                        "INSERT INTO personnel (PCode, Name, Dept, WorkGroup) VALUES (%s, %s, %s, %s)",
                        (pcode, name, dept, work_group)
                    )
                    count += 1
            
        conn.commit()
        print(f"✅ {count} رکورد پرسنل با موفقیت وارد شد.")
        
    except Exception as e:
        print(f"❌ خطا در خواندن فایل: {e}")
        
    finally:
        cursor.close()
        conn.close()
        print("عملیات به پایان رسید.")

if __name__ == "__main__":
    main()