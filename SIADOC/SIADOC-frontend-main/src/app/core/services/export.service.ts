import { environment } from '@env/environment';
import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class ExportDataService {
  private http = inject(HttpClient);
  private apiUrl = `${environment.apiUrl}/api/export`;
  private keyApiUrl = `${environment.apiUrl}/api/admin/api-keys`;
  private mappingUrl = `${environment.apiUrl}/api/admin/gesmil-mappings`;
  private compagnieUrl = `${environment.apiUrl}/api/compagnies`;

  // --- EXPORT ---
  getFullDossier(matricule: string): Observable<any> {
    return this.http.get(`${this.apiUrl}/dossier`, { params: { matricule } });
  }

  // --- IMPORT unitaire ---
  importDossier(dto: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/import`, dto);
  }

  // --- IMPORT en lot (tableau JSON) ---
  importBulk(dtos: any[]): Observable<any> {
    return this.http.post(`${this.apiUrl}/import/bulk`, dtos);
  }

  // --- Pool : militaires sans compagnie ---
  getNonAffectes(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/non-affectes`);
  }

  assignCompany(militaireIds: string[], compagnieId: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/assign-company`, { militaireIds, compagnieId });
  }

  // --- API KEYS ---
  getKeys(): Observable<any[]> {
    return this.http.get<any[]>(this.keyApiUrl);
  }
  createKey(appName: string): Observable<any> {
    return this.http.post(this.keyApiUrl, null, { params: { appName } });
  }
  deleteKey(id: string): Observable<any> {
    return this.http.delete(`${this.keyApiUrl}/${id}`);
  }

  // --- CORRESPONDANCES GESMIL ↔ Compagnie ---
  getMappings(): Observable<any[]> {
    return this.http.get<any[]>(this.mappingUrl);
  }
  createMapping(codeGesmil: string, compagnieId: string): Observable<any> {
    return this.http.post(this.mappingUrl, { codeGesmil, compagnieId });
  }
  deleteMapping(id: string): Observable<any> {
    return this.http.delete(`${this.mappingUrl}/${id}`);
  }

  // --- Compagnies (pour le select) ---
  getCompagnies(): Observable<any[]> {
    return this.http.get<any[]>(`${this.compagnieUrl}`);
  }
}
