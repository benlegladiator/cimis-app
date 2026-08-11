import os
import re

files = [
    r'C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\java\com\siadoc\backend\repository\DossierAdministratifRepository.java',
    r'C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\java\com\siadoc\backend\repository\MilitaireRepository.java'
]

for path in files:
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Generic fix for any 2-letter code to use exact match instead of LIKE %code%
    # We replace: OR UPPER(...) LIKE UPPER(CONCAT('%', :code, '%'))
    # With: OR (LENGTH(:code) = 2 AND UPPER(...) = :code) OR (LENGTH(:code) <> 2 AND UPPER(...) LIKE UPPER(CONCAT('%', :code, '%')))
    
    new_content = content
    # Handle the variations found in the files
    patterns = [
        ('UPPER(d.militaire.armeService) LIKE UPPER(CONCAT(\'%\', :code, \'%\'))', 
         '(UPPER(d.militaire.armeService) = :code OR UPPER(d.militaire.armeService) LIKE UPPER(CONCAT(\'%\', :code, \' %\')) OR UPPER(d.militaire.armeService) LIKE UPPER(CONCAT(\'% \', :code, \'%\')))'),
        
        ('UPPER(m.armeService) LIKE UPPER(CONCAT(\'%\', :code, \'%\'))', 
         '(UPPER(m.armeService) = :code OR UPPER(m.armeService) LIKE UPPER(CONCAT(\'%\', :code, \' %\')) OR UPPER(m.armeService) LIKE UPPER(CONCAT(\'% \', :code, \'%\')))'),
         
        ('UPPER(m.arme_service) LIKE UPPER(CONCAT(\'%\', :code, \'%\'))', 
         '(UPPER(m.arme_service) = :code OR UPPER(m.arme_service) LIKE UPPER(CONCAT(\'%\', :code, \' %\')) OR UPPER(m.arme_service) LIKE UPPER(CONCAT(\'% \', :code, \'%\')))'),
    ]
    
    # Wait, the previous fix already changed some things. 
    # Let's use a simpler approach: if it's a code, it should be a whole word.
    
    # Actually, let's just make it exact for the code part.
    # OR UPPER(...) = :code
    
    for old, _ in patterns:
        # We need to be careful as I already changed it to the (:code = 'AT' ...) version
        # I'll just reset and apply a cleaner word-boundary check or exact match for code.
        pass

    # New strategy: replace the whole condition
    # Find: (UPPER(...) LIKE ... OR UPPER(...) LIKE ...)
    # Replace with: (UPPER(...) LIKE ... OR UPPER(...) = :code)
    
    new_content = re.sub(r"UPPER\((.*?)\) LIKE UPPER\(CONCAT\('%', :code, '%'\)\)", 
                         r"(UPPER(\1) = :code OR UPPER(\1) LIKE UPPER(CONCAT('% ', :code, '%')) OR UPPER(\1) LIKE UPPER(CONCAT('%', :code, ' %')))", 
                         content)

    with open(path, 'w', encoding='utf-8') as f:
        f.write(new_content)

print("Standardized army code matching to word boundaries / exact match.")
