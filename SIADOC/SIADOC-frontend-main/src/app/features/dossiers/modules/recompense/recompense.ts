import { environment } from '@env/environment';
import { Component, Input, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { DomSanitizer } from '@angular/platform-browser';

@Component({
  selector: 'app-recompense',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './recompense.html',
  styleUrls: ['./recompense.scss']
})
export class Recompense implements OnInit {

  @Input() militaireId!: string;
  @Input() readOnly: boolean = false;

  afficherFormulaire = false;
  recompenses: any[] = [];
  selectionnee: any = null;
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

  ngOnInit(): void {
    this.charger();
  }

  // ================= LOAD =================

  charger() {
    if (!this.militaireId) return;

    this.http.get<any[]>(
      `${environment.apiUrl}/api/recompenses/${this.militaireId}`
    ).subscribe(data => {
      this.recompenses = data;
    });
  }

  // ================= FILE =================

  onFileSelected(event: any) {
    this.fichier = event.target.files[0];
  }

  // ================= SAVE =================

  enregistrer() {
    if (!this.militaireId) return;

    const formData = new FormData();
    formData.append('data', JSON.stringify(this.form));

    if (this.fichier) {
      formData.append('file', this.fichier);
    }

    this.http.post(
      `${environment.apiUrl}/api/recompenses/${this.militaireId}`,
      formData
    ).subscribe({
      next: () => {
        alert('Récompense enregistrée ! / Reward saved ! ✅');
        this.afficherFormulaire = false;
        this.resetForm();
        this.charger();
      },
      error: err => console.error(err)
    });
  }

  // ================= SELECT =================

  selectionner(r: any) {
    this.selectionnee = r;
    const url = this.getRawUrl(r);
    this.selectionnee.safeUrl =
      this.sanitizer.bypassSecurityTrustResourceUrl(url);
  }

  fermerPreview() {
    this.selectionnee = null;
  }

  getRawUrl(r: any): string {
    return `${environment.apiUrl}/api/recompenses/${r.id}/fichier`;
  }

  // ================= RESET =================

  resetForm() {
    this.form = {
      designation: '',
      texte: '',
      dateEffet: ''
    };
    this.fichier = null;
  }
}
