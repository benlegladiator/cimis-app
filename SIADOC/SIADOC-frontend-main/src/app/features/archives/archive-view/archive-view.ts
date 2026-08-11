import { environment } from '@env/environment';
import { Component } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { ActivatedRoute, Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { DomSanitizer } from '@angular/platform-browser';

@Component({
  selector: 'app-archive-view',
  imports: [CommonModule],
  templateUrl: './archive-view.html',
  styleUrl: './archive-view.scss',
})
export class ArchiveView {

  dossier: any = null;
  militaireId!: string;
  selectedPieceUrl: any = null;
  selectedPieces: { type: string; id: string }[] = [];
  isArchivePhysique: boolean = false;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private http: HttpClient,
    private sanitizer: DomSanitizer
  ) {}

  ngOnInit() {
    this.militaireId = this.route.snapshot.paramMap.get('id')!;
    const id = this.route.snapshot.paramMap.get('id');

    this.http.get(
      `${environment.apiUrl}/api/dossiers/militaire/${id}`
    ).subscribe(d => {
      this.dossier = d;
    });

    this.http.get<boolean>(
      `${environment.apiUrl}/api/archives-physiques/check/${id}`
    ).subscribe(isPhysical => {
      this.isArchivePhysique = isPhysical;
    });
  }

  editerDossier() {
    this.router.navigate(['/dossier', this.militaireId]);
  }

  exportPDF(){

    this.http.get(
      `${environment.apiUrl}/api/archives/${this.militaireId}/pdf`,
      { responseType: 'blob' }
    ).subscribe(blob => {

      const url = window.URL.createObjectURL(blob);

      const a = document.createElement('a');
      a.href = url;
      a.download = 'dossier_archive.pdf';
      a.click();

    });
  }

  voirPiece(url: string) {
    this.selectedPieceUrl =
      this.sanitizer.bypassSecurityTrustResourceUrl(
        `${environment.apiUrl}` + url
      );
  }

  togglePiece(type: string, id: string) {

    const index = this.selectedPieces.findIndex(
      p => p.id === id && p.type === type
    );

    if (index > -1) {

      this.selectedPieces.splice(index, 1);

    } else {

      this.selectedPieces.push({ type, id });

    }

  }
  imprimerSelection(){

    if(this.selectedPieces.length === 0){
      alert("Aucune pièce sélectionnée");
      return;
    }

    const base = `${environment.apiUrl}`;

    let contenu = "";

    this.selectedPieces.forEach(piece => {

      let url = "";

      switch(piece.type){

        case "mutation":
          url = "/api/mutations/item/"+piece.id+"/document";
          break;

        case "stage":
          url = "/api/stages/item/"+piece.id+"/document";
          break;

        case "diplome":
          url = "/api/diplomes/"+piece.id+"/fichier";
          break;

        case "punition":
          url = "/api/punitions/item/"+piece.id+"/document";
          break;

        case "notation":
          url = "/api/notations/"+piece.id+"/fichier";
          break;

        case "recompense":
          url = "/api/recompenses/"+piece.id+"/fichier";
          break;

        case "avancement":
          url = "/api/avancements/"+piece.id+"/fichier";
          break;

        case "campagne":
          url = "/api/campagnes/"+piece.id+"/fichier";
          break;

        case "cni":
          url = "/api/cnis/"+piece.id+"/fichier";
          break;

        case "naissance":
          url = "/api/actes-naissance/"+piece.id+"/fichier";
          break;

        case "mariage":
          url = "/api/actes-mariage/"+piece.id+"/fichier";
          break;

        case "deces":
          url = "/api/actes-deces/"+piece.id+"/fichier";
          break;

        case "divorce":
          url = "/api/actes-divorce/"+piece.id+"/fichier";
          break;

        case "jugement":
          url = "/api/jugements/"+piece.id+"/fichier";
          break;

        case "blessure":
          url = "/api/medical/blessures/"+piece.id+"/fichier";
          break;

        case "pension":
          url = "/api/medical/pensions/"+piece.id+"/fichier";
          break;

        case "carriere":
          url = "/api/carriere/"+piece.id+"/fichier";
          break;

      }

      if(url !== ""){

        const full = base + url;

        contenu += `
        <div class="page">
          <iframe src="${full}"></iframe>
        </div>
        `;

      }

    });

    const win = window.open("", "", "width=1000,height=800");

    if(win){

      win.document.write(`
      <html>

      <head>

      <title>Impression</title>

      <style>

      body{
        margin:0;
        padding:0;
      }

      .page{
        width:100%;
        height:100vh;
        page-break-after:always;
      }

      iframe{
        width:100%;
        height:100%;
        border:none;
      }

      </style>

      </head>

      <body>

      ${contenu}

      </body>

      </html>
      `);

      win.document.close();

      setTimeout(()=>{
        win.focus();
        win.print();
      },500);

    }

  }
  exporterPDF() {

    if(this.selectedPieces.length === 0){
      alert("Aucune pièce sélectionnée");
      return;
    }

    const payload = {
      militaireId: this.militaireId,
      pieces: this.selectedPieces
    };

    this.http.post(
      `${environment.apiUrl}/api/export/pdf`,
      payload,
      { responseType: 'blob' }

    ).subscribe(file => {

      const url = window.URL.createObjectURL(file);

      const a = document.createElement("a");

      a.href = url;
      a.download = `archive_${this.dossier?.militaire?.nom || 'dossier'}.pdf`;
      a.click();

    });

  }
}
