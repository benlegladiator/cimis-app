import { environment } from '@env/environment';
import { Component, Input, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';

@Component({
  selector: 'app-historique',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './historique.html',
  styles: [`
    .historique-container {
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    th, td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    th {
      background-color: #f4f6f8;
      color: #333;
      font-weight: 600;
    }
    .badge-action {
      background-color: #e3f2fd;
      color: #1976d2;
      padding: 4px 8px;
      border-radius: 12px;
      font-size: 0.85em;
      font-weight: bold;
    }
    .empty-state {
      padding: 20px;
      text-align: center;
      color: #666;
    }
  `]
})
export class Historique implements OnInit {
  @Input() militaireId!: string;
  
  logs: any[] = [];
  loading = true;

  constructor(private http: HttpClient) {}

  ngOnInit(): void {
    if (this.militaireId) {
      this.chargerHistorique();
    }
  }

  chargerHistorique() {
    this.http.get<any[]>(`${environment.apiUrl}/api/militaires/${this.militaireId}/historique`, { withCredentials: true })
      .subscribe({
        next: (data) => {
          this.logs = data;
          this.loading = false;
        },
        error: (err) => {
          console.error("Erreur chargement historique", err);
          this.loading = false;
        }
      });
  }
}
