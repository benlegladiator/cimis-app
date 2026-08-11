import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-settings-home',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './settings-home.html',
  styleUrls: ['./settings-home.scss']
})
export class SettingsHome {
  categories = [
    {
      id: 'hierarchy',
      title: '🌍 Hiérarchie / Units',
      desc: 'Gérer les RMIA, Brigades, Bataillons et Compagnies.',
      icon: 'fa-sitemap',
      link: '/settings/hierarchy'
    },
    {
      id: 'ranks',
      title: '🎖️ Grades & Référentiels',
      desc: 'Gérer la liste des grades et catégories militaires.',
      icon: 'fa-medal',
      link: '/settings/ranks'
    },
    {
      id: 'system',
      title: '⚙️ Paramètres Système',
      desc: 'Configuration globale, logos et informations du système.',
      icon: 'fa-desktop',
      link: '/settings/system'
    }

  ];
}
