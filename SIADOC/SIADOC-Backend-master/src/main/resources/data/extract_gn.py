import pandas as pd
import json
import re

file_path = r"C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\resources\data\UNITES GN FINAL.xlsx"
df = pd.read_excel(file_path, sheet_name='UNITES GN', header=None)

def clean_label(label):
    if pd.isna(label): return ""
    return str(label).strip()

def slugify(text):
    text = text.lower()
    text = re.sub(r'[^a-z0-9]', '-', text)
    text = re.sub(r'-+', '-', text)
    return text.strip('-')

hierarchy = {}

for _, row in df.iterrows():
    l1 = clean_label(row[3])
    l2 = clean_label(row[4])
    l3 = clean_label(row[5])
    l4 = clean_label(row[6])
    
    if not l1: continue
    
    if l1 not in hierarchy:
        hierarchy[l1] = {}
    
    if l2:
        if l2 not in hierarchy[l1]:
            hierarchy[l1][l2] = {}
        if l3:
            if l3 not in hierarchy[l1][l2]:
                hierarchy[l1][l2][l3] = []
            if l4:
                if l4 not in hierarchy[l1][l2][l3]:
                    hierarchy[l1][l2][l3].append(l4)

# Convert to the required structure
def convert_to_unit_list(d, level):
    if isinstance(d, list):
        return [{"id": slugify(x), "label": x, "description": "Unité", "icon": "fa-shield"} for x in d]
    
    units = []
    for k, v in d.items():
        desc = "Région" if level == 0 else "Direction" if level == 1 else "Service"
        icon = "fa-map" if level == 0 else "fa-building-shield" if level == 1 else "fa-file-lines"
        unit = {
            "id": slugify(k),
            "label": k,
            "description": desc,
            "icon": icon,
            "children": convert_to_unit_list(v, level + 1)
        }
        units.append(unit)
    return units

rg_data = []
ac_gn_data = []

regions_labels = ["RG1", "RG2", "RG3", "RG4", "RG5", "REGION DE GENDARMERIE"]

for k, v in hierarchy.items():
    units = convert_to_unit_list(v, 1)
    if any(r in k for r in regions_labels):
        label = k if len(k) > 3 else f"Région de Gendarmerie {k[-1]}"
        rg_data.append({
            "id": slugify(label),
            "label": label,
            "description": "Région de Gendarmerie",
            "icon": "fa-map",
            "children": units
        })
    elif k == "GN":
        ac_gn_data.extend(units)

# Deduplicate regions (in case of RG1 and PREMIERE REGION...)
seen_ids = set()
dedup_rg = []
for r in rg_data:
    if r["id"] not in seen_ids:
        dedup_rg.append(r)
        seen_ids.add(r["id"])

# Fix degree symbols
def fix_symbols(obj):
    if isinstance(obj, dict):
        for k, v in obj.items():
            if isinstance(v, str):
                obj[k] = re.sub(r'(\d+)O', r'\1°', v)
            else:
                fix_symbols(v)
    elif isinstance(obj, list):
        for item in obj:
            fix_symbols(item)

fix_symbols(dedup_rg)
fix_symbols(ac_gn_data)

with open('gn_rg_data.json', 'w', encoding='utf-8') as f:
    json.dump(dedup_rg, f, ensure_ascii=False, indent=2)

with open('ac_gn_data.json', 'w', encoding='utf-8') as f:
    json.dump(ac_gn_data, f, ensure_ascii=False, indent=2)

print(f"Extracted {len(dedup_rg)} regions and {len(ac_gn_data)} AC/GN units")
