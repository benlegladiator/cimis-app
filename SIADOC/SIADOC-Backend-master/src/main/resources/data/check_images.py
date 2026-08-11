import docx
import os

file_path = r"C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\resources\data\new org.docx"
try:
    doc = docx.Document(file_path)
    print(f"Number of images: {len(doc.inline_shapes)}")
    for i, shape in enumerate(doc.inline_shapes):
        print(f"Shape {i} type: {shape.type}")
except Exception as e:
    print(f"Error: {e}")
