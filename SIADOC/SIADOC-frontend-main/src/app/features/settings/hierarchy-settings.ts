import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { environment } from '@env/environment';

@Component({
  selector: 'app-hierarchy-settings',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './hierarchy-settings.html',
  styleUrls: ['./hierarchy-settings.scss']
})
export class HierarchySettings implements OnInit {
  rmias: any[] = [];
  brigades: any[] = [];
  bataillons: any[] = [];
  compagnies: any[] = [];

  showModal = false;
  modalType: 'RMIA' | 'BRIGADE' | 'BATAILLON' | 'COMPAGNIE' | 'UNIT_AC' | 'UNIT_FS' = 'RMIA';
  parentId: string | null = null;
  parentName: string = '';
  
  newName: string = '';
  newLocalisation: string = '';

  activeTab: 'CT' | 'AC' | 'FS' = 'CT';
  
  // Accordion state tracker
  expandedNodes: { [id: string]: boolean } = {};
  
  // Données pour AC et FS
  acUnits: any[] = [];
  fsUnits: any[] = [];

  constructor(private http: HttpClient) {}

  ngOnInit(): void {
    this.chargerTout();
  }

  setTab(tab: 'CT' | 'AC' | 'FS') {
    this.activeTab = tab;
    this.chargerTout();
  }

  chargerTout() {
    if (this.activeTab === 'CT') {
      this.http.get<any[]>(`${environment.apiUrl}/api/region-militaires`).subscribe(data => this.rmias = data);
      this.http.get<any[]>(`${environment.apiUrl}/api/brigades`).subscribe(data => this.brigades = data);
      this.http.get<any[]>(`${environment.apiUrl}/api/bataillons`).subscribe(data => this.bataillons = data);
      this.http.get<any[]>(`${environment.apiUrl}/api/compagnies`).subscribe(data => this.compagnies = data);
    } else if (this.activeTab === 'AC') {
      // Endpoint temporaire pour AC
      this.http.get<any[]>(`${environment.apiUrl}/api/settings/hierarchy/ac`).subscribe(data => this.acUnits = data);
    } else if (this.activeTab === 'FS') {
      // Endpoint temporaire pour FS
      this.http.get<any[]>(`${environment.apiUrl}/api/settings/hierarchy/fs`).subscribe(data => this.fsUnits = data);
    }
  }

  toggleNode(id: string) {
    this.expandedNodes[id] = !this.expandedNodes[id];
  }

  isExpanded(id: string) {
    return !!this.expandedNodes[id];
  }


  getBrigadesByRMIA(rmiaId: string) {
    return this.brigades.filter(b => b.regionId === rmiaId);
  }

  getBataillonsByBrigade(brigadeId: string) {
    return this.bataillons.filter(b => b.brigadeId === brigadeId);
  }

  getCompagniesByBataillon(bataillonId: string) {
    return this.compagnies.filter(c => c.bataillonId === bataillonId);
  }

  ouvrirModal(type: any, parentId: string | null = null, parentName: string = '') {
    this.modalType = type;
    this.parentId = parentId;
    this.parentName = parentName;
    this.newName = '';
    this.newLocalisation = '';
    this.showModal = true;
  }

  fermerModal() {
    this.showModal = false;
  }

  enregistrer() {
    let url = `${environment.apiUrl}/api/settings/hierarchy`;
    let params: any = { nom: this.newName };

    if (this.modalType === 'RMIA') {
      this.http.post(`${url}/rmia`, { nom: this.newName }).subscribe(() => this.finEnregistrement());
    } else if (this.modalType === 'BRIGADE') {
      this.http.post(`${url}/brigade?nom=${this.newName}&regionId=${this.parentId}`, {}).subscribe(() => this.finEnregistrement());
    } else if (this.modalType === 'BATAILLON') {
      this.http.post(`${url}/bataillon?nom=${this.newName}&brigadeId=${this.parentId}`, {}).subscribe(() => this.finEnregistrement());
    } else if (this.modalType === 'COMPAGNIE') {
      this.http.post(`${url}/compagnie?nom=${this.newName}&bataillonId=${this.parentId}&localisation=${this.newLocalisation}`, {}).subscribe(() => this.finEnregistrement());
    } else if (this.modalType === 'UNIT_AC' || this.modalType === 'UNIT_FS') {
      const type = this.modalType === 'UNIT_AC' ? 'AC' : 'FS';
      this.http.post(`${url}/generic`, { nom: this.newName, type: type }).subscribe(() => this.finEnregistrement());
    }
  }

  finEnregistrement() {
    this.fermerModal();
    this.chargerTout();
  }

  supprimerUnite(id: string) {
    if (confirm('Voulez-vous vraiment supprimer cette unité ?')) {
      this.http.delete(`${environment.apiUrl}/api/settings/hierarchy/generic/${id}`).subscribe(() => this.chargerTout());
    }
  }


  supprimerCompagnie(id: string) {
    if (confirm('Voulez-vous vraiment supprimer cette compagnie ?')) {
      this.http.delete(`${environment.apiUrl}/api/settings/hierarchy/compagnie/${id}`).subscribe(() => this.chargerTout());
    }
  }
}
