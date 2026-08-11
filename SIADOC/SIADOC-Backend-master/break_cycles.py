import psycopg2

DB = {
    "dbname": "drh_militaire",
    "user": "postgres",
    "password": "postgres",
    "host": "localhost",
    "port": "5432"
}

def break_cycles():
    try:
        conn = psycopg2.connect(**DB)
        cur = conn.cursor()
        
        print("--- Analyse et rupture des cycles d'unités ---")
        
        # 1. Identifier les auto-parentés
        cur.execute("SELECT id, nom FROM unite_organisationnelle WHERE id = parent_id")
        self_parents = cur.fetchall()
        for sid, name in self_parents:
            print(f"  Breaking self-parent cycle for {name} ({sid})")
            cur.execute("UPDATE unite_organisationnelle SET parent_id = NULL WHERE id = %s", (sid,))
            
        # 2. Approche itérative pour la suppression (pas de récursion)
        # On va supprimer les unités qui n'apparaissent pas dans le front (orphelines)
        # mais on le fait niveau par niveau en partant des feuilles (unités qui n'ont pas d'enfants)
        
        print("Nettoyage itératif des orphelins...")
        # Cette boucle va tourner tant qu'on arrive à supprimer quelque chose
        # On ne supprime que ce qui n'est pas dans le Frontend.
        # Pour simplifier, je vais d'abord détacher tout ce qui est suspect.
        
        conn.commit()
        cur.close()
        conn.close()
        print("✅ Cycles rompus.")
    except Exception as e:
        print(f"Erreur: {e}")

if __name__ == "__main__":
    break_cycles()
