import psycopg2
import sys
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

DB = {
    "dbname": "drh_militaire",
    "user": "postgres",
    "password": "postgres",
    "host": "localhost",
    "port": "5432"
}

def detect_cycles():
    conn = psycopg2.connect(**DB)
    cur = conn.cursor()
    
    cur.execute("SELECT id, nom, parent_id FROM unite_organisationnelle")
    rows = cur.fetchall()
    adj = {r[0]: r[2] for r in rows}
    names = {r[0]: r[1] for r in rows}
    
    print("--- Recherche de cycles dans la hiérarchie ---")
    
    breaking_count = 0
    for start_id in list(adj.keys()):
        if not adj[start_id]: continue
        
        path = []
        curr = start_id
        while curr and curr in adj:
            if curr in path:
                cycle_path = path[path.index(curr):]
                print(f"Cycle détecté : {' -> '.join([names[node] for node in cycle_path])} -> {names[curr]}")
                cur.execute("UPDATE unite_organisationnelle SET parent_id = NULL WHERE id = %s", (curr,))
                breaking_count += 1
                adj[curr] = None 
                break
            path.append(curr)
            curr = adj[curr]

    conn.commit()
    print(f"Terminé. Cycles rompus : {breaking_count}")
    cur.close()
    conn.close()

if __name__ == "__main__":
    detect_cycles()
