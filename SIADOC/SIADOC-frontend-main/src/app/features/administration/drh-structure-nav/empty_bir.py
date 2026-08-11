import re

file_path = r"c:\Users\HP\Documents\20 mai\SIADOC-frontend\src\app\features\administration\drh-structure-nav\hierarchy-data.ts"

with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

# Remplacer la constante BIR_DATA par une version vide
new_content = re.sub(r'export const BIR_DATA = \[\s*[\s\S]*?\];', 'export const BIR_DATA = [];', content)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(new_content)

print("BIR_DATA vidé avec succès.")
