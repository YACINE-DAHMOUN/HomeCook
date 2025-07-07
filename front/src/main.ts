import { bootstrapApplication } from '@angular/platform-browser';
import { AppComponent } from './app/app.component';
import { HTTP_INTERCEPTORS, provideHttpClient } from '@angular/common/http'; // <-- AJOUTER
import { provideRouter } from '@angular/router'; // si tu utilises le routing
import { routes } from './app/app.routes'; // adapter selon ton projet
import { AuthInterceptor } from './interceptors/interceptor';

bootstrapApplication(AppComponent, {
  providers: [
    provideHttpClient(),
    provideRouter(routes),
    { provide: HTTP_INTERCEPTORS,
      useClass: AuthInterceptor,
      multi: true
    }
  ]
});
