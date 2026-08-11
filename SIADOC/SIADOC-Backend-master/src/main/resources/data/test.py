
import re
text = open(r'C:\Users\HP\Documents\20 mai\SIADOC-frontend-main\SIADOC-frontend-main\src\app\features\administration\drh-structure-nav\hierarchy-data.ts', encoding='utf-8').read()
m = re.search(r'export const RMIA_DATA = (\[.*?\]);', text, re.DOTALL)
if m:
    import json
    data = json.loads(m.group(1))
    for r in data:
        if r['id'] == 'rmia-1':
            print('RMIA 1 BRIGADES:')
            for b in r['children']:
                print('-', b['label'])
