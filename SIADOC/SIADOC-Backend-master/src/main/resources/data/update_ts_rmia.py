import json
import re

json_path = r"C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\resources\data\rmia_hierarchy.json"
ts_path = r"C:\Users\HP\Documents\01 Mars\frontend_siadoc\src\app\features\administration\drh-structure-nav\hierarchy-data.ts"

with open(json_path, 'r', encoding='utf-8') as f:
    rmia_data = json.load(f)

with open(ts_path, 'r', encoding='utf-8') as f:
    ts_content = f.read()

# Generate the TS constant string
rmia_ts = "export const RMIA_DATA = " + json.dumps(rmia_data, indent=2, ensure_ascii=False) + ";"

# Replace the RMIA_DATA constant in the TS file
# It starts with export const RMIA_DATA = [ ... ];
# We need to find the start and the end.
# Since it's usually at the end of the file or followed by another export, 
# we can use regex but carefully.

pattern = re.compile(r'export const RMIA_DATA = \[.*?\n\];', re.DOTALL)
if pattern.search(ts_content):
    new_content = pattern.sub(rmia_ts, ts_content)
else:
    # If not found (maybe I changed the structure), let's look for the start
    start_marker = "export const RMIA_DATA ="
    start_idx = ts_content.find(start_marker)
    if start_idx != -1:
        # Find the next export or end of file
        next_export = ts_content.find("export const", start_idx + len(start_marker))
        if next_export != -1:
            new_content = ts_content[:start_idx] + rmia_ts + "\n\n" + ts_content[next_export:]
        else:
            new_content = ts_content[:start_idx] + rmia_ts
    else:
        print("Error: RMIA_DATA constant not found in hierarchy-data.ts")
        exit(1)

with open(ts_path, 'w', encoding='utf-8') as f:
    f.write(new_content)

print("Successfully updated hierarchy-data.ts with new RMIA data")
