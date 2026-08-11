import pandas as pd
excel_path = r"C:\Users\HP\Documents\20 mai\SIADOC-Backend-master\SIADOC-Backend-master\src\main\resources\data\RMIA-CORRIGE.xlsx"
df = pd.read_excel(excel_path, sheet_name='RMIA1', header=None)
bdes = set()
cb = ""
def clean(v): return str(v).strip().upper() if not pd.isna(v) else ""
for _, row in df.iterrows():
    b = clean(row[2]) if len(row) > 2 else ""
    if b: cb = b; bdes.add(cb)
print("Brigades for RMIA 1:")
for b in sorted(list(bdes)):
    print(f"- {b}")
