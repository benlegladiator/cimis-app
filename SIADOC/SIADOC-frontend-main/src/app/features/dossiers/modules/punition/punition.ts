import { environment } from '@env/environment';
import { Component, Input, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { DomSanitizer } from '@angular/platform-browser';

@Component({
  selector: 'app-punition',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './punition.html',
  styleUrls: ['./punition.scss']
})
export class Punition implements OnInit {

  @Input() militaireId!: string;
  @Input() readOnly: boolean = false;

  punitions: any[] = [];
  afficherFormulaire = false;
  selection: any = null;
  fichier: File | null = null;

  form = {
    designation: '',
    texte: '',
    dateEffet: ''
  };

  constructor(
    private http: HttpClient,
    private sanitizer: DomSanitizer
  ) {}

  ngOnInit() {
    this.charger();
  }

  charger() {
    if (!this.militaireId) return;
    this.http.get<any[]>(`${environment.apiUrl}/api/punitions/${this.militaireId}`)
      .subscribe(data => this.punitions = data);
  }

  getBadgeMessage(): string {
    const n = this.punitions.length;
    if (n === 0) return 'Dossier Disciplinaire Néant';
    if (n < 3) return `${n} sanction(s) enregistrée(s)`;
    return `ALERTE : ${n} sanctions (Dossier Lourd)`;
  }

  ajouter() {
    this.afficherFormulaire = true;
  }

  onFileSelected(event: any) {
    this.fichier = event.target.files[0];
  }

  enregistrer() {
    if (!this.militaireId) return;

    const formData = new FormData();
    formData.append('data', JSON.stringify(this.form));
    if (this.fichier) {
      formData.append('file', this.fichier);
    }

    this.http.post(`${environment.apiUrl}/api/punitions/${this.militaireId}`, formData)
      .subscribe(() => {
        alert('Sanction enregistrée ✅');
        this.afficherFormulaire = false;
        this.resetForm();
        this.charger();
      });
  }

  voir(p: any) {
    if (!p.document) {
      alert("Aucun document justificatif n'est associé à cette sanction. / No supporting document is associated with this sanction.");
      this.selection = { ...p, safeUrl: null };
      return;
    }
    this.selection = p;
    const url = this.getRawUrl(p);
    this.selection.safeUrl = this.sanitizer.bypassSecurityTrustResourceUrl(url);
  }

  getRawUrl(p: any): string {
    return `${environment.apiUrl}/api/punitions/item/${p.id}/document`;
  }

  supprimer(id: string) {
    if (!confirm('Supprimer cette punition ?')) return;
    this.http.delete(`${environment.apiUrl}/api/punitions/${id}`)
      .subscribe(() => this.charger());
  }

  resetForm() {
    this.form = { designation: '', texte: '', dateEffet: '' };
    this.fichier = null;
  }
}
