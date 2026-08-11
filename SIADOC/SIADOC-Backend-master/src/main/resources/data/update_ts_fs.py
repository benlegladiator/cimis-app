import json
import re

cnsp_path = r"C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\resources\data\cnsp_data.json"
bir_path = r"C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\resources\data\bir_data.json"
ts_path = r"C:\Users\HP\Documents\01 Mars\frontend_siadoc\src\app\features\administration\drh-structure-nav\hierarchy-data.ts"

with open(cnsp_path, 'r', encoding='utf-8') as f:
    cnsp_data = json.load(f)
with open(bir_path, 'r', encoding='utf-8') as f:
    bir_data = json.load(f)

with open(ts_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Replace CNSP_DATA
content = re.sub(r'export const CNSP_DATA = \[.*?\];', 
                 "export const CNSP_DATA = " + json.dumps(cnsp_data, indent=2, ensure_ascii=False) + ";", 
                 content, flags=re.DOTALL)

# Replace BIR_DATA
content = re.sub(r'export const BIR_DATA = \[.*?\];', 
                 "export const BIR_DATA = " + json.dumps(bir_data, indent=2, ensure_ascii=False) + ";", 
                 content, flags=re.DOTALL)

# Ensure GP_DATA is empty as requested
content = re.sub(r'export const GP_DATA = \[.*?\];', 
                 "export const GP_DATA = [];", 
                 content, flags=re.DOTALL)

with open(ts_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Successfully updated CNSP and BIR data in hierarchy-data.ts (GP remains empty).")
