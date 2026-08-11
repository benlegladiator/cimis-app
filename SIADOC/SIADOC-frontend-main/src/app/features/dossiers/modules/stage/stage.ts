import { environment } from '@env/environment';
import { Component, Input, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { DomSanitizer } from '@angular/platform-browser';

@Component({
  selector: 'app-stage',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './stage.html',
  styleUrls: ['./stage.scss']
})
export class Stage implements OnInit {

  @Input() militaireId!: string;
  @Input() readOnly: boolean = false;

  stages: any[] = [];
  afficherForm = false;
  stageSelectionne: any = null;
  fichier: File | null = null;

  stageForm = {
    designation: '',
    diplome: '',
    ville: '',
    pays: '',
    dateObtention: ''
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
    this.http.get<any[]>(`${environment.apiUrl}/api/stages/${this.militaireId}`)
      .subscribe(data => this.stages = data);
  }

  onFileSelected(event: any) {
    this.fichier = event.target.files[0];
  }

  enregistrer() {
    if (!this.militaireId) return;

    const formData = new FormData();
    formData.append('data', JSON.stringify(this.stageForm));
    if (this.fichier) {
      formData.append('file', this.fichier);
    }

    this.http.post(`${environment.apiUrl}/api/stages/${this.militaireId}`, formData)
      .subscribe(() => {
        alert('Stage enregistré ✅');
        this.afficherForm = false;
        this.resetForm();
        this.charger();
      });
  }

  voirStage(s: any) {
    this.stageSelectionne = s;
    const url = this.getRawUrl(s);
    this.stageSelectionne.safeUrl = this.sanitizer.bypassSecurityTrustResourceUrl(url);
  }

  getRawUrl(s: any): string {
    return `${environment.apiUrl}/api/stages/${s.id}/fichier`;
  }

  resetForm() {
    this.stageForm = { designation: '', diplome: '', ville: '', pays: '', dateObtention: '' };
    this.fichier = null;
  }
}