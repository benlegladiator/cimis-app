import { environment } from '@env/environment';
import { Component, Input, OnInit, OnChanges, SimpleChanges } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { DomSanitizer } from '@angular/platform-browser';
import { GradeService } from '../../../../core/grade.service';

@Component({
  selector: 'app-avancement',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './avancement.html',
  styleUrls: ['./avancement.scss']
})
export class Avancement implements OnInit, OnChanges {

  @Input() avancementModuleId!: string;
  @Input() militaire!: any;
  @Input() readOnly: boolean = false;

  afficherMiniFormulaire = false;
  avancementsList: any[] = [];
  filtreTexte = '';
  avancementSelectionne: any = null;
  nouvelAvancement = this.initForm();
  fichierSelectionne: File | null = null;

  // Nomenclature groupée
  gradesGroupes: Record<string, Record<string, string[]>> = {};
  categoriesAffichees: { label: string, grades: string[] }[] = [];

  constructor(
    private http: HttpClient, 
    private sanitizer: DomSanitizer,
    private gradeService: GradeService
  ) {}

  ngOnInit() {
    if (!this.avancementModuleId) return;

    this.chargerAvancements();
    
    // Charger la nomenclature groupée
    this.gradeService.getGradesGroupes().subscribe(data => {
      this.gradesGroupes = data;
      this.calculerCategories();
    });
  }

  ngOnChanges(changes: SimpleChanges) {
    if (changes['militaire']) {
      this.calculerCategories();
    }
  }

  calculerCategories() {
    if (!this.gradesGroupes || Object.keys(this.gradesGroupes).length === 0) {
      this.categoriesAffichees = [];
      return;
    }
    
    // On réunit TOUT sans distinction d'armée
    let tousOfficiers: string[] = [];
    let tousSousOfficiers: string[] = [];
    let tousMdr: string[] = [];

    Object.values(this.gradesGroupes).forEach(armee => {
      if (armee['OFFICIERS']) tousOfficiers = [...tousOfficiers, ...armee['OFFICIERS']];
      if (armee['SOUS_OFFICIERS']) tousSousOfficiers = [...tousSousOfficiers, ...armee['SOUS_OFFICIERS']];
      if (armee['MILITAIRES_DU_RANG']) tousMdr = [...tousMdr, ...armee['MILITAIRES_DU_RANG']];
    });

    this.categoriesAffichees = [
      { label: 'Officiers / Officers', grades: [...new Set(tousOfficiers)] },
      { label: 'Sous-Officiers / Non-Commissioned Officers', grades: [...new Set(tousSousOfficiers)] },
      { label: 'Militaires du Rang / Enlisted', grades: [...new Set(tousMdr)] }
    ].filter(c => c.grades.length > 0);
  }

  // Pour le template, on utilise la propriété calculée
  get categoriesDisponibles() {
    return this.categoriesAffichees;
  }

  initForm() {
    return {
      typeAvancement: 'AVANCEMENT',
      avancement: '',
      numeroTexte: '',
      signataire: '',
      dateEffet: '',
      dureeAnnees: 1
    };
  }

  toggleMiniForm() {
    this.afficherMiniFormulaire = !this.afficherMiniFormulaire;
  }

  showMiniForm() {
    return this.afficherMiniFormulaire;
  }

  annulerAjout() {
    this.afficherMiniFormulaire = false;
    this.nouvelAvancement = this.initForm();
    this.fichierSelectionne = null;
  }

  onFileSelected(event: any) {
    this.fichierSelectionne = event.target.files[0];
  }

  enregistrerAvancement() {
    const formData = new FormData();
    formData.append('moduleId', this.avancementModuleId);
    formData.append('typeAvancement', this.nouvelAvancement.typeAvancement);
    formData.append('avancement', this.nouvelAvancement.avancement);
    formData.append('numeroTexte', this.nouvelAvancement.numeroTexte);
    formData.append('signataire', this.nouvelAvancement.signataire);
    formData.append('dateEffet', this.nouvelAvancement.dateEffet);

    if (this.nouvelAvancement.typeAvancement === 'PROLONGATION_SERVICE') {
      formData.append('dureeAnnees', this.nouvelAvancement.dureeAnnees.toString());
    }

    if (this.fichierSelectionne) {
      formData.append('fichier', this.fichierSelectionne);
    }

    this.http.post(`${environment.apiUrl}/api/avancement`, formData).subscribe({
      next: () => {
        this.annulerAjout();
        this.chargerAvancements();
      },
      error: () => alert("Erreur lors de l'enregistrement / Error while saving")
    });
  }

  chargerAvancements() {
    this.http.get<any[]>(`${environment.apiUrl}/api/avancement/module/${this.avancementModuleId}`).subscribe({
      next: (data) => this.avancementsList = data
    });
  }

  get avancementsFiltres() {
    if (!this.filtreTexte) return this.avancementsList;
    const search = this.filtreTexte.toLowerCase();
    return this.avancementsList.filter(a =>
      a.avancement?.toLowerCase().includes(search) ||
      a.signataire?.toLowerCase().includes(search) ||
      a.numeroTexte?.toLowerCase().includes(search)
    );
  }

  voirAvancement(av: any) {
    this.avancementSelectionne = av;
    this.avancementSelectionne.safeUrl = this.sanitizer.bypassSecurityTrustResourceUrl(this.getRawUrl(av));
  }

  getRawUrl(av: any): string {
    return `${environment.apiUrl}/api/avancement/${av.id}/fichier`;
  }
}