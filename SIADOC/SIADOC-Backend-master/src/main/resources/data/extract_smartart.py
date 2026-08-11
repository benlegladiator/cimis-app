import zipfile
import xml.etree.ElementTree as ET
import os

file_path = r"C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\resources\data\new org.docx"
extract_path = r"C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\resources\data\temp_docx"

if not os.path.exists(extract_path):
    os.makedirs(extract_path)

try:
    with zipfile.ZipFile(file_path, 'r') as zip_ref:
        zip_ref.extractall(extract_path)
    
    diagram_dir = os.path.join(extract_path, 'word', 'diagrams')
    all_texts = []
    
    if os.path.exists(diagram_dir):
        for filename in os.listdir(diagram_dir):
            if filename.startswith('data') and filename.endswith('.xml'):
                tree = ET.parse(os.path.join(diagram_dir, filename))
                root = tree.getroot()
                # Find all text nodes in the diagram
                for t in root.iter():
                    if t.tag.endswith('t') and t.text:
                        all_texts.append(t.text.strip())
    
    # Also check the main document just in case
    doc_xml = os.path.join(extract_path, 'word', 'document.xml')
    if os.path.exists(doc_xml):
        tree = ET.parse(doc_xml)
        root = tree.getroot()
        for t in root.iter():
            if t.tag.endswith('t') and t.text:
                all_texts.append(t.text.strip())

    unique_texts = list(dict.fromkeys([t for t in all_texts if t.strip()]))
    for txt in unique_texts:
        print(txt)

except Exception as e:
    print(f"Error: {e}")
finally:
    # Cleanup might be needed but let's keep it for now to debug if it fails
    pass
