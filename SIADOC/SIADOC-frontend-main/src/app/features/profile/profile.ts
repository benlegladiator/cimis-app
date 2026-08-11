import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../core/auth.service';

@Component({
  selector: 'app-profile',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './profile.html',
  styleUrls: ['./profile.scss']
})
export class ProfileComponent implements OnInit {

  user: any;
  passwordData = {
    oldPassword: '',
    newPassword: '',
    confirmPassword: ''
  };
  
  errorMsg: string = '';
  successMsg: string = '';
  isLoading: boolean = false;

  constructor(
    private auth: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    this.user = this.auth.getUser();
    if (!this.user) {
        // Fallback or retry
        this.auth.loadUser().subscribe({
            next: u => {
                this.user = u;
            },
            error: () => this.router.navigate(['/login'])
        });
    }
  }

  getUnitName(): string {
    if (!this.user) return 'N/A';
    if (this.user.region) return this.user.region.nom;
    if (this.user.brigade) return this.user.brigade.nom;
    if (this.user.bataillon) return this.user.bataillon.nom;
    if (this.user.compagnie) return this.user.compagnie.nom;
    if (this.user.secteur) return this.user.secteur.nom;
    return 'Secrétariat Général';
  }

  changePassword(): void {
    this.errorMsg = '';
    this.successMsg = '';

    if (!this.passwordData.oldPassword || !this.passwordData.newPassword || !this.passwordData.confirmPassword) {
      this.errorMsg = 'Tous les champs sont obligatoires.';
      return;
    }

    if (this.passwordData.newPassword !== this.passwordData.confirmPassword) {
      this.errorMsg = 'Les nouveaux mots de passe ne correspondent pas.';
      return;
    }

    if (this.passwordData.newPassword.length < 6) {
      this.errorMsg = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
      return;
    }

    this.isLoading = true;
    this.auth.changePassword(this.passwordData.oldPassword, this.passwordData.newPassword)
      .subscribe({
        next: () => {
          this.isLoading = false;
          this.successMsg = 'Mot de passe modifié avec succès. Déconnexion en cours...';
          setTimeout(() => {
              this.auth.logout().subscribe(() => {
                  this.router.navigate(['/login']);
              });
          }, 2000);
        },
        error: (err) => {
          this.isLoading = false;
          console.error(err);
          // Le backend renvoie souvent les erreurs dans err.error.message ou similaire
          this.errorMsg = err.error?.message || 'Erreur lors de la modification. Vérifiez votre ancien mot de passe.';
        }
      });
  }
}
