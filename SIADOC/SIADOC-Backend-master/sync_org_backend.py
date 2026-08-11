import psycopg2
import json
import re
import uuid
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

TS_FILE = r"c:\Users\HP\Documents\20 mai\SIADOC-frontend\src\app\features\administration\drh-structure-nav\hierarchy-data.ts"

def extract_json_from_ts(content, const_name):
    pattern = rf"export const {const_name} = (\[[\s\S]*?\]);"
    match = re.search(pattern, content)
    if match:
        json_str = match.group(1)
        json_str = re.sub(r'//.*', '', json_str)
        return json.loads(json_str)
    return []

def purge_recursive(cur, unit_id):
    # Trouver tous les enfants directs en DB
    cur.execute("SELECT id FROM unite_organisationnelle WHERE parent_id = %s", (unit_id,))
    children = [r[0] for r in cur.fetchall()]
    for cid in children:
        purge_recursive(cur, cid)
    
    # Détacher les liens métiers
    cur.execute("UPDATE dossier_administratif SET unite_organisationnelle_id = NULL WHERE unite_organisationnelle_id = %s", (unit_id,))
    cur.execute("UPDATE utilisateur SET unite_organisationnelle_id = NULL WHERE unite_organisationnelle_id = %s", (unit_id,))
    
    # Supprimer l'unité
    cur.execute("DELETE FROM unite_organisationnelle WHERE id = %s", (unit_id,))

def sync():
    try:
        conn = psycopg2.connect(**DB)
        cur = conn.cursor()
        
        with open(TS_FILE, 'r', encoding='utf-8') as f:
            ts_content = f.read()

        all_data = []
        for const in ["GENDARMERIE_DATA", "CNSP_DATA", "BIR_DATA", "GP_DATA", "FS_DATA", "RMIA_DATA", "ADMINISTRATION_CENTRALE_DATA"]:
            data = extract_json_from_ts(ts_content, const)
            org_type = "AC"
            if const in ["BIR_DATA", "FS_DATA", "GP_DATA", "CNSP_DATA"]: org_type = "FS"
            elif const == "RMIA_DATA": org_type = "CT"
            for item in data: item['_type'] = org_type
            all_data.extend(data)

        # On garde trace des unités traitées pour ne pas les purger
        processed_ids = set()

        def sync_node(node, parent_id=None):
            nom = node.get('label')
            desc = node.get('description', '')
            icon = node.get('icon', '')
            type_u = node.get('_type', 'AC')

            # Recherche intelligente pour éviter les cycles
            # 1. Priorité au même nom + même parent
            if parent_id:
                cur.execute("SELECT id FROM unite_organisationnelle WHERE nom = %s AND parent_id = %s", (nom, parent_id))
            else:
                cur.execute("SELECT id FROM unite_organisationnelle WHERE nom = %s AND parent_id IS NULL", (nom,))
            
            res = cur.fetchone()
            if res:
                target_id = res[0]
            else:
                # 2. Sinon, prendre n'importe lequel avec ce nom qui n'est pas encore "pris"
                cur.execute("SELECT id FROM unite_organisationnelle WHERE nom = %s", (nom,))
                others = [r[0] for r in cur.fetchall() if r[0] not in processed_ids]
                if others:
                    target_id = others[0]
                else:
                    # 3. Créer si vraiment nouveau
                    target_id = str(uuid.uuid4())
                    cur.execute("INSERT INTO unite_organisationnelle (id, nom, type) VALUES (%s, %s, %s)", (target_id, nom, type_u))

            processed_ids.add(target_id)
            
            # Mise à jour des infos
            cur.execute("""
                UPDATE unite_organisationnelle 
                SET nom = %s, description = %s, icon = %s, parent_id = %s, type = %s 
                WHERE id = %s
            """, (nom, desc, icon, parent_id, type_u, target_id))

            # Enfants
            frontend_children = node.get('children', [])
            frontend_child_names = [c.get('label') for c in frontend_children]
            
            # Lister les enfants en DB pour ce parent
            cur.execute("SELECT id, nom FROM unite_organisationnelle WHERE parent_id = %s", (target_id,))
            db_children = cur.fetchall()
            
            for db_cid, db_cnom in db_children:
                # Si l'unité en DB n'est plus dans le front pour ce parent -> Purge
                if db_cnom not in frontend_child_names:
                    print(f"  Purge : {db_cnom} (sous {nom})")
                    purge_recursive(cur, db_cid)
            
            for child in frontend_children:
                child['_type'] = type_u
                sync_node(child, target_id)

        print("Synchronisation sécurisée...")
        for root in all_data:
            sync_node(root)

        conn.commit()
        print("✅ Backend synchronisé avec succès !")
        
    except Exception as e:
        if 'conn' in locals(): conn.rollback()
        print(f"❌ Erreur : {e}")
        import traceback
        traceback.print_exc()
    finally:
        if 'cur' in locals(): cur.close()
        if 'conn' in locals(): conn.close()

if __name__ == "__main__":
    sync()
