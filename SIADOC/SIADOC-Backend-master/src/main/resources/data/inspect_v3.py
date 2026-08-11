import pandas as pd
excel_path = r"C:\Users\HP\Documents\20 mai\SIADOC-Backend-master\SIADOC-Backend-master\src\main\resources\data\RMIA-CORRIGE.xlsx"
xl = pd.ExcelFile(excel_path)
for sheet in xl.sheet_names:
    print(f"--- Sheet: {sheet} ---")
    df = pd.read_excel(excel_path, sheet_name=sheet, header=None)
    print(df.head(10))
