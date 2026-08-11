import { environment } from '@env/environment';
import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { debounceTime, distinctUntilChanged, switchMap } from 'rxjs/operators';
import { GradeService } from '../../../core/grade.service';

@Component({
  selector: 'app-archives',
  standalone: true,
  imports: [CommonModule, FormsModule, ReactiveFormsModule],
  templateUrl: './archives.html',
  styleUrls: ['./archives.scss']
})
export class Archives implements OnInit {

  // Recherche
  resultats: any[] = [];
  loading = false;
  searchControl = new FormControl('');

  // Navigation
  // archivesGroupes[armee][contingent][categorie] = dossiers[]
  archivesGroupes: { [armee: string]: { [cont: string]: { [cat: string]: any[] } } } = {};
  
  // Ouvertures
  armeeOuverte: string | null = null;
  contOuvert: { [armee: string]: string | null } = {};
  catOuverte: { [armee: string]: { [cont: string]: string | null } } = {};

  // Listes ordonnées
  armeesList: string[] = ['Gendarmerie', 'Armée de Terre', "Armée de l'Air", 'Marine Nationale'];
  categoriesList = ['OFFICIER', 'SOUS_OFFICIER', 'MILITAIRE_RANG'];
  contingentsList: { [armee: string]: string[] } = {};

  archivesPhysiques: any[] = [];
  loadingPhysiques = true;

  // Formulaire nouvelle archive
  showForm = false;
  savingArchive = false;
  newArchive = {
    nom: '',
    prenom: '',
    matricule: '',
    armee: '',
    grade: '',
    numeroCase: '',
    anneeContingent: null as number | null
  };

  // Grades pour le formulaire (venant du service)
  gradesArmee: { [key: string]: string[] } = {};

  constructor(
    private http: HttpClient,
    private router: Router,
    private gradeService: GradeService
  ) {}

  ngOnInit() {
    this.gradeService.getGradesParArmee().subscribe(gs => this.gradesArmee = gs);
    
    // Recherche auto
    this.searchControl.valueChanges
      .pipe(
        debounceTime(300),
        distinctUntilChanged(),
        switchMap(value => {
          if (!value || value.trim().length < 2) {
            this.resultats = [];
            return [];
          }
          return this.http.get<any[]>(
            `${environment.apiUrl}/api/archives/search`,
            { params: { search: value } }
          );
        })
      )
      .subscribe(res => {
        this.resultats = res;
      });

    this.chargerArchivesPhysiques();
  }

  getContingentLabel(annee: number | null | undefined): string {
    if (!annee || annee < 1940 || annee > 2030) return 'Non classé';
    const debut = Math.floor(annee / 10) * 10;
    return `${debut}-${debut + 9}`;
  }

  formatCategorieLabel(cat: string): string {
    if (cat === 'OFFICIER') return 'Officiers';
    if (cat === 'SOUS_OFFICIER') return 'Sous-Officiers';
    if (cat === 'MILITAIRE_RANG') return 'Militaires de Rang';
    return cat || 'Inconnue';
  }

  chargerArchivesPhysiques() {
    this.loadingPhysiques = true;
    this.http.get<any[]>(`${environment.apiUrl}/api/archives-physiques`)
      .subscribe({
        next: (data) => {
          this.archivesPhysiques = data;
          
          // Initialisation
          this.archivesGroupes = {};
          this.contingentsList = {};
          this.contOuvert = {};
          this.catOuverte = {};

          this.armeesList.forEach(armee => {
            this.archivesGroupes[armee] = {};
            this.contingentsList[armee] = [];
            this.contOuvert[armee] = null;
            this.catOuverte[armee] = {};
          });

          // Remplissage
          data.forEach(item => {
            let armee = item.armee || 'Armée de Terre';
            if (!this.armeesList.includes(armee)) armee = 'Armée de Terre';
            
            const cont = this.getContingentLabel(item.anneeContingent);
            
            let cat = item.categorie || 'MILITAIRE_RANG';
            if (!this.categoriesList.includes(cat)) cat = 'MILITAIRE_RANG';

            if (!this.archivesGroupes[armee][cont]) {
              this.archivesGroupes[armee][cont] = {};
              this.catOuverte[armee][cont] = null;
            }
            if (!this.archivesGroupes[armee][cont][cat]) {
              this.archivesGroupes[armee][cont][cat] = [];
            }
            this.archivesGroupes[armee][cont][cat].push(item);
          });

          // Trier les contingents
          this.armeesList.forEach(armee => {
            const keys = Object.keys(this.archivesGroupes[armee]);
            keys.sort((a, b) => {
              if (a === 'Non classé') return 1;
              if (b === 'Non classé') return -1;
              return parseInt(a) - parseInt(b);
            });
            this.contingentsList[armee] = keys;
          });

          this.loadingPhysiques = false;
        },
        error: () => { this.loadingPhysiques = false; }
      });
  }

  toggleArmee(armee: string) {
    this.armeeOuverte = (this.armeeOuverte === armee) ? null : armee;
  }

  toggleCont(armee: string, cont: string) {
    this.contOuvert[armee] = (this.contOuvert[armee] === cont) ? null : cont;
  }

  toggleCat(armee: string, cont: string, cat: string) {
    this.catOuverte[armee][cont] = (this.catOuverte[armee][cont] === cat) ? null : cat;
  }

  getArmeeClass(a: string): string {
    if (a.includes('Terre')) return 'terre';
    if (a.includes('Air')) return 'air';
    if (a.includes('Marine')) return 'marine';
    if (a.includes('Gendarmerie')) return 'gn';
    return 'default';
  }

  getCount(armee: string, cont?: string, cat?: string): number {
    if (cat && cont) return this.archivesGroupes[armee]?.[cont]?.[cat]?.length ?? 0;
    if (cont) {
      let sum = 0;
      Object.values(this.archivesGroupes[armee]?.[cont] ?? {}).forEach(arr => sum += arr.length);
      return sum;
    }
    let total = 0;
    Object.values(this.archivesGroupes[armee] ?? {}).forEach(contObj => {
      Object.values(contObj).forEach(arr => total += arr.length);
    });
    return total;
  }

  get gradesDisponibles(): string[] {
    if (!this.newArchive.armee) return [];
    let key = this.newArchive.armee;
    if (key === 'Armée de Terre') key = 'AT';
    if (key === "Armée de l'Air") key = 'AA';
    if (key === 'Marine Nationale') key = 'AM';
    if (key === 'Gendarmerie') key = 'GN';
    return this.gradesArmee[key] || [];
  }

  // =========================
  // RECHERCHE
  // =========================
  rechercher() {
    const value = this.searchControl.value;
    let params: any = {};
    if (value && value.trim() !== '') {
      params.search = value;
    }
    this.loading = true;
    this.http.get<any[]>(`${environment.apiUrl}/api/archives/search`, { params })
      .subscribe({
        next: data => { this.resultats = data; this.loading = false; },
        error: () => { this.loading = false; }
      });
  }

  // =========================
  // OUVRIR DOSSIER
  // =========================
  ouvrir(r: any) {
    this.router.navigate(['/archives', r.militaireId]);
  }

  // =========================
  // FORMULAIRE AJOUT
  // =========================
  ajouterArchive() {
    this.showForm = !this.showForm;
    if (!this.showForm) {
      this.newArchive = { nom: '', prenom: '', matricule: '', armee: '', grade: '', numeroCase: '', anneeContingent: null };
    }
  }

  saveArchive() {
    if (!this.newArchive.nom || !this.newArchive.prenom || !this.newArchive.matricule || !this.newArchive.armee || !this.newArchive.grade) {
      alert('Veuillez remplir tous les champs obligatoires (Nom, Prénom, Matricule, Armée et Grade).');
      return;
    }

    this.savingArchive = true;
    this.http.post<any>(`${environment.apiUrl}/api/archives-physiques`, this.newArchive)
      .subscribe({
        next: (createdArchive) => {
          this.savingArchive = false;
          this.showForm = false;
          this.newArchive = { nom: '', prenom: '', matricule: '', armee: '', grade: '', numeroCase: '', anneeContingent: null };
          this.chargerArchivesPhysiques();
          if (createdArchive && createdArchive.militaireId) {
            this.router.navigate(['/archives', createdArchive.militaireId]);
          }
        },
        error: (err) => {
          this.savingArchive = false;
          console.error('Erreur lors de l\'enregistrement:', err);
          alert('Erreur lors de l\'enregistrement. Vérifiez que le serveur est bien lancé.');
        }
      });
  }
}