import { environment } from '@env/environment';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class DashboardService {
  private API = `${environment.apiUrl}/api/dashboards`;

  constructor(private http: HttpClient) {}

  getRmiaStats(id: string): Observable<any> {
    return this.http.get<any>(`${this.API}/rmia/${id}`);
  }

  getBrigadeStats(id: string): Observable<any> {
    return this.http.get<any>(`${this.API}/brigade/${id}`);
  }

  getBataillonStats(id: string): Observable<any> {
    return this.http.get<any>(`${this.API}/bataillon/${id}`);
  }

  getCompagnieStats(id: string): Observable<any> {
    return this.http.get<any>(`${this.API}/compagnie/${id}`);
  }

  getDrhStats(): Observable<any> {
    return this.http.get<any>(`${this.API}/drh`);
  }

  getEtatMajorStats(): Observable<any> {
    return this.http.get<any>(`${this.API}/etat-major`);
  }
}
