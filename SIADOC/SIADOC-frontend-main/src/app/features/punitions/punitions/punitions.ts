import { environment } from '@env/environment';
import { Component, Input, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { DomSanitizer } from '@angular/platform-browser';

@Component({
  selector: 'app-punitions',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './punitions.html',
  styleUrls: ['./punitions.scss']
})
export class Punitions implements OnInit {

  @Input() militaireId!: string;

  punitions: any[] = [];
  afficherFormulaire = false;
  selection: any = null;

  form = this.initForm();

  constructor(
    private http: HttpClient,
    private sanitizer: DomSanitizer
  ) {}

  ngOnInit() {
    if (this.militaireId) {
      this.charger();
    }
  }

  initForm() {
    return {
      designation: '',
      texte: '',
      dateEffet: '',
      fichier: null as File | null
    };
  }

  // ======================
  // LOAD
  // ======================
  charger() {
    this.http.get<any[]>(
      `${environment.apiUrl}/api/punitions/${this.militaireId}`
    ).subscribe(data => {
      this.punitions = data;
    });
  }

  // ======================
  // AJOUT FORM
  // ======================
  ajouter() {
    this.afficherFormulaire = true;
    this.form = this.initForm();
  }

  onFileSelected(event: any) {
    this.form.fichier = event.target.files[0];
  }

  // ======================
  // SAVE
  // ======================
  enregistrer() {

    const formData = new FormData();

    const data = {
      designation: this.form.designation,
      texte: this.form.texte,
      dateEffet: this.form.dateEffet
    };

    formData.append('data', JSON.stringify(data));

    if (this.form.fichier) {
      formData.append('file', this.form.fichier);
    }

    this.http.post(
      `${environment.apiUrl}/api/punitions/${this.militaireId}`,
      formData
    ).subscribe(() => {
      this.afficherFormulaire = false;
      this.charger();
    });
  }

  // ======================
  // VOIR DOCUMENT
  // ======================
  voir(p: any) {

    this.selection = p;

    const url =
      `${environment.apiUrl}/api/punitions/item/${p.id}/document`;

    this.selection.safeUrl =
      this.sanitizer.bypassSecurityTrustResourceUrl(url);
  }

  // ======================
  // DELETE
  // ======================
  supprimer(id: string) {
    this.http.delete(
      `${environment.apiUrl}/api/punitions/${id}`
    ).subscribe(() => this.charger());
  }
}
