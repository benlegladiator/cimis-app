import pandas as pd

file_path = r"C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\resources\data\new rmia.xlsx"

xl = pd.ExcelFile(file_path)
for sheet in xl.sheet_names:
    df = pd.read_excel(file_path, sheet_name=sheet)
    print(f"\n--- {sheet} ---")
    # Drop rows where everything is NaN
    df = df.dropna(how='all')
    print(df.head(20).to_string())
