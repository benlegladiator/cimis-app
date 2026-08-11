import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { PersonnelCivilService } from '../../core/personnel-civil.service';
import { PersonnelCivil } from '../../core/models';

@Component({
  selector: 'app-civil-archive-list',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './civil-archive-list.html',
  styleUrls: ['./civil-archive-list.scss']
})
export class CivilArchiveList implements OnInit {
  personnels: PersonnelCivil[] = [];
  showModal = false;
  
  newPersonnel: any = {
    nom: '',
    prenom: '',
    dateNaissance: '',
    lieuNaissance: '',
    sexe: 'M',
    matricule: '',
    dateEntreeService: '',
    poste: ''
  };

  constructor(private service: PersonnelCivilService) {}

  ngOnInit(): void {
    this.charger();
  }

  charger() {
    this.service.lister().subscribe(data => this.personnels = data);
  }

  ouvrirModal() {
    this.showModal = true;
  }

  fermerModal() {
    this.showModal = false;
  }

  enregistrer() {
    this.service.ajouter(this.newPersonnel).subscribe(() => {
      this.fermerModal();
      this.charger();
      this.resetForm();
    });
  }

  resetForm() {
    this.newPersonnel = {
      nom: '',
      prenom: '',
      dateNaissance: '',
      lieuNaissance: '',
      sexe: 'M',
      matricule: '',
      dateEntreeService: '',
      poste: ''
    };
  }
}
