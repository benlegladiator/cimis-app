import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EtatCivil } from './etat-civil';

describe('EtatCivil', () => {
  let component: EtatCivil;
  let fixture: ComponentFixture<EtatCivil>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [EtatCivil]
    })
    .compileComponents();

    fixture = TestBed.createComponent(EtatCivil);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
