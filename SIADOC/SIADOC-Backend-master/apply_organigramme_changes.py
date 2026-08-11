import psycopg2
import sys
import uuid

# Fix printing
sys.stdout.reconfigure(encoding='utf-8')

DB = {
    "dbname": "drh_militaire",
    "user": "postgres",
    "password": "postgres",
    "host": "localhost",
    "port": "5432"
}

def get_node(cur, nom, parent_nom=None):
    if parent_nom:
        cur.execute("""
            SELECT u.id, u.nom FROM unite_organisationnelle u 
            JOIN unite_organisationnelle p ON u.parent_id = p.id 
            WHERE u.nom ilike %s AND p.nom ilike %s
            LIMIT 1
        """, ('%'+nom+'%', '%'+parent_nom+'%'))
    else:
        cur.execute("SELECT id, nom FROM unite_organisationnelle WHERE nom ilike %s LIMIT 1", ('%'+nom+'%',))
    res = cur.fetchone()
    return res if res else (None, None)

def rename_node(cur, old_nom, new_nom, parent_nom=None):
    node_id, found_nom = get_node(cur, old_nom, parent_nom)
    if node_id:
        cur.execute("UPDATE unite_organisationnelle SET nom = %s WHERE id = %s", (new_nom, node_id))
        print(f"[OK] Renommé: '{found_nom}' -> '{new_nom}'")
    else:
        print(f"[ERROR] Introuvable pour renommage: '{old_nom}' (parent: {parent_nom})")

def add_node(cur, nom, parent_nom, type_unite='BUREAU'):
    parent_id, found_parent = get_node(cur, parent_nom)
    if not parent_id:
        print(f"[ERROR] Parent introuvable pour ajout: '{parent_nom}'")
        return
    
    # check if already exists
    cur.execute("SELECT id FROM unite_organisationnelle WHERE nom ilike %s AND parent_id = %s", (nom, parent_id))
    if cur.fetchone():
        print(f"[WARN] Existe déjà: '{nom}' sous '{found_parent}'")
        return
    
    new_id = str(uuid.uuid4())
    cur.execute("INSERT INTO unite_organisationnelle (id, nom, parent_id, type) VALUES (%s, %s, %s, %s)", (new_id, nom, parent_id, type_unite))
    print(f"[OK] Ajouté: '{nom}' sous '{found_parent}'")

def main():
    conn = psycopg2.connect(**DB)
    cur = conn.cursor()
    
    # 1. DP/GN -> Bureau GPO, Bureau GPNO
    add_node(cur, "Bureau GPO", "DP/GN", "BUREAU")
    add_node(cur, "Bureau GPNO", "DP/GN", "BUREAU")
    
    # 2. musique -> compagnie musique
    rename_node(cur, "musique", "compagnie musique")
    
    # 3. COMECIIA -> ccs/cifan, ccs/cpfan
    add_node(cur, "ccs/cifan", "COMECIIA", "COMPAGNIE")
    add_node(cur, "ccs/cpfan", "COMECIIA", "COMPAGNIE")
    
    # 4. EMA -> ccs EFOM
    add_node(cur, "ccs EFOM", "EMA", "COMPAGNIE")
    
    # 6. EMAA -> ccs ci ba102 to ccs ci ba201, 301 to 302, CIE EMAA
    rename_node(cur, "ccs ci ba102", "ccs ci ba201", parent_nom="EMAA")
    rename_node(cur, "ccs ci ba301", "ccs ci ba302", parent_nom="EMAA")
    add_node(cur, "CIE EMAA", "EMAA", "COMPAGNIE") # Assuming it doesn't exist yet, or we rename it?
    
    # 7. rmia 3 -> ccs/sm 3 and 5, rmia 4 -> ccs/sm4
    add_node(cur, "ccs/sm 3", "rmia 3", "COMPAGNIE")
    add_node(cur, "ccs/sm 5", "rmia 3", "COMPAGNIE")
    add_node(cur, "ccs/sm4", "rmia 4", "COMPAGNIE")
    
    # 8. rmia1 -> FORFUMAPCO to FORFUMACO. Add 11e and 12e BAFUMAR
    rename_node(cur, "FORFUMAPCO", "FORFUMACO", parent_nom="rmia 1") # Try space or not
    rename_node(cur, "FORFUMAPCO", "FORFUMACO", parent_nom="rmia1")
    
    # Let's do it specifically for rmia1 -> FORFUMACO
    p_id, _ = get_node(cur, "FORFUMACO", "rmia 1")
    if not p_id: p_id, _ = get_node(cur, "FORFUMACO", "rmia1")
    if p_id:
        cur.execute("SELECT id FROM unite_organisationnelle WHERE nom ilike %s AND parent_id = %s", ('11e BAFUMAR', p_id))
        if not cur.fetchone():
            cur.execute("INSERT INTO unite_organisationnelle (id, nom, parent_id, type) VALUES (%s, %s, %s, %s)", (str(uuid.uuid4()), '11e BAFUMAR', p_id, 'BATAILLON'))
            print("[OK] Ajouté 11e BAFUMAR sous RMIA 1 -> FORFUMACO")
        cur.execute("SELECT id FROM unite_organisationnelle WHERE nom ilike %s AND parent_id = %s", ('12e BAFUMAR', p_id))
        if not cur.fetchone():
            cur.execute("INSERT INTO unite_organisationnelle (id, nom, parent_id, type) VALUES (%s, %s, %s, %s)", (str(uuid.uuid4()), '12e BAFUMAR', p_id, 'BATAILLON'))
            print("[OK] Ajouté 12e BAFUMAR sous RMIA 1 -> FORFUMACO")
            
    # 9. rmia4 -> FORFUMACO -> 41e and 42e BAFUMAR
    add_node(cur, "FORFUMACO", "rmia 4", "BRIGADE")
    p_id, _ = get_node(cur, "FORFUMACO", "rmia 4")
    if p_id:
        cur.execute("SELECT id FROM unite_organisationnelle WHERE nom ilike %s AND parent_id = %s", ('41e BAFUMAR', p_id))
        if not cur.fetchone():
            cur.execute("INSERT INTO unite_organisationnelle (id, nom, parent_id, type) VALUES (%s, %s, %s, %s)", (str(uuid.uuid4()), '41e BAFUMAR', p_id, 'BATAILLON'))
        cur.execute("SELECT id FROM unite_organisationnelle WHERE nom ilike %s AND parent_id = %s", ('42e BAFUMAR', p_id))
        if not cur.fetchone():
            cur.execute("INSERT INTO unite_organisationnelle (id, nom, parent_id, type) VALUES (%s, %s, %s, %s)", (str(uuid.uuid4()), '42e BAFUMAR', p_id, 'BATAILLON'))
        print("[OK] Ajouté 41e et 42e BAFUMAR sous RMIA 4 -> FORFUMACO")

    # 10. rmia5 -> FORFUMACO -> 51e BAFUMAR
    add_node(cur, "FORFUMACO", "rmia 5", "BRIGADE")
    p_id, _ = get_node(cur, "FORFUMACO", "rmia 5")
    if p_id:
        cur.execute("SELECT id FROM unite_organisationnelle WHERE nom ilike %s AND parent_id = %s", ('51e BAFUMAR', p_id))
        if not cur.fetchone():
            cur.execute("INSERT INTO unite_organisationnelle (id, nom, parent_id, type) VALUES (%s, %s, %s, %s)", (str(uuid.uuid4()), '51e BAFUMAR', p_id, 'BATAILLON'))
        print("[OK] Ajouté 51e BAFUMAR sous RMIA 5 -> FORFUMACO")
        
    # 11. BTAP -> ccs to cct, add ci btap
    rename_node(cur, "ccs", "cct", parent_nom="BTAP")
    add_node(cur, "ci btap", "BTAP", "COMPAGNIE")
    
    # 12. bbr -> EBR to EB
    rename_node(cur, "EBR", "EB", parent_nom="bbr")
    
    # 13. BCS of BQG -> COM to CCOM
    rename_node(cur, "COM", "CCOM", parent_nom="BCS")
    
    conn.commit()
    print("Mise à jour terminée.")
    cur.close()
    conn.close()

if __name__ == "__main__":
    main()
