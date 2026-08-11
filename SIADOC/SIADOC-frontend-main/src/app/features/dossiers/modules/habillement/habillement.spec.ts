import { ComponentFixture, TestBed } from '@angular/core/testing';
import { Habillement } from './habillement';
import { provideHttpClient } from '@angular/common/http';

describe('Habillement', () => {
  let component: Habillement;
  let fixture: ComponentFixture<Habillement>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Habillement],
      providers: [provideHttpClient()]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Habillement);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('should initialize with default articles for male', () => {
    component.sexe = 'MASCULIN';
    component.initialiserArticlesParDefaut();
    expect(component.articles.length).toBeGreaterThan(0);
    expect(component.articles.some(a => a.designation === 'Caleçon')).toBeTruthy();
  });

  it('should initialize with default articles for female', () => {
    component.sexe = 'FEMININ';
    component.initialiserArticlesParDefaut();
    expect(component.articles.length).toBeGreaterThan(0);
    expect(component.articles.some(a => a.designation === 'Brassière')).toBeTruthy();
  });
});
