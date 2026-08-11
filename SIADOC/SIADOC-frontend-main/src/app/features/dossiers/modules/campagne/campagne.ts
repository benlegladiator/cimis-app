import { environment } from '@env/environment';
import { Component, Input, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';

interface Campagne {
  id: string;
  designation: string;
  signataire: string;
  date: string;
  document: string | null;
}

@Component({
  selector: 'app-campagne',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './campagne.html',
  styleUrls: ['./campagne.scss']
})
export class CampagneModule implements OnInit {

  @Input() militaireId!: string;
  @Input() readOnly: boolean = false;


  campagnes: Campagne[] = [];
  loading = false;

  // Formulaire d'ajout
  afficherFormulaire = false;
  nouvelleCampagne: any = {
    designation: '',
    signataire: '',
    date: ''
  };
  fichierSelectionne: File | null = null;

  // Document viewer
  pieceSelectionnee: Campagne | null = null;
  documentUrl: SafeResourceUrl | null = null;

  // Édition
  editionId: string | null = null;
  formEdition: any = {};

  constructor(
    private http: HttpClient,
    private sanitizer: DomSanitizer
  ) {}

  ngOnInit(): void {
    this.chargerCampagnes();
  }

  // ==========================================
  // CHARGEMENT
  // ==========================================

  chargerCampagnes() {
    if (!this.militaireId) return;

    this.loading = true;
    this.http.get<Campagne[]>(`${environment.apiUrl}/api/campagnes/${this.militaireId}`)
      .subscribe({
        next: (data) => {
          this.campagnes = data;
          this.loading = false;
        },
        error: (err) => {
          console.error("Erreur chargement campagnes", err);
          this.loading = false;
        }
      });
  }

  // ==========================================
  // AJOUT
  // ==========================================

  toggleFormulaire() {
    this.afficherFormulaire = !this.afficherFormulaire;
    if (!this.afficherFormulaire) {
      this.resetFormulaire();
    }
  }

  onFileSelected(event: any) {
    this.fichierSelectionne = event.target.files[0];
  }

  enregistrer() {
    if (!this.militaireId) {
      alert("ID militaire manquant");
      return;
    }

    if (!this.nouvelleCampagne.designation || !this.nouvelleCampagne.date) {
      alert("Veuillez remplir la désignation et la date");
      return;
    }

    const formData = new FormData();
    
    const data = {
      designation: this.nouvelleCampagne.designation,
      signataire: this.nouvelleCampagne.signataire,
      date: this.nouvelleCampagne.date
    };

    formData.append('data', JSON.stringify(data));

    if (this.fichierSelectionne) {
      formData.append('file', this.fichierSelectionne);
    }

    this.http.post<Campagne>(`${environment.apiUrl}/api/campagnes/${this.militaireId}`, formData)
      .subscribe({
        next: () => {
          alert("Campagne ajoutée avec succès ✅");
          this.resetFormulaire();
          this.afficherFormulaire = false;
          this.chargerCampagnes();
        },
        error: (err) => {
          console.error("Erreur ajout campagne", err);
          alert("Erreur lors de l'ajout");
        }
      });
  }

  resetFormulaire() {
    this.nouvelleCampagne = {
      designation: '',
      signataire: '',
      date: ''
    };
    this.fichierSelectionne = null;
  }

  // ==========================================
  // ÉDITION
  // ==========================================

  commencerEdition(campagne: Campagne) {
    this.editionId = campagne.id;
    this.formEdition = { ...campagne };
  }

  annulerEdition() {
    this.editionId = null;
    this.formEdition = {};
  }

  sauvegarderEdition() {
    if (!this.editionId) return;

    const dto = {
      designation: this.formEdition.designation,
      signataire: this.formEdition.signataire,
      date: this.formEdition.date
    };

    this.http.put<Campagne>(`${environment.apiUrl}/api/campagnes/${this.editionId}`, dto)
      .subscribe({
        next: () => {
          alert("Modifications enregistrées ✅");
          this.editionId = null;
          this.chargerCampagnes();
        },
        error: (err) => {
          console.error("Erreur modification", err);
          alert("Erreur lors de la modification");
        }
      });
  }

  // ==========================================
  // SUPPRESSION
  // ==========================================

  supprimer(id: string) {
    if (!confirm("Êtes-vous sûr de vouloir supprimer cette campagne ?")) {
      return;
    }

    this.http.delete(`${environment.apiUrl}/api/campagnes/${id}`)
      .subscribe({
        next: () => {
          alert("Campagne supprimée ✅");
          this.chargerCampagnes();
          if (this.pieceSelectionnee?.id === id) {
            this.fermerDocument();
          }
        },
        error: (err) => {
          console.error("Erreur suppression", err);
          alert("Erreur lors de la suppression");
        }
      });
  }

  // ==========================================
  // VISUALISATION DOCUMENT
  // ==========================================

  voirDocument(campagne: Campagne) {
    if (!campagne.document) {
      alert("Aucun document associé");
      return;
    }

    this.pieceSelectionnee = campagne;
    const url = `${environment.apiUrl}/api/campagnes/${campagne.id}/fichier`;
    this.documentUrl = this.sanitizer.bypassSecurityTrustResourceUrl(url);
  }

  fermerDocument() {
    this.pieceSelectionnee = null;
    this.documentUrl = null;
  }

  // ==========================================
  // UTILITAIRES
  // ==========================================

  formatDate(date: string): string {
    if (!date) return '';
    return new Date(date).toLocaleDateString('fr-FR');
  }
}
