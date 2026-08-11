import json
import re

rg_json_path = r"C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\resources\data\gn_rg_data.json"
ts_path = r"C:\Users\HP\Documents\01 Mars\frontend_siadoc\src\app\features\administration\drh-structure-nav\hierarchy-data.ts"

with open(rg_json_path, 'r', encoding='utf-8') as f:
    rg_data = json.load(f)

with open(ts_path, 'r', encoding='utf-8') as f:
    ts_content = f.read()

# Generate the TS constant string
rg_ts = "export const GENDARMERIE_DATA = " + json.dumps(rg_data, indent=2, ensure_ascii=False) + ";"

# Replace the GENDARMERIE_DATA constant in the TS file
pattern = re.compile(r'export const GENDARMERIE_DATA = \[.*?\n\];', re.DOTALL)
if pattern.search(ts_content):
    new_content = pattern.sub(rg_ts, ts_content)
else:
    # Try finding the start if pattern fails
    start_marker = "export const GENDARMERIE_DATA ="
    start_idx = ts_content.find(start_marker)
    if start_idx != -1:
        next_export = ts_content.find("export const", start_idx + len(start_marker))
        if next_export != -1:
            new_content = ts_content[:start_idx] + rg_ts + "\n\n" + ts_content[next_export:]
        else:
            new_content = ts_content[:start_idx] + rg_ts
    else:
        print("Error: GENDARMERIE_DATA constant not found in hierarchy-data.ts")
        exit(1)

with open(ts_path, 'w', encoding='utf-8') as f:
    f.write(new_content)

print("Successfully updated hierarchy-data.ts with new Gendarmerie data")
