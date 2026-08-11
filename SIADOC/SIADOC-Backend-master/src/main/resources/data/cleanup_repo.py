import re
import os

path = r'C:\Users\HP\Documents\01 Mars\SIADOC\SIADOC\src\main\java\com\siadoc\backend\repository\DossierAdministratifRepository.java'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

# Collapsing the messy parts by looking for the start and a long sequence of closing parens
# The corrupted blocks look like: ((UPPER(...) ... )))
# We'll replace the whole block starting with ((UPPER up to a point where it's safe

def fix_jpql(content):
    # Match blocks starting with ((UPPER and ending with ))))))
    # We use a non-greedy match but ensure we capture the trailing parens
    res = re.sub(r'\(\(UPPER\((.*?)\) LIKE .*?\)\)\)\)+', 
                 r"(UPPER(\1) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(\1) = :code)", 
                 content, flags=re.DOTALL)
    
    # Also fix the native ones
    res = re.sub(r'\(m\.id IS NULL OR UPPER\(m\.arme_service\) LIKE .*?\)\)\)\)+', 
                 r"(m.id IS NULL OR UPPER(m.arme_service) LIKE UPPER(CONCAT('%', :arme, '%')) OR UPPER(m.arme_service) = :code)", 
                 res, flags=re.DOTALL)
    return res

new_content = fix_jpql(content)

with open(path, 'w', encoding='utf-8') as f:
    f.write(new_content)

print("Cleanup complete.")
