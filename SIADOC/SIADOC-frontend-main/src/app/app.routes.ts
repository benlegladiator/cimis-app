import { Routes } from '@angular/router';
import { Shell } from './layout/shell/shell';
import { DossierLayout } from './features/dossiers/dossier-layout/dossier-layout';
import { authGuard } from './core/auth.guard';



export const routes: Routes = [

  // =========================
  // REDIRECTION RACINE
  // =========================
  { path: '', redirectTo: 'login', pathMatch: 'full' },

  // =========================
  // PAGE LOGIN (HORS SHELL)
  // =========================
  {
    path: 'login',
    loadComponent: () =>
      import('./features/auth/login/login')
        .then(m => m.Login)
  },

  // =========================
  // APPLICATION PRINCIPALE
  // =========================
  {
    path: '',
    component: Shell,
    canActivate: [authGuard],
    children: [

      {
        path: 'dashboard',
        loadComponent: () =>
          import('./features/dashboard/dashboard')
            .then(m => m.Dashboard)
      },

      {
        path: 'notifications',
        loadComponent: () =>
          import('./features/notifications/notifications')
            .then(m => m.Notifications)
      },

      {
        path: 'archives',
        loadComponent: () =>
          import('./features/archives/archives/archives')
            .then(m => m.Archives)
      },

      {
        path: 'archives/:id',
        loadComponent: () =>
          import('./features/archives/archive-view/archive-view')
            .then(m => m.ArchiveView)
      },

      {
        path: 'archives-civiles',
        loadComponent: () =>
          import('./features/archives-civiles/civil-archive-list')
            .then(m => m.CivilArchiveList)
      },

      {
        path: 'archives-civiles/:id',
        loadComponent: () =>
          import('./features/archives-civiles/civil-archive-detail')
            .then(m => m.CivilArchiveDetail)
      },

      {
        path: 'administration',
        loadComponent: () =>
          import('./features/administration/gestion-utilisateurs/gestion-utilisateurs')
            .then(m => m.GestionUtilisateurs)
      },

      {
        path: 'administration/structure',
        loadComponent: () =>
          import('./features/administration/drh-structure-nav/drh-structure-nav')
            .then(m => m.DrhStructureNav)
      },

      {
        path: 'settings',
        loadComponent: () =>
          import('./features/settings/settings-home')
            .then(m => m.SettingsHome)
      },

      {
        path: 'settings/hierarchy',
        loadComponent: () =>
          import('./features/settings/hierarchy-settings')
            .then(m => m.HierarchySettings)
      },

      {
        path: 'settings/ranks',
        loadComponent: () =>
          import('./features/settings/rank-settings')
            .then(m => m.RankSettings)
      },

      {
        path: 'settings/civil',
        loadComponent: () =>
          import('./features/settings/civil-settings')
            .then(m => m.CivilSettings)
      },

      {
        path: 'settings/system',
        loadComponent: () =>
          import('./features/settings/system-settings')
            .then(m => m.SystemSettings)
      },

      {
        path: 'recherche',
        loadComponent: () =>
          import('./features/recherche/recherche/recherche')
            .then(m => m.RechercheComponent)
      },

      {
        path: 'tec',
        loadComponent: () =>
          import('./features/tec/tec').then(m => m.Tec)
      },

      {
        path: 'profil',
        loadComponent: () =>
          import('./features/profile/profile').then(m => m.ProfileComponent)
      },

      {
        path: 'exportation',
        loadComponent: () =>
          import('./features/exportation/exportation').then(m => m.Exportation)
      },
      {
        path: 'cimis',
        loadComponent: () =>
          import('./features/cimis/cimis-biometrie/cimis-biometrie')
            .then(m => m.CimisBiometrie)
      },
      {
        path: 'dossier',
        component: DossierLayout,
        children: [

          {
            path: 'creer',
            loadComponent: () =>
              import('./features/militaires/form-militaire/form-militaire')
                .then(m => m.FormMilitaire)
          },

          {
            path: 'modifier',
            loadComponent: () =>
              import('./features/militaires/liste-militaires/liste-militaires')
                .then(m => m.ListeMilitaires)
          },

          {
            path: ':id',
            loadComponent: () =>
              import('./features/dossiers/dossier-militaire/dossier-militaire')
                .then(m => m.DossierMilitaire)
          },

        ]
      }

    ]
  },

  // =========================
  // ROUTE PAR DEFAUT
  // =========================
  { path: '**', redirectTo: 'dashboard' }

];
