import { environment } from '@env/environment';
import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { HierarchyService } from '../../../core/hierarchy.service';
import { Role } from '../../../core/models';

@Component({
  selector: 'app-gestion-utilisateurs',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './gestion-utilisateurs.html',
  styleUrls: ['./gestion-utilisateurs.scss']
})
export class GestionUtilisateurs implements OnInit {

  API = `${environment.apiUrl}/api`;

  utilisateurs: any[] = [];
  secteurs: any[] = [];
  regions: any[] = [];
  brigades: any[] = [];
  bataillons: any[] = [];
  compagnies: any[] = [];
  
  filteredBrigades: any[] = [];
  filteredBataillons: any[] = [];
  filteredCompagnies: any[] = [];

  roles = Object.values(Role);
  searchTerm = '';

  form: any = {
    username: '',
    password: '',
    role: '',
    secteurId: '',
    regionId: '',
    brigadeId: '',
    bataillonId: '',
    compagnieId: ''
  };

  constructor(
    private http: HttpClient,
    private hierarchyService: HierarchyService
  ) {}

  ngOnInit() {
    this.chargerUtilisateurs();
    this.chargerSecteurs();
    this.chargerHierarchy();
  }

  chargerUtilisateurs() {
    this.http.get<any[]>(`${this.API}/utilisateurs`, { withCredentials: true })
      .subscribe(data => this.utilisateurs = data);
  }

  chargerSecteurs() {
    this.http.get<any[]>(`${this.API}/secteurs`, { withCredentials: true })
      .subscribe(data => this.secteurs = data);
  }

  chargerHierarchy() {
    this.hierarchyService.getRMIA().subscribe(data => this.regions = data);
    this.hierarchyService.getBrigades().subscribe(data => this.brigades = data);
    this.hierarchyService.getBataillons().subscribe(data => this.bataillons = data);
    this.hierarchyService.getCompagnies().subscribe(data => this.compagnies = data);
  }

  onRegionChange() {
    this.form.brigadeId = '';
    this.form.bataillonId = '';
    this.form.compagnieId = '';
    this.filteredBrigades = this.brigades.filter(b => b.regionId && b.regionId.toString() === this.form.regionId.toString());
    this.filteredBataillons = [];
    this.filteredCompagnies = [];
  }

  onBrigadeChange() {
    this.form.bataillonId = '';
    this.form.compagnieId = '';
    this.filteredBataillons = this.bataillons.filter(bat => bat.brigadeId && bat.brigadeId.toString() === this.form.brigadeId.toString());
    this.filteredCompagnies = [];
  }

  onBataillonChange() {
    this.form.compagnieId = '';
    this.filteredCompagnies = this.compagnies.filter(c => 
      c.bataillonId && c.bataillonId.toString() === this.form.bataillonId.toString()
    );
  }

  get filteredUtilisateurs() {
    if (!this.searchTerm) return this.utilisateurs;
    return this.utilisateurs.filter(u => 
      u.username.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
      u.role.toLowerCase().includes(this.searchTerm.toLowerCase())
    );
  }

  importHierarchy() {
    if (confirm('Voulez-vous vraiment importer la hiérarchie depuis Excel ?')) {
      this.hierarchyService.importHierarchy().subscribe({
        next: () => {
          alert('Importation réussie ✅');
          this.chargerHierarchy();
        },
        error: () => alert('Erreur lors de l\'importation')
      });
    }
  }

  creerUtilisateur() {
    const payload = { ...this.form };
    
    // Clean payload based on role if needed (optional but cleaner)
    this.http.post(`${this.API}/utilisateurs`, payload, { withCredentials: true })
      .subscribe({
        next: () => {
          alert('Utilisateur créé ✅');
          this.resetForm();
          this.chargerUtilisateurs();
        },
        error: () => alert('Erreur création utilisateur')
      });
  }

  supprimerUtilisateur(id: string) {
    if (confirm('Voulez-vous vraiment supprimer cet utilisateur ?')) {
      this.http.delete(`${this.API}/utilisateurs/${id}`, { withCredentials: true })
        .subscribe({
          next: () => {
            this.chargerUtilisateurs();
          },
          error: (err) => {
            console.error(err);
            alert("Erreur lors de la suppression de l'utilisateur. Vérifiez les droits ou rechargez la page.");
          }
        });
    }
  }

  resetForm() {
    this.form = {
      username: '',
      password: '',
      role: '',
      secteurId: '',
      regionId: '',
      brigadeId: '',
      bataillonId: '',
      compagnieId: ''
    };
  }
}
