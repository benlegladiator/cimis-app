import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { Router, NavigationEnd } from '@angular/router';
import { filter } from 'rxjs/operators';
import { Location } from '@angular/common';
import { LucideAngularModule, Folder, PlusCircle, Search } from 'lucide-angular';
import { AuthService } from '../../core/auth.service';
import { NotificationService } from '../../core/notification.service';
import { Role } from '../../core/models';

@Component({
  selector: 'app-shell',
  standalone: true,
  imports: [CommonModule, RouterModule, LucideAngularModule],
  templateUrl: './shell.html',
  styleUrls: ['./shell.scss']
})
export class Shell {

  user: any = null;
  Role = Role;
  notificationsCount = 0;

  constructor(
      public router: Router,
      private location: Location,
      private auth: AuthService,
      private notify: NotificationService
    ) {
      this.router.events
        .pipe(filter(event => event instanceof NavigationEnd))
        .subscribe(() => {
          window.scrollTo(0, 0);
        });
    }
    retourGlobal() {
      this.location.back();
    }

    ngOnInit() {
      this.auth.loadUser().subscribe({
        next: (user: any) => {
          this.auth.setUser(user);
          this.user = user;
          const rolesWithNotifs = [Role.DRH, Role.COMMANDANT_COMPAGNIE, Role.BATAILLON, Role.BRIGADE, Role.RMIA];
          if (rolesWithNotifs.includes(this.user?.role)) {
            this.loadNotifications();
            setInterval(() => this.loadNotifications(), 60000);
          }
        },
        error: () => {
          this.logout();
        }
      });
    }

    loadNotifications() {
      if (!this.user) return;
      
      let obs;
      if (this.user.role === Role.DRH) obs = this.notify.getDrhNotifications();
      else if (this.user.role === Role.BATAILLON) obs = this.notify.getMonBataillon();
      else if (this.user.role === Role.BRIGADE) obs = this.notify.getMaBrigade();
      else if (this.user.role === Role.RMIA) obs = this.notify.getMaRegion();
      else if (this.user.role === Role.COMMANDANT_COMPAGNIE) obs = this.notify.getMaCompagnie();

      if (obs) {
        obs.subscribe({
          next: data => { this.notificationsCount = data.length; },
          error: err => { console.error('Erreur notifications', err); }
        });
      }
    }

    dossierOpen = false;
    archiveOpen = false;

    readonly FolderIcon = Folder;
    readonly PlusIcon = PlusCircle;
    readonly SearchIcon = Search;

    toggleDossier() {
      this.dossierOpen = !this.dossierOpen;
      this.archiveOpen = false;
    }

    toggleArchive() {
      this.archiveOpen = !this.archiveOpen;
      this.dossierOpen = false;
    }

  logout() {
    this.auth.logout().subscribe({
      next: () => {
        this.auth.clearUser();
        localStorage.clear();
        sessionStorage.clear();
        window.location.href = '/login';
      },
      error: () => {
        window.location.href = '/login';
      }
    });
  }


}
