import { environment } from '@env/environment';
import { Component, Input, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { DomSanitizer } from '@angular/platform-browser';
import { CompagnieService } from '../../../../core/compagnie.service';

@Component({
  selector: 'app-mutation',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './mutation.html',
  styleUrls: ['./mutation.scss']
})
export class Mutation implements OnInit {

  @Input() militaireId!: string;
  @Input() readOnly: boolean = false;

  afficherFormulaire = false;
  allRMIA: any[] = [];
  allBrigades: any[] = [];
  allBataillons: any[] = [];
  allCompagnies: any[] = [];

  filteredBrigades: any[] = [];
  filteredBataillons: any[] = [];
  filteredCompagnies: any[] = [];

  selectedRMIA: string = '';
  selectedBrigade: string = '';
  selectedBataillon: string = '';
  
  affectations: any[] = [];
  fonctions: any[] = [];

  fichier: File | null = null;
  evenementSelectionne: any = null; // Unifié avec le HTML

  evenementForm = this.initForm();

  constructor(
    private http: HttpClient,
    private sanitizer: DomSanitizer,
    private compagnieService: CompagnieService
  ) {}

  ngOnInit() {
    if (this.militaireId) {
      this.chargerEvenements();
      this.compagnieService.listerRMIA().subscribe(data => this.allRMIA = data);
      this.compagnieService.listerBrigades().subscribe(data => this.allBrigades = data);
      this.compagnieService.listerBataillons().subscribe(data => this.allBataillons = data);
      this.compagnieService.lister().subscribe(data => this.allCompagnies = data);
    }
  }

  initForm() {
    return {
      type: 'AFFECTATION',
      numeroTexte: '',
      dateTexte: '',
      autoriteSignataire: '',
      emploi: '',
      compagnieId: '',
      unite: 'COMPAGNIE', // Valeur de l'enum par défaut pour affectation
      ville: null
    };
  }

  onRMIAChange() {
    this.selectedBrigade = '';
    this.selectedBataillon = '';
    this.evenementForm.compagnieId = '';
    this.filteredBrigades = this.allBrigades.filter(b => b.regionId === this.selectedRMIA);
    this.filteredBataillons = [];
    this.filteredCompagnies = [];
  }

  onBrigadeChange() {
    this.selectedBataillon = '';
    this.evenementForm.compagnieId = '';
    this.filteredBataillons = this.allBataillons.filter(b => b.brigadeId === this.selectedBrigade);
    this.filteredCompagnies = [];
  }

  onBataillonChange() {
    this.evenementForm.compagnieId = '';
    this.filteredCompagnies = this.allCompagnies.filter(c => c.bataillonId === this.selectedBataillon);
  }

  onCompagnieChange() {
    // Optionnel : on pourrait stocker des infos ici
  }

  // ================= ENREGISTREMENT =================

  enregistrerEvenement() {
    if (!this.militaireId) return;

    const formData = new FormData();
    const payload = { ...this.evenementForm };
    if (!payload.dateTexte) delete (payload as any).dateTexte;

    formData.append('data', JSON.stringify(payload));

    if (this.fichier) {
      formData.append('file', this.fichier);
    }

    this.http.post(
      `${environment.apiUrl}/api/mutations/${this.militaireId}`,
      formData
    ).subscribe({
      next: () => {
        alert("Événement enregistré avec succès ! / Event saved successfully ! ✅");
        this.afficherFormulaire = false;
        this.chargerEvenements();
        this.evenementForm = this.initForm();
        this.fichier = null;
      },
      error: err => {
        console.error(err);
        alert("Erreur lors de l'enregistrement / Error during saving ❌");
      }
    });
  }

  // ================= AUTRES MÉTHODES =================

  ajouterEvenement() {
    this.afficherFormulaire = true;
  }

  onFileSelected(event: any) {
    this.fichier = event.target.files[0];
  }

  chargerEvenements() {
    this.http.get(
      `${environment.apiUrl}/api/mutations/${this.militaireId}`,
      { withCredentials: true }
    ).subscribe((data: any) => {
      this.affectations = (data.affectations || []).map((a: any) => ({ ...a, type: 'AFFECTATION' }));
      this.fonctions = (data.fonctions || []).map((f: any) => ({ ...f, type: 'FONCTION' }));
    });
  }

  voirEvenement(e: any) {
    const url = `${environment.apiUrl}/api/mutations/item/${e.id}/document`;
    this.evenementSelectionne = {
      ...e,
      safeUrl: this.sanitizer.bypassSecurityTrustResourceUrl(url)
    };
  }

  fermerPreview() {
    this.evenementSelectionne = null;
  }
}