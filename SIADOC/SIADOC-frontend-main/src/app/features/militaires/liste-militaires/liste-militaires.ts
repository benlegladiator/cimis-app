import { environment } from '@env/environment';
import { Component, OnInit, ChangeDetectorRef  } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MilitaireService } from '../militaire';
import { RouterLink, Router, ActivatedRoute } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { NgZone } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { CompagnieService } from '../../../core/compagnie.service';
import { Compagnie, AffectationRequestDTO, Role, Militaire } from '../../../core/models';
import { AuthService } from '../../../core/auth.service';

@Component({
  selector: 'app-liste-militaires',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule],
  templateUrl: './liste-militaires.html',
  styleUrls: ['./liste-militaires.scss']
})
export class ListeMilitaires implements OnInit {

  militaires: Militaire[] = [];
  loading = true;
  filtre = '';
  showArchiveModal = false;
  selectedDossierId: string | null = null;

  showAffecterModal = false;
  selectedMilitaire: Militaire | null = null;
  compagnies: Compagnie[] = [];
  affectationRequest: AffectationRequestDTO = {
    compagnieId: '',
    numeroTexte: '',
    dateTexte: '',
    emploi: ''
  };
  user: any = null;
  Role = Role;

  constructor(
    private militaireService: MilitaireService,
    private router: Router,
    private cdr: ChangeDetectorRef,
    private zone: NgZone,
    private http: HttpClient,
    private compagnieService: CompagnieService,
    private auth: AuthService,
    private route: ActivatedRoute
  ) {}

  ngOnInit(): void {
    this.user = this.auth.getUser();
    
    this.route.queryParams.subscribe(params => {
      const uniteNom = params['uniteNom'];
      const statut = params['statut'];

      if (statut === 'RETRAITE') {
        this.chargerRetraites();
      } else if (uniteNom) {
        this.chargerMilitairesParUnite(uniteNom);
      } else {
        this.chargerMilitaires();
      }
    });
    
    this.chargerCompagnies();
  }

  chargerMilitairesParUnite(nom: string) {
    this.loading = true;
    this.militaireService.getByUniteNom(nom).subscribe({
      next: (data: Militaire[]) => {
        this.militaires = data;
        this.loading = false;
        this.cdr.detectChanges();
      },
      error: (err: any) => {
        console.error('Erreur API militaires par unité', err);
        this.loading = false;
        this.cdr.detectChanges();
      }
    });
  }

  chargerCompagnies() {
    this.compagnieService.lister().subscribe({
      next: (data: any) => this.compagnies = data,
      error: (err: any) => console.error(err)
    });
  }

  ouvrirAffectation(m: Militaire) {
    this.selectedMilitaire = m;
    this.showAffecterModal = true;
  }

  validerAffectation() {
    if (!this.selectedMilitaire) return;
    this.militaireService.affecter(this.selectedMilitaire.id, this.affectationRequest)
      .subscribe({
        next: () => {
          alert("Affectation effectuée avec succès !");
          this.showAffecterModal = false;
          this.chargerMilitaires();
        },
        error: err => {
          console.error(err);
          alert("Erreur lors de l'affectation");
        }
      });
  }

  chargerMilitaires() {
    this.loading = true;

    const request$ = this.user?.role === Role.COMMANDANT_COMPAGNIE 
      ? this.militaireService.getMaCompagnie() 
      : this.militaireService.lister();

    request$.subscribe({
      next: (data: Militaire[]) => {
        this.militaires = data;
        this.loading = false;
        this.cdr.detectChanges();
      },
      error: (err: any) => {
        console.error('Erreur API militaires', err);
        this.loading = false;
        this.cdr.detectChanges();
      }
    });
  }

  chargerRetraites() {
    this.loading = true;
    this.militaireService.listerRetraites().subscribe({
      next: (data: Militaire[]) => {
        this.militaires = data;
        this.loading = false;
        this.cdr.detectChanges();
      },
      error: (err: any) => {
        console.error('Erreur API retraités', err);
        this.loading = false;
        this.cdr.detectChanges();
      }
    });
  }

  ouvrirDossier(id: string) {
    console.log("🔥 Navigation vers:", id);
    this.router.navigate(['/dossier', id]);
  }

  get militairesFiltres(): Militaire[] {
    return this.militaires.filter(m =>
      m.nom?.toLowerCase().includes(this.filtre.toLowerCase()) ||
      m.matriculeMilitaire?.toLowerCase().includes(this.filtre.toLowerCase())
    );
  }

  archiver(id: string) {

    const confirmation = confirm(
      "📦 Archivage définitif\n\nLe dossier ne sera plus modifiable.\n\nConfirmer ?"
    );

    if (!confirmation) return;

    this.http.put(
      `${environment.apiUrl}/api/dossiers/archiver/militaire/${id}`,
      {}
    ).subscribe({
      next: () => {

        this.militaires = this.militaires.filter(m => m.id !== id);

        alert("Dossier archivé avec succès ✅");
      },
      error: (err: any) => {
        console.error(err);
        alert("Erreur lors de l’archivage");
      }
    });

  }

  openArchiveModal(id: string) {
    this.selectedDossierId = id;
    this.showArchiveModal = true;
  }
  confirmArchive() {

    if (!this.selectedDossierId) return;

    this.http.put(
      `${environment.apiUrl}/api/dossiers/archiver/militaire/${this.selectedDossierId}`,
      {}
    ).subscribe({

      next: () => {

        // retirer le militaire de la liste
        this.militaires = this.militaires.filter(
          (m:any) => m.id !== this.selectedDossierId
        );

        this.showArchiveModal = false;

      },

      error: err => {
        console.error(err);
        alert("Erreur lors de l'archivage");
      }

    });

  }

  cancelArchive() {
    this.showArchiveModal = false;
  }
}
