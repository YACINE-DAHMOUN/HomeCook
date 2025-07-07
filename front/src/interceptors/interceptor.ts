import { Injectable } from '@angular/core';
import { HttpInterceptor, HttpRequest, HttpHandler, HttpEvent, HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError } from 'rxjs';
import { AuthService } from '../app/services/auth.service';
import { Router } from '@angular/router';
import { environment } from '../environments/environment';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  

  constructor(
    private authService: AuthService,
    private router: Router
  ) {}

  intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    // Ajouter withCredentials pour toutes les requêtes vers l'API
    if (req.url.startsWith(environment.apiUrl)) {
      const authReq = req.clone({
        setHeaders: {
          'Content-Type': 'application/ld+json',
          'Accept': 'application/ld+json'
        },
        withCredentials: true // Inclure les cookies dans les requêtes
      });
      return next.handle(authReq);
    }

    return next.handle(req);
  }

  private isAuthRequest(url: string): boolean {
    return url.includes('/auth/login') || url.includes('/auth/register');
  }
}


