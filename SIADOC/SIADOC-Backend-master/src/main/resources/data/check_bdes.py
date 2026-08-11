import pandas as pd
import re

excel_path = r"C:\Users\HP\Documents\20 mai\SIADOC-Backend-master\SIADOC-Backend-master\src\main\resources\data\RMIA-CORRIGE.xlsx"
xl = pd.ExcelFile(excel_path)
bdes = set()

def clean(v):
    if pd.isna(v): return ""
    return str(v).strip().upper()

for sheet in xl.sheet_names:
    df = pd.read_excel(excel_path, sheet_name=sheet, header=None)
    cb = ""
    for _, row in df.iterrows():
        b = clean(row[2]) if len(row) > 2 else ""
        if b:
            cb = b
            bdes.add(cb)

print("--- Unique Brigades found ---")
for b in sorted(list(bdes)):
    print(f"- {b}")
