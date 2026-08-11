import { environment } from '@env/environment';
import { Component, Input, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { CarriereDTO, Reengagement, AdmissionSoc } from '../../../../core/models';
import { CarriereService } from '../../../../core/services/carriere.service';

interface AncienneteCalculee {
  ancienneteServiceFormatee: string;
  ancienneteGradeFormatee: string;
  totalAnneesProlongation: number;
  dateReference: string;
}

@Component({
  selector: 'app-identification',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './identification.html',
  styleUrls: ['./identification.scss']
})
export class Identification implements OnInit {

  @Input() militaireId!: string;
  @Input() readOnly: boolean = false;

  // ==========================================
  // DONNÉES PRINCIPALES
  // ==========================================

  form: CarriereDTO = {
    corps: '',
    arme: '',
    origine: '',
    cnim: '',
    formationStructure: '',
    compagnie: '',
    observationEmploi: '',
        statut: '',
    matriculeSolde: '',
    matriculeMilitaire: '',
    ancienneteService: '',
    ancienneteGrade: '',
    anneesProlongation: 0,
    estArchive: false,
    dateCalculReference: '',
    reengagements: [],
    admissionSocs: [],
    nomFichier: ''
  };

  anciennetes: AncienneteCalculee | null = null;
  loading = false;

  // ==========================================
  // RÉENGAGEMENTS (Section 3)
  // ==========================================

  afficherFormReengagement = false;
  nouveauReengagement: Partial<Reengagement> = {
    designation: '',
    lieu: '',
    date: ''
  };

  // ==========================================
  // ADMISSIONS SOC (Section 4)
  // ==========================================

  afficherFormAdmission = false;
  nouvelleAdmission: Partial<AdmissionSoc> = {
    designation: '',
    lieu: '',
    date: ''
  };

  // ==========================================
  // DOCUMENTS
  // ==========================================

  fichierSelectionne: File | null = null;
  documentUrl: SafeResourceUrl | null = null;
  afficherDocument = false;

  constructor(
    private carriereService: CarriereService,
    private sanitizer: DomSanitizer
  ) { }

  ngOnInit(): void {
    this.chargerCarriere();
  }

  // ==========================================
  // CHARGEMENT DES DONNÉES
  // ==========================================

  chargerCarriere() {
    if (!this.militaireId) return;
 
    this.loading = true;
    this.carriereService.getCarriere(this.militaireId).subscribe({
      next: (data: any) => {
        this.form = data;
        this.loading = false;
      },
      error: (err: any) => {
        console.error("Erreur chargement carrière", err);
        this.loading = false;
      }
    });
  }
 
  // La méthode chargerAnciennetes est désormais intégrée dans chargerCarriere (DTO complet)

  // ==========================================
  // MISE À JOUR INFOS PRINCIPALES
  // ==========================================

  enregistrer() {
    if (!this.militaireId) {
      alert("ID militaire manquant / Missing military ID");
      return;
    }
 
    if (this.fichierSelectionne) {
      this.uploadDocument();
    }
 
    this.carriereService.updateCarriere(this.militaireId, this.form).subscribe({
      next: (data: any) => {
        this.form = data;
        alert("Profil enregistré avec succès ! / Profile saved successfully ! ✅");
      },
      error: (err: any) => {
        console.error("Erreur enregistrement profil", err);
        alert("Erreur lors de l'enregistrement / Error while saving");
      }
    });
  }

  // ==========================================
  // RÉENGAGEMENTS (Section 3)
  // ==========================================

  toggleFormReengagement() {
    this.afficherFormReengagement = !this.afficherFormReengagement;
    if (!this.afficherFormReengagement) {
      this.resetNouveauReengagement();
    }
  }

  resetNouveauReengagement() {
    this.nouveauReengagement = {
      designation: '',
      lieu: '',
      date: ''
    };
  }

  ajouterReengagement() {
    if (!this.militaireId) return;
 
    if (!this.nouveauReengagement.designation || !this.nouveauReengagement.lieu || !this.nouveauReengagement.date) {
      alert("Veuillez remplir tous les champs. / Please fill all fields.");
      return;
    }
 
    this.carriereService.addReengagement(this.militaireId, this.nouveauReengagement as Reengagement).subscribe({
      next: (saved: any) => {
        this.form.reengagements = [...(this.form.reengagements || []), saved];
        this.resetNouveauReengagement();
        this.toggleFormReengagement();
        alert("Réengagement ajouté avec succès ! / Re-enlistment added successfully ! ✅");
      },
      error: (err: any) => {
        console.error("Erreur ajout réengagement", err);
        alert("Erreur lors de l'ajout / Error while adding");
      }
    });
  }

  supprimerReengagement(id: string) {
    if (!confirm("Êtes-vous sûr de vouloir supprimer ce réengagement ? / Are you sure you want to delete this re-enlistment ?")) return;
 
    this.carriereService.deleteReengagement(id).subscribe({
      next: () => {
        this.form.reengagements = this.form.reengagements?.filter(r => r.id !== id) || [];
        alert("Réengagement supprimé ! / Re-enlistment deleted ! ✅");
      },
      error: (err: any) => {
        console.error("Erreur suppression réengagement", err);
        alert("Erreur lors de la suppression / Error while deleting");
      }
    });
  }

  // ==========================================
  // ADMISSIONS SOC (Section 4)
  // ==========================================

  toggleFormAdmission() {
    this.afficherFormAdmission = !this.afficherFormAdmission;
    if (!this.afficherFormAdmission) {
      this.resetNouvelleAdmission();
    }
  }

  resetNouvelleAdmission() {
    this.nouvelleAdmission = {
      designation: '',
      lieu: '',
      date: ''
    };
  }

  ajouterAdmission() {
    if (!this.militaireId) return;
 
    if (!this.nouvelleAdmission.designation || !this.nouvelleAdmission.lieu || !this.nouvelleAdmission.date) {
      alert("Veuillez remplir tous les champs. / Please fill all fields.");
      return;
    }
 
    this.carriereService.addAdmission(this.militaireId, this.nouvelleAdmission as AdmissionSoc).subscribe({
      next: (saved: any) => {
        this.form.admissionSocs = [...(this.form.admissionSocs || []), saved];
        this.resetNouvelleAdmission();
        this.toggleFormAdmission();
        alert("Admission SOC ajoutée avec succès ! / Service admission added successfully ! ✅");
      },
      error: (err: any) => {
        console.error("Erreur ajout admission", err);
        alert("Erreur lors de l'ajout / Error while adding");
      }
    });
  }

  supprimerAdmission(id: string) {
    if (!confirm("Êtes-vous sûr de vouloir supprimer cette admission SOC ? / Are you sure you want to delete this service admission ?")) return;
 
    this.carriereService.deleteAdmission(id).subscribe({
      next: () => {
        this.form.admissionSocs = this.form.admissionSocs?.filter(a => a.id !== id) || [];
        alert("Admission SOC supprimée ! / Service admission deleted ! ✅");
      },
      error: (err: any) => {
        console.error("Erreur suppression admission", err);
        alert("Erreur lors de la suppression / Error while deleting");
      }
    });
  }

  // ==========================================
  // DOCUMENTS
  // ==========================================

  onFileSelected(event: any) {
    this.fichierSelectionne = event.target.files[0];
  }

  uploadDocument() {
    if (!this.militaireId || !this.fichierSelectionne) return;
 
    this.carriereService.uploadDocument(this.militaireId, this.fichierSelectionne).subscribe({
      next: () => {
        this.form.nomFichier = this.fichierSelectionne?.name || '';
        this.fichierSelectionne = null;
        alert("Document uploadé avec succès ! / Document uploaded successfully ! ✅");
      },
      error: (err: any) => {
        console.error("Erreur upload document", err);
        alert("Erreur lors de l'upload / Error while uploading");
      }
    });
  }

  voirDocument() {
    if (!this.form.nomFichier) {
      alert("Aucun document associé / No document associated");
      return;
    }

    if (!this.form.id) {
      alert("ID du module carrière manquant / Career module ID missing");
      return;
    }

    // Le backend attend l'ID du module carrière, pas du militaire
    const url = `${environment.apiUrl}/api/carriere/${this.form.id}/fichier`;
    this.documentUrl = this.sanitizer.bypassSecurityTrustResourceUrl(url);
    this.afficherDocument = true;
  }

  fermerDocument() {
    this.afficherDocument = false;
    this.documentUrl = null;
  }

  // ==========================================
  // UTILITAIRES
  // ==========================================

  armesDisponibles(): string[] {
    const armes: Record<string, string[]> = {
      AA: ['Aviation', 'Défense aérienne'],
      AM: ['Infanterie de marine', 'Navigation'],
      AT: ['Infanterie', 'Artillerie', 'Cavalerie', 'Génie Militaire', 'Transmissions', 'Logistique', 'Matérielle', 'Ressources Humaines', 'Commissariat'],
      GN: ['Gendarmerie Territoriale', 'Gendarmerie Mobile', 'Services Techniques']
    };

    return armes[this.form.corps] || [];
  }

  onCorpsChange() {
    this.form.arme = '';
  }

  formatDate(date: string): string {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR');
  }
}
