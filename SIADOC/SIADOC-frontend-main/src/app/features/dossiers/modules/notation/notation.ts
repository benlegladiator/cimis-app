import { environment } from '@env/environment';
import { Component, Input, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { DomSanitizer } from '@angular/platform-browser';

@Component({
  selector: 'app-notation',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './notation.html',
  styleUrls: ['./notation.scss']
})
export class Notation implements OnInit {

  @Input() militaireId!: string;
  @Input() readOnly: boolean = false;

  afficherForm = false;
  notations: any[] = [];
  notationSelectionnee: any = null;
  fichier: File | null = null;

  notation: any = {
    periodeDu: '',
    periodeAu: '',
    appreciationGenerale: ''
  };

  constructor(
    private http: HttpClient,
    private sanitizer: DomSanitizer
  ) { }

  ngOnInit() {
    this.chargerNotations();
  }

  chargerNotations() {
    if (!this.militaireId) return;
    this.http.get<any[]>(
      `${environment.apiUrl}/api/notations/${this.militaireId}`
    ).subscribe(data => {
      this.notations = data;
    });
  }

  onFileSelected(event: any) {
    this.fichier = event.target.files[0];
  }

  enregistrer() {
    const formData = new FormData();
    formData.append('data', JSON.stringify(this.notation));
    if (this.fichier) {
      formData.append('file', this.fichier);
    }

    this.http.post(
      `${environment.apiUrl}/api/notations/${this.militaireId}`,
      formData
    ).subscribe(() => {
      alert('Notation enregistrée ✅');
      this.afficherForm = false;
      this.notation = {
        periodeDu: '',
        periodeAu: '',
        appreciationGenerale: ''
      };
      this.fichier = null;
      this.chargerNotations();
    });
  }

  voirNotation(n: any) {
    this.notationSelectionnee = n;
    const url = this.getRawUrl(n);
    this.notationSelectionnee.safeUrl = this.sanitizer.bypassSecurityTrustResourceUrl(url);
  }

  getRawUrl(n: any): string {
    return `${environment.apiUrl}/api/notations/${n.id}/fichier`;
  }

  fermerPreview() {
    this.notationSelectionnee = null;
  }
}