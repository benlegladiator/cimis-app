import pandas as pd

file_path = r"C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\resources\data\UNITES GN FINAL.xlsx"

try:
    df = pd.read_excel(file_path, header=None)
    # On regarde les lignes 40 à 100
    subset = df.iloc[40:100, [3, 4, 5, 6]]
    subset.columns = ['D', 'E', 'F', 'G']
    print(subset.to_string())
except Exception as e:
    print(f"Error: {e}")
