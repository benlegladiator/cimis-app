import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, ActivatedRoute } from '@angular/router';
import { MilitaireService } from '../militaire';
import { CompagnieService } from '../../../core/compagnie.service';
import { GradeService } from '../../../core/grade.service';
import { Role } from '../../../core/models';
import { AuthService } from '../../../core/auth.service';

@Component({
  selector: 'app-form-militaire',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './form-militaire.html',
  styleUrls: ['./form-militaire.scss']
})
export class FormMilitaire implements OnInit {

  militaire = {
    nom: '',
    prenom: '',
    dateNaissance: '',
    matriculeMilitaire: '',
    matriculeSolde: '',
    grade: '',
    dateGrade: '',
    echelon: '',
    dateEchelon: '',
    dateService: '',
    armeService: '',
    statut: 'ADMINISTRATIF',
    lieuNaissance: '',
    sexe: ''
  };

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
  selectedCompagnieId: string = '';
  inferredHierarchy: any = null;

  searchTerm: string = '';
  filteredSuggestions: any[] = [];
  showSuggestions: boolean = false;

  photoFile: File | null = null;
  errorMessage = '';
  loading = false;
  user: any = null;
  Role = Role;

  gradesArmee: Record<string, string[]> = {};

  get gradesDisponibles(): string[] {
    if (!this.militaire.armeService) return [];
    return this.gradesArmee[this.militaire.armeService] || [];
  }

  onArmeChange() {
    this.militaire.grade = '';
  }

  onRMIAChange() {
    this.selectedBrigade = '';
    this.selectedBataillon = '';
    this.selectedCompagnieId = '';
    this.filteredBrigades = this.allBrigades.filter(b => b.regionId === this.selectedRMIA);
    this.filteredBataillons = [];
    this.filteredCompagnies = [];
  }

  onBrigadeChange() {
    this.selectedBataillon = '';
    this.selectedCompagnieId = '';
    this.filteredBataillons = this.allBataillons.filter(b => b.brigadeId === this.selectedBrigade);
    this.filteredCompagnies = [];
  }

  onBataillonChange() {
    this.selectedCompagnieId = '';
    this.inferredHierarchy = null;
    this.filteredCompagnies = this.allCompagnies.filter(c => c.bataillonId === this.selectedBataillon);
  }

  onCompagnieChange() {
    const comp = this.allCompagnies.find(c => c.id === this.selectedCompagnieId);
    if (comp) {
        this.inferredHierarchy = comp.hierarchy;
        this.searchTerm = comp.labelAffichage || comp.nom;
    } else {
        this.inferredHierarchy = null;
    }
  }

  onSearchChange() {
    if (this.allCompagnies.length === 0) {
      this.compagnieService.lister().subscribe(data => {
        this.allCompagnies = data;
        this.performSearch();
      });
    } else {
      this.performSearch();
    }
  }

  performSearch() {
    if (!this.searchTerm) {
      this.filteredSuggestions = this.allCompagnies.slice(0, 10);
      this.showSuggestions = true;
      return;
    }

    const term = this.searchTerm.toLowerCase();
    this.filteredSuggestions = this.allCompagnies.filter(c => 
      c.nom.toLowerCase().includes(term) || 
      (c.labelAffichage && c.labelAffichage.toLowerCase().includes(term))
    ).slice(0, 10);

    this.showSuggestions = this.filteredSuggestions.length > 0;
  }

  selectCompagnieAutocomplete(comp: any) {
    this.selectedCompagnieId = comp.id;
    this.inferredHierarchy = comp.hierarchy;
    this.searchTerm = comp.labelAffichage || comp.nom;
    this.showSuggestions = false;

    // Sync manual selects
    if (comp.hierarchy) {
      const rmia = this.allRMIA.find(r => r.nom === comp.hierarchy.rmia);
      if (rmia) {
        this.selectedRMIA = rmia.id;
        this.filteredBrigades = this.allBrigades.filter(b => b.regionId === rmia.id);
        const bri = this.allBrigades.find(b => b.nom === comp.hierarchy.brigade && b.regionId === rmia.id);
        if (bri) {
          this.selectedBrigade = bri.id;
          this.filteredBataillons = this.allBataillons.filter(bt => bt.brigadeId === bri.id);
          const bat = this.allBataillons.find(bt => bt.nom === comp.hierarchy.bataillon && bt.brigadeId === bri.id);
          if (bat) {
            this.selectedBataillon = bat.id;
            this.filteredCompagnies = this.allCompagnies.filter(c => c.bataillonId === bat.id);
          }
        }
      }
    }
  }

  constructor(
    private militaireService: MilitaireService,
    private compagnieService: CompagnieService,
    private gradeService: GradeService,
    private router: Router,
    private route: ActivatedRoute,
    private auth: AuthService
  ) {}

  ngOnInit() {
    this.user = this.auth.getUser();
    this.compagnieService.listerRMIA().subscribe(data => this.allRMIA = data);
    this.compagnieService.listerBrigades().subscribe(data => this.allBrigades = data);
    this.compagnieService.listerBataillons().subscribe(data => this.allBataillons = data);
    this.compagnieService.lister().subscribe(data => this.allCompagnies = data);
    
    this.gradeService.getGradesParArmee().subscribe(gs => this.gradesArmee = gs);

    this.route.queryParams.subscribe(params => {
      if (params['statut']) this.militaire.statut = params['statut'];
      if (params['etat']) (this.militaire as any).etat = params['etat'];
    });
  }

  onFileSelected(event: any) {
    this.photoFile = event.target.files[0];
  }

  enregistrer() {
    this.loading = true;
    this.errorMessage = '';

    const formData = new FormData();
    Object.keys(this.militaire).forEach(key => {
      const val = (this.militaire as any)[key];
      if (val) formData.append(key, val);
    });

    if (this.photoFile) formData.append('photo', this.photoFile);
    if (this.selectedCompagnieId) formData.append('compagnieId', this.selectedCompagnieId);

    this.militaireService.creer(formData).subscribe({
      next: (res) => {
        this.loading = false;
        this.router.navigate(['/dossier', res.id]);
      },
      error: (err) => {
        this.loading = false;
        this.errorMessage = "Erreur lors de la création du militaire.";
        console.error(err);
      }
    });
  }
}
