import { environment } from '@env/environment';
import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface DonneeBiometriqueReponse {
  matricule: string;
  nomMilitaire: string;
  prenomMilitaire: string;
  empreinteDoigt1: string | null;
  empreinteDoigt1Type: string | null;
  empreinteDoigt2: string | null;
  empreinteDoigt2Type: string | null;
  photoVisage: string | null;
  photoVisageType: string | null;
  qrCodeImage: string | null;
  qrCodeContenu: string | null;
  numeroCIM: string | null;
  dateReception: string;
  sourceApplication: string;
}

@Injectable({
  providedIn: 'root'
})
export class CimisService {
  private http = inject(HttpClient);
  private baseUrl = `${environment.apiUrl}/api/import/cimis`;

  /**
   * Consulte les données biométriques stockées pour un militaire.
   */
  getBiometrie(matricule: string): Observable<DonneeBiometriqueReponse> {
    return this.http.get<DonneeBiometriqueReponse>(`${this.baseUrl}/biometrie`, {
      params: { matricule }
    });
  }

  /**
   * Vérifie si des données biométriques existent pour un matricule.
   * Utilisé pour afficher un indicateur sur la liste des militaires.
   */
  hasBiometrie(matricule: string): Observable<DonneeBiometriqueReponse> {
    return this.http.get<DonneeBiometriqueReponse>(`${this.baseUrl}/biometrie`, {
      params: { matricule }
    });
  }

  /**
   * Appelle l'API CIMIS en direct via le pont SIADOC.
   */
  getCarteDirecte(matricule: string): Observable<any> {
    return this.http.get<any>(`${environment.apiUrl}/api/integration/cimis/carte/${matricule}`);
  }

  /**
   * Teste la liaison avec le serveur CIMIS.
   */
  testConnection(): Observable<string> {
    return this.http.get(`${environment.apiUrl}/api/integration/cimis/test-connection`, { responseType: 'text' });
  }

  simulateExport(matricule: string): Observable<any> {
    return this.http.get<any>(`${environment.apiUrl}/api/integration/cimis/simulate-export?matricule=${matricule}`);
  }

  getListe(page: number = 1, limit: number = 20, grade: string = '', unite: string = '', search: string = ''): Observable<any> {
    let params = `?page=${page}&limit=${limit}`;
    if (grade) params += `&grade=${encodeURIComponent(grade)}`;
    if (unite) params += `&unite=${encodeURIComponent(unite)}`;
    if (search) params += `&search=${encodeURIComponent(search)}`;
    return this.http.get<any>(`${environment.apiUrl}/api/integration/cimis/liste${params}`);
  }

  /**
   * Récupère la situation d'un militaire depuis GESMIL
   */
  getGesmilSituation(matricule: string): Observable<any> {
    return this.http.get<any>(`${environment.apiUrl}/api/integration/gesmil/situation/${matricule}`);
  }
}
