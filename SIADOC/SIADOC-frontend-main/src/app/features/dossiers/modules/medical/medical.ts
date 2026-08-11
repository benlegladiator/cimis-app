import { environment } from '@env/environment';
import { Component, Input, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { DomSanitizer } from '@angular/platform-browser';

@Component({
  selector: 'app-medical',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './medical.html',
  styleUrls: ['./medical.scss']
})
export class Medical implements OnInit {

  @Input() militaireId!: string;
  @Input() readOnly: boolean = false;

  blessures: any[] = [];
  pensions: any[] = [];
  arrets: any[] = [];
  documents: any[] = [];

  fichier: File | null = null;

  afficherFormBlessure = false;
  afficherFormPension = false;
  afficherFormArret = false;
  afficherFormDocument = false;

  blessure: any = {
    nature: '',
    lieu: '',
    autorite: '',
    dateEffet: ''
  };

  pension: any = {
    typeInvalidite: '',
    datePriseEffet: '',
    reference: '',
    taux: null
  };

  arret: any = {
    motif: '',
    dateDebut: '',
    dateFin: ''
  };

  documentMedical: any = {
    titre: '',
    description: '',
    dateDocument: ''
  };

  selection: any = null;

  constructor(
    private http: HttpClient,
    private sanitizer: DomSanitizer
  ) {}

  ngOnInit() {
    this.charger();
  }

  charger() {
    this.http.get<any>(
      `${environment.apiUrl}/api/medical/${this.militaireId}`
    ).subscribe(data => {
      this.blessures = data.blessures || [];
      this.pensions = data.pensions || [];
      this.arrets = data.arrets || [];
      this.documents = data.documents || [];
    });
  }

  onFileSelected(event: any) {
    this.fichier = event.target.files[0];
  }

  // ================= BLESSURE =================

  enregistrerBlessure() {
    const formData = new FormData();
    formData.append('data', JSON.stringify(this.blessure));
    if (this.fichier) {
      formData.append('file', this.fichier);
    }

    this.http.post(
      `${environment.apiUrl}/api/medical/${this.militaireId}/blessure`,
      formData
    ).subscribe(() => {
      alert("Blessure enregistrée ! / Injury saved ! ✅");
      this.afficherFormBlessure = false;
      this.charger();
      this.resetForms();
    });
  }

  // ================= PENSION =================

  enregistrerPension() {
    const formData = new FormData();
    formData.append('data', JSON.stringify(this.pension));
    if (this.fichier) {
      formData.append('file', this.fichier);
    }

    this.http.post(
      `${environment.apiUrl}/api/medical/${this.militaireId}/pension`,
      formData
    ).subscribe(() => {
      alert("Pension enregistrée ! / Pension saved ! ✅");
      this.afficherFormPension = false;
      this.charger();
      this.resetForms();
    });
  }

  // ================= ARRET =================

  enregistrerArret() {
    const formData = new FormData();
    formData.append('data', JSON.stringify(this.arret));
    if (this.fichier) {
      formData.append('file', this.fichier);
    }

    this.http.post(
      `${environment.apiUrl}/api/medical/${this.militaireId}/arret`,
      formData
    ).subscribe(() => {
      alert("Arrêt de travail enregistré ! / Sick leave saved ! ✅");
      this.afficherFormArret = false;
      this.charger();
      this.resetForms();
    });
  }

  // ================= DOCUMENT MEDICAL =================

  enregistrerDocument() {
    const formData = new FormData();
    formData.append('data', JSON.stringify(this.documentMedical));
    if (this.fichier) {
      formData.append('file', this.fichier);
    }

    this.http.post(
      `${environment.apiUrl}/api/medical/${this.militaireId}/document`,
      formData
    ).subscribe(() => {
      alert("Document médical enregistré ! / Medical document saved ! ✅");
      this.afficherFormDocument = false;
      this.charger();
      this.resetForms();
    });
  }

  // ================= PREVIEW =================

  voirBlessure(b: any) {
    const url = `${environment.apiUrl}/api/medical/blessures/${b.id}/fichier`;
    this.selection = {
      type: 'BLESSURE',
      ...b,
      safeUrl: this.sanitizer.bypassSecurityTrustResourceUrl(url),
      isImage: b.document?.match(/\.(jpg|jpeg|png)$/i)
    };
  }

  voirPension(p: any) {
    const url = `${environment.apiUrl}/api/medical/pensions/${p.id}/fichier`;
    this.selection = {
      type: 'PENSION',
      ...p,
      safeUrl: this.sanitizer.bypassSecurityTrustResourceUrl(url),
      isImage: p.document?.match(/\.(jpg|jpeg|png)$/i)
    };
  }

  voirDocument(d: any) {
    const url = `${environment.apiUrl}/api/medical/documents/${d.id}/fichier`;
    this.selection = {
      type: 'DOCUMENT',
      ...d,
      safeUrl: this.sanitizer.bypassSecurityTrustResourceUrl(url),
      isImage: d.document?.match(/\.(jpg|jpeg|png)$/i)
    };
  }

  voirArret(a: any) {
    const url = `${environment.apiUrl}/api/medical/arrets/${a.id}/fichier`;
    this.selection = {
      type: 'ARRET',
      ...a,
      safeUrl: this.sanitizer.bypassSecurityTrustResourceUrl(url),
      isImage: a.document?.match(/\.(jpg|jpeg|png)$/i)
    };
  }

  resetForms() {
    this.blessure = { nature: '', lieu: '', autorite: '', dateEffet: '' };
    this.pension = { typeInvalidite: '', datePriseEffet: '', reference: '', taux: null };
    this.arret = { motif: '', dateDebut: '', dateFin: '' };
    this.documentMedical = { titre: '', description: '', dateDocument: '' };
    this.fichier = null;
  }
}
