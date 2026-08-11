import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ListeMilitaires } from './liste-militaires';

describe('ListeMilitaires', () => {
  let component: ListeMilitaires;
  let fixture: ComponentFixture<ListeMilitaires>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ListeMilitaires]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ListeMilitaires);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
