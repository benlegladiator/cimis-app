export enum Role {
  SUPER_ADMIN = 'SUPER_ADMIN',
  DRH = 'DRH',
  ETAT_MAJOR_TERRE = 'ETAT_MAJOR_TERRE',
  ETAT_MAJOR_AIR = 'ETAT_MAJOR_AIR',
  ETAT_MAJOR_MARINE = 'ETAT_MAJOR_MARINE',
  COMMANDANT = 'COMMANDANT',
  COMMANDANT_COMPAGNIE = 'COMMANDANT_COMPAGNIE',
  RMIA = 'RMIA',
  BRIGADE = 'BRIGADE',
  BATAILLON = 'BATAILLON'
}

export enum CategorieMilitaire {
  OFFICIER = 'OFFICIER',
  SOUS_OFFICIER = 'SOUS_OFFICIER',
  MILITAIRE_RANG = 'MILITAIRE_RANG'
}

export enum StatutValidation {
  EN_COURS = 'EN_COURS',
  EN_ATTENTE_VALIDATION = 'EN_ATTENTE_VALIDATION',
  EN_ATTENTE_DRH = 'EN_ATTENTE_DRH',
  VALIDE = 'VALIDE',
  REJETE = 'REJETE'
}

export enum EtatMilitaire {
  NOUVELLE_RECRUE = 'NOUVELLE_RECRUE',
  ACTIF = 'ACTIF',
  DECEDE = 'DECEDE',
  RETRAITE = 'RETRAITE'
}

export interface Compagnie {
  id: string;
  nom: string;
  abreviation?: string;
  labelAffichage?: string;
  hierarchy?: {
    rmia: string;
    brigade: string;
    bataillon: string;
  };
  localisation?: string;
}

export interface Utilisateur {
  id: string;
  username: string;
  role: Role;
  compagnie?: Compagnie;
}

export interface Militaire {
  id: string;
  nom: string;
  prenom: string;
  matriculeMilitaire?: string;
  matriculeSolde: string;
  dateNaissance?: string;
  grade: string;
  dateGrade?: string;
  echelon?: number;
  armeService?: string;
  statut?: string;
  lieuNaissance?: string;
  sexe?: string;
  statutValidation?: StatutValidation;
  etat?: EtatMilitaire;
  categorie?: CategorieMilitaire;
}

export interface SiadocNotification {
  id: string;
  titre?: string;
  message: string;
  dateCreation: string;
  lu: boolean;
  militaire: Militaire;
  compagnieConcernee?: Compagnie;
  dossierConcerne?: { id: string };
  type: string;
}

export interface AffectationRequestDTO {
  compagnieId: string;
  numeroTexte: string;
  dateTexte: string;
  emploi: string;
}

export interface TeecRow {
  nomPrenom: string;
  grade: string;
  echelon?: number;
  categorieX?: string;
  categorieY?: string;
  categorieZ?: string;
  categorieC?: string;
  qualifMilitaire?: string;
  qualifCivile?: string;
  regionOrigine?: string;
  observationEmploi?: string;
  dateEntreeService?: string;
  languesParlees?: string;
  aptitude?: string;
  emploiPoste?: string;
  datePriseFonction?: string;
  numero?: string;
  nomCompagnie?: string;
}

export interface DiplomeDTO {
  id?: string;
  designation: string;
  ecole: string;
  dateObtention: string;
  document?: string;
}

export interface Reengagement {
  id?: string;
  designation: string;
  lieu: string;
  date: string;
}

export interface AdmissionSoc {
  id?: string;
  designation: string;
  lieu: string;
  date: string;
}

export interface UserSession {
  user: any;
  token: string;
}

export interface PersonnelCivil {
  id?: string;
  nom: string;
  prenom: string;
  dateNaissance: string;
  lieuNaissance?: string;
  sexe?: string;
  matricule: string;
  dateEntreeService: string;
  poste: string;
  documents?: DocumentCivil[];
}

export interface DocumentCivil {
  id: string;
  label: string;
  nomFichier: string;
  typeFichier: string;
  dateTeleversement: string;
}

export interface CarriereDTO {
  id?: string;
  corps: string;
  arme: string;
  origine: string;
  cnim: string;
  formationStructure: string;
  compagnie: string;
  observationEmploi: string;
  statut: string;
  matriculeSolde: string;
  matriculeMilitaire: string;
  ancienneteService: string;
  ancienneteGrade: string;
  anneesProlongation: number;
  estArchive: boolean;
  dateCalculReference: string;
  reengagements?: Reengagement[];
  admissionSocs?: AdmissionSoc[];
  nomFichier: string;
  dateGrade?: string;
  dateEchelon?: string;
  categorie?: CategorieMilitaire;
}
