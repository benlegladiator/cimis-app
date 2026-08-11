import { environment } from '@env/environment';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class GradeService {
  private apiUrl = `${environment.apiUrl}/api/grades`;

  constructor(private http: HttpClient) {}

  getGradesParArmee(): Observable<Record<string, string[]>> {
    return this.http.get<Record<string, string[]>>(this.apiUrl);
  }

  getGradesGroupes(): Observable<Record<string, Record<string, string[]>>> {
    return this.http.get<Record<string, Record<string, string[]>>>(`${this.apiUrl}/groupes`);
  }
}
