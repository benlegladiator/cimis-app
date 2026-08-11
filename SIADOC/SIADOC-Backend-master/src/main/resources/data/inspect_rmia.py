import pandas as pd

file_path = r"C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\resources\data\new rmia.xlsx"

try:
    xl = pd.ExcelFile(file_path)
    print(f"Sheet names: {xl.sheet_names}")
    for sheet in xl.sheet_names:
        df = pd.read_excel(file_path, sheet_name=sheet)
        print(f"\n--- Sheet: {sheet} ---")
        print(f"Shape: {df.shape}")
        # Print first 20 rows of the first 6 columns
        print(df.iloc[:20, :6].to_string())
except Exception as e:
    print(f"Error: {e}")
