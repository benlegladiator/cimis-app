import pandas as pd

file_path = r"C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\resources\data\new rmia.xlsx"
df = pd.read_excel(file_path, sheet_name='RMIA1', header=None)
print("First 10 rows, all columns:")
print(df.head(10).to_string())
