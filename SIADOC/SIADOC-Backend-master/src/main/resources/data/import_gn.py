import pandas as pd
import json

file_path = r"C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\resources\data\UNITES GN FINAL.xlsx"

try:
    df_raw = pd.read_excel(file_path, header=None)
    # On commence à la ligne 49 (index 48)
    df_filtered = df_raw.iloc[48:, [3, 4, 5, 6]]
    df_filtered.columns = ['RG', 'Legion', 'Groupement', 'Escadron']
    
    df_filtered = df_filtered.fillna("")
    for col in df_filtered.columns:
        df_filtered[col] = df_filtered[col].astype(str).str.strip()
    
    # Filtrer pour ne garder que les RG1, RG2, RG3, RG4, RG5
    valid_rg = ["RG1", "RG2", "RG3", "RG4", "RG5"]
    df_filtered = df_filtered[df_filtered['RG'].isin(valid_rg)]
    
    # Mapping des noms longs
    rg_names = {
        "RG1": "1ère Région de Gendarmerie",
        "RG2": "2ème Région de Gendarmerie",
        "RG3": "3ème Région de Gendarmerie",
        "RG4": "4ème Région de Gendarmerie",
        "RG5": "5ème Région de Gendarmerie"
    }
    
    hierarchy = {}
    for _, row in df_filtered.iterrows():
        rg_code = row['RG']
        rg = rg_names.get(rg_code, rg_code)
        legion = row['Legion']
        groupement = row['Groupement']
        escadron = row['Escadron']
        
        if not legion or legion == "NaN": continue
        
        if rg not in hierarchy: hierarchy[rg] = {}
        if legion not in hierarchy[rg]: hierarchy[rg][legion] = {}
        
        if not groupement or groupement == "NaN": continue
        if groupement not in hierarchy[rg][legion]: hierarchy[rg][legion][groupement] = []
        
        if escadron and escadron != "NaN" and escadron not in hierarchy[rg][legion][groupement]:
            hierarchy[rg][legion][groupement].append(escadron)
            
    with open(r"C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\resources\data\gn_hierarchy.json", 'w', encoding='utf-8') as f:
        json.dump(hierarchy, f, indent=2, ensure_ascii=False)
        
    print(f"Hierarchy saved. Found {len(hierarchy)} regions.")
    for rg in hierarchy:
        print(f" - {rg}: {len(hierarchy[rg])} Legions")

except Exception as e:
    print(f"Error: {e}")
