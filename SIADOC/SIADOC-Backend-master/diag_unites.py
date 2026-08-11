import psycopg2
import sys
import io

# Configurer la console pour l'UTF-8
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

DB = {
    "dbname": "drh_militaire",
    "user": "postgres",
    "password": "postgres",
    "host": "localhost",
    "port": "5432"
}

def check_db():
    try:
        conn = psycopg2.connect(**DB)
        cur = conn.cursor()
        
        print("--- Analyse de la table unite_organisationnelle ---")
        
        # Compter le total
        cur.execute("SELECT COUNT(*) FROM unite_organisationnelle")
        count = cur.fetchone()[0]
        print(f"Total unités: {count}")
        
        # Liste des unités racines (parent null)
        cur.execute("SELECT id, nom, type FROM unite_organisationnelle WHERE parent_id IS NULL")
        roots = cur.fetchall()
        print("\nUnités racines:")
        for r in roots:
            print(f" - {r[1]} (Type: {r[2]}, ID: {r[0]})")
            
        # Chercher spécifiquement AC/GN
        cur.execute("SELECT id, nom FROM unite_organisationnelle WHERE nom = 'AC/GN'")
        acgn = cur.fetchone()
        if acgn:
            acgn_id = acgn[0]
            print(f"\nEnfants de AC/GN (ID: {acgn_id}):")
            cur.execute("SELECT nom, type FROM unite_organisationnelle WHERE parent_id = %s", (acgn_id,))
            for child in cur.fetchall():
                print(f"  - {child[0]} ({child[1]})")
        else:
            print("\nAC/GN non trouvé dans la table.")

        cur.close()
        conn.close()
    except Exception as e:
        print(f"Erreur: {e}")

if __name__ == "__main__":
    check_db()
