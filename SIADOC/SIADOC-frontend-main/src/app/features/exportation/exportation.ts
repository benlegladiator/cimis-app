import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ExportDataService } from '../../core/services/export.service';

@Component({
  selector: 'app-exportation',
  templateUrl: './exportation.html',
  styleUrls: ['./exportation.scss'],
  standalone: true,
  imports: [CommonModule, FormsModule]
})
export class Exportation implements OnInit {
  private exportService = inject(ExportDataService);

  activeTab: 'export' | 'import' | 'correspondances' | 'api' = 'export';

  // --- Export ---
  matriculeSearch = '';
  exportResult: any = null;
  exportLoading = false;
  exportError: string | null = null;

  // --- Import ---
  importFile: File | null = null;
  importPreview: any[] | null = null;   // toujours un tableau
  importLoading = false;
  importResult: any = null;
  importError: string | null = null;

  // --- Non affectés ---
  nonAffectes: any[] = [];
  nonAffectesLoading = false;
  selectedMilitaires = new Set<string>();
  bulkCompagnieId = '';

  // --- Correspondances ---
  mappings: any[] = [];
  compagnies: any[] = [];
  compagnieSearchText = '';
  showCompagnieDropdown = false;
  newCodeGesmil = '';
  newCompagnieId = '';

  // --- API Keys ---
  apiKeys: any[] = [];
  newAppName = '';

  ngOnInit() {
    this.loadKeys();
    this.loadMappings();
    this.loadCompagnies();
    this.loadNonAffectes();
  }

  get filteredCompagnies() {
    const filter = this.compagnieSearchText.toLowerCase();
    return this.compagnies.filter(c => 
      c.nom?.toLowerCase().includes(filter) || 
      c.abreviation?.toLowerCase().includes(filter)
    );
  }

  selectCompagnie(c: any, type: 'bulk' | 'new') {
    if (type === 'bulk') {
      this.bulkCompagnieId = c.id;
      this.compagnieSearchText = c.nom;
    } else {
      this.newCompagnieId = c.id;
      this.compagnieSearchText = c.nom;
    }
    this.showCompagnieDropdown = false;
  }

  setTab(tab: 'export' | 'import' | 'correspondances' | 'api') {
    this.activeTab = tab;
  }

  // ──────────── EXPORT ────────────────────────────────────────────────────────

  searchDossier() {
    if (!this.matriculeSearch) return;
    this.exportLoading = true;
    this.exportError = null;
    this.exportResult = null;
    this.exportService.getFullDossier(this.matriculeSearch).subscribe({
      next: (data) => { this.exportResult = data; this.exportLoading = false; },
      error: ()     => { this.exportError = 'Militaire non trouvé.'; this.exportLoading = false; }
    });
  }

  downloadJson() {
    if (!this.exportResult) return;
    const blob = new Blob([JSON.stringify(this.exportResult, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `dossier_${this.exportResult.matriculeSolde}.json`;
    a.click();
    URL.revokeObjectURL(url);
  }

  // ──────────── IMPORT EN LOT ──────────────────────────────────────────────────

  onFileSelected(event: any) {
    const file = event.target.files[0];
    if (!file) return;
    this.importFile = file;
    this.importResult = null;
    this.importError = null;
    const reader = new FileReader();
    reader.onload = (e: any) => {
      try {
        const parsed = JSON.parse(e.target.result);
        // Accepte un objet unique OU un tableau
        this.importPreview = Array.isArray(parsed) ? parsed : [parsed];
      } catch {
        this.importError = 'Fichier JSON invalide.';
        this.importPreview = null;
      }
    };
    reader.readAsText(file);
  }

  processImport() {
    if (!this.importPreview || this.importPreview.length === 0) return;
    this.importLoading = true;
    this.importError = null;
    const obs = this.importPreview.length === 1
      ? this.exportService.importDossier(this.importPreview[0])
      : this.exportService.importBulk(this.importPreview);

    obs.subscribe({
      next: (res) => {
        this.importResult = res;
        this.importLoading = false;
        this.importFile = null;
        this.importPreview = null;
        this.loadNonAffectes(); // Rafraîchir le pool
      },
      error: () => { this.importError = "Erreur lors de l'importation."; this.importLoading = false; }
    });
  }

  // ──────────── NON AFFECTÉS ───────────────────────────────────────────────────

  loadNonAffectes() {
    this.nonAffectesLoading = true;
    this.exportService.getNonAffectes().subscribe({
      next: (list) => { 
        this.nonAffectes = list; 
        this.nonAffectesLoading = false; 
        this.selectedMilitaires.clear(); 
      },
      error: ()     => { this.nonAffectesLoading = false; }
    });
  }

  toggleSelection(mId: string) {
    if (this.selectedMilitaires.has(mId)) this.selectedMilitaires.delete(mId);
    else this.selectedMilitaires.add(mId);
  }

  isSelected(mId: string): boolean {
    return this.selectedMilitaires.has(mId);
  }

  assignSelection() {
    if (this.selectedMilitaires.size === 0 || !this.bulkCompagnieId) return;
    
    const ids = Array.from(this.selectedMilitaires);
    this.exportService.assignCompany(ids, this.bulkCompagnieId).subscribe({
      next: () => {
        alert(`${ids.length} militaire(s) affecté(s) avec succès.`);
        this.bulkCompagnieId = '';
        this.loadNonAffectes();
      },
      error: (err) => alert("Erreur d'affectation : " + (err.error || err.message))
    });
  }

  // ──────────── CORRESPONDANCES ────────────────────────────────────────────────

  loadMappings() {
    this.exportService.getMappings().subscribe(m => this.mappings = m);
  }

  loadCompagnies() {
    this.exportService.getCompagnies().subscribe(c => this.compagnies = c);
  }

  addMapping() {
    if (!this.newCodeGesmil || !this.newCompagnieId) return;
    this.exportService.createMapping(this.newCodeGesmil, this.newCompagnieId).subscribe({
      next: () => { this.newCodeGesmil = ''; this.newCompagnieId = ''; this.loadMappings(); },
      error: (err) => alert('Erreur : ' + (err.error || err.message))
    });
  }

  deleteMapping(id: string) {
    if (confirm('Supprimer ce mapping ?')) {
      this.exportService.deleteMapping(id).subscribe(() => this.loadMappings());
    }
  }

  // ──────────── API KEYS ────────────────────────────────────────────────────────

  loadKeys() {
    this.exportService.getKeys().subscribe(k => this.apiKeys = k);
  }

  generateKey() {
    if (!this.newAppName) return;
    this.exportService.createKey(this.newAppName).subscribe(() => {
      this.newAppName = '';
      this.loadKeys();
    });
  }

  deleteKey(id: string) {
    if (confirm("Voulez-vous vraiment supprimer cette clé ? L'application associée n'aura plus accès.")) {
      this.exportService.deleteKey(id).subscribe(() => this.loadKeys());
    }
  }

  copyToClipboard(text: string) {
    navigator.clipboard.writeText(text).then(() => alert('Clé copiée !'));
  }
}
