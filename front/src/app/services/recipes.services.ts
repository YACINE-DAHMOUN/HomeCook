import { inject, Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { catchError, Observable, throwError } from 'rxjs';
import { environment } from '../../environments/environment';

export interface Recipe {
  id: number;
  name: string;
  description: string;
  descriptionPreview?: string;
  imageUrl?: string;
  servings: number;
  cookingTime: number;
  createdAt: string;
  instructions?: string;
  ingredients?: any[];
}

@Injectable({
  providedIn: 'root'
})
export class RecipesService {
  private apiUrl = `${environment.apiUrl}/api/recipes`;

  constructor(private http: HttpClient) { }

  // Récupérer les recettes publiques (aperçus) - SANS authentification
  getRecipesPreview(limit: number = 6): Observable<any> {
    // Options HTTP explicites sans en-têtes d'authentification
    const options = {
      headers: new HttpHeaders({
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      })
    };

    return this.http.get<any>(`${this.apiUrl}?page=1&itemsPerPage=${limit}`, options)
      .pipe(
        catchError(this.handleError)
      );
  }

  // Récupérer toutes les recettes (nécessite authentification)
  getRecipes(): Observable<any> {
    return this.http.get<any>(this.apiUrl)
      .pipe(
        catchError(this.handleError)
      );
  }

  // Récupérer une recette spécifique (version publique)
  getRecipePreview(id: number): Observable<Recipe> {
    const options = {
      headers: new HttpHeaders({
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      })
    };

    return this.http.get<Recipe>(`${this.apiUrl}/${id}`, options)
      .pipe(
        catchError(this.handleError)
      );
  }

  // Récupérer une recette spécifique (version complète avec auth)
  getRecipe(id: number): Observable<Recipe> {
    return this.http.get<Recipe>(`${this.apiUrl}/${id}`)
      .pipe(
        catchError(this.handleError)
      );
  }

  private handleError(error: any): Observable<never> {
    console.error('An error occurred:', error);

    // Message d'erreur plus informatif
    let errorMessage = 'Une erreur est survenue';
    if (error.status === 401) {
      errorMessage = 'Accès non autorisé';
    } else if (error.status === 404) {
      errorMessage = 'Ressource non trouvée';
    } else if (error.status === 500) {
      errorMessage = 'Erreur serveur';
    } else if (error.error?.message) {
      errorMessage = error.error.message;
    }

    return throwError(() => new Error(errorMessage));
  }
}
