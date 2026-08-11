import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { SettingsService } from '../../core/services/settings.service';

@Component({
  selector: 'app-system-settings',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterModule],
  templateUrl: './system-settings.html',
  styleUrls: ['./system-settings.scss']
})
export class SystemSettings implements OnInit {
  activeTab: string = 'institution';
  loading: boolean = false;

  settings: any = {
    institutionName: '',
    directionName: '',
    motto: '',
    appVersion: '',
    fiscalYear: new Date().getFullYear(),
    maxFileSize: 5,
    allowedExtensions: '.pdf, .jpg, .png',
    supportEmail: '',
    defaultLanguage: 'fr',
    maintenanceMode: false
  };

  constructor(private settingsService: SettingsService) {}

  ngOnInit(): void {
    this.loadSettings();
  }

  loadSettings() {
    this.loading = true;
    this.settingsService.getSystemSettings().subscribe({
      next: (data) => {
        this.settings = data;
        this.loading = false;
      },
      error: (err) => {
        console.error('Error loading settings', err);
        this.loading = false;
      }
    });
  }

  setTab(tab: string) {
    this.activeTab = tab;
  }

  saveSettings() {
    this.loading = true;
    this.settingsService.updateSystemSettings(this.settings).subscribe({
      next: (data) => {
        this.settings = data;
        this.loading = false;
        alert('Paramètres enregistrés avec succès !');
      },
      error: (err) => {
        console.error('Error saving settings', err);
        this.loading = false;
        alert('Erreur lors de l\'enregistrement.');
      }
    });
  }

  onLogoUpload(event: any, type: string) {
    const file = event.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = (e: any) => {
        if (type === 'header') {
          this.settings.logoHeader = e.target.result;
        } else {
          this.settings.logoSeal = e.target.result;
        }
      };
      reader.readAsDataURL(file);
    }
  }
}
