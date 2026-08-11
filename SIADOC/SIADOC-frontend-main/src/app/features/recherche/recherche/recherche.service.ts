import { environment } from '@env/environment';
import { Injectable } from '@angular/core'
import { HttpClient } from '@angular/common/http'
import { Observable } from 'rxjs'
import { RechercheEtatCivil, ResultRechercheEtatCivil,
    RechercheMariage, ResultRechercheMariage
 } from '../models/search.model'

@Injectable({
  providedIn: 'root'
})
export class RechercheService {

  private api = `${environment.apiUrl}/api/recherche`

  constructor(private http: HttpClient) {}

  rechercherActeNaissance(data: RechercheEtatCivil): Observable<ResultRechercheEtatCivil[]> {

    return this.http.post<ResultRechercheEtatCivil[]>(
      `${this.api}/etat-civil/naissance`,
      data
    )

  }

  rechercherMariage(data: RechercheMariage){
    return this.http.post<ResultRechercheMariage[]>(
        `${this.api}/etat-civil/mariage`,
        data
    )

  }

  rechercherRecompenses(data: any){
    return this.http.post<any[]>(
        `${this.api}/recompenses`,
        data
    )

  }

  rechercherCni(data: any){
    return this.http.post<any[]>(
        `${this.api}/etat-civil/cni`,
        data
    )

  }
  rechercherPunitions(data: any){
    return this.http.post<any[]>(
        `${this.api}/punitions`,
        data
    )

    }

    rechercherDiplomes(data: any){
        return this.http.post<any[]>(
            `${this.api}/diplomes`,
            data
        )
    }

    rechercherMilitaire(data: any): Observable<any[]> {
        return this.http.post<any[]>(`${this.api}/militaires`, data);
    }
    rechercherAvancements(data: any): Observable<any[]> {
        return this.http.post<any[]>(`${this.api}/avancements`, data);
    }
    rechercherStages(data: any): Observable<any[]> {
        return this.http.post<any[]>(`${this.api}/stages`, data);
    }
    rechercherMedicals(data: any): Observable<any[]> {
        return this.http.post<any[]>(`${this.api}/medicals`, data);
    }
    rechercherCarrieres(data: any): Observable<any[]> {
        return this.http.post<any[]>(`${this.api}/carrieres`, data);
    }
    rechercherMutations(data: any): Observable<any[]> {
        return this.http.post<any[]>(`${this.api}/mutations`, data);
    }
    rechercherNotations(data: any): Observable<any[]> {
        return this.http.post<any[]>(`${this.api}/notations`, data);
    }
    rechercherCampagneMilitaire(data: any): Observable<any[]> {
        return this.http.post<any[]>(`${this.api}/campagnes`, data);
    }

}