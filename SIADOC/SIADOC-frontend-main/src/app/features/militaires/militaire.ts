import { environment } from '@env/environment';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Militaire, AffectationRequestDTO } from '../../core/models';

@Injectable({
  providedIn: 'root'
})
export class MilitaireService {

  private apiUrl = `${environment.apiUrl}/api/militaires`;

  constructor(private http: HttpClient) {}

  lister(): Observable<Militaire[]> {
    return this.http.get<Militaire[]>(this.apiUrl);
  }

  getByCompagnieNom(nom: string): Observable<Militaire[]> {
    return this.http.get<Militaire[]>(`${this.apiUrl}/by-compagnie-nom`, { params: { nom } });
  }

  getByUniteNom(nom: string): Observable<Militaire[]> {
    return this.http.get<Militaire[]>(`${this.apiUrl}/by-unite-nom`, { params: { nom } });
  }

  creer(formData: FormData): Observable<Militaire> {
    return this.http.post<Militaire>(this.apiUrl, formData);
  }

  getById(id: string): Observable<Militaire> {
    return this.http.get<Militaire>(`${this.apiUrl}/${id}`);
  }

  listerEnAttente(): Observable<Militaire[]> {
    return this.http.get<Militaire[]>(`${this.apiUrl}/en-attente`);
  }

  creerNouvelleRecrue(militaire: Partial<Militaire>, compagnieId: string): Observable<Militaire> {
    return this.http.post<Militaire>(`${this.apiUrl}/nouvelle-recrue`, militaire, {
      params: { compagnieId }
    });
  }

  affecter(id: string, request: AffectationRequestDTO): Observable<Militaire> {
    return this.http.post<Militaire>(`${this.apiUrl}/${id}/affecter`, request);
  }

  getMaCompagnie(): Observable<Militaire[]> {
    return this.http.get<Militaire[]>(`${this.apiUrl}/ma-compagnie`, { withCredentials: true });
  }

  recevoir(id: string): Observable<string> {
    return this.http.post(`${this.apiUrl}/${id}/recevoir`, {}, { responseType: 'text' });
  }

  valider(id: string): Observable<string> {
    return this.http.post(`${this.apiUrl}/${id}/valider`, {}, { responseType: 'text' });
  }

  rejeter(id: string): Observable<string> {
    return this.http.post(`${this.apiUrl}/${id}/rejeter`, {}, { responseType: 'text' });
  }

  listerRetraites(): Observable<Militaire[]> {
    return this.http.get<Militaire[]>(`${this.apiUrl}/retraites`);
  }
}

