# ========================================
# PROFILS ET ROLES POUR LA HIÉRARCHIE MILITAIRE
# ========================================

## RMIA (Région Militaire)
- RMIA1: "Région Militaire Nord"
- RMIA2: "Région Militaire Centre" 
- RMIA3: "Région Militaire Sud"
- RMIA4: "Région Militaire Est"
- RMIA5: "Région Militaire Ouest"

## BRIGADES
- Brigade1: "1ère Brigade d'Infanterie"
- Brigade2: "2ème Brigade d'Infanterie"
- Brigade3: "3ème Brigade d'Infanterie"
- Brigade4: "Brigade de Support Logistique"
- Brigade5: "Brigade du Génie Militaire"

## BATAILLONS
- Bataillon1: "1er Bataillon de Commandement"
- Bataillon2: "2ème Bataillon d'Infanterie"
- Bataillon3: "3ème Bataillon d'Infanterie"
- Bataillon4: "Bataillon de Support"
- Bataillon5: "Bataillon du Génie"

## COMPAGNIES
- Compagnie1: "Compagnie de Commandement"
- Compagnie2: "1ère Compagnie d'Infanterie"
- Compagnie3: "2ème Compagnie d'Infanterie"
- Compagnie4: "Compagnie de Support"
- Compagnie5: "Compagnie du Génie"
- Compagnie6: "Compagnie de Transmission"

# ========================================
# ROLES ET PERMISSIONS
# ========================================

## RÔLE RMIA
- rmia_admin: Administrateur de la Région Militaire
- rmia_chef: Chef de la Région Militaire
- rmia_officier: Officier de la Région Militaire

## RÔLE BRIGADE
- brigade_chef: Chef de Brigade
- brigade_officier: Officier de Brigade
- brigade_adjoint: Adjoint de Brigade

## RÔLE BATAILLON
- bataillon_chef: Chef de Bataillon
- bataillon_officier: Officier de Bataillon
- bataillon_adjoint: Adjoint de Bataillon

## RÔLE COMPAGNIE
- compagnie_chef: Chef de Compagnie
- compagnie_officier: Officier de Compagnie
- compagnie_adjoint: Adjoint de Compagnie
- compagnie_sergent: Sergent de Compagnie

## PERMISSIONS PAR RÔLE
- rmia_admin: TOUTES LES PERMISSIONS
- rmia_chef: Gestion des brigades, bataillons, compagnies, militaires
- rmia_officier: Consultation des rapports, validation des dossiers
- brigade_chef: Gestion des bataillons, compagnies, militaires de sa brigade
- brigade_officier: Consultation des rapports de brigade, validation des dossiers
- bataillon_chef: Gestion des compagnies et militaires de son bataillon
- bataillon_officier: Consultation des rapports de bataillon
- compagnie_chef: Gestion complète de sa compagnie et de ses militaires
- compagnie_officier: Gestion des militaires de sa compagnie
- compagnie_adjoint: Gestion administrative de la compagnie
- compagnie_sergent: Gestion quotidienne des militaires
