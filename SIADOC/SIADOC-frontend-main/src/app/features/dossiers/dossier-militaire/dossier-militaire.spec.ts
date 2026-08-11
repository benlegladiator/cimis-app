import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DossierMilitaire } from './dossier-militaire';

describe('DossierMilitaire', () => {
  let component: DossierMilitaire;
  let fixture: ComponentFixture<DossierMilitaire>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [DossierMilitaire]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DossierMilitaire);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
