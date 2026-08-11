import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RechercheDossier } from './recherche-dossier';

describe('RechercheDossier', () => {
  let component: RechercheDossier;
  let fixture: ComponentFixture<RechercheDossier>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [RechercheDossier]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RechercheDossier);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
