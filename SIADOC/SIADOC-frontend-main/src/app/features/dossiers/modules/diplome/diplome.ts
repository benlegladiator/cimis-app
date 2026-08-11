import { environment } from '@env/environment';
import { Component, Input, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { DomSanitizer } from '@angular/platform-browser';
import { DiplomeDTO } from '../../../../core/models';

@Component({
  selector: 'app-diplome',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './diplome.html',
  styleUrls: ['./diplome.scss']
})
export class Diplome implements OnInit {

  @Input() militaireId!: string;
  @Input() readOnly: boolean = false;


  afficherForm = false;
  diplomes: DiplomeDTO[] = [];
  diplomeSelectionne: any = null;
  fichier: File | null = null;

  diplomeForm: DiplomeDTO = {
    designation: '',
    ecole: '',
    dateObtention: ''
  };

  constructor(
    private http: HttpClient,
    private sanitizer: DomSanitizer
  ) {}

  ngOnInit() {
    this.chargerDiplomes();
  }

  // ================= LOAD =================

  chargerDiplomes() {
    this.http.get<any[]>(
      `${environment.apiUrl}/api/diplomes/${this.militaireId}`
    ).subscribe(data => this.diplomes = data);
  }

  // ================= FILE =================

  onFileSelected(event:any){
    this.fichier = event.target.files[0];
  }

  // ================= SAVE =================

  enregistrer() {

    const formData = new FormData();

    formData.append(
      'data',
      JSON.stringify(this.diplomeForm)
    );

    if (this.fichier) {
      formData.append('file', this.fichier);
    }

    this.http.post(
      `${environment.apiUrl}/api/diplomes/${this.militaireId}`,
      formData
    ).subscribe(() => {

      alert('Diplôme enregistré ✅');

      this.afficherForm = false;
      this.diplomeForm = {
        designation: '',
        ecole: '',
        dateObtention: ''
      };
      this.fichier = null;

      this.chargerDiplomes();
    });
  }

  // ================= PREVIEW =================

  voirDiplome(d:any){

    this.diplomeSelectionne = d;

    const url =
      `${environment.apiUrl}/api/diplomes/item/${d.id}/document`;

    this.diplomeSelectionne.safeUrl =
      this.sanitizer.bypassSecurityTrustResourceUrl(url);
  }

  fermerPreview(){
    this.diplomeSelectionne = null;
  }
}