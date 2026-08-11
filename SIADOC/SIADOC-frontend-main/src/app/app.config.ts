import { ApplicationConfig, APP_INITIALIZER, inject } from '@angular/core';
import { provideRouter, withRouterConfig } from '@angular/router';
import { routes } from './app.routes';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { AuthService } from './core/auth.service';
import { authInterceptor } from './core/auth.interceptor';

function initAuth() {
  const auth = inject(AuthService);

  return () => auth.loadUser().toPromise()
    .then(user => {
      auth.setUser(user);
    })
    .catch(() => {
      // pas connecté → on ignore
    });
}

export const appConfig: ApplicationConfig = {
  providers: [
    provideRouter(
      routes,
      withRouterConfig({
        onSameUrlNavigation: 'reload'
      })
    ),
    provideHttpClient(
      withInterceptors([authInterceptor])
    ),

    {
      provide: APP_INITIALIZER,
      useFactory: initAuth,
      multi: true
    }
  ]
};



