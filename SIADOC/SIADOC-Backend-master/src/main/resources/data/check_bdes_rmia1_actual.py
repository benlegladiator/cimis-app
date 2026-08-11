import pandas as pd
import re

excel_path = r"C:\Users\HP\Documents\20 mai\SIADOC-Backend-master\SIADOC-Backend-master\src\main\resources\data\RMIA-CORRIGE.xlsx"
xl = pd.ExcelFile(excel_path)

EXCLUDE = [
    "UNITES ET FORMATIONS", "OBSERVATION", "TOTAL", "SOUS-TOTAL", 
    "RECAPITULATIF", "REGION MILITAIRE", "FORCES DE", "FORCES DU", 
    "FORCES SUR", "LES FORCES", "BASES NAVALES"
]

def clean(v): return str(v).strip().upper() if not pd.isna(v) else ""

df = pd.read_excel(excel_path, sheet_name='RMIA1', header=None)
bdes = set()
current_bde = ""
for _, row in df.iterrows():
    bde = clean(row[2]) if len(row) > 2 else ""
    bat = clean(row[3]) if len(row) > 3 else ""
    cie = clean(row[4]) if len(row) > 4 else ""
    
    for ex in EXCLUDE:
        if ex in bde: bde = ""; current_bde = ""
        if ex in bat: bat = ""

    if bde: current_bde = bde
    
    if cie:
        if current_bde: bdes.add(current_bde)
        else:
            secteur = clean(row[1]) if len(row) > 1 else "UNITES DIRECTES"
            bdes.add(secteur)

print("Actual Brigades created for RMIA 1:")
for b in sorted(list(bdes)):
    print(f"- {b}")
print(f"Total: {len(bdes)}")
