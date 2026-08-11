import { environment } from '@env/environment';
import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { EtatCivil } from '../modules/etat-civil/etat-civil';
import { Mutation } from '../modules/mutation/mutation';
import { Recompense } from '../modules/recompense/recompense';
import { Identification } from '../modules/identification/identification';
import { Avancement } from '../modules/avancement/avancement';
import { Notation } from '../modules/notation/notation';
import { Punition } from '../modules/punition/punition';
import { Medical } from '../modules/medical/medical';
import { Stage } from '../modules/stage/stage';
import { CampagneModule } from '../modules/campagne/campagne';
import { Diplome } from '../modules/diplome/diplome';
import { Historique } from '../modules/historique/historique';
import { AuthService } from '../../../core/auth.service';
import { Role } from '../../../core/models';
import { Router } from '@angular/router';
import { Habillement } from '../modules/habillement/habillement';


@Component({
  selector: 'app-dossier-militaire',
  standalone: true,
  imports: [CommonModule,
              EtatCivil,
              Mutation,
              Recompense, 
              Identification,
              Avancement, 
              Notation, 
              Punition, 
              Medical, Stage, Diplome, CampagneModule, Historique, Habillement],
  templateUrl: './dossier-militaire.html',
  styleUrls: ['./dossier-militaire.scss']
})
export class DossierMilitaire implements OnInit {

  dossier: any = null;
  loading = true;
  user: any = null;
  Role = Role;
  canEdit = false;
  modifiedModules = new Set<string>();
  environment = environment;

  onModuleModified(name: string) {
    this.modifiedModules.add(name);
  }

  modules = [
    { key: 'IDENTIFICATION', label: '1. Profil / Profile' },
    { key: 'ETAT_CIVIL', label: '2. État Civil / Civil Status' },
    { key: 'MUTATION', label: '3. Affectations / Assignments' },
    { key: 'AVANCEMENT', label: '4. Avancements / Promotions' },
    { key: 'RECOMPENSES', label: '5. Récompenses / Rewards' },
    { key: 'DOSSIER_MEDICAL', label: '6. Médical / Medical' },
    { key: 'NOTATION', label: '7. Notation' },
    { key: 'PUNITION', label: '8. Punitions / Punishments' },
    { key: 'STAGE', label: '9. Stages / Training' },
    { key: 'DIPLOME', label: '10. Diplômes / Diplomas' },
    { key: 'CAMPAGNE', label: '11. Campagnes / Campaigns' },
    { key: 'LIVRET_INDIVIDUEL', label: '12. Livret / Service Book' },
    { key: 'CARNET_HABILLEMENT', label: '13. Habillement / Clothing' },
  ];

  moduleActif: string | null = null;

  constructor(
    private route: ActivatedRoute,
    private http: HttpClient,
    private auth: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.user = this.auth.getUser();

    if (this.user?.role === Role.DRH) {
      if (!this.modules.find(m => m.key === 'HISTORIQUE')) {
        this.modules.push({ key: 'HISTORIQUE', label: 'Historique / History (DRH)' });
      }
    }

    this.route.paramMap.subscribe(params => {

      this.dossier = null;   
      this.loading = true;

      const id = params.get('id');

      if (!id) return;

      this.http.get(`${environment.apiUrl}/api/dossiers/militaire/${id}`)
        .subscribe({
          next: (data: any) => {
            this.dossier = data;
            this.loading = false;
            this.checkPermissions();
          },
          error: err => {
            console.error(err);
            this.loading = false;
          }
        });

    });
  }

  checkPermissions() {
    if (!this.user || !this.dossier) return;
    
    // Seul le commandant de compagnie peut modifier, et seulement si pas en attente de validation
    this.canEdit = (this.user.role === Role.COMMANDANT_COMPAGNIE) && 
                   (this.dossier.statutValidation !== 'EN_ATTENTE_VALIDATION');
    
    // Cas particulier : si archivé, personne ne modifie
    if (this.dossier.statut === 'ARCHIVE') {
      this.canEdit = false;
    }
  }


  recevoir() {
    if (!this.dossier?.militaire?.id) return;
    this.http.post(`${environment.apiUrl}/api/militaires/${this.dossier.militaire.id}/recevoir`, {})
      .subscribe({
        next: () => {
          alert("Dossier reçu et activé ! / File received and activated ! ✅");
          this.ngOnInit(); // recharger
        },
        error: err => {
          console.error(err);
          alert("Erreur lors de la réception");
        }
      });
  }

  choisirModule(module: any) {
    this.moduleActif = module.key;
  }

  archiver() {
    if (!this.dossier?.militaire?.id) return;
    const confirmation = confirm("📦 Archivage définitif / Permanent Archiving\n\nLe dossier ne sera plus modifiable. / File will be read-only.\n\nConfirmer / Confirm ?");
    if (!confirmation) return;

    this.http.put(`${environment.apiUrl}/api/dossiers/archiver/militaire/${this.dossier.militaire.id}`, {})
      .subscribe({
        next: () => {
          alert("Dossier archivé avec succès ! / File archived successfully ! ✅");
          this.router.navigate(['/archives', this.dossier.militaire.id]);
        },
        error: err => {
          console.error(err);
          alert("Erreur lors de l'archivage");
        }
      });
  }

  voirBiometrie() {
    if (!this.dossier?.militaire) return;
    const m = this.dossier.militaire;
    const matricule = m.matriculeMilitaire || m.matriculeSolde;
    this.router.navigate(['/cimis'], { queryParams: { matricule } });
  }

  soumettrePourValidation() {
    if (!this.dossier?.id) return;
    
    let modulesChanges = this.dossier.modulesModifies;
    
    if (!modulesChanges || modulesChanges.trim() === "") {
      modulesChanges = prompt("Aucune modification détectée. Veuillez lister les changements : / No changes detected. Please list changes :", "Modifications générales / General changes") || "";
      if (!modulesChanges) return;
    } else {
      if (!confirm(`Confirmer l'envoi pour validation : / Confirm submission for validation : ${modulesChanges} ?`)) return;
    }
    
    this.http.post(`${environment.apiUrl}/api/dossiers/${this.dossier.id}/soumettre`, modulesChanges)
      .subscribe({
        next: () => {
          alert("Dossier envoyé pour validation ! / File sent for validation ! ✅");
          this.ngOnInit();
        },
        error: err => alert("Erreur lors de l'envoi")
      });
  }

  approuverModifications() {
    if (!this.dossier?.id) return;
    if (!confirm("Voulez-vous valider ces modifications ? / Do you want to approve these changes ?")) return;

    this.http.post(`${environment.apiUrl}/api/dossiers/${this.dossier.id}/approuver`, {})
      .subscribe({
        next: () => {
          alert("Modifications validées ! / Changes approved ! ✅");
          this.ngOnInit();
        },
        error: err => alert("Erreur lors de la validation")
      });
  }

  rejeterModifications() {
    if (!this.dossier?.id) return;
    const motif = prompt("Veuillez saisir le motif du refus : / Please enter the reason for rejection :");
    if (!motif) return;

    this.http.post(`${environment.apiUrl}/api/dossiers/${this.dossier.id}/rejeter`, motif)
      .subscribe({
        next: () => {
          alert("Modifications rejetées. / Changes rejected. ❌");
          this.ngOnInit();
        },
        error: err => alert("Erreur lors du rejet")
      });
  }

  mettreEnRetraite() {
    if (!this.dossier?.militaire?.id) return;
    const confirmation = confirm("⏳ Mise en retraite / Retirement\n\nCela va changer le statut du militaire en 'RETRAITE' et figer le dossier.\n\nConfirmer / Confirm ?");
    if (!confirmation) return;

    this.http.post(`${environment.apiUrl}/api/militaires/${this.dossier.militaire.id}/retraite`, {})
      .subscribe({
        next: () => {
          alert("Militare mis en retraite avec succès ! / Military retired successfully ! ✅");
          this.ngOnInit();
        },
        error: err => {
          console.error(err);
          alert("Erreur lors de la mise en retraite");
        }
      });
  }
}
