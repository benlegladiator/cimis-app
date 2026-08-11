import { environment } from '@env/environment';
import { Component, Input, Output, EventEmitter, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { DomSanitizer } from '@angular/platform-browser';

@Component({
  selector: 'app-etat-civil',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './etat-civil.html',
  styleUrls: ['./etat-civil.scss']
})
export class EtatCivil implements OnInit {

  @Input() etatCivilId!: string;
  @Input() militaire: any; // Ajout du militaire pour récupération auto
  @Input() isArchive: boolean = false;
  @Input() readOnly: boolean = false;
  @Output() modificationSaved = new EventEmitter<string>();


  // ===============================
  // ===== INFORMATIONS PERSO =====
  // ===============================

  infoPerso: any = {
    nom: '',
    prenom: '',
    sexe: '',
    numeroCNI: '',
    situationMatrimoniale: '',
    regime: '',
    nombreConjoints: 0,
    nombreEnfants: 0,
    telephone: '',
    ppcaNom: '',
    ppcaTelephone: '',
    ppcaLien: '',
    adresseComplete: '',
    regionOrigine: '',
    languesParlees: ''
  };

  // ===============================
  // ===== PIECES =====
  // ===============================

  afficherFormulaire = false;
  pieces: any[] = [];
  typePiece = '';
  pieceSelectionnee: any = null;
  fichier: File | null = null;

  // ===== CNI =====
  cni = {
    numero: '',
    dateDelivrance: '',
    dateExpiration: '',
    lieu: ''
  };

  // ===== ACTE NAISSANCE =====
  naissance = {
    numeroActe: '',
    dateEtablissement: '',
    lieu: ''
  };

  // ===== ACTE MARIAGE =====
  mariage = {
    numeroActe: '',
    nomConjoint: '',
    dateMariage: '',
    lieuMariage: ''
  };

  // ===== ACTE DECES =====
  deces = {
    numeroActe: '',
    dateDeces: '',
    lieu: ''
  };

  // ===== ACTE DIVORCE =====
  divorce = {
    numeroJugement: '',
    dateJugement: '',
    tribunal: ''
  };

  // ===== JUGEMENT SUPPLETIF =====
  jugement = {
    numeroJugement: '',
    dateJugement: '',
    tribunal: '',
    objet: ''
  };

  constructor(
    private http: HttpClient,
    private sanitizer: DomSanitizer
  ) {}

  ngOnInit(): void {
    this.chargerInformations();
    this.chargerToutesLesPieces();
  }

  // ==========================================
  // ===== INFORMATIONS PERSONNELLES ==========
  // ==========================================

  chargerInformations() {
    if (!this.etatCivilId) return;

    this.http.get<any>(
      `${environment.apiUrl}/api/etat-civil/informations/${this.etatCivilId}`
    ).subscribe({
      next: data => {
        if (data) {
          this.infoPerso = data;
        }
        this.appliquerInfosMilitaires();
      },
      error: () => {
        this.appliquerInfosMilitaires();
      }
    });
  }

  private appliquerInfosMilitaires() {
    if (this.militaire) {
      this.infoPerso.nom = this.militaire.nom;
      this.infoPerso.prenom = this.militaire.prenom;
      this.infoPerso.sexe = this.militaire.sexe;
    }
  }

  enregistrerInformations() {
    if (!this.etatCivilId) {
      alert("ID manquant / Missing ID !");
      return;
    }

    // On s'assure que les données officielles sont bien là avant envoi
    this.appliquerInfosMilitaires();

    this.http.post(
      `${environment.apiUrl}/api/etat-civil/informations/${this.etatCivilId}`,
      this.infoPerso
    ).subscribe({
      next: (res:any) => {
        this.infoPerso = res;
        alert("Informations enregistrées avec succès ! / Information saved successfully ! ✅");
      },
      error: err => console.error(err)
    });
  }

  // ==========================================
  // =============== PIECES ===================
  // ==========================================

  chargerToutesLesPieces() {
    if (!this.etatCivilId) return;

    this.pieces = [];

    // CNI
    this.http.get<any[]>(
      `${environment.apiUrl}/api/etat-civil/cni/module/${this.etatCivilId}`
    ).subscribe(data => {
      this.pieces = [...this.pieces, ...data.map(p => ({ ...p, type: 'CNI' }))];
    });

    // ACTE NAISSANCE
    this.http.get<any[]>(
      `${environment.apiUrl}/api/etat-civil/acte-naissance/module/${this.etatCivilId}`
    ).subscribe(data => {
      this.pieces = [...this.pieces, ...data.map(p => ({ ...p, type: 'ACTE_NAISSANCE' }))];
    });

    // ACTE MARIAGE
    this.http.get<any[]>(
      `${environment.apiUrl}/api/etat-civil/acte-mariage/module/${this.etatCivilId}`
    ).subscribe(data => {
      this.pieces = [...this.pieces, ...data.map(p => ({ ...p, type: 'ACTE_MARIAGE' }))];
    });

    // ACTE DECES
    this.http.get<any[]>(
      `${environment.apiUrl}/api/etat-civil/acte-deces/module/${this.etatCivilId}`
    ).subscribe(data => {
      this.pieces = [...this.pieces, ...data.map(p => ({ ...p, type: 'ACTE_DECES' }))];
    });

    // ACTE DIVORCE
    this.http.get<any[]>(
      `${environment.apiUrl}/api/etat-civil/acte-divorce/module/${this.etatCivilId}`
    ).subscribe(data => {
      this.pieces = [...this.pieces, ...data.map(p => ({ ...p, type: 'ACTE_DIVORCE' }))];
    });

    // JUGEMENT SUPPLETIF
    this.http.get<any[]>(
      `${environment.apiUrl}/api/etat-civil/jugement-suppletif/module/${this.etatCivilId}`
    ).subscribe(data => {
      this.pieces = [...this.pieces, ...data.map(p => ({ ...p, type: 'JUGEMENT_SUPPLETIF' }))];
    });
  }

  onFileSelected(event: any) {
    this.fichier = event.target.files[0];
  }

  enregistrerPiece() {
    if (!this.etatCivilId) {
      alert("ID manquant / Missing ID !");
      return;
    }
    if (!this.typePiece) {
      alert("Choisir un type / Select a type");
      return;
    }

    const formData = new FormData();
    formData.append('etatCivilId', this.etatCivilId);

    if (this.typePiece === 'CNI') {
      formData.append('numero', this.cni.numero);
      if (this.cni.dateDelivrance) formData.append('dateDelivrance', this.cni.dateDelivrance);
      if (this.cni.dateExpiration) formData.append('dateExpiration', this.cni.dateExpiration);
      formData.append('lieu', this.cni.lieu || '');
      if (this.fichier) formData.append('fichier', this.fichier);
      this.http.post(`${environment.apiUrl}/api/etat-civil/cni`, formData).subscribe(() => this.resetApresEnregistrement());
    }

    if (this.typePiece === 'ACTE_NAISSANCE') {
      formData.append('numeroActe', this.naissance.numeroActe);
      if (this.naissance.dateEtablissement) formData.append('dateEtablissement', this.naissance.dateEtablissement);
      formData.append('lieu', this.naissance.lieu || '');
      if (this.fichier) formData.append('fichier', this.fichier);
      this.http.post(`${environment.apiUrl}/api/etat-civil/acte-naissance`, formData).subscribe(() => this.resetApresEnregistrement());
    }

    if (this.typePiece === 'ACTE_MARIAGE') {
      formData.append('numeroActe', this.mariage.numeroActe);
      formData.append('nomConjoint', this.mariage.nomConjoint || '');
      if (this.mariage.dateMariage) formData.append('dateMariage', this.mariage.dateMariage);
      formData.append('lieuMariage', this.mariage.lieuMariage || '');
      if (this.fichier) formData.append('fichier', this.fichier);
      this.http.post(`${environment.apiUrl}/api/etat-civil/acte-mariage`, formData).subscribe(() => this.resetApresEnregistrement());
    }

    if (this.typePiece === 'ACTE_DECES') {
      formData.append('numeroActe', this.deces.numeroActe);
      if (this.deces.dateDeces) formData.append('dateDeces', this.deces.dateDeces);
      formData.append('lieu', this.deces.lieu || '');
      if (this.fichier) formData.append('fichier', this.fichier);
      this.http.post(`${environment.apiUrl}/api/etat-civil/acte-deces`, formData).subscribe(() => this.resetApresEnregistrement());
    }

    if (this.typePiece === 'ACTE_DIVORCE') {
      formData.append('numeroJugement', this.divorce.numeroJugement);
      if (this.divorce.dateJugement) formData.append('dateJugement', this.divorce.dateJugement);
      formData.append('tribunal', this.divorce.tribunal || '');
      if (this.fichier) formData.append('fichier', this.fichier);
      this.http.post(`${environment.apiUrl}/api/etat-civil/acte-divorce`, formData).subscribe(() => this.resetApresEnregistrement());
    }

    if (this.typePiece === 'JUGEMENT_SUPPLETIF') {
      formData.append('numeroJugement', this.jugement.numeroJugement);
      if (this.jugement.dateJugement) formData.append('dateJugement', this.jugement.dateJugement);
      formData.append('tribunal', this.jugement.tribunal || '');
      formData.append('objet', this.jugement.objet || '');
      if (this.fichier) formData.append('fichier', this.fichier);
      this.http.post(`${environment.apiUrl}/api/etat-civil/jugement-suppletif`, formData).subscribe(() => this.resetApresEnregistrement());
    }
  }

  resetApresEnregistrement() {
    alert("Pièce enregistrée avec succès ! / Document saved successfully ! ✅");
    this.afficherFormulaire = false;
    this.typePiece = '';
    this.fichier = null;
    this.pieces = [];
    this.chargerToutesLesPieces();
  }

  voirPiece(p: any) {
    this.pieceSelectionnee = p;
    let url = '';
    if (p.type === 'CNI') url = `${environment.apiUrl}/api/etat-civil/cni/${p.id}/fichier`;
    if (p.type === 'ACTE_NAISSANCE') url = `${environment.apiUrl}/api/etat-civil/acte-naissance/${p.id}/fichier`;
    if (p.type === 'ACTE_MARIAGE') url = `${environment.apiUrl}/api/etat-civil/acte-mariage/${p.id}/fichier`;
    if (p.type === 'ACTE_DECES') url = `${environment.apiUrl}/api/etat-civil/acte-deces/${p.id}/fichier`;
    if (p.type === 'ACTE_DIVORCE') url = `${environment.apiUrl}/api/etat-civil/acte-divorce/${p.id}/fichier`;
    if (p.type === 'JUGEMENT_SUPPLETIF') url = `${environment.apiUrl}/api/etat-civil/jugement-suppletif/${p.id}/fichier`;
    this.pieceSelectionnee.safeUrl = this.sanitizer.bypassSecurityTrustResourceUrl(url);
  }
}