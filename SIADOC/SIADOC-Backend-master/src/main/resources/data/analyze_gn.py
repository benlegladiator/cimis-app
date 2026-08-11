import pandas as pd

file_path = r"C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\resources\data\UNITES GN FINAL.xlsx"
df = pd.read_excel(file_path, sheet_name='UNITES GN', header=None)

for col in df.columns:
    unique_vals = df[col].dropna().unique()
    print(f"\nCol {col} unique values (first 20):")
    print(unique_vals[:20])
