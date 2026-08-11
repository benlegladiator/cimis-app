import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Diplome } from './diplome';

describe('Diplome', () => {
  let component: Diplome;
  let fixture: ComponentFixture<Diplome>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Diplome]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Diplome);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
