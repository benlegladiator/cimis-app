export interface RechercheEtatCivil {
  nom?: string
  prenom?: string
  grade?: string
  arme?: string
  annee?: number
  lieu?: string
}

export interface ResultRechercheEtatCivil {
  nom: string
  prenom: string
  grade: string
  arme: string
  lieuEtablissement: string
  dateEtablissement: string
}

export interface RechercheMariage {
  nom?: string
  prenom?: string
  grade?: string
  arme?: string
  lieu?: string
  annee?: number
  nomConjoint?: string
}

export interface ResultRechercheMariage {
  nom: string
  prenom: string
  grade: string
  arme: string
  numeroActe: string
  dateMariage: string
  lieuMariage: string
  nomConjoint: string
}

export interface RechercheCni {
  nom?: string
  prenom?: string
  grade?: string
  arme?: string
  numero?: string
}

export interface ResultRechercheCni {
  nom: string
  prenom: string
  grade: string
  arme: string
  numero: string
  dateExpiration: string
}

export interface RecherchePunition {
  nom?: string
  prenom?: string
  grade?: string
  arme?: string
  designation?: string
  texte?: string
  annee?: number
}

export interface ResultRecherchePunition {
  nom: string
  prenom: string
  grade: string
  arme: string
  designation: string
  texte: string
  dateEffet: string
}

export interface RechercheDiplome {
  nom?: string
  prenom?: string
  grade?: string
  arme?: string
  designation?: string
  ecole?: string
  annee?: number
}

export interface ResultRechercheDiplome {
  nom: string
  prenom: string
  grade: string
  arme: string
  designation: string
  ecole: string
  dateObtention: string
}

export interface RechercheAvancement {
  nom?: string
  prenom?: string
  grade?: string
  arme?: string
  nouveauGrade?: string
  annee?: number
  motif?: string
}

export interface ResultRechercheAvancement {
  nom: string
  prenom: string
  grade: string
  arme: string
  nouveauGrade: string
  dateAvancement: string
  typeAvancement: string
  motif: string
}

export interface RechercheStage {
  nom?: string
  prenom?: string
  grade?: string
  arme?: string
  designation?: string
  lieu?: string
  annee?: number
}

export interface ResultRechercheStage {
  nom: string
  prenom: string
  grade: string
  arme: string
  designation: string
  lieu: string
  dateStage: string
}

export interface RechercheMedical {
  nom?: string
  prenom?: string
  grade?: string
  arme?: string
  nature?: string
  lieu?: string
  annee?: number
  taux?: number
  typeExamen?: string
  aptitude?: string
}

export interface ResultRechercheMedical {
  nom: string
  prenom: string
  grade: string
  arme: string
  nature: string
  lieu: string
  date: string
  typeExamen: string
  aptitude: string
}

export interface RechercheCarriere {
  nom?: string
  prenom?: string
  grade?: string
  arme?: string
  poste?: string
  unite?: string
  annee?: number
}

export interface ResultRechercheCarriere {
  nom: string
  prenom: string
  grade: string
  arme: string
  poste: string
  unite: string
  datePrisePoste: string
}

export interface RechercheMutation {
  nom?: string
  prenom?: string
  grade?: string
  arme?: string
  provenance?: string
  destination?: string
  annee?: number
  origine?: string
}

export interface ResultRechercheMutation {
  nom: string
  prenom: string
  grade: string
  arme: string
  emploi: string
  unite: string
  ville: string
  dateMutation: string
  origine: string
  destination: string
}

export interface RechercheNotation {
  nom?: string
  prenom?: string
  grade?: string
  arme?: string
  annee?: number
  appreciation?: string
  notation?: number
}

export interface ResultRechercheNotation {
  nom: string
  prenom: string
  grade: string
  arme: string
  annee: number
  appreciation: string
  notation: number
}

export interface RechercheCampagneMilitaire {
  nom?: string
  prenom?: string
  grade?: string
  arme?: string
  campagne?: string
  annee?: number
  lieu?: string
  role?: string
}

export interface ResultRechercheCampagneMilitaire {
  nom: string
  prenom: string
  grade: string
  arme: string
  campagne: string
  date: string
  lieu: string
  role: string
  annee: number
}