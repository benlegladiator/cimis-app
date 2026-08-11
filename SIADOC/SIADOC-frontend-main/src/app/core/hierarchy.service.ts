import { environment } from '@env/environment';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class HierarchyService {
  private API = `${environment.apiUrl}/api`;

  constructor(private http: HttpClient) {}

  getRMIA(): Observable<any[]> {
    return this.http.get<any[]>(`${this.API}/region-militaires`);
  }

  getBrigades(): Observable<any[]> {
    return this.http.get<any[]>(`${this.API}/brigades`);
  }

  getBataillons(): Observable<any[]> {
    return this.http.get<any[]>(`${this.API}/bataillons`);
  }

  getCompagnies(): Observable<any[]> {
    return this.http.get<any[]>(`${this.API}/compagnies`);
  }

  importHierarchy(): Observable<string> {
    return this.http.post<string>(`${this.API}/admin/hierarchy/import`, {}, { responseType: 'text' as 'json' });
  }
}
