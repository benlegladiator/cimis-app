import psycopg2

DB = {
    "dbname": "drh_militaire",
    "user": "postgres",
    "password": "postgres",
    "host": "localhost",
    "port": "5432"
}

conn = psycopg2.connect(**DB)
cur = conn.cursor()

# Get column names of unite_organisationnelle
cur.execute("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'unite_organisationnelle'")
columns = cur.fetchall()
print("Columns:", [c[0] for c in columns])

# Fix CCS CI BA 301
cur.execute("UPDATE unite_organisationnelle SET nom = 'CCS CI BA 302' WHERE nom ilike '%ba%301%'")
print("Updated CCS CI BA 301 -> 302, rowcount:", cur.rowcount)

has_ordre = 'ordre' in [c[0] for c in columns]

if has_ordre:
    # Get ID of CIE EMAA
    cur.execute("SELECT id FROM unite_organisationnelle WHERE nom ilike 'CIE EMAA'")
    res = cur.fetchone()
    if res:
        cie_id = res[0]
        cur.execute("UPDATE unite_organisationnelle SET ordre = 1 WHERE id = %s", (cie_id,))
        print("Updated ordre of CIE EMAA to 1")
else:
    # If no 'ordre' column, maybe we prepend a zero width space or an explicit space to make it sort first if it's alphabetical
    cur.execute("UPDATE unite_organisationnelle SET nom = ' CIE EMAA' WHERE nom ilike 'CIE EMAA'")
    print("Updated CIE EMAA to ' CIE EMAA' for sorting")

conn.commit()
cur.close()
conn.close()
