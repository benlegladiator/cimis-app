import re
import os

path = r'C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\java\com\siadoc\backend\repository\DossierAdministratifRepository.java'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

def clean_all(text):
    # This regex targets the messy blocks that survived the last pass
    # We look for the nested code=AT mess
    
    # Replacement for d.militaire.armeService
    text = re.sub(r'\(UPPER\(d\.militaire\.armeService\) LIKE UPPER\(CONCAT\(\'%\', :arme, \'%\'\)\) OR \(\(:code = \'AT\' AND UPPER\(d\.militaire\.armeService\) = \'AT\'\).*?:code, \' %\'\)\)\)\)+', 
                  r"(UPPER(d.militaire.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(d.militaire.armeService) = :code)", text, flags=re.DOTALL)
    
    # Replacement for m.armeService
    text = re.sub(r'\(UPPER\(m\.armeService\) LIKE UPPER\(CONCAT\(\'%\', :arme, \'%\'\)\) OR \(\(:code = \'AT\' AND UPPER\(m\.armeService\) = \'AT\'\).*?:code, \' %\'\)\)\)\)+', 
                  r"(UPPER(m.armeService) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.armeService) = :code)", text, flags=re.DOTALL)

    # Replacement for m.arme_service (native)
    text = re.sub(r'\(m\.id IS NULL OR UPPER\(m\.arme_service\) LIKE UPPER\(CONCAT\(\'%\', :arme, \'%\'\)\) OR \(\(:code = \'AT\' AND UPPER\(m\.arme_service\) = \'AT\'\).*?:code, \' %\'\)\)\)\)+', 
                  r"(m.id IS NULL OR UPPER(m.arme_service) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.arme_service) = :code)", text, flags=re.DOTALL)

    return text

# Run it multiple times if needed to collapse any remaining nested ones
new_content = clean_all(content)
new_content = clean_all(new_content)

with open(path, 'w', encoding='utf-8') as f:
    f.write(new_content)

print("Deep cleanup complete.")
