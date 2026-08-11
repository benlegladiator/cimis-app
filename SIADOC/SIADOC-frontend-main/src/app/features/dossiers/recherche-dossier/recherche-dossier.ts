import { environment } from '@env/environment';
import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';

@Component({
  standalone: true,
  imports: [CommonModule, FormsModule],
  template: `
    <h2>Rechercher un militaire</h2>

    <input [(ngModel)]="motCle" placeholder="Nom ou matricule">
    <button (click)="rechercher()">Rechercher</button>
  `
})
export class RechercheDossier {

  motCle = '';

  constructor(
    private http: HttpClient,
    private router: Router
  ) {}

  rechercher() {
    this.http.get(`${environment.apiUrl}/api/militaires/recherche?motCle=${this.motCle}`)
      .subscribe({
        next: (militaire: any) => {
          this.router.navigate(['/dossier', militaire.id]);
        },
        error: () => {
          alert("Militaire introuvable");
        }
      });
  }
}
