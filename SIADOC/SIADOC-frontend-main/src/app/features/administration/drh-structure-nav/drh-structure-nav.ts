import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { environment } from '@env/environment';
import { GENDARMERIE_DATA, FS_DATA, RMIA_DATA, ADMINISTRATION_CENTRALE_DATA, GP_DATA, CNSP_DATA, BIR_DATA } from './hierarchy-data';

interface Unit {
  id: string;
  label: string;
  description: string;
  icon: string;
  children?: Unit[];
}

@Component({
  selector: 'app-drh-structure-nav',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule],
  templateUrl: './drh-structure-nav.html',
  styleUrls: ['./drh-structure-nav.scss']
})
export class DrhStructureNav implements OnInit {
  
  fullHierarchy: Unit = {
    id: 'drh',
    label: 'DRH',
    description: 'Direction des Ressources Humaines',
    icon: 'fa-users-gear',
    children: [
      {
        id: 'ac',
        label: 'AC',
        description: 'Administration Centrale',
        icon: 'fa-building-columns',
        children: [...ADMINISTRATION_CENTRALE_DATA]
      },
      {
        id: 'ct',
        label: 'CT',
        description: 'Commandements Territoriaux',
        icon: 'fa-map-location-dot',
        children: [
          { id: 'rg', label: 'RG', description: 'Régions de Gendarmerie', icon: 'fa-map', children: [...GENDARMERIE_DATA] },
          { id: 'rmia', label: 'RMIA', description: 'Régions Militaires Inter-Armées', icon: 'fa-chess-rook', children: [...RMIA_DATA] }
        ]
      },
      {
        id: 'fs',
        label: 'FS',
        description: 'Formations Spécialisées',
        icon: 'fa-bolt-lightning',
        children: [
          { id: 'gp', label: 'GP', description: 'Garde Présidentielle', icon: 'fa-user-shield', children: [...GP_DATA] },
          { id: 'cnsp', label: 'CNSP', description: 'Sapeurs Pompiers', icon: 'fa-fire-extinguisher', children: [...CNSP_DATA] },
          { id: 'bir', label: 'BIR', description: "Bataillon d'Intervention Rapide", icon: 'fa-person-military-pointing', children: [...BIR_DATA] }
        ]
      }
    ]
  };

  currentUnit: Unit = this.fullHierarchy;
  history: Unit[] = [this.fullHierarchy];
  searchTerm: string = '';
  counts: { [key: string]: number } = {};

  constructor(private router: Router, private http: HttpClient) {}
  
  ngOnInit(): void {
    this.chargerCounts();
  }

  chargerCounts() {
    this.http.get<{ [key: string]: number }>(`${environment.apiUrl}/api/stats/unites-counts`)
      .subscribe({
        next: (res) => this.counts = res || {},
        error: (err) => console.error('Erreur chargement stats', err)
      });
  }

  getDossierCount(unit: Unit): number {
    if (this.counts[unit.label] !== undefined) {
      return this.counts[unit.label];
    }
    
    // Essayer de trouver une correspondance (ex: la DB a "EMIA - CCS EMIA", l'UI a "CCS EMIA")
    for (const key of Object.keys(this.counts)) {
      if (key.endsWith(' - ' + unit.label) || key.endsWith(' ' + unit.label)) {
         return this.counts[key];
      }
    }
    return 0;
  }

  getBadgeLabel(unit: Unit): string {
    const isInsideAC = this.history.some(h => h.id === 'ac') || unit.id === 'ac';
    if (isInsideAC) return 'structures';
    return this.history.length <= 2 ? 'formations' : 'unités';
  }

  getDisplayDescription(unit: Unit): string {
    const isInsideAC = this.history.some(h => h.id === 'ac') || unit.id === 'ac';
    if (isInsideAC) {
      if (unit.description.toLowerCase() === 'formation' || unit.description.toLowerCase() === 'unité' || unit.description.toLowerCase() === 'unite' || unit.description.toLowerCase() === 'bureau' || unit.description.toLowerCase() === 'compagnie') {
        return 'Structure';
      }
    }
    return unit.description;
  }

  /*
  chargerUnitesDynamiques() {
    ...
  }
  */

  mapApiToUnit(u: any): Unit {
    return {
      id: u.id,
      label: u.nom,
      description: u.description || '',
      icon: u.icon || 'fa-building',
      children: u.children ? u.children.map((c: any) => this.mapApiToUnit(c)) : []
    };
  }

  
  navigateTo(unit: Unit) {
    if (unit.children && unit.children.length > 0) {
      if (this.searchTerm) {
        // On reconstruie l'historique complet depuis la racine
        const path = this.findPathToUnit(unit.id, this.fullHierarchy, []);
        this.history = path ? path : [this.fullHierarchy, unit];
        this.currentUnit = unit;
        this.searchTerm = ''; // Effacer la recherche pour afficher les enfants de l'unité sélectionnée
      } else {
        this.currentUnit = unit;
        this.history.push(unit);
      }
    } else {
      let exactNom = unit.label;
      for (const key of Object.keys(this.counts)) {
        if (key === unit.label || key.endsWith(' - ' + unit.label) || key.endsWith(' ' + unit.label)) {
           exactNom = key;
           break;
        }
      }
      this.router.navigate(['/dossier/modifier'], { queryParams: { uniteNom: exactNom } });
    }
  }

  goBackTo(index: number) {
    this.history = this.history.slice(0, index + 1);
    this.currentUnit = this.history[index];
    this.searchTerm = ''; // Si on clique sur le breadcrumb, on annule la recherche
  }

  findPathToUnit(targetId: string, current: Unit, path: Unit[]): Unit[] | null {
    const newPath = [...path, current];
    if (current.id === targetId) {
      return newPath;
    }
    if (current.children) {
      for (const child of current.children) {
        const found = this.findPathToUnit(targetId, child, newPath);
        if (found) return found;
      }
    }
    return null;
  }

  searchGlobal(term: string, current: Unit, results: Unit[]) {
    // Ne pas inclure la racine dans les résultats si elle correspond
    if (current.id !== 'drh' && (current.label.toLowerCase().includes(term) || current.description.toLowerCase().includes(term))) {
      results.push(current);
    }
    if (current.children) {
      for (const child of current.children) {
        this.searchGlobal(term, child, results);
      }
    }
  }

  getFilteredUnits() {
    if (!this.searchTerm) return this.currentUnit.children || [];
    
    // Recherche globale
    const term = this.searchTerm.toLowerCase();
    const results: Unit[] = [];
    this.searchGlobal(term, this.fullHierarchy, results);
    return results;
  }
}

