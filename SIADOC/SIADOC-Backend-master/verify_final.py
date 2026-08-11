import psycopg2

DB = {
    "dbname": "drh_militaire",
    "user": "postgres",
    "password": "postgres",
    "host": "localhost",
    "port": "5432"
}

def verify():
    conn = psycopg2.connect(**DB)
    cur = conn.cursor()
    
    print("--- Vérification finale ---")
    
    # Vérifier AC/GN -> CFS -> CECIG
    cur.execute("""
        SELECT u1.nom as parent, u2.nom as child
        FROM unite_organisationnelle u1
        JOIN unite_organisationnelle u2 ON u2.parent_id = u1.id
        WHERE u1.nom IN ('AC/GN', 'CFS', 'CECIG')
    """)
    links = cur.fetchall()
    for p, c in links:
        print(f"Lien : {p} -> {c}")
        
    print("\nExemples de renommages globaux :")
    cur.execute("SELECT nom FROM unite_organisationnelle WHERE nom LIKE 'POSTE %' LIMIT 3")
    for r in cur.fetchall(): print(f" - {r[0]}")
    
    cur.execute("SELECT nom FROM unite_organisationnelle WHERE nom LIKE 'BRIGADE %' LIMIT 3")
    for r in cur.fetchall(): print(f" - {r[0]}")

    cur.close()
    conn.close()

if __name__ == "__main__":
    verify()
