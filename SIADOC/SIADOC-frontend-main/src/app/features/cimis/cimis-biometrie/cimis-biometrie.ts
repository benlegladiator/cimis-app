import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { CimisService, DonneeBiometriqueReponse } from '../../../core/services/cimis.service';

@Component({
  selector: 'app-cimis-biometrie',
  templateUrl: './cimis-biometrie.html',
  styleUrls: ['./cimis-biometrie.scss'],
  standalone: true,
  imports: [CommonModule, FormsModule]
})
export class CimisBiometrie implements OnInit {
  private cimisService = inject(CimisService);
  private route = inject(ActivatedRoute);

  // ── Recherche ──────────────────────────────────────────────────
  matriculeSearch = '';
  searchLoading   = false;
  searchError: string | null = null;

  // ── Résultat ───────────────────────────────────────────────────
  result: DonneeBiometriqueReponse | null = null;
  directResult: any | null = null;

  // ── Affichage empreintes ───────────────────────────────────────
  showEmpreinte1 = false;
  showEmpreinte2 = false;

  ngOnInit(): void {
    // Vérifier si un matricule est passé en paramètre d'URL (ex: /cimis?matricule=XXX)
    this.route.queryParamMap.subscribe(params => {
      const matricule = params.get('matricule');
      if (matricule) {
        this.matriculeSearch = matricule;
        this.rechercher();
      }
    });
  }

  // ── Recherche ──────────────────────────────────────────────────

  rechercher(): void {
    if (!this.matriculeSearch.trim()) return;
    this.searchLoading = true;
    this.searchError = null;
    this.result = null;
    this.directResult = null;

    this.cimisService.getBiometrie(this.matriculeSearch.trim()).subscribe({
      next: (data) => {
        this.result = data;
        this.searchLoading = false;
        this.showEmpreinte1 = false;
        this.showEmpreinte2 = false;
      },
      error: () => {
        this.searchError = `Aucune donnée biométrique CIMIS trouvée localement pour le matricule "${this.matriculeSearch}". Tentative de recherche directe...`;
        this.rechercherDirect();
      }
    });
  }

  rechercherDirect(): void {
    this.searchLoading = true;
    this.cimisService.getCarteDirecte(this.matriculeSearch.trim()).subscribe({
      next: (data) => {
        this.directResult = data;
        this.searchLoading = false;
        this.searchError = null;
      },
      error: (err) => {
        console.error('Erreur CIMIS:', err);
        const msg = err.error?.error || err.message || 'Erreur inconnue';
        this.searchError = `ÉCHEC CIMIS : ${msg}`;
        this.searchLoading = false;
      }
    });
  }


  // ── Helpers affichage ──────────────────────────────────────────

  /** Retourne une URL data: pour afficher l'image encodée en Base64 */
  toImageSrc(base64: string | null, type: string | null): string | null {
    if (!base64) return null;
    const mime = type || 'image/png';
    return `data:${mime};base64,${base64}`;
  }

  /** Retourne l'icône FontAwesome pour un type MIME */
  mimeIcon(type: string | null): string {
    if (!type) return 'fa-file';
    if (type.startsWith('image/')) return 'fa-image';
    return 'fa-file-binary';
  }

  /** Formate la date de réception */
  formatDate(iso: string | null): string {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('fr-FR', {
      day: '2-digit', month: '2-digit', year: 'numeric',
      hour: '2-digit', minute: '2-digit'
    });
  }

  /** Vide le formulaire */
  reinitialiser(): void {
    this.matriculeSearch = '';
    this.result = null;
    this.directResult = null;
    this.searchError = null;
  }

  testConnection(): void {
    this.searchLoading = true;
    this.searchError = null;
    this.cimisService.testConnection().subscribe({
      next: (res) => {
        alert("Connexion établie avec CIMIS ! Réponse : " + res);
        this.searchLoading = false;
      },
      error: (err) => {
        const msg = err.error?.error || err.message || 'Erreur inconnue';
        this.searchError = `ERREUR DE CONNEXION AU PONT : ${msg}`;
        this.searchLoading = false;
      }
    });
  }

  simulateExport(): void {
    if (!this.matriculeSearch) {
      alert("Veuillez saisir un matricule à exporter pour le test.");
      return;
    }
    this.searchLoading = true;
    this.searchError = null;
    this.cimisService.simulateExport(this.matriculeSearch.trim()).subscribe({
      next: (res) => {
        alert("Succès ! Voici ce que CIMIS recevrait en temps réel : \n\n" + JSON.stringify(res, null, 2));
        this.searchLoading = false;
      },
      error: (err) => {
        const msg = err.error?.error || err.message || 'Erreur inconnue';
        this.searchError = `ÉCHEC DE LA SIMULATION D'EXPORT : ${msg}`;
        this.searchLoading = false;
      }
    });
  }

  // --- NOUVELLE SECTION ANNUAIRE ---
  cimisList: any[] = [];
  pagination: any = {};
  filters = {
    grade: '',
    unite: '',
    search: '',
    page: 1,
    limit: 12
  };
  listLoading = false;
  listError: string | null = null;
  
  // -- GESMIL --
  gesmilSearch = '';
  gesmilResult: any = null;
  gesmilLoading = false;
  gesmilError: string | null = null;

  viewMode: any = 'search';

  chargerAnnuaire(): void {
    this.listLoading = true;
    this.listError = null;
    this.viewMode = 'list';
    this.cimisService.getListe(
      this.filters.page, 
      this.filters.limit, 
      this.filters.grade, 
      this.filters.unite, 
      this.filters.search
    ).subscribe({
      next: (res) => {
        if (res && res.militaires) {
          this.cimisList = res.militaires;
          this.pagination = res.pagination;
        } else {
          this.cimisList = [];
          this.listError = "Le serveur CIMIS a renvoyé une réponse valide mais sans données de militaires.";
        }
        this.listLoading = false;
      },
      error: (err) => {
        console.error('Erreur chargement annuaire CIMIS', err);
        this.listError = err.error?.error || err.message || 'Impossible de charger l\'annuaire CIMIS.';
        this.listLoading = false;
      }
    });
  }

  changerPage(page: number): void {
    this.filters.page = page;
    this.chargerAnnuaire();
  }

  appliquerFiltres(): void {
    this.filters.page = 1;
    this.chargerAnnuaire();
  }

  rechercherGesmil(): void {
    if (!this.gesmilSearch.trim()) return;
    this.gesmilLoading = true;
    this.gesmilError = null;
    this.gesmilResult = null;

    this.cimisService.getGesmilSituation(this.gesmilSearch.trim()).subscribe({
      next: (res) => {
        this.gesmilResult = res;
        this.gesmilLoading = false;
      },
      error: (err) => {
        console.error('Erreur GESMIL:', err);
        this.gesmilError = err.error?.error || err.message || 'Erreur lors de la récupération des données GESMIL';
        this.gesmilLoading = false;
      }
    });
  }
}
