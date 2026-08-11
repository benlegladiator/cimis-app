# -*- coding: utf-8 -*-
"""
Migration script:
1. Lit RMIA-CORRIGE.xlsx pour construire la nouvelle hierarchie
2. Sauvegarde l'ancienne affectation des militaires (par nom de compagnie)
3. Reconstruit toute la hierarchie en DB
4. Retransmet les militaires dans les nouvelles compagnies du meme nom
5. Supprime les militaires qui ne peuvent pas etre rattaches (hors unite)
"""
import sys
import io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

import psycopg2
import pandas as pd
import re
import uuid

# --- CONFIGURATION ---
DB = {
    "dbname": "drh_militaire",
    "user": "postgres",
    "password": "postgres",
    "host": "localhost",
    "port": "5432"
}
EXCEL_PATH = r"C:\Users\HP\Documents\20 mai\SIADOC-Backend-master\SIADOC-Backend-master\src\main\resources\data\RMIA-CORRIGE.xlsx"

def clean(val):
    if pd.isna(val): return ""
    return str(val).strip().upper()

# Structure hiérarchique par RMIA: 
#   region_brigades: {rmia_nom: [brigade_nom, ...]}
#   region_bat: {(rmia_nom, brigade_nom): [bataillon_nom, ...]}
#   region_cie: {(rmia_nom, brigade_nom, bataillon_nom): [cie_nom, ...]}
region_brigades = {}   # Toutes les brigades, même sans compagnies
region_bat = {}        # Tous les bataillons
region_cie = {}        # Toutes les compagnies

EXCLUDE = ["TOTAL", "SOUS-TOTAL", "RECAPITULATIF", "REGION MILITAIRE", "OBSERVATION"]

def append_unique(lst, val):
    if val and val not in lst:
        lst.append(val)

xl = pd.ExcelFile(EXCEL_PATH)

for sheet in xl.sheet_names:
    df = pd.read_excel(EXCEL_PATH, sheet_name=sheet, header=None)
    rmia_nom = sheet
    match = re.match(r'RMIA(\d+)', rmia_nom, re.IGNORECASE)
    if match:
        rmia_nom = f"RMIA {match.group(1)}"

    if rmia_nom not in region_brigades:
        region_brigades[rmia_nom] = []

    current_bde = ""
    current_bat = ""

    for _, row in df.iterrows():
        bde = clean(row[2]) if len(row) > 2 else ""
        bat = clean(row[3]) if len(row) > 3 else ""
        cie = clean(row[4]) if len(row) > 4 else ""

        # Ignorer les lignes de TOTAL / RECAPITULATIF
        is_junk = any(ex in v for ex in EXCLUDE for v in [bde, bat, cie])
        if is_junk: continue

        # Brigade: la créer dès qu'on la voit, même sans compagnies
        if bde:
            current_bde = bde
            current_bat = ""  # Réinitialiser le bataillon quand la brigade change
            append_unique(region_brigades[rmia_nom], current_bde)

        # Bataillon: créer dès qu'on le voit
        if bat and current_bde:
            current_bat = bat
            bat_key = (rmia_nom, current_bde)
            if bat_key not in region_bat:
                region_bat[bat_key] = []
            append_unique(region_bat[bat_key], current_bat)

        # Compagnie
        if cie and current_bde:
            final_bat = current_bat if current_bat else "SERVICES DIRECTS"
            # S'assurer que le bataillon existe
            bat_key = (rmia_nom, current_bde)
            if bat_key not in region_bat:
                region_bat[bat_key] = []
            append_unique(region_bat[bat_key], final_bat)

            cie_key = (rmia_nom, current_bde, final_bat)
            if cie_key not in region_cie:
                region_cie[cie_key] = []
            append_unique(region_cie[cie_key], cie)

# Compter
total_bde = sum(len(v) for v in region_brigades.values())
total_bat = sum(len(v) for v in region_bat.values())
total_cie = sum(len(v) for v in region_cie.values())
print(f"✅ Structure lue: {len(region_brigades)} RMIA, {total_bde} brigades, {total_bat} bataillons, {total_cie} compagnies")
for rmia, bdes in region_brigades.items():
    print(f"  - {rmia}: {len(bdes)} brigades")


# ---- Connexion DB ----
conn = psycopg2.connect(**DB)
conn.autocommit = False
cur = conn.cursor()

try:
    # 1. Sauvegarder les affectations des militaires (nom_compagnie -> [dossier_id])
    cur.execute("""
        SELECT d.id, c.nom 
        FROM dossier_administratif d 
        JOIN compagnie c ON d.compagnie_id = c.id
    """)
    old_assignments = {}  # compagnie_nom -> [dossier_id]
    for dossier_id, cie_nom in cur.fetchall():
        old_assignments.setdefault(cie_nom.upper().strip(), []).append(dossier_id)

    print(f"✅ {sum(len(v) for v in old_assignments.values())} militaires sauvegardés")

    # 2. Detacher tous les dossiers de leurs compagnies
    cur.execute('UPDATE dossier_administratif SET compagnie_id = NULL')
    cur.execute('UPDATE utilisateur SET compagnie_id = NULL, bataillon_id = NULL, brigade_id = NULL, region_id = NULL')
    cur.execute('DELETE FROM notification')
    cur.execute('UPDATE mutation_item SET compagnie_id = NULL')

    # 3. Supprimer ancienne hiérarchie
    cur.execute('DELETE FROM compagnie')
    cur.execute('DELETE FROM bataillon')
    cur.execute('DELETE FROM brigade')
    cur.execute('DELETE FROM region_militaire')
    print("✅ Ancienne hierarchie supprimée")

    # 4. Reconstruire la nouvelle hierarchie (avec TOUTES les brigades/bataillons/compagnies)
    region_cache = {}    # rmia_nom -> region_id
    brigade_cache = {}   # (rmia_nom, bde_nom) -> brigade_id
    bataillon_cache = {} # (rmia_nom, bde_nom, bat_nom) -> bataillon_id
    compagnie_name_to_id = {}  # nom_cie -> compagnie_id

    # Créer toutes les RMIA
    for rmia_nom in region_brigades:
        rid = str(uuid.uuid4())
        cur.execute('INSERT INTO region_militaire (id, nom) VALUES (%s, %s)', (rid, rmia_nom))
        region_cache[rmia_nom] = rid

    # Créer toutes les brigades (même celles sans compagnies)
    for rmia_nom, bde_list in region_brigades.items():
        region_id = region_cache[rmia_nom]
        for bde_nom in bde_list:
            bid = str(uuid.uuid4())
            cur.execute('INSERT INTO brigade (id, nom, region_id) VALUES (%s, %s, %s)', (bid, bde_nom, region_id))
            brigade_cache[(rmia_nom, bde_nom)] = bid

    # Créer tous les bataillons
    for (rmia_nom, bde_nom), bat_list in region_bat.items():
        brigade_id = brigade_cache.get((rmia_nom, bde_nom))
        if not brigade_id: continue
        for bat_nom in bat_list:
            btid = str(uuid.uuid4())
            cur.execute('INSERT INTO bataillon (id, nom, brigade_id) VALUES (%s, %s, %s)', (btid, bat_nom, brigade_id))
            bataillon_cache[(rmia_nom, bde_nom, bat_nom)] = btid

    # Créer toutes les compagnies
    for (rmia_nom, bde_nom, bat_nom), cie_list in region_cie.items():
        bataillon_id = bataillon_cache.get((rmia_nom, bde_nom, bat_nom))
        if not bataillon_id: continue
        for cie_nom in cie_list:
            cid = str(uuid.uuid4())
            cur.execute('INSERT INTO compagnie (id, nom, bataillon_id) VALUES (%s, %s, %s)', (cid, cie_nom, bataillon_id))
            compagnie_name_to_id[cie_nom.upper().strip()] = cid

    print(f"✅ Nouvelle hierarchie créée: {len(region_cache)} RMIA, {len(brigade_cache)} brigades, {len(bataillon_cache)} bataillons, {len(compagnie_name_to_id)} compagnies")

    # 5. Rerelier les militaires à leurs nouvelles compagnies
    reassigned = 0
    not_found_dossiers = []

    for cie_nom_upper, dossier_ids in old_assignments.items():
        new_cie_id = compagnie_name_to_id.get(cie_nom_upper)
        if new_cie_id:
            for dos_id in dossier_ids:
                cur.execute('UPDATE dossier_administratif SET compagnie_id = %s WHERE id = %s', (new_cie_id, dos_id))
            reassigned += len(dossier_ids)
            print(f"  ✅ '{cie_nom_upper}' -> {len(dossier_ids)} militaire(s) transférés")
        else:
            not_found_dossiers.extend(dossier_ids)
            print(f"  ⚠️ '{cie_nom_upper}' introuvable dans la nouvelle structure -> {len(dossier_ids)} militaire(s) sans unité")

    print(f"\n✅ {reassigned} militaires rereliés")
    print(f"⚠️ {len(not_found_dossiers)} sans unité -> SUPPRESSION...")

    # 6. Supprimer les militaires sans compagnie (hors unite)
    deleted_total = 0
    for dos_id in not_found_dossiers:
        # Recuperer l'id du militaire
        cur.execute('SELECT militaire_id FROM dossier_administratif WHERE id = %s', (dos_id,))
        row = cur.fetchone()
        if row:
            mil_id = row[0]
            # Supprimer toutes les donnees liees au dossier (cascade manuelle)
            cur.execute('DELETE FROM diplome_item WHERE module_id IN (SELECT id FROM diplome_module WHERE dossier_id = %s)', (dos_id,))
            cur.execute('DELETE FROM notation_item WHERE module_id IN (SELECT id FROM notation_module WHERE dossier_id = %s)', (dos_id,))
            cur.execute('DELETE FROM punition_item WHERE module_id IN (SELECT id FROM punition_module WHERE dossier_id = %s)', (dos_id,))
            cur.execute('DELETE FROM recompense_item WHERE module_id IN (SELECT id FROM recompense_module WHERE dossier_id = %s)', (dos_id,))
            cur.execute('DELETE FROM stage_item WHERE module_id IN (SELECT id FROM stage_module WHERE dossier_id = %s)', (dos_id,))
            cur.execute('DELETE FROM campagne_militaire_item WHERE module_id IN (SELECT id FROM campagne_militaire_module WHERE dossier_id = %s)', (dos_id,))
            cur.execute('DELETE FROM perception_article WHERE module_id IN (SELECT id FROM habillement_module WHERE dossier_id = %s)', (dos_id,))
            cur.execute('DELETE FROM mutation_item WHERE module_id IN (SELECT id FROM mutations_module WHERE dossier_id = %s)', (dos_id,))
            
            cur.execute('DELETE FROM carriere WHERE dossier_id = %s', (dos_id,))
            cur.execute('DELETE FROM notation_module WHERE dossier_id = %s', (dos_id,))
            cur.execute('DELETE FROM diplome_module WHERE dossier_id = %s', (dos_id,))
            cur.execute('DELETE FROM stage_module WHERE dossier_id = %s', (dos_id,))
            cur.execute('DELETE FROM recompense_module WHERE dossier_id = %s', (dos_id,))
            cur.execute('DELETE FROM punition_module WHERE dossier_id = %s', (dos_id,))
            cur.execute('DELETE FROM mutations_module WHERE dossier_id = %s', (dos_id,))
            cur.execute('DELETE FROM habillement_module WHERE dossier_id = %s', (dos_id,))
            cur.execute('DELETE FROM document_medical WHERE module_id IN (SELECT id FROM medical_module WHERE dossier_id = %s)', (dos_id,))
            cur.execute('DELETE FROM arret_travail WHERE module_id IN (SELECT id FROM medical_module WHERE dossier_id = %s)', (dos_id,))
            cur.execute('DELETE FROM medical_module WHERE dossier_id = %s', (dos_id,))
            cur.execute('DELETE FROM campagne_militaire_module WHERE dossier_id = %s', (dos_id,))
            cur.execute('DELETE FROM avancement_module WHERE dossier_id = %s', (dos_id,))
            # 1. Cascade Etat Civil
            cur.execute('DELETE FROM informations_personnelles WHERE etat_civil_id IN (SELECT id FROM etat_civil WHERE dossier_id = %s)', (dos_id,))
            cur.execute('DELETE FROM cni WHERE etat_civil_id IN (SELECT id FROM etat_civil WHERE dossier_id = %s)', (dos_id,))
            cur.execute('DELETE FROM acte_naissance WHERE etat_civil_id IN (SELECT id FROM etat_civil WHERE dossier_id = %s)', (dos_id,))
            cur.execute('DELETE FROM acte_mariage WHERE etat_civil_id IN (SELECT id FROM etat_civil WHERE dossier_id = %s)', (dos_id,))
            cur.execute('DELETE FROM acte_divorce WHERE etat_civil_id IN (SELECT id FROM etat_civil WHERE dossier_id = %s)', (dos_id,))
            cur.execute('DELETE FROM acte_deces WHERE etat_civil_id IN (SELECT id FROM etat_civil WHERE dossier_id = %s)', (dos_id,))
            cur.execute('DELETE FROM jugement_suppletif WHERE etat_civil_id IN (SELECT id FROM etat_civil WHERE dossier_id = %s)', (dos_id,))
            cur.execute('DELETE FROM etat_civil WHERE dossier_id = %s', (dos_id,))

            # 2. Identification
            cur.execute('DELETE FROM identification WHERE dossier_id = %s', (dos_id,))
            
            # 3. Supprimer le dossier
            cur.execute('DELETE FROM dossier_administratif WHERE id = %s', (dos_id,))
            
            # 4. Autres references externes a militaire_id
            cur.execute('DELETE FROM historique_militaire WHERE militaire_id = %s', (mil_id,))
            cur.execute('DELETE FROM archive_decede WHERE militaire_id = %s', (mil_id,))
            cur.execute('DELETE FROM donnee_biometrique WHERE militaire_id = %s', (mil_id,))
            
            # 5. Supprimer le militaire
            cur.execute('DELETE FROM militaire WHERE id = %s', (mil_id,))
            deleted_total += 1

    print(f"🗑️ {deleted_total} militaire(s) supprimé(s) (hors unité)")

    # 7. Rattacher les comptes utilisateurs COM RMIA aux nouvelles regions
    print("\n🔗 Rattachement des comptes utilisateurs...")
    cur.execute("SELECT id, username FROM utilisateur")
    all_users = cur.fetchall()
    
    for uid, username in all_users:
        u_lower = username.lower()
        linked = False
        
        # Cas RMIA
        for rmia_key, rid in region_cache.items():
            match = re.search(r'(\d+)', rmia_key)
            if match:
                num = match.group(1)
                # On cherche "rmia1", "com_rmia1", "comrmia1"
                if f"rmia{num}" in u_lower:
                    cur.execute("UPDATE utilisateur SET region_id = %s WHERE id = %s", (rid, uid))
                    print(f"  ✅ Utilisateur '{username}' relié à '{rmia_key}'")
                    linked = True
                    break
        
        if linked: continue

        # Cas Brigade (par alias/tri-lettre entre parenthèses)
        for (bde_rmia, bde_nom_key), bid in brigade_cache.items():
            match = re.search(r'\((.*?)\)', bde_nom_key)
            if match:
                trigger = match.group(1).lower()
                if trigger in u_lower:
                    cur.execute("UPDATE utilisateur SET brigade_id = %s WHERE id = %s", (bid, uid))
                    print(f"  ✅ Utilisateur '{username}' relié à '{bde_nom_key}'")
                    break

    conn.commit()
    print("\n🎉 Migration terminée avec succès!")

except Exception as e:
    conn.rollback()
    print(f"\n❌ ERREUR - Rollback effectué: {e}")
    import traceback
    traceback.print_exc()
finally:
    cur.close()
    conn.close()
