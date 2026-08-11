import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Tec } from './tec';

describe('Tec', () => {
  let component: Tec;
  let fixture: ComponentFixture<Tec>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Tec]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Tec);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
