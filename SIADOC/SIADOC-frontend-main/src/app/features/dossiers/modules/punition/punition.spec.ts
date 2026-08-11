import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Punition } from './punition';

describe('Punition', () => {
  let component: Punition;
  let fixture: ComponentFixture<Punition>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Punition]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Punition);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
