import pandas as pd

file_path = r"C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\resources\data\SIADOC HIERARCHIE.xlsx"

try:
    xl = pd.ExcelFile(file_path)
    print(f"Sheet names: {xl.sheet_names}")
except Exception as e:
    print(f"Error: {e}")
