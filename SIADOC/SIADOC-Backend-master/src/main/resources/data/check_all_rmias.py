import pandas as pd
import os

excel_path = r"C:\Users\HP\Documents\20 mai\SIADOC-Backend-master\SIADOC-Backend-master\src\main\resources\data\RMIA-CORRIGE.xlsx"
xl = pd.ExcelFile(excel_path)

EXCLUDE = [
    "TOTAL", "SOUS-TOTAL", "RECAPITULATIF", "REGION MILITAIRE", 
    "OBSERVATION", "UNITES ET FORMATIONS DE COMBAT"
]

def clean(v): return str(v).strip().upper() if not pd.isna(v) else ""

results = {}

for sheet in xl.sheet_names:
    if not sheet.startswith('RMIA'): continue
    
    df = pd.read_excel(excel_path, sheet_name=sheet, header=None)
    bdes = set()
    current_bde = ""
    
    for _, row in df.iterrows():
        bde = clean(row[2]) if len(row) > 2 else ""
        bat = clean(row[3]) if len(row) > 3 else ""
        cie = clean(row[4]) if len(row) > 4 else ""
        
        is_junk = False
        for ex in EXCLUDE:
            if ex in bde or ex in bat: 
                is_junk = True
                break
        if is_junk: continue
        
        if bde: current_bde = bde
        
        if cie:
            if current_bde: bdes.add(current_bde)
            else: bdes.add(f"COMMANDEMENT {sheet}")
            
    results[sheet] = len(bdes)

print("Brigade counts per RMIA:")
for sheet, count in results.items():
    print(f"- {sheet}: {count} brigades")
