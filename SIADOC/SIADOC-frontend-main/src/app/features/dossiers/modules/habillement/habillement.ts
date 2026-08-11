import { Component, Input, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { environment } from '@env/environment';

// ==========================================
// INTERFACES
// ==========================================

interface Mensurations {
  tailleCm: number | null;
  poidsKg: number | null;
  tourDeTete: string;
  pointure: string;
  tailleVeste: string;
  taillePantalon: string;
}

interface Article {
  id?: string;
  designation: string;
  categorie: 'CAMPAGNE_INDIVIDUEL' | 'CAMPAGNE_UNITE' | 'SERVICE' | 'LINGE' | 'SPECIFIQUE';
  quantite: number;
  etat: string;
  datePerception: string;
  observation: string;
}

interface HabillementData {
  mensurations: Mensurations;
  articles: Article[];
}

// Articles par défaut selon le sexe
const ARTICLES_HOMME: Article[] = [
  // Campagne individuel
  { designation: 'Chaussures de combat', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Bottes de combat', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Tenue de camouflage (Jungle)', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Tenue de camouflage (Désert)', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Casque de combat', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Gilet pare-balles', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Ceinturon', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Baudrier', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Musette', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Gourde', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Gamelle', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Couverture', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Tente individuelle', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Matelas pneumatique', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Sac de couchage', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  
  // Service
  { designation: 'Tenue de cérémonie (Tenue 1)', categorie: 'SERVICE', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Tenue de sortie (Tenue 2)', categorie: 'SERVICE', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Tenue de travail (Tenue 3)', categorie: 'SERVICE', quantite: 3, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Tenue de sport', categorie: 'SERVICE', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Béret', categorie: 'SERVICE', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Képi (pour S/off)', categorie: 'SERVICE', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Ceinture de pantalon', categorie: 'SERVICE', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Cravate', categorie: 'SERVICE', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Gants de cuir', categorie: 'SERVICE', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Gants de coton', categorie: 'SERVICE', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
  
  // Linge
  { designation: 'Maillot de corps', categorie: 'LINGE', quantite: 6, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Caleçon', categorie: 'LINGE', quantite: 6, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Chaussettes', categorie: 'LINGE', quantite: 6, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'T-shirt', categorie: 'LINGE', quantite: 4, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Short', categorie: 'LINGE', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Serviette de bain', categorie: 'LINGE', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Drap housse', categorie: 'LINGE', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Taie d\'oreiller', categorie: 'LINGE', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
];

const ARTICLES_FEMME: Article[] = [
  // Campagne individuel
  { designation: 'Chaussures de combat', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Bottes de combat', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Tenue de camouflage (Jungle)', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Tenue de camouflage (Désert)', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Casque de combat', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Gilet pare-balles (femme)', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Ceinturon', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Baudrier', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Musette', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Gourde', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Gamelle', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Couverture', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Tente individuelle', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Matelas pneumatique', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Sac de couchage', categorie: 'CAMPAGNE_INDIVIDUEL', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  
  // Service
  { designation: 'Tenue de cérémonie (Tenue 1)', categorie: 'SERVICE', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Tenue de sortie (Tenue 2)', categorie: 'SERVICE', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Tenue de travail (Tenue 3)', categorie: 'SERVICE', quantite: 3, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Tenue de sport', categorie: 'SERVICE', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Béret', categorie: 'SERVICE', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Képi (pour S/off)', categorie: 'SERVICE', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Ceinture de pantalon', categorie: 'SERVICE', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Cravate', categorie: 'SERVICE', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Gants de cuir', categorie: 'SERVICE', quantite: 1, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Gants de coton', categorie: 'SERVICE', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
  
  // Linge - spécifique femme
  { designation: 'Brassière', categorie: 'LINGE', quantite: 4, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Maillot de corps', categorie: 'LINGE', quantite: 6, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Culotte', categorie: 'LINGE', quantite: 6, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Chaussettes', categorie: 'LINGE', quantite: 6, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'T-shirt', categorie: 'LINGE', quantite: 4, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Short', categorie: 'LINGE', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Serviette de bain', categorie: 'LINGE', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Drap housse', categorie: 'LINGE', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
  { designation: 'Taie d\'oreiller', categorie: 'LINGE', quantite: 2, etat: 'Neuf', datePerception: '', observation: '' },
];

@Component({
  selector: 'app-habillement',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './habillement.html',
  styleUrls: ['./habillement.scss']
})
export class Habillement implements OnInit {

  @Input() militaireId!: string;
  @Input() sexe: 'MASCULIN' | 'FEMININ' = 'MASCULIN';
  @Input() readOnly: boolean = false;

  // Onglet actif
  ongletActif: 'mensurations' | 'campagne' | 'service' | 'linge' | 'specifique' = 'mensurations';

  // Données
  mensurations: Mensurations = {
    tailleCm: null,
    poidsKg: null,
    tourDeTete: '',
    pointure: '',
    tailleVeste: '',
    taillePantalon: ''
  };

  articles: Article[] = [];
  
  // État
  loading = false;
  sauvegardeEnCours = false;
  messageSucces = '';
  messageErreur = '';

  constructor(private http: HttpClient) {}

  ngOnInit() {
    if (this.militaireId) {
      this.chargerHabillement();
    }
  }

  // ==========================================
  // CHARGEMENT
  // ==========================================

  chargerHabillement() {
    this.loading = true;
    
    this.http.get<HabillementData>(
      `${environment.apiUrl}/api/habillement/${this.militaireId}`
    ).subscribe({
      next: (data) => {
        if (data.mensurations) {
          this.mensurations = data.mensurations;
        }
        if (data.articles && data.articles.length > 0) {
          this.articles = data.articles;
        } else {
          // Initialiser avec les articles par défaut selon le sexe
          this.initialiserArticlesParDefaut();
        }
        this.loading = false;
      },
      error: (err) => {
        console.error('Erreur chargement habillement:', err);
        // En cas d'erreur, initialiser avec les articles par défaut
        this.initialiserArticlesParDefaut();
        this.loading = false;
      }
    });
  }

  initialiserArticlesParDefaut() {
    const articlesBase = this.sexe === 'FEMININ' ? ARTICLES_FEMME : ARTICLES_HOMME;
    // Créer une copie profonde pour éviter les références partagées
    this.articles = articlesBase.map(a => ({ ...a }));
  }

  // ==========================================
  // NAVIGATION ONGLETS
  // ==========================================

  setOnglet(onglet: 'mensurations' | 'campagne' | 'service' | 'linge' | 'specifique') {
    this.ongletActif = onglet;
  }

  // ==========================================
  // GETTERS POUR FILTRER LES ARTICLES
  // ==========================================

  get articlesCampagne(): Article[] {
    return this.articles.filter(a => a.categorie === 'CAMPAGNE_INDIVIDUEL' || a.categorie === 'CAMPAGNE_UNITE');
  }

  get articlesService(): Article[] {
    return this.articles.filter(a => a.categorie === 'SERVICE');
  }

  get articlesLinge(): Article[] {
    return this.articles.filter(a => a.categorie === 'LINGE');
  }

  get articlesSpecifique(): Article[] {
    return this.articles.filter(a => a.categorie === 'SPECIFIQUE');
  }

  // ==========================================
  // AJOUT/SUPPRESSION ARTICLES
  // ==========================================

  ajouterArticle(categorie: Article['categorie']) {
    this.articles.push({
      designation: '',
      categorie: categorie,
      quantite: 1,
      etat: 'Neuf',
      datePerception: '',
      observation: ''
    });
  }

  ajouterArticleDepuisOnglet() {
    const mapping: Record<string, Article['categorie']> = {
      'campagne': 'CAMPAGNE_INDIVIDUEL',
      'service': 'SERVICE',
      'linge': 'LINGE',
      'specifique': 'SPECIFIQUE'
    };
    
    const categorie = mapping[this.ongletActif];
    if (categorie) {
      this.ajouterArticle(categorie);
    }
  }

  supprimerArticle(index: number) {
    // Trouver l'index réel dans le tableau complet
    const articleASupprimer = this.getArticlesParCategorie(this.ongletActif)[index];
    const vraiIndex = this.articles.indexOf(articleASupprimer);
    if (vraiIndex > -1) {
      this.articles.splice(vraiIndex, 1);
    }
  }

  getArticlesParCategorie(categorie: string): Article[] {
    switch (categorie) {
      case 'campagne': return this.articlesCampagne;
      case 'service': return this.articlesService;
      case 'linge': return this.articlesLinge;
      case 'specifique': return this.articlesSpecifique;
      default: return [];
    }
  }

  // ==========================================
  // SAUVEGARDE
  // ==========================================

  sauvegarder() {
    if (!this.militaireId) {
      this.messageErreur = 'ID militaire manquant';
      return;
    }

    this.sauvegardeEnCours = true;
    this.messageSucces = '';
    this.messageErreur = '';

    const payload: HabillementData = {
      mensurations: this.mensurations,
      articles: this.articles
    };

    this.http.post<HabillementData>(
      `${environment.apiUrl}/api/habillement/${this.militaireId}`,
      payload
    ).subscribe({
      next: (data) => {
        this.sauvegardeEnCours = false;
        this.messageSucces = 'Carnet d\'habillement enregistré avec succès ✅';
        setTimeout(() => this.messageSucces = '', 3000);
      },
      error: (err) => {
        console.error('Erreur sauvegarde habillement:', err);
        this.sauvegardeEnCours = false;
        this.messageErreur = 'Erreur lors de l\'enregistrement';
        setTimeout(() => this.messageErreur = '', 3000);
      }
    });
  }

  // ==========================================
  // UTILITAIRES
  // ==========================================

  getTitreOnglet(): string {
    switch (this.ongletActif) {
      case 'mensurations': return 'Mensurations';
      case 'campagne': return 'Campagne Individuel/Unité';
      case 'service': return 'Tenues de Service';
      case 'linge': return 'Linge de Corps';
      case 'specifique': return 'Articles Spécifiques';
      default: return '';
    }
  }
}
