import { eventDateParts } from '../src/lib/presentation';

test('poster calendar matches the web weekday, day and month without timezone shifts', () => {
  expect(eventDateParts('2026-10-10')).toEqual({
    weekday: 'SÁB',
    day: '10',
    month: 'OCT',
  });
  expect(eventDateParts('2026-10-16')).toEqual({
    weekday: 'VIE',
    day: '16',
    month: 'OCT',
  });
  expect(eventDateParts('2026-01-01')).toEqual({
    weekday: 'JUE',
    day: '1',
    month: 'ENE',
  });
});

test('invalid dates do not render a misleading calendar', () => {
  for (const value of ['', '2026-02-30', '2026-99-10', 'not a date']) {
    expect(eventDateParts(value)).toBeNull();
  }
});
