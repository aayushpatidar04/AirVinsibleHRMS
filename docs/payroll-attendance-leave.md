# Attendance, Leave & Payroll

Implements the *Client-Based Payroll & Workforce Attendance Management System* BRD on top of the HR system.
Everything starts once a candidate has been converted to an employee (a `users` row with an `employee_id`).

## Setup after deploying

```bash
composer install
php artisan migrate                                      # rebuilds the attendance / leave / payroll tables
php artisan db:seed --class=RolePermissionSeeder         # new permissions + the team_leader role
npm install && npm run build                             # public/build is committed and does not yet contain the new pages
```

Add the scheduler to cron (or run `php artisan schedule:work`):

```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

| Command | When | What it does |
|---|---|---|
| `attendance:process-daily` | 01:00 | Calculates yesterday for every active employee, records absences, alerts employee + manager about missing punches (once). |
| `leave:accrue` | 02:00 | Credits leave that is due; in January applies year-end carry-forward. Safe to re-run: every credit has a period key. |

Set the app timezone (`APP_TIMEZONE=Asia/Kolkata`) — attendance days, shifts and cut-offs all use it.

Then, in the admin panel, in this order:
1. **Clients** – make sure each client exists; assign employees (Employees → client).
2. **Shifts** – create shifts, assign them to employees.
3. **Office Locations** – add each office (use "my current location" while standing there). Default radius 10 m, as in the BRD — test on site.
4. **Holidays** – company-wide or per client.
5. **Attendance Policies** – one per client with an effective date.
6. **Leave Types**, then **Leave Policies**.
7. **Payroll → Settings** (per client), **Periods**, and **Salary Structures** (import from the accepted offer, then review).

## Who can do what

| | Employee | Team leader | HR | Admin |
|---|---|---|---|---|
| Punch, own attendance, corrections, leave, payslips | ✔ | ✔ | ✔ | – |
| See team attendance / leave | – | own reportees | own branch | everyone |
| Approve corrections & leave | – | own reportees | own branch | ✔ |
| Manual punches, status override, machine import | – | – | ✔ (branch) | ✔ |
| Leave balance adjustments, holidays | – | – | ✔ | ✔ |
| Calculate payroll, salary structures, adjustments, hold | – | – | ✔ (branch) | ✔ |
| Review payroll | – | – | ✔ | ✔ |
| **Approve payroll, publish payslips, close, reopen** | – | – | – | ✔ |
| Office locations, policies, leave types, payroll settings | – | – | – | ✔ |

Permissions live in `RolePermissionSeeder` and can be changed per role. Visibility is enforced in
`WorkforceScope`, not just in the menu: every list, detail page and action re-checks it.

## How the rules work

**Punching.** Time comes from the server; the source is fixed to *portal*; the geofence is checked server-side
(distance + GPS accuracy) and every punch stores its distance and result. Order is enforced
(in → break → out). A shift that crosses midnight keeps its *out* on the day it started.

**Raw vs derived.** `attendance_punches` is never edited or deleted — a wrong punch is *voided* with a reason, and an
approved correction adds *manual* punches that point back at the request. `attendance_records` (one per person per
day) is derived and can be recalculated until payroll locks it. Each record stores the policy and shift it used.

**Daily result.** present / half day / absent / leave / weekly off / holiday / incomplete / pending, with late, early,
break, overtime and flags (`missing_out`, `source_conflict`, `geo_exception`, …). Days with a blocking flag need review
before payroll.

**Policies are effective-dated per client.** A client's policy never touches another client's staff. Overlapping active
policies for one client are refused. Editing a policy does not re-price days that payroll has locked.

**Leave.** Balances are the sum of `leave_ledger` (accrual, debit, reversal, adjustment, lapse). Requests are checked against the
policy (notice, half day, probation, eligibility, max consecutive, overlap, balance, locked payroll) and go through the
configured approval chain (reporting manager → HR). Unpaid leave never has a balance and becomes loss of pay.

**Payroll.** `PayrollEngine` reads only approved facts. Monthly fixed salary:
`earned = monthly − monthly ÷ divisor × LOP days`, where LOP = absent + unpaid leave + days before joining + late/early
penalties. Statutory items keep the rate they were offered with; settings add the PF wage ceiling and ESIC eligibility
ceiling. Salary revisions inside a month are paid pro rata. Hourly clients are paid for hours.
A sample full month (basic 6,500 + HRA 6,500) reproduces your offer letter: PF 780, ESIC 49, take-home 12,171, employer 991.

**Run workflow.** draft → calculated → pending review → pending approval → approved → payslips published → closed.
Approving locks the attendance used and generates **hidden** payslips; publishing makes them visible to the employee
(and only that employee). *Reopen* (admin, with a reason, before publishing) unlocks attendance and voids the unpublished
payslips. After publishing, corrections go into the next period as arrears adjustments.

## Open points from the BRD (section 19)

These are configurable or have a documented default, but the client should confirm them before go-live:

* Mandatory sources and their order, and which wins on conflict → attendance policy.
* Geofence radius / allowed locations / behaviour on poor GPS → office locations (poor GPS is refused).
* Break and overtime treatment → attendance policy. Without break punches nothing is deducted automatically.
* Per-day divisor, rounding, second approval → payroll settings.
* Statutory rates and ceilings must be validated by payroll/CA. Professional tax is a fixed amount in the structure.
* Weekly offs between absences are paid (no "sandwich" rule).

## Not built

* The **desktop activity agent** and its API. The tables, calculation and reports already use `attendance_activity_sessions`
  (session / active / idle minutes, short-session flag); only the data source is missing. Decide whether an agent is needed first.
* **Device-specific biometric integration.** The machine import takes a CSV (`employee_id, punched_at, direction, device_id`).
* Email / SMS / WhatsApp notifications — in-app notifications are in place (`WorkforceAlert`).
* Bank payment files / payment status. Payslips show "Pending".
* Anti-spoofing beyond server-side accuracy and distance checks.
