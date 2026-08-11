import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '@env/environment';
import { PersonnelCivil, DocumentCivil } from './models';

@Injectable({
  providedIn: 'root'
})
export class PersonnelCivilService {
  private apiUrl = `${environment.apiUrl}/api/personnel-civil`;

  constructor(private http: HttpClient) { }

  lister(): Observable<PersonnelCivil[]> {
    return this.http.get<PersonnelCivil[]>(this.apiUrl);
  }

  getById(id: string): Observable<PersonnelCivil> {
    return this.http.get<PersonnelCivil>(`${this.apiUrl}/${id}`);
  }

  ajouter(personnel: PersonnelCivil): Observable<PersonnelCivil> {
    return this.http.post<PersonnelCivil>(this.apiUrl, personnel);
  }

  listerDocuments(id: string): Observable<DocumentCivil[]> {
    return this.http.get<DocumentCivil[]>(`${this.apiUrl}/${id}/documents`);
  }

  ajouterDocument(id: string, label: string, fichier: File): Observable<DocumentCivil> {
    const formData = new FormData();
    formData.append('label', label);
    formData.append('fichier', fichier);
    return this.http.post<DocumentCivil>(`${this.apiUrl}/${id}/documents`, formData);
  }

  downloadUrl(docId: string): string {
    return `${this.apiUrl}/documents/${docId}/download`;
  }
}
