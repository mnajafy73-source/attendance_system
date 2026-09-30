import pypxlib
import mysql.connector
import os
import re
import sys

try:
    sys.stdout.reconfigure(encoding='utf-8')
except:
    pass

FOLDER = r"D:\RIC\New folder\hozor-jadid"
PERSONNEL_TXT = "list personal.txt"

db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'hozozmohsen'
}


def read_attendance_from_paradox(file_name):
    path = os.path.join(FOLDER, file_name)
    if not os.path.exists(path):
        print("[!] File " + file_name + " not found.")
        return None, []

    table = pypxlib.Table(path)
    columns = list(table.fields.keys())

    date_col_index = None
    for i, c in enumerate(columns):
        if c == 'Prc_Date':
            date_col_index = i
            break

    data = []
    skipped = 0
    for record in table:
        row = []
        for col in columns:
            val = record[col]
            if isinstance(val, bytes):
                try:
                    val = val.decode('cp1256')
                except:
                    val = val.decode('utf-8', errors='ignore')
            row.append(val)

        if date_col_index is not None:
            date_val = str(row[date_col_index])
            if date_val.endswith('/00'):
                skipped += 1
                continue

        data.append(row)

    table.close()

    if skipped > 0:
        print("[i] " + str(skipped) + " summary rows (day 00) skipped.")

    return columns, data


def parse_personnel_line(line):
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

        if not re.match(r'^\d+$', pcode):
            return None

        return pcode, name, dept, work_group
    except Exception:
        return None


def sync_personnel(cursor):
    txt_path = os.path.join(FOLDER, PERSONNEL_TXT)
    if not os.path.exists(txt_path):
        print("[!] File " + PERSONNEL_TXT + " not found. Skipping personnel.")
        return

    print("[>] Reading personnel from " + PERSONNEL_TXT + "...")

    cursor.execute("SHOW COLUMNS FROM personnel LIKE 'RuleID'")
    if not cursor.fetchone():
        cursor.execute("ALTER TABLE personnel ADD COLUMN RuleID INT DEFAULT NULL")
        print("  [+] Column RuleID added.")

    cursor.execute("SHOW COLUMNS FROM personnel LIKE 'WorkGroupID'")
    if not cursor.fetchone():
        cursor.execute("ALTER TABLE personnel ADD COLUMN WorkGroupID INT DEFAULT NULL")
        print("  [+] Column WorkGroupID added.")

    inserted = 0
    updated = 0
    skipped = 0

    with open(txt_path, 'r', encoding='cp1256') as f:
        for line in f:
            result = parse_personnel_line(line)
            if not result:
                skipped += 1
                continue

            pcode, name, dept, work_group = result

            cursor.execute("SELECT PCode FROM personnel WHERE PCode = %s", (pcode,))
            exists = cursor.fetchone()

            if exists:
                cursor.execute(
                    "UPDATE personnel SET Name=%s, Dept=%s, WorkGroup=%s WHERE PCode=%s",
                    (name, dept, work_group, pcode)
                )
                updated += 1
            else:
                cursor.execute(
                    "INSERT INTO personnel (PCode, Name, Dept, WorkGroup, RuleID, WorkGroupID) VALUES (%s, %s, %s, %s, NULL, NULL)",
                    (pcode, name, dept, work_group)
                )
                inserted += 1

    print("  [OK] " + str(inserted) + " new personnel added.")
    print("  [OK] " + str(updated) + " personnel updated (rules and work groups preserved).")
    if skipped > 0:
        print("  [i] " + str(skipped) + " invalid rows skipped.")


def sync_attendance(cursor, file_name):
    print("[>] Reading attendance from " + file_name + "...")
    columns, data = read_attendance_from_paradox(file_name)

    if not columns:
        print("  [!] No attendance data found.")
        return

    cursor.execute("DROP TABLE IF EXISTS attendance")
    cols_sql = ", ".join(["`" + c + "` TEXT" for c in columns])
    cursor.execute("CREATE TABLE attendance (" + cols_sql + ")")

    if data:
        placeholders = ", ".join(["%s"] * len(columns))
        insert_sql = "INSERT INTO attendance VALUES (" + placeholders + ")"
        cursor.executemany(insert_sql, data)
        print("  [OK] " + str(len(data)) + " attendance records saved.")


def main():
    print("=" * 55)
    print("Starting data synchronization")
    print("=" * 55)

    try:
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor(buffered=True)
        print("[OK] Connected to MySQL.\n")
    except Exception as e:
        print("[X] MySQL connection error: " + str(e))
        return

    sync_personnel(cursor)

    print()
    sync_attendance(cursor, "p140506.DB")

    conn.commit()
    cursor.close()
    conn.close()

    print("\n" + "=" * 55)
    print("Synchronization completed successfully.")
    print("=" * 55)


if __name__ == "__main__":
    main()