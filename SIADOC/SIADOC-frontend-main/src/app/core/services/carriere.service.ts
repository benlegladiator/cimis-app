import { environment } from '@env/environment';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { CarriereDTO, Reengagement, AdmissionSoc } from '../models';

@Injectable({
  providedIn: 'root'
})
export class CarriereService {

  private api = `${environment.apiUrl}/api/carriere`;

  constructor(private http: HttpClient) { }

  getCarriere(militaireId: string): Observable<CarriereDTO> {
    return this.http.get<CarriereDTO>(`${this.api}/${militaireId}`);
  }

  updateCarriere(militaireId: string, data: CarriereDTO): Observable<CarriereDTO> {
    return this.http.put<CarriereDTO>(`${this.api}/${militaireId}`, data);
  }

  addReengagement(militaireId: string, data: Reengagement): Observable<Reengagement> {
    return this.http.post<Reengagement>(`${this.api}/${militaireId}/reengagement`, data);
  }

  deleteReengagement(id: string): Observable<void> {
    return this.http.delete<void>(`${this.api}/reengagement/${id}`);
  }

  addAdmission(militaireId: string, data: AdmissionSoc): Observable<AdmissionSoc> {
    return this.http.post<AdmissionSoc>(`${this.api}/${militaireId}/admission`, data);
  }

  deleteAdmission(id: string): Observable<void> {
    return this.http.delete<void>(`${this.api}/admission/${id}`);
  }

  uploadDocument(militaireId: string, file: File): Observable<any> {
    const formData = new FormData();
    formData.append('file', file);
    return this.http.post(`${this.api}/${militaireId}/fichier`, formData);
  }
}
