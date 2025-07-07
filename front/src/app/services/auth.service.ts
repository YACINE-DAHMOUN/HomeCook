import { Injectable } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { BehaviorSubject, Observable, throwError } from 'rxjs';
import { map, tap, catchError } from 'rxjs/operators';
import { environment } from '../../environments/environment';
import { User, RegisterRequest, LoginRequest, AuthResponse } from '../models/user.interface';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private currentUserSubject = new BehaviorSubject<User | null>(null);
  public currentUser$ = this.currentUserSubject.asObservable();

  constructor(private http: HttpClient) {
    // Charger l'utilisateur au démarrage
    this.loadCurrentUser();
  }

  register(userData: RegisterRequest): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(
      `${environment.apiUrl}${environment.authEndpoints.register}`,
      userData,
      { withCredentials: true }
    ).pipe(
      tap(response => this.handleAuthSuccess(response)),
      catchError(this.handleError)
    );
  }

  login(credentials: LoginRequest): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(
      `${environment.apiUrl}${environment.authEndpoints.login}`,
      credentials,
      { withCredentials: true }
    ).pipe(
      tap(response => this.handleAuthSuccess(response)),
      catchError(this.handleError)
    );
  }

  logout(): Observable<any> {
    return this.http.post(
      `${environment.apiUrl}${environment.authEndpoints.logout}`,
      {},
      { withCredentials: true }
    ).pipe(
      tap(() => {
        this.currentUserSubject.next(null);
      }),
      catchError(() => {
        // Même en cas d'erreur, on déconnecte localement
        this.currentUserSubject.next(null);
        return throwError(() => 'Erreur lors de la déconnexion');
      })
    );
  }

  getCurrentUser(): User | null {
    return this.currentUserSubject.value;
  }

  isAuthenticated(): boolean {
    return this.currentUserSubject.value !== null;
  }

  getCurrentUserFromAPI(): Observable<User> {
    return this.http.get<{user: User}>(
      `${environment.apiUrl}/api/auth/me`,
      { withCredentials: true }
    ).pipe(
      map(response => response.user),
      tap(user => this.currentUserSubject.next(user)),
      catchError(error => {
        this.currentUserSubject.next(null);
        return throwError(() => error);
      })
    );
  }

  // Vérifier si l'utilisateur est connecté au démarrage de l'app
  loadCurrentUser(): void {
    this.getCurrentUserFromAPI().subscribe({
      next: (user) => {
        this.currentUserSubject.next(user);
      },
      error: () => {
        this.currentUserSubject.next(null);
      }
    });
  }

  private handleAuthSuccess(response: AuthResponse): void {
    // Plus besoin de localStorage, les cookies sont gérés automatiquement
    this.currentUserSubject.next(response.user);
  }

  private handleError = (error: HttpErrorResponse): Observable<never> => {
    let errorMessage = 'Une erreur est survenue';

    if (error.error instanceof ErrorEvent) {
      errorMessage = `Erreur: ${error.error.message}`;
    } else {
      if (error.error && typeof error.error === 'object' && error.error.error) {
        errorMessage = error.error.error;
      } else {
        switch (error.status) {
          case 400:
            errorMessage = 'Données invalides';
            break;
          case 401:
            errorMessage = 'Email ou mot de passe incorrect';
            this.currentUserSubject.next(null);
            break;
          case 409:
            errorMessage = 'Cet email ou nom d\'utilisateur est déjà utilisé';
            break;
          case 422:
            errorMessage = 'Données de validation invalides';
            break;
          case 500:
            errorMessage = 'Erreur serveur, veuillez réessayer plus tard';
            break;
          default:
            errorMessage = `Erreur ${error.status}: ${error.message}`;
        }
      }
    }

    return throwError(() => errorMessage);
  };
}
