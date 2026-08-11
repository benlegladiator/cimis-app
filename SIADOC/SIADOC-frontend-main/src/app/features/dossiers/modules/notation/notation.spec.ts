import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Notation } from './notation';

describe('Notation', () => {
  let component: Notation;
  let fixture: ComponentFixture<Notation>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Notation]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Notation);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
