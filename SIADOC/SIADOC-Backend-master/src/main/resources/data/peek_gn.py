import pandas as pd
import json
import os

file_path = r"C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\resources\data\UNITES GN FINAL.xlsx"

try:
    # On commence à la ligne 49 (index 48 en 0-indexed)
    df = pd.read_excel(file_path, header=48)
    
    # Colonnes D, E, F, G correspondantes aux indices 3, 4, 5, 6
    # Mais pandas charge les noms de colonnes. On va utiliser les indices si possible ou vérifier les noms.
    # On va recharger sans header pour être sûr des indices
    df_raw = pd.read_excel(file_path, header=None)
    df_filtered = df_raw.iloc[48:, [3, 4, 5, 6]]
    df_filtered.columns = ['RG', 'Legion', 'Groupement', 'Escadron']
    
    # Nettoyage : enlever les lignes vides
    df_filtered = df_filtered.dropna(how='all')
    
    # Convertir en liste de dict
    data = df_filtered.to_dict(orient='records')
    
    print(json.dumps(data[:20], indent=2))
except Exception as e:
    print(f"Error: {e}")
