import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Recompense } from './recompense';

describe('Recompense', () => {
  let component: Recompense;
  let fixture: ComponentFixture<Recompense>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Recompense]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Recompense);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
