import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FormMilitaire } from './form-militaire';

describe('FormMilitaire', () => {
  let component: FormMilitaire;
  let fixture: ComponentFixture<FormMilitaire>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [FormMilitaire]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FormMilitaire);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
