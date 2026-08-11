import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { environment } from '@env/environment';

@Component({
  selector: 'app-rank-settings',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './rank-settings.html',
  styleUrls: ['./rank-settings.scss']
})
export class RankSettings implements OnInit {
  grades: any[] = [];
  
  showModal = false;
  currentGrade: any = { label: '', armee: 'AT', categorie: 'OFFICIERS', ordre: 100 };
  isEditing = false;

  armees = [
    { id: 'AT', label: 'Armée de Terre' },
    { id: 'AA', label: 'Armée de l\'Air' },
    { id: 'AM', label: 'Marine Nationale' },
    { id: 'GN', label: 'Gendarmerie' }
  ];

  categories = [
    { id: 'OFFICIERS', label: 'Officiers' },
    { id: 'SOUS_OFFICIERS', label: 'Sous-Officiers' },
    { id: 'MILITAIRES_DU_RANG', label: 'MDR' }
  ];

  constructor(private http: HttpClient) {}

  ngOnInit(): void {
    this.chargerGrades();
  }

  chargerGrades() {
    this.http.get<any[]>(`${environment.apiUrl}/api/grades/config`).subscribe(data => {
      this.grades = data;
    });
  }

  ouvrirModal(grade: any = null) {
    if (grade) {
      this.currentGrade = { ...grade };
      this.isEditing = true;
    } else {
      this.currentGrade = { label: '', armee: 'AT', categorie: 'OFFICIERS', ordre: this.prochainOrdre() };
      this.isEditing = false;
    }
    this.showModal = true;
  }

  prochainOrdre() {
    if (this.grades.length === 0) return 100;
    return Math.max(...this.grades.map(g => g.ordre)) + 1;
  }

  fermerModal() {
    this.showModal = false;
  }

  enregistrer() {
    this.http.post(`${environment.apiUrl}/api/grades/config`, this.currentGrade).subscribe(() => {
      this.fermerModal();
      this.chargerGrades();
    });
  }

  supprimer(id: string) {
    if (confirm('Voulez-vous vraiment supprimer ce grade ?')) {
      this.http.delete(`${environment.apiUrl}/api/grades/config/${id}`).subscribe(() => {
        this.chargerGrades();
      });
    }
  }

  getGradesFiltres(armee: string, cat: string) {
    return this.grades.filter(g => g.armee === armee && g.categorie === cat);
  }
}
