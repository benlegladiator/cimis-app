import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Punitions } from './punitions';

describe('Punitions', () => {
  let component: Punitions;
  let fixture: ComponentFixture<Punitions>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Punitions]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Punitions);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
