INSERT INTO civicrm_anoncheckin_session_group
  (title, event_id, start_datetime_utc, end_datetime_utc, timezone)
VALUES
  ('Monday 8:00am',    113, '2026-06-29 12:00:00', '2026-06-29 13:15:00', 'America/New_York'),
  ('Monday 9:30am',    113, '2026-06-29 13:30:00', '2026-06-29 14:30:00', 'America/New_York'),
  ('Monday 11:30am',   113, '2026-06-29 15:30:00', '2026-06-29 16:30:00', 'America/New_York'),
  ('Monday 2:00pm',    113, '2026-06-29 18:00:00', '2026-06-29 19:15:00', 'America/New_York'),
  ('Monday 4:00pm',    113, '2026-06-29 20:00:00', '2026-06-29 21:00:00', 'America/New_York'),
  ('Tuesday 8:30am',   113, '2026-06-30 12:30:00', '2026-06-30 13:30:00', 'America/New_York'),
  ('Tuesday 9:30am',   113, '2026-06-30 13:30:00', '2026-06-30 14:30:00', 'America/New_York'),
  ('Tuesday 11:30am',  113, '2026-06-30 15:30:00', '2026-06-30 16:30:00', 'America/New_York'),
  ('Tuesday 2:15pm',   113, '2026-06-30 18:15:00', '2026-06-30 19:15:00', 'America/New_York'),
  ('Tuesday 3:00pm',   113, '2026-06-30 19:00:00', '2026-06-30 20:00:00', 'America/New_York'),
  ('Wednesday 8:45am', 113, '2026-07-01 12:30:00', '2026-07-01 12:45:00', 'America/New_York');

INSERT INTO civicrm_anoncheckin_session
  (title, session_group_id)
VALUES
  ('Monday 8:00am — Opening Ceremonies', 1),
  ('Monday 8:00am — Opening: Alternate Session (for Testing)', 1),
  ('Monday 2:00pm — How are the Capital Markets Responding', 4),
  ('Monday 4:00pm — Afternoon Break', 5),
  ('Tuesday 8:30am — FPPTA Morning Show', 6),
  ('Tuesday 2:15pm — Elimination of Local Property Tax: Legislative Referendum', 9),
  ('Tuesday 4:00pm — Afternoon Break', 10),
  ('Tuesday 3:30pm — FPPTA Annual Membership Meeting', 10),
  ('Wednesday 8:45am — Opening Comments', 11);

