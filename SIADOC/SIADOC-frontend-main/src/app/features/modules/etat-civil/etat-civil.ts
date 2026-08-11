import { environment } from '@env/environment';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class EtatCivilService {

  private apiUrl = `${environment.apiUrl}/api/pieces`;

  constructor(private http: HttpClient) {}

  listerParDossier(dossierId: number): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/dossier/${dossierId}`);
  }

  creer(formData: FormData): Observable<any> {
    return this.http.post(this.apiUrl, formData);
  }
}
