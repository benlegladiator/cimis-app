import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { environment } from '@env/environment';
import { NotificationService } from '../../core/notification.service';
import { MilitaireService } from '../militaires/militaire';
import { SiadocNotification, Role } from '../../core/models';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../core/auth.service';

@Component({
  selector: 'app-notifications',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './notifications.html',
  styleUrls: ['./notifications.scss']
})
export class Notifications implements OnInit {

  notifications: SiadocNotification[] = [];
  loading = true;
  user: any = null;
  Role = Role;

  constructor(
    private notify: NotificationService,
    private militaireService: MilitaireService,
    private auth: AuthService,
    private http: HttpClient
  ) {}

  ngOnInit(): void {
    this.user = this.auth.getUser();
    this.loadNotifications();
  }

  loadNotifications() {
    this.loading = true;
    if (this.user?.role === Role.DRH) {
      this.notify.getDrhNotifications().subscribe(data => { this.notifications = data; this.loading = false; });
    } else if (this.user?.role === Role.BATAILLON) {
      this.notify.getMonBataillon().subscribe(data => { this.notifications = data; this.loading = false; });
    } else if (this.user?.role === Role.BRIGADE) {
      this.notify.getMaBrigade().subscribe(data => { this.notifications = data; this.loading = false; });
    } else if (this.user?.role === Role.RMIA) {
      this.notify.getMaRegion().subscribe(data => { this.notifications = data; this.loading = false; });
    } else {
      this.notify.getMaCompagnie().subscribe(data => { this.notifications = data; this.loading = false; });
    }
  }

  confirmerReception(n: SiadocNotification) {
    this.militaireService.recevoir(n.militaire.id).subscribe(() => {
      this.loadNotifications();
      alert("Dossier reçu avec succès ! / File received successfully !");
    });
  }

  approuver(n: SiadocNotification) {
    if (!n.dossierConcerne?.id) return;
    if (!confirm("Approuver ces modifications ? / Approve these changes ?")) return;
    this.http.post(`${environment.apiUrl}/api/dossiers/${n.dossierConcerne.id}/approuver`, {})
      .subscribe(() => {
        alert("Modifications approuvées / Changes approved ✅");
        this.loadNotifications();
      });
  }

  rejeter(n: SiadocNotification) {
    if (!n.dossierConcerne?.id) return;
    const motif = prompt("Motif du rejet / Reason for rejection :");
    if (!motif) return;
    this.http.post(`${environment.apiUrl}/api/dossiers/${n.dossierConcerne.id}/rejeter`, motif)
      .subscribe(() => {
        alert("Modifications rejetées / Changes rejected ❌");
        this.loadNotifications();
      });
  }

  marquerLue(n: SiadocNotification) {
    if (!n.id) return;
    this.notify.marquerCommeLue(n.id)
      .subscribe(() => {
        this.loadNotifications();
      });
  }

}
