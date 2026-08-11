import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../core/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './login.html',
  styleUrls: ['./login.scss']
})
export class Login {

  username = '';
  password = '';
  errorMessage = '';

  constructor(
    private auth: AuthService,
    private router: Router
  ) {}

  login() {

    this.auth.login(this.username, this.password)
      .subscribe({
        next: (user: any) => {

          console.log('Connecté:', user);

          // IMPORTANT
          this.auth.setUser(user);

          // navigation conditionnelle selon le rôle
          if (user.role === 'DRH') {
            this.router.navigateByUrl('/administration/structure');
          } else {
            this.router.navigateByUrl('/dashboard');
          }

        },
        error: () => {
          this.errorMessage = "Identifiants incorrects";
        }
      });
  }
}
