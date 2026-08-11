import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { TeecService } from '../../core/teec.service';
import { CompagnieService } from '../../core/compagnie.service';
import { AuthService } from '../../core/auth.service';
import { TeecRow, Compagnie, Role } from '../../core/models';

@Component({
  selector: 'app-tec',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './tec.html',
  styleUrls: ['./tec.scss']
})
export class Tec implements OnInit {

  teecRows: TeecRow[] = [];
  compagnies: Compagnie[] = [];
  selectedCompagnieId: string = '';
  loading = false;
  
  currentUser: any;
  currentDate = new Date();

  constructor(
    private teecService: TeecService,
    private compagnieService: CompagnieService,
    private authService: AuthService
  ) {}

  ngOnInit() {
    this.currentUser = this.authService.getUser();
    this.chargerCompagnies();
    
    // Si l'utilisateur est un chef de compagnie, on sélectionne sa compagnie par défaut
    if (this.currentUser?.role === Role.COMMANDANT_COMPAGNIE && this.currentUser?.compagnie?.id) {
      this.selectedCompagnieId = this.currentUser.compagnie.id;
    }
    
    // Charger le TEEC (vue globale par défaut pour DRH, vue compagnie pour chef)
    this.chargerTeec();
  }

  chargerCompagnies() {
    this.compagnieService.lister().subscribe({
      next: (data) => this.compagnies = data,
      error: (err) => console.error('Erreur chargement compagnies', err)
    });
  }

  chargerTeec() {
    this.loading = true;
    this.teecService.getTeec(this.selectedCompagnieId).subscribe({
      next: (data) => {
        // Tri côté frontend : regrouper par compagnie, puis par numéro dans la compagnie
        this.teecRows = data.sort((a, b) => {
          const c1 = a.nomCompagnie || 'ZZZZ';
          const c2 = b.nomCompagnie || 'ZZZZ';
          
          if (c1 !== c2) {
            // Prioriser la CCS, CCT ou Compagnie de Commandement pour qu'elle soit toujours en premier
            const isC1Commandement = c1.toUpperCase().includes('CCS') || c1.toUpperCase().includes('CCT') || c1.toUpperCase().includes('COMMANDEMENT');
            const isC2Commandement = c2.toUpperCase().includes('CCS') || c2.toUpperCase().includes('CCT') || c2.toUpperCase().includes('COMMANDEMENT');
            
            if (isC1Commandement && !isC2Commandement) return -1;
            if (!isC1Commandement && isC2Commandement) return 1;

            return c1.localeCompare(c2, 'fr', { sensitivity: 'base' });
          }
          
          const n1 = a.numero || 'ZZZ';
          const n2 = b.numero || 'ZZZ';
          return n1.localeCompare(n2, 'fr', { numeric: true });
        });
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur chargement TEEC', err);
        this.loading = false;
      }
    });
  }

  getNomCompagnieSelectionnee(): string {
    const comp = this.compagnies.find(c => c.id === this.selectedCompagnieId);
    return comp ? comp.nom : 'UNITÉ NON SÉLECTIONNÉE';
  }

  getLocalisationSelectionnee(): string {
    const comp = this.compagnies.find(c => c.id === this.selectedCompagnieId);
    return comp?.localisation || 'NON RENSEIGNÉE';
  }

  getDateRecueilFormatee(): string {
    const months = ['JANVIER', 'FÉVRIER', 'MARS', 'AVRIL', 'MAI', 'JUIN', 'JUILLET', 'AOÛT', 'SEPTEMBRE', 'OCTOBRE', 'NOVEMBRE', 'DÉCEMBRE'];
    const d = this.currentDate;
    return `${d.getDate().toString().padStart(2, '0')} ${months[d.getMonth()]} ${d.getFullYear()}`;
  }

  imprimer() {
    window.print();
  }

  exporterWord() {
    const tableId = 'teecReport';
    const filename = `TEEC_${this.getNomCompagnieSelectionnee().replace(/[\s\/]/g, '_')}_${this.currentDate.toISOString().split('T')[0]}.doc`;
    
    // Le code HTML source avec balises MSOffice pour forcer la reconnaissance du format Word
    const header = `<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
      <head>
        <meta charset='utf-8'>
        <title>TEEC</title>
        <style>
          @page Section1 { size: 841.9pt 595.3pt; mso-page-orientation: landscape; margin: 36.0pt 36.0pt 36.0pt 36.0pt; }
          div.Section1 { page: Section1; }
          body { font-family: 'Times New Roman', serif; }
          .report-header { text-align: center; margin-bottom: 20px; font-weight: bold; }
          .report-title h1 { text-align: center; text-decoration: underline; font-size: 18pt; }
          .report-title h3 { text-align: center; font-size: 14pt; }
          table { width: 100%; border-collapse: collapse; font-size: 8pt; }
          th, td { border: 1pt solid black; padding: 4pt; text-align: center; }
          th { background-color: #f2f2f2; font-weight: bold; }
          .text-left { text-align: left; }
          .badge-ops { font-weight: bold; }
        </style>
      </head><body><div class="Section1">`;
    
    const footer = `</div></body></html>`;
    
    const element = document.getElementById(tableId);
    if (!element) return;
    
    // Convertir l'element HTML en string pour Word (on inclut le contenu)
    const sourceHTML = header + element.innerHTML + footer;
    
    // Préparer un Blob avec le type mime MS Word
    const source = 'data:application/vnd.ms-word;charset=utf-8,' + encodeURIComponent(sourceHTML);
    
    // Créer un lien de téléchargement
    const fileDownload = document.createElement("a");
    document.body.appendChild(fileDownload);
    fileDownload.href = source;
    fileDownload.download = filename;
    fileDownload.click();
    document.body.removeChild(fileDownload);
  }

  // Helper pour vérifier si une catégorie est vide
  hasValue(val?: any): boolean {
    return val !== null && val !== undefined && val !== '';
  }

  // Retourne true si la ligne courante commence un nouveau groupe de compagnie
  // Utilisé uniquement en vue "toutes les unités" (aucune compagnie sélectionnée)
  isNewCompagnie(index: number): boolean {
    if (this.selectedCompagnieId) return false; // Vue mono-compagnie : pas de séparateur
    if (index === 0) return true;               // Première ligne : toujours un nouveau groupe
    return this.teecRows[index].nomCompagnie !== this.teecRows[index - 1].nomCompagnie;
  }
}