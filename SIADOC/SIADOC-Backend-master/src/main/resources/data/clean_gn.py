import json
import re

ts_path = r"C:\Users\HP\Documents\01 Mars\frontend_siadoc\src\app\features\administration\drh-structure-nav\hierarchy-data.ts"

with open(ts_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Extract GENDARMERIE_DATA content
match = re.search(r'export const GENDARMERIE_DATA = (\[.*?\]);', content, re.DOTALL)
if not match:
    print("Error: GENDARMERIE_DATA not found")
    exit(1)

data = json.loads(match.group(1))

# Keep only the 5 regions that have children and are clearly labeled as RG 1-5
final_rg_data = []
for item in data:
    label = item.get("label", "")
    if "Région de Gendarmerie" in label or re.match(r'RG[1-5]', label):
        if item.get("children"):
            # Normalize label to RG 1, RG 2, etc. if possible
            match_num = re.search(r'(\d)', label)
            if match_num:
                item["label"] = f"RG {match_num.group(1)}"
                item["id"] = f"rg{match_num.group(1)}"
            final_rg_data.append(item)

# Sort by label
final_rg_data.sort(key=lambda x: x["label"])

# Limit to 5
final_rg_data = final_rg_data[:5]

new_rg_ts = "export const GENDARMERIE_DATA = " + json.dumps(final_rg_data, indent=2, ensure_ascii=False) + ";"
new_content = content.replace(match.group(0), new_rg_ts)

with open(ts_path, 'w', encoding='utf-8') as f:
    f.write(new_content)

print(f"Cleaned up GENDARMERIE_DATA. Now has {len(final_rg_data)} regions.")
