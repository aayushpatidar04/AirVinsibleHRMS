# Test Plan — Payroll, Attendance & Intent Letter Changes

**Date:** 2026-11-11  
**Stack:** Laravel 13 + Inertia.js/Vue 3 + SQLite  
**Environment:** `C:\xampp\htdocs\AirVinsibleHRMS`

---

## 1. Pre-Test Checklist

Before starting, verify:

- [ ] All migrations ran: `php artisan migrate:status` shows all 35 migrations as **Ran**
- [ ] No PHP syntax errors: run `php -l` on all modified/new files (see Section 8)
- [ ] Development server is running: `php artisan serve`
- [ ] You have at least one admin user and one HR user in the database
- [ ] You have at least one employee (User with `employee_id`) for attendance testing

---

## 2. Intent Letter Visibility Across Rounds

**Goal:** The Letter of Intent section should appear when salary was accepted in *any* prior completed round, not just the current round.

### Test Steps

| Step | Action | Expected Result |
|------|--------|-----------------|
| 2.1 | Log in as an interviewer with an assigned candidate | Interviewer dashboard loads |
| 2.2 | Open a candidate in an early round (e.g., Round 1 — OPS) | Complete Round modal shows |
| 2.3 | Select `mark_all_rounds_cleared` | Completion action set correctly |
| 2.4 | Select `salary_offer_status = accepted` | Salary accepted flag set |
| 2.5 | Fill in all Intent Letter fields (position, department, reporting_to, work_location, monthly_ctc, monthly_in_hand, joining_date) | All fields accept input |
| 2.6 | Click Complete | Round completes; candidate marked `all_rounds_cleared`; Letter of Intent created |
| 2.7 | Open the same candidate in a **later round** (e.g., Round 2 — HR) | Intent Letter section is visible because `candidate.latest_salary_offer_status === 'accepted'` persists from Round 1 |

### Files Modified
- `resources/js/Pages/Interviewer/Interviews/Show.vue` — line 217-220: `shouldCreateIntentLetter` checks both current form status and `candidate.latest_salary_offer_status`
- `app/Http/Controllers/Interviewer/InterviewController.php` — `requiresIntentLetter` uses `salary_offer_status === 'accepted'` and `completion_action === 'mark_all_rounds_cleared'`

---

## 3. Candidate-to-Employee Conversion

**Goal:** An accepted offer candidate can be converted to an employee via the offer portal.

### Test Steps

| Step | Action | Expected Result |
|------|--------|-----------------|
| 3.1 | Ensure a candidate has an accepted offer (`candidate_offers.status = 'accepted'`) | Offer exists in DB |
| 3.2 | Open the offer portal URL (`/candidate-offer/{token}`) | Portal page loads |
| 3.3 | Verify the "Convert to Employee" button is visible | Button shows because `portal.response === 'accepted'` |
| 3.4 | Click "Convert to Employee" | Convert modal opens |
| 3.5 | Fill: employee_id, date_of_joining, designation, department, branch_id, employment_type, phone | All fields accepted |
| 3.6 | Confirm conversion | Success message: "Candidate converted to employee successfully. Employee ID: X" |
| 3.7 | Check `users` table | New user row created with `employee_id`, `employment_status = 'active'`, role `employee` |
| 3.8 | Check `candidates` table | `user_id` populated, `final_status = 'hired'`, `current_status = 'converted_to_employee'` |
| 3.9 | Try converting again | Error: "Candidate is already converted to an employee." |
| 3.10 | Try converting a declined offer | Error: "Offer must be accepted before converting to employee." |

### Files Modified
- `app/Http/Controllers/Recruitment/CandidateOfferPortalController.php` — `convertToEmployee()` method + `convert_to_employee` route in `show()`
- `routes/web.php` — `candidate-offers.portal.convert-to-employee` POST route
- `resources/js/Pages/Public/OfferPortal/Show.vue` — Convert modal + button
- `database/migrations/2026_11_11_000001_add_user_id_to_candidates_table.php` — `user_id` column on candidates
- `app/Models/Candidate.php` — `user_id` fillable + `user()` relation

---

## 4. Payroll & Attendance Module

### 4.1 Clients (Multi-Tenancy)

**Goal:** Create and manage client accounts.

| Step | Action | Expected Result |
|------|--------|-----------------|
| 4.1.1 | Log in as admin | Admin dashboard loads |
| 4.1.2 | Navigate to **Clients** (Admin sidebar) | Clients list page loads |
| 4.1.3 | Click "+ New Client" | Create form loads |
| 4.1.4 | Fill: name, code (unique), contact details, address, is_active | Form accepts input |
| 4.1.5 | Click Create Client | Client created; redirected to show page; success message |
| 4.1.6 | Click Edit on a client | Edit form pre-populated |
| 4.1.7 | Update client details | Changes saved; success message |
| 4.1.8 | View client | Shows client details, employee count, configurations (policies, shifts, leave types) |

**URL:** `/admin/clients`  
**Route names:** `admin.clients.index`, `admin.clients.create`, `admin.clients.store`, `admin.clients.show`, `admin.clients.edit`, `admin.clients.update`, `admin.clients.destroy`

---

### 4.2 Attendance Policies (Rule Engine)

**Goal:** Configure client-specific attendance rules per PDF Section 6.

| Step | Action | Expected Result |
|------|--------|-----------------|
| 4.2.1 | Log in as HR | HR dashboard loads |
| 4.2.2 | Navigate to **Attendance** (HR sidebar) | Attendance index loads |
| 4.2.3 | Create a shift first (via admin or direct DB) | Shift exists for policy reference |
| 4.2.4 | Create an attendance policy for a client | Policy form accepts: shift times, grace period, max late arrivals, break duration, overtime eligibility, weekly offs, public holidays, half/full-day thresholds |
| 4.2.5 | Save policy | Policy stored with `effective_from` date |
| 4.2.6 | Verify policy is client-specific | Policy shows under the correct client |

**Note:** Policy CRUD UI is not yet built — use database seeds or direct DB inserts for policies/shifts until UI is added.

**Tables:** `attendance_policies`, `shifts`, `employee_shifts`

---

### 4.3 Attendance Records & Punches

**Goal:** Record and view employee attendance.

| Step | Action | Expected Result |
|------|--------|-----------------|
| 4.3.1 | Log in as HR | HR dashboard loads |
| 4.3.2 | Navigate to **Attendance** | Attendance records list loads |
| 4.3.3 | Filter by client, status, date range | Results filter correctly |
| 4.3.4 | Click View on a record | Attendance detail shows: employee, client, date, status, punch times, working hours, overtime, missing punch flag |
| 4.3.5 | Check punch history table | Shows all punches (in, out, break_start, break_end) with source (portal/machine/manual) |
| 4.3.6 | Record a manual punch (API test) | POST to `hr.attendance.punch` creates punch record |

**API Endpoint:** `POST /attendance/punch` (named `hr.attendance.punch`)  
**Body:** `{ user_id, punch_type: in\|out\|break_start\|break_end, source: portal\|machine\|manual\|desktop_agent, device_id?, geolocation?, ip_address?, notes? }`

**URL:** `/hr/attendance`  
**Route names:** `hr.attendance.index`, `hr.attendance.show`, `hr.attendance.punch`, `hr.attendance.corrections`, `hr.attendance.corrections.approve`

---

### 4.4 Attendance Corrections

**Goal:** Review and approve/reject attendance corrections.

| Step | Action | Expected Result |
|------|--------|-----------------|
| 4.4.1 | Navigate to Attendance → Corrections | Corrections list loads |
| 4.4.2 | Filter by status (pending) | Pending corrections shown |
| 4.4.3 | Click Approve on a correction | Status changes to approved; attendance record updated with corrected punch times |
| 4.4.4 | Click Reject on a correction | Status changes to rejected; rejection reason required |
| 4.4.5 | Verify audit trail | Correction shows requested_by, approved_by, approved_at timestamps |

**URL:** `/hr/attendance/corrections`  
**Route names:** `hr.attendance.corrections`, `hr.attendance.corrections.approve`

---

### 4.5 Leave Management

**Goal:** Configure leave types, apply for leave, approve/reject.

| Step | Action | Expected Result |
|------|--------|-----------------|
| 4.5.1 | Navigate to **Leave** → **Types** | Leave types list loads |
| 4.5.2 | Create a leave type (e.g., Casual Leave, code CL) | Form accepts: name, code (unique), paid/unpaid, half-day, annual entitlement, max consecutive days, notice period, is_active |
| 4.5.3 | Save leave type | Leave type stored under selected client |
| 4.5.4 | Navigate to **Leave** (applications list) | Leave applications list loads |
| 4.5.5 | Apply for leave (as employee) | Form: leave_type, start_date, end_date, reason, half-day option |
| 4.5.6 | Submit application | Status = pending; success message |
| 4.5.7 | As HR manager, open the application | Approve/Reject buttons visible for pending applications |
| 4.5.8 | Approve the leave | Status = approved; approved_by and approved_at set |
| 4.5.9 | Reject the leave | Status = rejected; rejection_reason required |
| 4.5.10 | Navigate to **Leave Balances** | Shows employee leave balances with available/used/pending counts |

**URLs:** `/hr/leave`, `/hr/leave/types`, `/hr/leave/balances`  
**Route names:** `hr.leave.index`, `hr.leave.types`, `hr.leave.types.store`, `hr.leave.balances`, `hr.leave.apply`, `hr.leave.approve`

---

### 4.6 Payroll

**Goal:** Manage payroll periods, components, runs, and payslips.

#### 4.6.1 Payroll Periods

| Step | Action | Expected Result |
|------|--------|-----------------|
| 4.6.1.1 | Navigate to **Payroll** → **Periods** | Payroll periods list loads |
| 4.6.1.2 | Create a period | Form: client, name (e.g. "November 2026"), start_date, end_date, attendance_cutoff_date, salary_processing_date |
| 4.6.1.3 | Save | Period created with status `open` |

**URL:** `/hr/payroll/periods`  
**Route names:** `hr.payroll.periods`, `hr.payroll.periods.store`

#### 4.6.2 Payroll Components

| Step | Action | Expected Result |
|------|--------|-----------------|
| 4.6.2.1 | Navigate to **Payroll** → **Components** | Components list loads |
| 4.6.2.2 | Create an earning component (e.g., Basic Salary) | Form: client, name, code (unique), type (earning/deduction), calculation_method, default_value, is_statutory, is_active |
| 4.6.2.3 | Create a deduction component (e.g., PF) | Same form with type = deduction |
| 4.6.2.4 | Filter by type (earning/deduction) | Results filter correctly |

**URL:** `/hr/payroll/components`  
**Route names:** `hr.payroll.components`, `hr.payroll.components.store`

#### 4.6.3 Payroll Runs

| Step | Action | Expected Result |
|------|--------|-----------------|
| 4.6.3.1 | Navigate to **Payroll** (runs list) | Payroll runs list loads |
| 4.6.3.2 | Filter by client, status | Results filter correctly |
| 4.6.3.3 | Click View Details on a run | Calculations page shows per-employee breakdown |
| 4.6.3.4 | Check calculation details | Shows: payable days, present/absent days, leave days, working hours, overtime, gross earnings, deductions, net pay, status |

**URL:** `/hr/payroll`  
**Route names:** `hr.payroll.index`, `hr.payroll.calculations`

#### 4.6.4 Payslips

| Step | Action | Expected Result |
|------|--------|-----------------|
| 4.6.4.1 | Navigate to **Payroll** → **Payslips** | Payslips list loads |
| 4.6.4.2 | Filter by client, employee, published status | Results filter correctly |
| 4.6.4.3 | Click Publish on an unpublished payslip | `is_published = true`; `published_at` and `published_by` set |
| 4.6.4.4 | Click Unpublish on a published payslip | `is_published = false`; timestamps cleared |
| 4.6.4.5 | Verify employee can only see their own published payslips | (Frontend restriction — verify via direct DB or UI) |

**URL:** `/hr/payroll/payslips`  
**Route names:** `hr.payroll.payslips`, `hr.payroll.payslips.publish`

---

## 5. Navigation Verification

| Panel | Menu Item | Route | Permission |
|-------|-----------|-------|------------|
| Admin | Clients | `admin.clients.index` | `clients.view` |
| HR | Attendance | `hr.attendance.index` | `attendance.view` |
| HR | Leave | `hr.leave.index` | `leave.view` |
| HR | Payroll | `hr.payroll.index` | `payroll.view` |

Verify each menu item appears in the correct sidebar and clicking it loads the expected page.

**File:** `resources/js/Config/navigation.js`

---

## 6. API Endpoints Summary

| Method | Endpoint | Route Name | Description |
|--------|----------|------------|-------------|
| GET | `/admin/clients` | `admin.clients.index` | List clients |
| GET | `/admin/clients/create` | `admin.clients.create` | New client form |
| POST | `/admin/clients` | `admin.clients.store` | Create client |
| GET | `/admin/clients/{id}` | `admin.clients.show` | View client |
| GET | `/admin/clients/{id}/edit` | `admin.clients.edit` | Edit client form |
| PUT | `/admin/clients/{id}` | `admin.clients.update` | Update client |
| DELETE | `/admin/clients/{id}` | `admin.clients.destroy` | Delete client |
| GET | `/hr/attendance` | `hr.attendance.index` | List attendance records |
| GET | `/hr/attendance/{id}` | `hr.attendance.show` | View attendance detail |
| POST | `/hr/attendance/punch` | `hr.attendance.punch` | Record a punch |
| GET | `/hr/attendance/corrections` | `hr.attendance.corrections` | List corrections |
| POST | `/hr/attendance/corrections/{id}/approve` | `hr.attendance.corrections.approve` | Approve/reject correction |
| GET | `/hr/leave` | `hr.leave.index` | List leave applications |
| GET | `/hr/leave/types` | `hr.leave.types` | List leave types |
| POST | `/hr/leave/types` | `hr.leave.types.store` | Create leave type |
| GET | `/hr/leave/balances` | `hr.leave.balances` | List leave balances |
| POST | `/hr/leave/apply` | `hr.leave.apply` | Apply for leave |
| POST | `/hr/leave/{id}/approve` | `hr.leave.approve` | Approve/reject leave |
| GET | `/hr/payroll` | `hr.payroll.index` | List payroll runs |
| GET | `/hr/payroll/periods` | `hr.payroll.periods` | List payroll periods |
| POST | `/hr/payroll/periods` | `hr.payroll.periods.store` | Create payroll period |
| GET | `/hr/payroll/components` | `hr.payroll.components` | List payroll components |
| POST | `/hr/payroll/components` | `hr.payroll.components.store` | Create payroll component |
| GET | `/hr/payroll/{id}/calculations` | `hr.payroll.calculations` | View run calculations |
| GET | `/hr/payroll/payslips` | `hr.payroll.payslips` | List payslips |
| POST | `/hr/payroll/payslips/{id}/publish` | `hr.payroll.payslips.publish` | Publish/unpublish payslip |
| POST | `/candidate-offer/{token}/convert-to-employee` | `candidate-offers.portal.convert-to-employee` | Convert candidate to employee |

---

## 7. Database Tables Summary

| Table | Purpose |
|-------|---------|
| `clients` | Multi-tenant client accounts |
| `attendance_policies` | Client-specific attendance rules |
| `shifts` | Shift definitions |
| `employee_shifts` | Employee shift assignments |
| `attendance_records` | Daily attendance per employee |
| `attendance_punches` | Individual punch records |
| `leave_types` | Leave type definitions |
| `leave_policies` | Leave policies per client/category |
| `leave_balances` | Employee leave balances |
| `leave_applications` | Leave requests |
| `payroll_periods` | Payroll cycles |
| `payroll_runs` | Payroll runs with 7 statuses |
| `payroll_components` | Earnings/deductions components |
| `employee_salary_structures` | Employee compensation |
| `payroll_calculations` | Per-employee calculation results |
| `payslips` | Payslip records |
| `attendance_corrections` | Attendance correction requests |

---

## 8. PHP Syntax Verification Commands

Run these to verify all modified/new files have no syntax errors:

```bash
php -l app/Http/Controllers/Admin/ClientController.php
php -l app/Http/Controllers/HR/AttendanceController.php
php -l app/Http/Controllers/HR/LeaveController.php
php -l app/Http/Controllers/HR/PayrollController.php
php -l app/Http/Controllers/Recruitment/CandidateOfferPortalController.php
php -l app/Http/Controllers/Interviewer/InterviewController.php
php -l routes/web.php
php -l resources/js/Config/navigation.js
```

All should return "No syntax errors detected".

---

## 9. Known Limitations / Items Needing Client Confirmation (PDF Section 19)

These are **not yet implemented** and require client decisions:

| Item | Status | Notes |
|------|--------|-------|
| Desktop agent installation | Not implemented | PDF Section 5 — browser-only cannot reliably detect shutdown/sleep |
| Biometric/machine punch integration | Not implemented | API endpoint exists but no device driver |
| Geofencing (10m radius) | Not implemented | Geolocation stored but no validation logic |
| Statutory calculations (PF, ESI) | Not implemented | Payroll components exist but no statutory engine |
| Payroll formula/divisor | Not implemented | Fixed-salary model assumed; configurable formula pending |
| Email notifications | Not implemented | In-app only; email channels pending confirmation |
| Payslip PDF generation | Not implemented | `pdf_path` column exists but no PDF generator |
| Second-level payroll approval | Partially implemented | `reviewed_by` and `approved_by` fields exist; workflow not enforced |

---

## 10. Quick Smoke Test (5-minute verification)

1. `php artisan migrate:status` — all 35 migrations show **Ran**
2. `php artisan serve` — server starts
3. Log in as admin → navigate to **Clients** → create a client
4. Log in as HR → navigate to **Attendance** → view records
5. Log in as HR → navigate to **Leave** → view types
6. Log in as HR → navigate to **Payroll** → view runs, periods, components, payslips
7. Open offer portal for an accepted offer → click **Convert to Employee** → verify success
8. Open interviewer interview → complete with `mark_all_rounds_cleared` + `salary_offer_status = accepted` → verify Intent Letter section appears

---

**End of Test Plan**
