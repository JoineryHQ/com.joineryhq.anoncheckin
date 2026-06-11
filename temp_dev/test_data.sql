INSERT INTO civicrm_anoncheckin_session_group
  (title, event_id, start_datetime_utc, end_datetime_utc, timezone)
VALUES
  ('Monday 8:00',    113, '2026-06-29 12:00:00', '2026-06-29 13:15:00', 'America/New_York'),
  ('Monday 9:30',    113, '2026-06-29 13:30:00', '2026-06-29 14:30:00', 'America/New_York'),
  ('Monday 11:30',   113, '2026-06-29 15:30:00', '2026-06-29 16:30:00', 'America/New_York'),
  ('Monday 2:00',    113, '2026-06-29 18:00:00', '2026-06-29 19:15:00', 'America/New_York'),
  ('Monday 3:15',    113, '2026-06-29 19:15:00', '2026-06-29 20:00:00', 'America/New_York'),
  ('Tuesday 8:30',   113, '2026-06-30 12:30:00', '2026-06-30 13:30:00', 'America/New_York'),
  ('Tuesday 9:30',   113, '2026-06-30 13:30:00', '2026-06-30 14:30:00', 'America/New_York'),
  ('Tuesday 11:30',  113, '2026-06-30 15:30:00', '2026-06-30 16:30:00', 'America/New_York'),
  ('Tuesday 2:15',   113, '2026-06-30 18:15:00', '2026-06-30 19:15:00', 'America/New_York'),
  ('Tuesday 3:30',   113, '2026-06-30 19:30:00', '2026-06-30 20:15:00', 'America/New_York'),
  ('Wednesday 8:45', 113, '2026-07-01 12:45:00', '2026-07-01 14:00:00', 'America/New_York');

INSERT INTO civicrm_anoncheckin_session
  (title, session_group_id)
VALUES
  ('Monday 8:00 — Opening General Session', 1),
  ('Monday 9:30 — Emotional Intelligence', 2),
  ('Monday 11:30 — What''s Happening with the Global Economy', 3),
  ('Monday 2:00 — How are the Capital Markets Responding', 4),
  ('Monday 3:15 — Teamwork & Communicating', 5),
  ('Tuesday 8:30 — FPPTA Morning Show', 6),
  ('Tuesday 9:30 — FPPTA New Technology Demonstration', 7),
  ('Tuesday 11:30 — Strategic Shock', 8),
  ('Tuesday 2:15 — Elimination of Local Property Tax: Legislative Referendum', 9),
  ('Tuesday 3:30 — FPPTA Annual Membership Meeting', 10),
  ('Wednesday 8:45 — Embrace the Shake', 11),
  ('Monday 8:00 — Alternate Session', 1);
