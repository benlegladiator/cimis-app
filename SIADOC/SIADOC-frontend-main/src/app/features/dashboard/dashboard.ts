import { environment } from '@env/environment';
import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import Chart from 'chart.js/auto';
import { AuthService } from '../../core/auth.service';
import { NotificationService } from '../../core/notification.service';
import { MilitaireService } from '../militaires/militaire';
import { DashboardService as DashboardStatsService } from '../../core/dashboard-stats.service';
import { CompagnieService } from '../../core/compagnie.service';
import { SiadocNotification, Role } from '../../core/models';
import { Router } from '@angular/router';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, FormsModule], // Ensure FormsModule is imported for ngModel
  templateUrl: './dashboard.html',
  styleUrls: ['./dashboard.scss']
})
export class Dashboard implements OnInit {

  stats: any = null;
  nouvellesIntegrations: any[] = [];
  retraitesProches: any[] = [];
  recentTransfers: any[] = [];
  armeData: any[] = [];
  showTransfers = false;
  loading = true;

  armeChartInstance: Chart | null = null;
  categorieChartInstance: Chart | null = null;

  detailsBrigade: any[] = [];
  detailsBataillon: any[] = [];
  detailsCompagnie: any[] = [];
  detailsRMIA: any[] = [];
  statsCategorie: any[] = [];
  hommes = 0;
  femmes = 0;

  officiers = 0;
  sousOfficiers = 0;
  militairesRang = 0;

  notifications: SiadocNotification[] = [];
  user: any = null;
  Role = Role;

  allRMIA: any[] = [];
  allBrigades: any[] = [];
  allBataillons: any[] = [];
  allCompagnies: any[] = [];

  searchTerm: string = '';
  filteredSuggestions: any[] = [];
  showSuggestions: boolean = false;

  filteredBrigades: any[] = [];
  filteredBataillons: any[] = [];

  selectedRMIA: string = '';
  selectedBrigade: string = '';
  selectedBataillon: string = '';

  currentLevel: 'GLOBAL' | 'BRIGADE' | 'BATAILLON' | 'COMPAGNIE' = 'GLOBAL';
  selectedBrigadeId: string = '';
  selectedUnitName: string = '';
  selectedBrigadeName: string = '';
  selectedBataillonName: string = '';

  constructor(
    private http: HttpClient,
    private auth: AuthService,
    private notify: NotificationService,
    private militaireService: MilitaireService,
    private dashboardStatsService: DashboardStatsService,
    private compagnieService: CompagnieService,
    private router: Router
  ) { }

  ngOnInit(): void {
    this.user = this.auth.getUser();
    this.loadDashboard();
    if (this.user?.role === Role.COMMANDANT_COMPAGNIE) {
      this.loadNotifications();
    }
    if (this.user?.role === Role.DRH || this.isEtatMajor()) {
      this.loadHierarchy();
    }
    // RMIA: pre-load bataillons list for drill-down
    if (this.user?.role === Role.RMIA || this.user?.role === Role.BRIGADE) {
      this.compagnieService.listerBataillons().subscribe(data => this.allBataillons = data);
      this.compagnieService.listerBrigades().subscribe(data => this.allBrigades = data);
    }

    // Set initial unit name for display
    if (this.user?.role === Role.BATAILLON && this.user.bataillon) {
      this.selectedUnitName = this.user.bataillon.nom;
    } else if (this.user?.role === Role.BRIGADE && this.user.brigade) {
      this.selectedUnitName = this.user.brigade.nom;
    } else if (this.user?.role === Role.RMIA && this.user.region) {
      this.selectedUnitName = this.user.region.nom;
    }
  }

  loadHierarchy() {
    this.compagnieService.listerRMIA().subscribe(data => this.allRMIA = data);
    this.compagnieService.listerBrigades().subscribe(data => this.allBrigades = data);
    this.compagnieService.listerBataillons().subscribe(data => this.allBataillons = data);
    this.compagnieService.lister().subscribe(data => this.allCompagnies = data);
  }

  getFormattedRole(): string {
    if (!this.user?.role) return '';
    let role = '';
    if (this.user.role === Role.RMIA) role = 'COM_RMIA / REGION_COM';
    else if (this.user.role === Role.BRIGADE) role = 'COM_BRIGADE / BRIGADE_COM';
    else if (this.user.role === Role.BATAILLON) role = 'COM_BATAILLON / BATTALION_COM';
    else if (this.user.role === Role.COMMANDANT_COMPAGNIE) role = 'COM_COMPAGNIE / COMPANY_COM';
    else if (this.user.role === Role.ETAT_MAJOR_TERRE) role = 'EM_TERRE / ARMY_HQ';
    else if (this.user.role === Role.ETAT_MAJOR_AIR) role = 'EM_AIR / AIR_HQ';
    else if (this.user.role === Role.ETAT_MAJOR_MARINE) role = 'EM_MARINE / NAVY_HQ';
    else role = this.user.role;

    return role;
  }

  loadNotifications() {
    this.notify.getMaCompagnie().subscribe(data => {
      this.notifications = data;
    });
  }

  confirmerReception(n: SiadocNotification) {
    this.militaireService.recevoir(n.militaire.id).subscribe(() => {
      this.loadNotifications();
      alert("Dossier reçu avec succès !");
    });
  }

  loadDashboard() {
    this.loading = true;
    // Nettoyer les anciennes données pour éviter les mélanges visuels
    this.detailsBrigade = [];
    this.detailsBataillon = [];
    this.detailsCompagnie = [];
    this.stats = null;

    if (this.selectedBataillon) {
      this.dashboardStatsService.getBataillonStats(this.selectedBataillon).subscribe(data => this.processStats(data));
    } else if (this.selectedBrigade) {
      this.dashboardStatsService.getBrigadeStats(this.selectedBrigade).subscribe(data => this.processStats(data));
    } else if (this.selectedRMIA) {
      this.dashboardStatsService.getRmiaStats(this.selectedRMIA).subscribe(data => this.processStats(data));
    } else if (this.user?.role === Role.RMIA && this.user.region?.id) {
      this.dashboardStatsService.getRmiaStats(this.user.region.id).subscribe(data => this.processStats(data));
    } else if (this.user?.role === Role.BRIGADE && this.user.brigade?.id) {
      this.dashboardStatsService.getBrigadeStats(this.user.brigade.id).subscribe(data => this.processStats(data));
    } else if (this.user?.role === Role.BATAILLON && this.user.bataillon?.id) {
      this.dashboardStatsService.getBataillonStats(this.user.bataillon.id).subscribe(data => this.processStats(data));
    } else if (this.user?.role === Role.COMMANDANT_COMPAGNIE && this.user.compagnie?.id) {
      this.dashboardStatsService.getCompagnieStats(this.user.compagnie.id).subscribe(data => this.processStats(data));
    } else if (this.isEtatMajor()) {
      this.dashboardStatsService.getEtatMajorStats().subscribe(data => this.processStats(data));
    } else {
      this.loadGlobalDashboard();
    }

    if (this.user?.role === Role.DRH || this.user?.role === Role.BATAILLON || this.isEtatMajor()) {
      this.loadDrhWidgets();
    }
  }

  isEtatMajor(): boolean {
    if (!this.user?.role) return false;
    return this.user.role === Role.ETAT_MAJOR_TERRE ||
      this.user.role === Role.ETAT_MAJOR_AIR ||
      this.user.role === Role.ETAT_MAJOR_MARINE;
  }

  onRMIAFilterChange() {
    this.selectedBrigade = '';
    this.selectedBataillon = '';
    this.filteredBrigades = this.allBrigades.filter(b => b.regionId === this.selectedRMIA);
    this.filteredBataillons = [];
    this.searchTerm = '';
    this.loadDashboard();
  }

  onBrigadeFilterChange() {
    this.selectedBataillon = '';
    this.filteredBataillons = this.allBataillons.filter(b => b.brigadeId === this.selectedBrigade);
    this.searchTerm = '';
    this.loadDashboard();
  }

  onBataillonFilterChange() {
    if (this.selectedBataillon) {
      this.currentLevel = 'BATAILLON';
      const bat = this.allBataillons.find(b => b.id === this.selectedBataillon);
      if (bat) {
        this.selectedUnitName = bat.nom;
        this.searchTerm = bat.nom;
      }
    }
    this.loadDashboard();
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
    this.searchTerm = comp.labelAffichage || comp.nom;
    this.showSuggestions = false;

    // Sync manual selects if possible
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
          }
        }
      }
    }

    // Trigger dashboard load for this specific unit
    this.loading = true;
    this.currentLevel = 'COMPAGNIE';
    this.selectedUnitName = comp.nom;
    this.dashboardStatsService.getCompagnieStats(comp.id).subscribe({
      next: data => this.processStats(data),
      error: err => { console.error(err); this.loading = false; }
    });
  }

  selectRMIA(id: string, nom: string) {
    this.selectedRMIA = id;
    this.selectedUnitName = nom;
    this.currentLevel = 'GLOBAL'; // Still global but filtered by RMIA
    this.onRMIAFilterChange();
  }

  selectBrigade(id: string, nom: string) {
    this.selectedBrigade = id;
    this.selectedBrigadeId = id;
    this.selectedUnitName = nom;
    this.selectedBrigadeName = nom;
    this.currentLevel = 'BRIGADE';
    this.onBrigadeFilterChange();
  }

  selectBataillon(id: string, nom: string) {
    this.selectedBataillon = id;
    this.selectedUnitName = nom;
    this.selectedBataillonName = nom;
    this.currentLevel = 'BATAILLON';
    this.onBataillonFilterChange();
  }

  goBack() {
    if (this.currentLevel === 'BATAILLON') {
      this.resetToBrigade();
    } else if (this.currentLevel === 'BRIGADE' || this.selectedRMIA) {
      this.resetToRMIA();
    }
  }

  resetToRMIA() {
    this.selectedBrigade = '';
    this.selectedBrigadeId = '';
    this.selectedBrigadeName = '';
    this.selectedBataillon = '';
    this.selectedBataillonName = '';
    this.searchTerm = '';
    
    if (this.user?.role === Role.RMIA) {
      this.selectedRMIA = this.user.region?.id || '';
      this.selectedUnitName = this.user.region?.nom || 'RMIA';
    } else if (this.user?.role === Role.BRIGADE) {
      // Pour une brigade, resetToRMIA n'a pas vraiment de sens, on reste sur sa brigade
      this.selectedUnitName = this.user.brigade?.nom || 'Brigade';
    } else {
      this.selectedRMIA = '';
      this.selectedUnitName = 'GLOBAL';
    }
    
    this.currentLevel = 'GLOBAL';
    this.loadDashboard();
  }

  resetToBrigade() {
    this.selectedBataillon = '';
    this.selectedBataillonName = '';
    this.selectedUnitName = this.selectedBrigadeName || this.user?.brigade?.nom || 'Brigade';
    this.searchTerm = '';
    this.currentLevel = 'BRIGADE';
    
    // Si l'utilisateur est rôle BRIGADE, il retourne à son niveau de base (GLOBAL pour lui)
    if (this.user?.role === Role.BRIGADE) {
      this.currentLevel = 'GLOBAL';
    }
    
    this.loadDashboard();
  }

  loadGlobalDashboard() {
    this.dashboardStatsService.getDrhStats()
      .subscribe({
        next: (data: any) => this.processStats(data),
        error: err => {
          console.error(err);
          this.loading = false;
        }
      });
  }

  loadDrhWidgets() {
    this.http.get<any[]>(`${environment.apiUrl}/api/militaires/dashboard/nouvelles-integrations`)
      .subscribe({
        next: data => this.nouvellesIntegrations = data,
        error: err => console.error("Erreur nouvelles integrations", err)
      });

    this.http.get<any[]>(`${environment.apiUrl}/api/militaires/dashboard/retraites-proches`)
      .subscribe({
        next: data => this.retraitesProches = data,
        error: err => console.error("Erreur retraites proches", err)
      });
  }

  processStats(data: any) {
    // Formater et Agréger la casse de l'armeService (venu du backend)
    // Formater et Agréger la casse de l'armeService (venu du backend)
    if (data.repartitionArmes) {
      const aggMap = new Map<string, number>();
      const entries = Array.isArray(data.repartitionArmes)
        ? data.repartitionArmes.map((a: any) => [a.arme, a.total])
        : Object.entries(data.repartitionArmes);

      entries.forEach(([arme, total]: any) => {
        const armeFormattee = this.formatArme(arme);
        aggMap.set(armeFormattee, (aggMap.get(armeFormattee) || 0) + total);
      });

      data.repartitionArmes = Array.from(aggMap.entries()).map(([arme, total]) => ({
        arme,
        total
      }));
      this.armeData = data.repartitionArmes;
    }


    this.detailsCompagnie = this.parseChildDistribution(data.repartitionCompagnies || data.detailsCompagnie || []);
    this.recentTransfers = data.recentTransfers || [];

    if (data.repartitionBataillons) {
      this.detailsBataillon = this.parseChildDistribution(data.repartitionBataillons);
    } else {
      this.detailsBataillon = [];
    }

    if (data.repartitionRMIA) {
      this.detailsRMIA = this.parseChildDistribution(data.repartitionRMIA);
    } else {
      this.detailsRMIA = [];
    }

    if (data.repartitionBrigades) {
      this.detailsBrigade = this.parseChildDistribution(data.repartitionBrigades);
    } else {
      this.detailsBrigade = [];
    }

    if (data.repartitionCategories) {
      this.statsCategorie = data.repartitionCategories.map((row: any) => ({
        categorie: this.formatCategorieLabel(row[0]),
        total: row[1]
      }));
    }

    this.stats = data;
    
    // RÉCUPÉRATION DES VRAIS CHIFFRES DEPUIS LE BACKEND
    this.officiers = 0;
    this.sousOfficiers = 0;
    this.militairesRang = 0;
    this.hommes = 0;
    this.femmes = 0;

    if (data.repartitionCategories) {
      data.repartitionCategories.forEach((row: any) => {
        const cat = row[0];
        const count = row[1];
        if (cat === 'OFFICIER') this.officiers = count;
        else if (cat === 'SOUS_OFF_ICIER' || cat === 'SOUS_OFFICIER') this.sousOfficiers = count;
        else if (cat === 'MILITAIRE_RANG') this.militairesRang = count;
      });
    }

    if (data.repartitionSexes) {
      data.repartitionSexes.forEach((row: any) => {
        const sexe = (row[0] || '').toUpperCase();
        const count = row[1];
        if (sexe === 'M' || sexe === 'MASCULIN' || sexe === 'HOMME') this.hommes = count;
        else if (sexe === 'F' || sexe === 'FEMININ' || sexe === 'FEMME') this.femmes = count;
      });
    }

    this.loading = false;
    setTimeout(() => {
      this.initArmeChart();
      if (this.statsCategorie.length > 0 && this.user?.role === Role.BATAILLON) {
        this.initCategorieChart();
      }
    }, 100);
  }

  initArmeChart() {
    if (!this.stats?.repartitionArmes) return;
    this.createChart('armeChart', this.stats.repartitionArmes, 'arme');
  }


  initCategorieChart() {
    if (!this.statsCategorie || this.statsCategorie.length === 0) return;
    this.createChart('categorieChart', this.statsCategorie, 'categorie');
  }

  createChart(elementId: string, data: any[], labelKey: string) {
    const canvas = document.getElementById(elementId) as HTMLCanvasElement;
    if (!canvas) return;

    if (elementId === 'armeChart') {
      const order = ['Gendarmerie Nationale / Gendarmerie', 'Armée de Terre / Army', "Armée de l'Air / Air Force", 'Marine / Navy'];
      data.sort((a, b) => {
        const indexA = order.indexOf(a[labelKey]);
        const indexB = order.indexOf(b[labelKey]);
        // Si un élément n'est pas dans l'ordre, il passe à la fin
        return (indexA === -1 ? 99 : indexA) - (indexB === -1 ? 99 : indexB);
      });
    }

    const originalLabels = data.map((d: any) => d[labelKey]);
    const displayLabels = data.map((d: any) => `${d[labelKey]} (${d.total})`);
    const totals = data.map((d: any) => d.total);

    const mapCouleurs: Record<string, string> = {
      'Armée de Terre / Army': '#2e7d32',   
      "Armée de l'Air / Air Force": '#1976d2',  
      'Marine / Navy': '#1a202c',           
      'Gendarmerie Nationale / Gendarmerie': '#808080',      
    };

    // Destroy existing chart if it exists
    if (elementId === 'armeChart') {
      if (this.armeChartInstance) this.armeChartInstance.destroy();
    } else if (elementId === 'categorieChart') {
      if (this.categorieChartInstance) this.categorieChartInstance.destroy();
    }

    let defaultBgBase = ['#3b82f6', '#f59e0b', '#10b981', '#6366f1', '#8b5cf6'];
    
    // On utilise originalLabels pour mapper les couleurs correctement
    let backgroundColors = originalLabels.map((label: string, i: number) => {
      if (elementId === 'categorieChart') {
        if (label.includes('Officier')) return '#1e40af';
        if (label.includes('Sous-Officier')) return '#ea580c';
        if (label.includes('Militaires de Rang')) return '#16a34a';
      }
      return mapCouleurs[label] || defaultBgBase[i % defaultBgBase.length];
    });

    const chart = new Chart(canvas, {
      type: 'pie',
      data: {
        labels: displayLabels,
        datasets: [{
          data: totals,
          backgroundColor: backgroundColors
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { 
            position: 'bottom',
            labels: {
              font: {
                size: 13,
                weight: 'bold'
              },
              padding: 20
            }
          }
        }
      }
    });

    if (elementId === 'armeChart') this.armeChartInstance = chart;
    else if (elementId === 'categorieChart') this.categorieChartInstance = chart;
  }

  formatArme(armeBrute: string): string {
    if (!armeBrute) return 'Inconnu / Unknown';
    const formatted = armeBrute.trim().toLowerCase();
    if (formatted.includes('terre') || formatted === 'at') return 'Armée de Terre / Army';
    if (formatted.includes('air') || formatted === 'aa') return 'Armée de l\'Air / Air Force';
    if (formatted.includes('marine') || formatted === 'am') return 'Marine / Navy';
    if (formatted.includes('gend') || formatted === 'gn') return 'Gendarmerie Nationale / Gendarmerie';
    return armeBrute.charAt(0).toUpperCase() + armeBrute.slice(1).toLowerCase();
  }


  formatCategorieLabel(cat: string): string {
    if (cat === 'OFFICIER') return 'Officiers / Officers';
    if (cat === 'SOUS_OFF_ICIER' || cat === 'SOUS_OFFICIER') return 'Sous-Officiers / Sub-Officers';
    if (cat === 'MILITAIRE_RANG') return 'Militaires de Rang / Enlisted';
    return cat;
  }

  parseChildDistribution(rawData: any[]): any[] {
    const map = new Map<string, any>();
    rawData.forEach((row: any) => {
      // row[0] = ID, row[1] = NOM, row[2] = CORPS, row[3] = COUNT
      let id: string, nom: string, corps: string, count: number;

      if (!Array.isArray(row)) return;

      if (row.length === 4) {
        id = row[0];
        nom = row[1];
        corps = row[2];
        count = Number(row[3]);
      } else if (row.length === 3) {
        // En cas de colonne ID manquante : row[0]=NOM, row[1]=CORPS, row[2]=COUNT
        nom = row[0];
        corps = row[1];
        count = Number(row[2]);
        id = nom;
      } else {
        return;
      }

      if (!id) id = 'unknown-' + Math.random();
      if (!nom) nom = 'Unité Inconnue';
      if (isNaN(count)) count = 0;

      if (!map.has(id)) {
        map.set(id, {
          id,
          nom,
          total: 0,
          Terre: 0,
          Air: 0,
          Marine: 0,
          Gendarmerie: 0,
          Officiers: 0,
          SousOfficiers: 0,
          MilitairesRang: 0
        });
      }
      const mapEntry = map.get(id);
      mapEntry.total += count;

      // Correspondance des corps (AT/AA/AM/GN)
      const c = (corps || '').toUpperCase();
      if (c === 'AT' || c.includes('TERRE')) mapEntry.Terre += count;
      else if (c === 'AA' || c.includes('AIR')) mapEntry.Air += count;
      else if (c === 'AM' || c.includes('MARINE')) mapEntry.Marine += count;
      else if (c === 'GN' || c.includes('GEND')) mapEntry.Gendarmerie += count;
      // Correspondance des catégories (pour EM)
      else if (c === 'OFFICIER') mapEntry.Officiers += count;
      else if (c === 'SOUS_OFFICIER' || c === 'SOUS_OFF_ICIER') mapEntry.SousOfficiers += count;
      else if (c === 'MILITAIRE_RANG') mapEntry.MilitairesRang += count;
    });
    return Array.from(map.values()).sort((a: any, b: any) => a.nom.localeCompare(b.nom));
  }

  calculateAge(birthDate: any): number {
    if (!birthDate) return 0;
    const birth = new Date(birthDate);
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
      age--;
    }
    return age;
  }

  getSum(list: any[], key: string): number {
    if (!list) return 0;
    return list.reduce((sum, item) => sum + (item[key] || 0), 0);
  }

  getGlobalSum(key: string): number {
    let activeList: any[] = [];
    if (this.currentLevel === 'GLOBAL' && (this.user?.role === this.Role.DRH || this.isEtatMajor()) && !this.selectedRMIA) {
      activeList = this.detailsRMIA;
    } else if (this.currentLevel === 'GLOBAL' && (this.selectedRMIA || this.user?.role === this.Role.RMIA)) {
      activeList = this.detailsBrigade;
    } else if (this.currentLevel === 'BRIGADE' || (this.user?.role === this.Role.BRIGADE && this.currentLevel === 'GLOBAL') || (this.isEtatMajor() && this.currentLevel === 'GLOBAL' && this.selectedBrigade)) {
      activeList = this.detailsBataillon;
    } else if (this.currentLevel === 'BATAILLON' || this.user?.role === this.Role.BATAILLON) {
      activeList = this.detailsCompagnie;
    }
    return this.getSum(activeList, key);
  }



  retirer(m: any) {
    const confirmation = confirm(`Souhaitez-vous mettre en retraite le militaire ${m.nom} ${m.prenom} ?`);
    if (!confirmation) return;

    this.http.post(`${environment.apiUrl}/api/militaires/${m.id}/retraite`, {})
      .subscribe({
        next: () => {
          alert("Militaire mis en retraite ! ✅");
          this.loadDashboard();
          this.loadDrhWidgets();
        },
        error: err => alert("Erreur lors de la mise en retraite")
      });
  }
}