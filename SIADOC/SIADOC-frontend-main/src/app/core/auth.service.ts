import { environment } from '@env/environment';
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, tap } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class AuthService {

  private API = `${environment.apiUrl}/api/auth`;

  private currentUserSubject = new BehaviorSubject<any>(
    JSON.parse(localStorage.getItem('user') || 'null')
  );

  currentUser$ = this.currentUserSubject.asObservable();

  constructor(private http: HttpClient) {}

  // ================= LOGIN =================
  login(username: string, password: string) {
    return this.http.post<any>(
      `${this.API}/login`,
      { username, password },
      { withCredentials: true }
    ).pipe(
      tap(user => {
        localStorage.setItem('user', JSON.stringify(user));
        this.currentUserSubject.next(user);
      })
    );
  }

   // ================= LOGOUT =================
  logout() {
    return this.http.post(
      `${this.API}/logout`,
      {},
      { withCredentials: true }
    ).pipe(
      tap(() => this.clearUser())
    );
  }

  // ================= PASSWORD =================
  changePassword(oldPassword: string, newPassword: string) {
    return this.http.put<any>(
      `${this.API}/me/password`,
      { oldPassword, newPassword },
      { withCredentials: true }
    );
  }

  // ================= SESSION =================
  clearUser() {
    localStorage.removeItem('user');
    this.currentUserSubject.next(null);
  }

  setUser(user: any) {
    localStorage.setItem('user', JSON.stringify(user));
    this.currentUserSubject.next(user);
  }

  getUser() {
    return this.currentUserSubject.value;
  }

  isLoggedIn(): boolean {
    return !!this.getUser();
  }

  loadUser() {
    return this.http.get<any>(
      `${this.API}/me`,
      { withCredentials: true }
    );
  }

}
