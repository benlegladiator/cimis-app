import { environment } from '@env/environment';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { TeecRow } from './models';

@Injectable({
  providedIn: 'root'
})
export class TeecService {

  private apiUrl = `${environment.apiUrl}/api/teec-report`;

  constructor(private http: HttpClient) {}

  getTeec(compagnieId?: string): Observable<TeecRow[]> {
    const url = compagnieId ? `${this.apiUrl}?compagnieId=${compagnieId}` : this.apiUrl;
    return this.http.get<TeecRow[]>(url);
  }
}
