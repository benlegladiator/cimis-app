import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { environment } from '@env/environment';

@Component({
  selector: 'app-civil-settings',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './civil-settings.html',
  styleUrls: ['./civil-settings.scss']
})
export class CivilSettings implements OnInit {
  settings: any[] = [];
  
  showModal = false;
  currentSetting: any = { type: 'POSTE', label: '' };
  isEditing = false;

  types = [
    { id: 'POSTE', label: 'Postes / Fonctions' },
    { id: 'TYPE_DOCUMENT', label: 'Types de Documents' }
  ];

  constructor(private http: HttpClient) {}

  ngOnInit(): void {
    this.chargerSettings();
  }

  chargerSettings() {
    this.http.get<any[]>(`${environment.apiUrl}/api/settings/civil`).subscribe(data => {
      this.settings = data;
    });
  }

  ouvrirModal(s: any = null) {
    if (s) {
      this.currentSetting = { ...s };
      this.isEditing = true;
    } else {
      this.currentSetting = { type: 'POSTE', label: '' };
      this.isEditing = false;
    }
    this.showModal = true;
  }

  fermerModal() {
    this.showModal = false;
  }

  enregistrer() {
    this.http.post(`${environment.apiUrl}/api/settings/civil`, this.currentSetting).subscribe(() => {
      this.fermerModal();
      this.chargerSettings();
    });
  }

  supprimer(id: string) {
    if (confirm('Voulez-vous vraiment supprimer cet élément ?')) {
      this.http.delete(`${environment.apiUrl}/api/settings/civil/${id}`).subscribe(() => {
        this.chargerSettings();
      });
    }
  }

  getSettingsByType(type: string) {
    return this.settings.filter(s => s.type === type);
  }
}
