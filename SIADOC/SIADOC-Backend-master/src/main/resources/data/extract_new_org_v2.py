import docx
from docx.oxml.ns import qn
import json

def get_text_from_shapes(doc):
    texts = []
    # Iterate through all elements in the document
    for elem in doc.element.xpath('//w:t'):
        if elem.text.strip():
            texts.append(elem.text.strip())
    
    # Try to find text in drawing/shapes specifically if missed
    # (The above xpath //w:t should catch almost all text in the document)
    
    return list(dict.fromkeys(texts))

file_path = r"C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\resources\data\new org.docx"
try:
    doc = docx.Document(file_path)
    text = get_text_from_shapes(doc)
    print(json.dumps(text, indent=2, ensure_ascii=False))
except Exception as e:
    print(f"Error: {e}")
