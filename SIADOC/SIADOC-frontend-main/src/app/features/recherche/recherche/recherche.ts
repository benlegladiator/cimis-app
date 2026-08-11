import { Component } from '@angular/core'
import { RechercheService } from './recherche.service'
import { RechercheEtatCivil, ResultRechercheEtatCivil,
  RechercheMariage, ResultRechercheMariage,
  RechercheAvancement, ResultRechercheAvancement,
  RechercheStage, ResultRechercheStage,
  RechercheMedical, ResultRechercheMedical,
  RechercheCarriere, ResultRechercheCarriere,
  RechercheMutation, ResultRechercheMutation,
  RechercheNotation, ResultRechercheNotation,
  RechercheCampagneMilitaire, ResultRechercheCampagneMilitaire
 } from '../models/search.model'
import { CommonModule } from '@angular/common'
import { FormsModule } from '@angular/forms'

@Component({
  selector: 'app-recherche',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './recherche.html',
  styleUrls: ['./recherche.scss']
})
export class RechercheComponent {

  module: string | null = null
  piece: string = ''
  filtre: any = {}
  resultats: any[] = []

  constructor(private service: RechercheService) {}

  selectModule(m: string) {
    this.module = m
    this.piece = ''
    this.filtre = {}
    this.resultats = []
    
    // Initialisation par défaut pour Etat Civil
    if (m === 'etatCivil') {
        this.piece = 'naissance';
    }
  }

  rechercher() {
    if (this.module === 'militaire') {
        this.service.rechercherMilitaire(this.filtre)
            .subscribe((res: any) => this.resultats = res);
    }

    if(this.module === 'etatCivil') {
      if(this.piece === 'naissance' || this.piece === 'deces' || this.piece === 'divorce' || this.piece === 'jugement') {
        this.service.rechercherActeNaissance(this.filtre)
          .subscribe((res: any) => this.resultats = res);
      } else if(this.piece === 'mariage') {
        this.service.rechercherMariage(this.filtre)
          .subscribe((res: any) => this.resultats = res);
      } else if(this.piece === 'cni') {
        this.service.rechercherCni(this.filtre)
          .subscribe((res: any) => this.resultats = res);
      }
    }

    if(this.module === "recompense") {
      this.service.rechercherRecompenses(this.filtre)
        .subscribe((res: any) => this.resultats = res);
    }

    if(this.module === "punition") {
      this.service.rechercherPunitions(this.filtre)
        .subscribe((res: any) => this.resultats = res);
    }

    if(this.module === "diplome") {
      this.service.rechercherDiplomes(this.filtre)
        .subscribe((res: any) => this.resultats = res);
    }

    if(this.module === "avancement") {
      this.service.rechercherAvancements(this.filtre)
        .subscribe((res: any) => this.resultats = res);
    }

    if(this.module === "stage") {
      this.service.rechercherStages(this.filtre)
        .subscribe((res: any) => this.resultats = res);
    }

    if(this.module === "medical") {
      this.service.rechercherMedicals(this.filtre)
        .subscribe((res: any) => this.resultats = res);
    }

    if(this.module === "carriere") {
      this.service.rechercherCarrieres(this.filtre)
        .subscribe((res: any) => this.resultats = res);
    }

    if(this.module === "mutation") {
      this.service.rechercherMutations(this.filtre)
        .subscribe((res: any) => this.resultats = res);
    }

    if(this.module === "notation") {
      this.service.rechercherNotations(this.filtre)
        .subscribe((res: any) => this.resultats = res);
    }

    if(this.module === "campagneMilitaire") {
      this.service.rechercherCampagneMilitaire(this.filtre)
        .subscribe((res: any) => this.resultats = res);
    }
  }

}