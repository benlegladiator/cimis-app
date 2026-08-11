import { environment } from '@env/environment';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class SettingsService {
  private api = `${environment.apiUrl}/api/settings/system`;

  constructor(private http: HttpClient) {}

  getSystemSettings(): Observable<any> {
    return this.http.get<any>(this.api);
  }

  updateSystemSettings(settings: any): Observable<any> {
    return this.http.put<any>(this.api, settings);
  }
}
