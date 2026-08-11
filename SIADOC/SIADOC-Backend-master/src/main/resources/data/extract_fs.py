import json
import re
from docx import Document

file_path = r"C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\resources\data\organigramme FS.docx"

def slugify(text):
    text = text.lower()
    text = re.sub(r'[^a-z0-9]', '-', text)
    text = re.sub(r'-+', '-', text)
    return text.strip('-')

doc = Document(file_path)
fs_hierarchy = {"CNSP": {}, "BIR": {}}

for table in doc.tables:
    for row in table.rows:
        cells = [c.text.strip().replace('\n', ' ') for c in row.cells]
        if len(cells) < 3: continue
        
        # Find which index contains CNSP or BIR
        parent = None
        start_idx = -1
        for i, val in enumerate(cells):
            if "CNSP" in val.upper():
                parent = "CNSP"
                start_idx = i
                break
            elif "BIR" in val.upper():
                parent = "BIR"
                start_idx = i
                break
        
        if parent and len(cells) > start_idx + 2:
            l2 = cells[start_idx + 1].strip()
            l3 = cells[start_idx + 2].strip()
            
            if not l2: continue
            
            if l2 not in fs_hierarchy[parent]:
                fs_hierarchy[parent][l2] = []
            if l3 and l3 not in fs_hierarchy[parent][l2]:
                fs_hierarchy[parent][l2].append(l3)

# ... build node list ...
def build_node_list(parent_key):
    nodes = []
    # Sort keys
    sorted_l2 = sorted(fs_hierarchy[parent_key].keys())
    for l2 in sorted_l2:
        l3_list = fs_hierarchy[parent_key][l2]
        node = {
            "id": slugify(l2),
            "label": l2,
            "description": "Groupement",
            "icon": "fa-building-shield",
            "children": [
                {"id": slugify(l3), "label": l3, "description": "Unité", "icon": "fa-shield"}
                for l3 in l3_list
            ]
        }
        nodes.append(node)
    return nodes

cnsp_data = build_node_list("CNSP")
bir_data = build_node_list("BIR")

# Fix O
def fix(obj):
    if isinstance(obj, dict):
        for k, v in obj.items():
            if isinstance(v, str):
                obj[k] = re.sub(r'(\d+)O', r'\1°', v)
            else:
                fix(v)
    elif isinstance(obj, list):
        for item in obj:
            fix(item)

fix(cnsp_data)
fix(bir_data)

with open('cnsp_data.json', 'w', encoding='utf-8') as f:
    json.dump(cnsp_data, f, ensure_ascii=False, indent=2)
with open('bir_data.json', 'w', encoding='utf-8') as f:
    json.dump(bir_data, f, ensure_ascii=False, indent=2)

print(f"Extracted CNSP ({len(cnsp_data)}) and BIR ({len(bir_data)})")
