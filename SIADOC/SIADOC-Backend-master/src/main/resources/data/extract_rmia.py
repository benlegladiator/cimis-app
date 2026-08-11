import pandas as pd
import json
import re

file_path = r"C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\resources\data\new rmia.xlsx"
xl = pd.ExcelFile(file_path)

def clean_label(label):
    if pd.isna(label): return ""
    return str(label).strip()

def slugify(text):
    text = text.lower()
    text = re.sub(r'[^a-z0-9]', '-', text)
    text = re.sub(r'-+', '-', text)
    return text.strip('-')

all_rmia_data = []

for sheet in xl.sheet_names:
    df = pd.read_excel(file_path, sheet_name=sheet, header=None)
    rmia_node = {
        "id": sheet.lower(),
        "label": sheet,
        "description": f"Région Militaire Inter-Armées {sheet[-1]}",
        "icon": "fa-chess-rook",
        "children": []
    }
    
    current_brigade = None
    current_bataillon = None
    
    for _, row in df.iterrows():
        bde_val = clean_label(row[2])
        bat_val = clean_label(row[3])
        cie_val = clean_label(row[4])
        
        if bde_val:
            current_brigade = {
                "id": slugify(bde_val),
                "label": bde_val,
                "description": "Brigade",
                "icon": "fa-shield",
                "children": []
            }
            rmia_node["children"].append(current_brigade)
            current_bataillon = None
            
        if bat_val:
            current_bataillon = {
                "id": slugify(bat_val),
                "label": bat_val,
                "description": "Bataillon",
                "icon": "fa-building-shield",
                "children": []
            }
            if current_brigade:
                current_brigade["children"].append(current_bataillon)
            else:
                rmia_node["children"].append(current_bataillon)
                
        if cie_val:
            compagnie = {
                "id": slugify(cie_val),
                "label": cie_val,
                "description": "Compagnie",
                "icon": "fa-file-lines"
            }
            if current_bataillon:
                current_bataillon["children"].append(compagnie)
            elif current_brigade:
                current_brigade["children"].append(compagnie)
            else:
                rmia_node["children"].append(compagnie)

    all_rmia_data.append(rmia_node)

with open('rmia_hierarchy.json', 'w', encoding='utf-8') as f:
    json.dump(all_rmia_data, f, ensure_ascii=False, indent=2)

print("Exported to rmia_hierarchy.json")
