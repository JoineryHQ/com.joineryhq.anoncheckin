# com.joineryhq.anoncheckin

/checkin (GET):
- stores in session, $sid and $sid-timestamp (`time()`).
- if $pid in session: show user name, session name, and confirm button; otherwise show only session name and "scan badge" button with simple instructions (upon badge scan, navigate to /status?pid=[pid]&h=[hmac])

/checkin (POST):
- reads pid and sid (plus verifying POST-provided hmacs for each) from POST data.
- if user @ session would be a duplicate, set $message "you've already logged that session"; otherwise: record user @ session, and set $message "We've recorded you @ $session-name"
- redirect to /status?pid=[pid]&h=[hmac] , and clear $sid from session.

/status (GET):
- displays any $messages
- if $sid is in session (and not more than N seconds old), immediately redirect to /checkin?sid=[sid]&h=[hmac] (therefore, none of the following happens ...)
- display all recorded sessions for this user.
-  show "scan session code" button with simple instructions (upon badge scan: navigate to  /checkin?sid=[sid]&h=[hmac]


This is an [extension for CiviCRM](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/), licensed under [GPL-3.0](LICENSE.txt).

## Getting Started

(* FIXME: Where would a new user navigate to get started? What changes would they see? *)

## Known Issues

(* FIXME *)
