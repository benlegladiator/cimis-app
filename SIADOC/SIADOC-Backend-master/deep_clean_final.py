import psycopg2

DB = {
    "dbname": "drh_militaire",
    "user": "postgres",
    "password": "postgres",
    "host": "localhost",
    "port": "5432"
}

def deep_clean():
    conn = psycopg2.connect(**DB)
    cur = conn.cursor()
    
    print("--- Nettoyage en profondeur des doublons (Même Nom + Même Parent) ---")
    
    # 1. Identifier les groupes (nom, parent_id) en doublon
    cur.execute("""
        SELECT nom, parent_id, COUNT(*) 
        FROM unite_organisationnelle 
        GROUP BY nom, parent_id 
        HAVING COUNT(*) > 1
    """)
    duplicates = cur.fetchall()
    
    for nom, parent_id, count in duplicates:
        print(f"Traitement de {nom} (Parent: {parent_id}) - {count} exemplaires")
        
        # Récupérer tous les IDs
        if parent_id:
            cur.execute("SELECT id FROM unite_organisationnelle WHERE nom = %s AND parent_id = %s ORDER BY id", (nom, parent_id))
        else:
            cur.execute("SELECT id FROM unite_organisationnelle WHERE nom = %s AND parent_id IS NULL ORDER BY id", (nom,))
            
        ids = [r[0] for r in cur.fetchall()]
        survivor = ids[0]
        to_delete = ids[1:]
        
        for dead_id in to_delete:
            # Re-attacher les enfants du doublon au survivant
            cur.execute("UPDATE unite_organisationnelle SET parent_id = %s WHERE parent_id = %s", (survivor, dead_id))
            # Re-attacher les dossiers
            cur.execute("UPDATE dossier_administratif SET unite_organisationnelle_id = %s WHERE unite_organisationnelle_id = %s", (survivor, dead_id))
            # Re-attacher les utilisateurs
            cur.execute("UPDATE utilisateur SET unite_organisationnelle_id = %s WHERE unite_organisationnelle_id = %s", (survivor, dead_id))
            # Supprimer le doublon
            cur.execute("DELETE FROM unite_organisationnelle WHERE id = %s", (dead_id,))

    conn.commit()
    print("✅ Nettoyage terminé.")
    cur.close()
    conn.close()

if __name__ == "__main__":
    deep_clean()
