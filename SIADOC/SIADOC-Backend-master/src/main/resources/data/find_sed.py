import pandas as pd

file_path = r"C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\resources\data\UNITES GN FINAL.xlsx"

try:
    df = pd.read_excel(file_path, header=None)
    # Search for SED in all columns
    mask = df.stack().str.contains("SED", na=False).unstack().any(axis=1)
    print(df[mask].to_string())
except Exception as e:
    print(f"Error: {e}")
