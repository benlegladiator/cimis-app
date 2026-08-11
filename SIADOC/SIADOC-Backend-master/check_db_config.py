import sqlite3
import os

# Essayer de trouver la base de données (H2 par défaut dans Spring Boot si non configuré)
# Mais l'utilisateur semble utiliser PostgreSQL ou une base persistante.
# Je vais vérifier application.properties pour voir la config BD.

db_search_paths = [
    r"c:\Users\HP\Documents\20 mai\SIADOC-Backend\src\main\resources\application.properties"
]

for path in db_search_paths:
    if os.path.exists(path):
        with open(path, 'r') as f:
            print(f"--- {path} ---")
            print(f.read())
