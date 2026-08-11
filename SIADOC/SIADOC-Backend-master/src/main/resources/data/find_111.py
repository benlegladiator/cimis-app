import pandas as pd

excel_path = r"C:\Users\HP\Documents\20 mai\SIADOC-Backend-master\SIADOC-Backend-master\src\main\resources\data\RMIA-CORRIGE.xlsx"
xl = pd.ExcelFile(excel_path)
found = False

for sheet in xl.sheet_names:
    df = pd.read_excel(excel_path, sheet_name=sheet, header=None)
    # Check column 4 for "111"
    matches = df[df[4].astype(str).str.contains('111', na=False, case=False)]
    if not matches.empty:
        for idx, row in matches.iterrows():
            print(f"Sheet: {sheet}, Row: {idx}")
            print(f"  Col 2: {row[2]}")
            print(f"  Col 3: {row[3]}")
            print(f"  Col 4: {row[4]}")
        found = True

if not found:
    print("111 not found in column 4")
