import { environment } from '@env/environment';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { SiadocNotification } from './models';

@Injectable({
  providedIn: 'root'
})
export class NotificationService {

  private apiUrl = `${environment.apiUrl}/api/notifications`;

  constructor(private http: HttpClient) {}

  getMaCompagnie(): Observable<SiadocNotification[]> {
    return this.http.get<SiadocNotification[]>(`${this.apiUrl}/ma-compagnie`, { withCredentials: true });
  }

  getDrhNotifications(): Observable<SiadocNotification[]> {
    return this.http.get<SiadocNotification[]>(`${this.apiUrl}/drh`, { withCredentials: true });
  }

  getMonBataillon(): Observable<SiadocNotification[]> {
    return this.http.get<SiadocNotification[]>(`${this.apiUrl}/mon-bataillon`, { withCredentials: true });
  }

  getMaBrigade(): Observable<SiadocNotification[]> {
    return this.http.get<SiadocNotification[]>(`${this.apiUrl}/ma-brigade`, { withCredentials: true });
  }

  getMaRegion(): Observable<SiadocNotification[]> {
    return this.http.get<SiadocNotification[]>(`${this.apiUrl}/ma-region`, { withCredentials: true });
  }

  getById(id: string): Observable<SiadocNotification> {
    return this.http.get<SiadocNotification>(`${this.apiUrl}/${id}`);
  }

  marquerCommeLue(id: string): Observable<void> {
    return this.http.post<void>(`${this.apiUrl}/${id}/lu`, {});
  }
}
