import pandas as pd
import json
import re

file_path = r"C:\Users\HP\Documents\20 mai\SIADOC-Backend-master\SIADOC-Backend-master\src\main\resources\data\RMIA-CORRIGE.xlsx"
ts_path = r"C:\Users\HP\Documents\20 mai\SIADOC-frontend-main\SIADOC-frontend-main\src\app\features\administration\drh-structure-nav\hierarchy-data.ts"
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
        "id": slugify("rmia " + sheet[-1] if sheet.startswith("RMIA") else sheet),
        "label": f"RMIA {sheet[-1]}" if sheet.startswith("RMIA") else sheet,
        "description": f"Région Militaire Inter-Armées {sheet[-1]}" if sheet.startswith("RMIA") else sheet,
        "icon": "fa-chess-rook",
        "children": []
    }
    
    current_brigade = None
    current_bataillon = None
    
    for _, row in df.iterrows():
        if len(row) < 5: continue
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

rmia_ts = "export const RMIA_DATA = " + json.dumps(all_rmia_data, indent=2, ensure_ascii=False) + ";"

with open(ts_path, 'r', encoding='utf-8') as f:
    ts_content = f.read()

pattern = re.compile(r'export const RMIA_DATA = \[.*?\n\];', re.DOTALL)
if pattern.search(ts_content):
    new_content = pattern.sub(rmia_ts, ts_content)
else:
    start_marker = "export const RMIA_DATA ="
    start_idx = ts_content.find(start_marker)
    if start_idx != -1:
        next_export = ts_content.find("export const", start_idx + len(start_marker))
        if next_export != -1:
            new_content = ts_content[:start_idx] + rmia_ts + "\n\n" + ts_content[next_export:]
        else:
            new_content = ts_content[:start_idx] + rmia_ts
    else:
        print("Error: RMIA_DATA constant not found")
        exit(1)

with open(ts_path, 'w', encoding='utf-8') as f:
    f.write(new_content)

print("SUCCESS")
