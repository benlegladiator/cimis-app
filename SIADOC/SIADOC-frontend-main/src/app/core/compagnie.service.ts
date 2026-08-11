import { environment } from '@env/environment';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { Compagnie } from './models';

@Injectable({
  providedIn: 'root'
})
export class CompagnieService {

  private apiUrl = `${environment.apiUrl}/api`;

  constructor(private http: HttpClient) {}

  lister(): Observable<Compagnie[]> {
    return this.http.get<Compagnie[]>(`${this.apiUrl}/compagnies`);
  }

  listerRMIA(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/region-militaires`);
  }

  listerBrigades(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/brigades`);
  }

  listerBataillons(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/bataillons`);
  }
}
