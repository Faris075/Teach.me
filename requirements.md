# Functional Requirements Specification (FRS)
### Project: Teach.me — LMS Classroom Platform (MVP → Multi-School SaaS)
> **Revision 3** — Extended with independent-tutor multi-tenancy model, hybrid grading engine, privacy-first parent digest, data erasure compliance, and flexible late-submission controls.

---

## Architectural Constraints

| Concern | Decision |
|---|---|
| Backend | Laravel 11+ (PHP 8.3+) |
| Frontend | Blade + Tailwind CSS / Livewire (Monolith) |
| Database | MySQL 8.0+ or PostgreSQL |
| Coding Standard | PSR-12, clean MVC, Service-Repository pattern for business logic isolation |
| Multi-Tenancy | Single database with `school_id` discriminator + `is_independent` flag; `is_independent = true` users bypass school scoping and are isolated by `teacher_id` instead |
| Dark Mode | `tailwind.config.js` → `darkMode: 'class'`; preference persisted in `localStorage` + user meta field |
| File Storage | Laravel `Storage` facade (Flysystem abstraction); local MVP → S3 / Cloudflare R2 / DO Spaces via `.env` swap only |
| File Security | Submissions stored under private paths `/tenants/{school_id}/submissions/` (institutional) or `/tenants/users/{user_id}/submissions/` (independent); served via **signed temporary URLs** (15-min TTL) |
| Auth | Laravel Breeze or Jetstream (session-based) |
| Notifications | Laravel queued database + mail notifications; all implement `ShouldQueue` |
| Queue Driver | Redis (production) or `database` driver (local); supervised via `php artisan queue:work` |
| Parent Privacy | No live guardian login; weekly digest email dispatched by scheduler every Friday at 18:00 to `users.parent_email` |
| Data Erasure | Administrative hard-purge tool performs `forceDelete()` + `Storage::deleteDirectory()` for full GDPR-style account removal |

---

## 0. System Architecture & High-Level Data Flow

### 0.1 Tenant Isolation Model

All incoming requests must be scoped through a `MultiTenantMiddleware` layer that resolves the active school context before any database query executes. A user authenticated under School A must **never** resolve data owned by School B — cross-tenant lookups must return `404 Not Found` to avoid leaking tenant existence.

```
                      [ Incoming Web Request ]
                                │
                   [ MultiTenantMiddleware ]
               (Resolves school_id from session / domain)
                                │
         ┌──────────────────────┴──────────────────────┐
         ▼                                             ▼
[ Tenant A Pipeline ]                       [ Tenant B Pipeline ]
  school_id scoped queries                    school_id scoped queries
  /tenants/A/submissions/                     /tenants/B/submissions/
  Isolated classrooms & users                 Isolated classrooms & users
```

> **Implementation:** A global Eloquent scope or base query macro must automatically append `WHERE school_id = {active_tenant_id}` to every query executed by `school_admin`, `teacher`, and `student` roles. `super_admin` is exempt.
>
> **Independent Tutor Bypass:** When `auth()->user()->is_independent === true`, the middleware skips school-level scoping entirely. Queries for classrooms and related data are instead constrained by `teacher_id = auth()->id()`, ensuring full data isolation without requiring a `school_id`.

### 0.2 Submission Processing Pipeline

To handle concurrent load spikes (hundreds of students submitting simultaneously), the submission flow must be fully decoupled through the queue layer:

```
[Student submits file/text]
         │
         ▼
[Server-side validation: MIME type, file size ≤ 10MB]
         │
         ▼
[Check allow_late_submissions if now() > due_date]
         │
   ┌─────┴─────────────────────────────────────────────────┐
   ▼                                                       ▼
(allow_late = true OR on time)               (allow_late = false AND overdue)
   │                                                       │
[File written to private storage path]       [Abort — return 422 Unprocessable Entity]
   │
   ▼
[DB record written — status: 'submitted' or 'turned_in_late']
   │
   ▼
[Dispatch NotifyTeacher job → Queue]
   │
   ▼
[Background Worker: in-app alert + email to teacher]
```

---

## 1. Database Schema & Data Models

> All tables use `BigInt` auto-increment PKs. `uuid` columns are used in public-facing URLs — never expose integer IDs. Foreign keys cascade on delete unless noted. SoftDeletes applied where marked.

### Entity Relationship Overview

```
┌──────────┐       ┌──────────┐       ┌─────────────┐
│ schools  │──────<│  users   │──────<│  classrooms │
└──────────┘       └──────────┘       └─────────────┘
                                             │
                              ┌──────────────┼──────────────┐
                              ▼              ▼              ▼
                          ┌────────┐  ┌──────────┐  ┌────────────┐
                          │ topics │  │classroom │  │assignments │
                          └────────┘  │_student  │  └────────────┘
                              │       └──────────┘        │
                              ▼                           ▼
                         ┌──────────┐             ┌─────────────┐
                         │materials │             │ submissions │
                         └──────────┘             └─────────────┘
```

---

### `schools` *(Primary Tenant Container)*
| Column | Type | Notes |
|---|---|---|
| `id` | BigInt PK | Internal FK target only |
| `uuid` | UUID, Unique | Used in all public-facing URLs |
| `name` | String(150) | |
| `domain` | String, Unique, Nullable | Custom domain e.g. `school.lms.com` |
| `branding_config` | JSON, Nullable | `{"primary_color": "#HEX", "logo_url": "..."}` |
| `status` | Enum: `active`, `suspended`, `trial` | Default: `trial` |
| `timestamps` | | |
| `deleted_at` | Timestamp, Nullable | SoftDeletes |

> Phase 1: Seed one default `active` school. All users reference it via `school_id`.

---

### `users`
| Column | Type | Notes |
|---|---|---|
| `id` | BigInt PK | |
| `uuid` | UUID, Unique | Used in public-facing URLs |
| `school_id` | BigInt, Nullable, FK → `schools.id` | `NULL` for `super_admin` and independent tutors |
| `is_independent` | Boolean, Default: `true` | `true` = full self-deletion rights; `false` = institutional teacher, archive-only |
| `name` | String | |
| `email` | String, Unique | |
| `password` | String | Hashed via `bcrypt` |
| `role` | Enum: `super_admin`, `school_admin`, `teacher`, `student` | |
| `candidate_number` | String, Nullable, **Indexed** | IGCSE / national exam tracking; indexed for fast lookup |
| `parent_email` | String, Nullable | Target address for weekly privacy-safe digest emails |
| `status` | Enum: `active`, `inactive` | Default: `active` |
| `remember_token` | String | |
| `timestamps` | | |
| `deleted_at` | Timestamp, Nullable | SoftDeletes |

---

### `classrooms`
| Column | Type | Notes |
|---|---|---|
| `id` | BigInt PK | |
| `school_id` | BigInt, Nullable, FK → `schools.id` | `NULL` for independent tutors |
| `teacher_id` | BigInt, FK → `users.id` | |
| `name` | String | e.g., "IGCSE English Second Language" |
| `class_code` | String(8), Unique, **Indexed** | 6–8 alphanumeric chars; index for fast join lookups |
| `status` | Enum: `active`, `archived` | Default: `active` |
| `timestamps` | | |
| `deleted_at` | Timestamp, Nullable | SoftDeletes |

---

### `classroom_student` *(Pivot)*
| Column | Type | Notes |
|---|---|---|
| `classroom_id` | BigInt, FK → `classrooms.id` | Cascades on delete |
| `student_id` | BigInt, FK → `users.id` | Cascades on delete |
| `joined_at` | Timestamp | Set on insert |
| **Composite Unique** | `[classroom_id, student_id]` | Prevents duplicate enrolments |

---

### `topics`
| Column | Type | Notes |
|---|---|---|
| `id` | BigInt PK | |
| `classroom_id` | BigInt, FK → `classrooms.id` | |
| `title` | String | e.g., "Directed Writing" |
| `sort_order` | Integer, Default: 0 | Teacher-controlled ordering |
| `timestamps` | | |

---

### `materials`
| Column | Type | Notes |
|---|---|---|
| `id` | BigInt PK | |
| `topic_id` | BigInt, FK → `topics.id` | |
| `title` | String | |
| `content` | Text, Nullable | Rich-text or plain body |
| `file_path` | String, Nullable | Private storage path |
| `timestamps` | | |

---

### `assignments`
| Column | Type | Notes |
|---|---|---|
| `id` | BigInt PK | |
| `classroom_id` | BigInt, FK → `classrooms.id` | |
| `topic_id` | BigInt, Nullable, FK → `topics.id` | Optional grouping; `NULL` on delete |
| `title` | String | |
| `description` | LongText | |
| `file_path` | String, Nullable | Attached resource (PDF/DOCX) |
| `due_date` | DateTime | |
| `allow_late_submissions` | Boolean, Default: `true` | If `false`, submissions are blocked the moment `now() > due_date` |
| `grading_type` | Enum: `percentage`, `igcse_letter` | Dictates which grading UI components are rendered |
| `max_score` | Decimal(5,2), Default: 100.00 | Supports regional grading scales |
| `timestamps` | | |
| `deleted_at` | Timestamp, Nullable | SoftDeletes |

---

### `submissions`
| Column | Type | Notes |
|---|---|---|
| `id` | BigInt PK | |
| `assignment_id` | BigInt, FK → `assignments.id` | |
| `student_id` | BigInt, FK → `users.id` | |
| `file_path` | String, Nullable | Stored under `/tenants/{school_id​\|user_id}/submissions/` |
| `submitted_text` | LongText, Nullable | Text-response alternative |
| `grade_numeric` | Decimal(5,2), Nullable | Raw numeric value; used for all statistical aggregations (GPA, turnaround, charts) |
| `grade_literal` | String(4), Nullable | Letter or symbol grade (e.g., `A*`, `9`, `B`); set when `grading_type = 'igcse_letter'` |
| `teacher_comment` | Text, Nullable | Qualitative feedback |
| `status` | Enum: `submitted`, `graded`, `turned_in_late` | Default: `submitted` |
| `graded_at` | Timestamp, Nullable | Set when grade is saved |
| `timestamps` | | |

---

## 2. Role-Based Access Control (RBAC)

Use Laravel Policies (`app/Policies`) or the **Spatie Permission** package.

```
[super_admin]   ──▶ Global read/write override across all schools
      │
      └── [school_admin]  ──▶ Full read/audit within own school_id
                │
                ├── [teacher]   ──▶ CRUD only within owned classrooms
                │
                └── [student]   ──▶ Read assigned classrooms; write own submissions only
```

### Route Security Groups

| Route Prefix | Guard |
|---|---|
| `/login`, `/register`, `/join/{class_code}` | Guest (unauthenticated) |
| `/dashboard`, `/settings` | `auth` middleware |
| `/admin/*` | `auth` + `role:super_admin,school_admin` |
| `/teacher/*` | `auth` + `role:teacher` |
| `/student/*` | `auth` + `role:student` |

> Implement a single `RoleMiddleware` registered in `bootstrap/app.php` that reads `auth()->user()->role` and aborts with `403` on mismatch.

### Classroom Deletion Policy (`ClassroomPolicy@delete`)

Institutional teachers (those assigned to a school) may only **archive** classrooms; permanent deletion requires admin intervention. Independent tutors retain full deletion rights over their own classrooms.

```php
public function delete(User $user, Classroom $classroom): Response
{
    if ($user->role === 'teacher' && !$user->is_independent) {
        return $this->deny(
            'Teachers inside an organisation can only archive classrooms. Contact your administrator for permanent deletion.'
        );
    }
    return $user->id === $classroom->teacher_id || $user->role === 'school_admin'
        ? Response::allow()
        : Response::deny('Unauthorised.');
}
```

---

## 3. Core Epics & Feature Requirements

### Epic 1 — Invitation & Onboarding

**Req 1.1 — Class Code Entry**
- A text input on the student dashboard accepts a 6–8 character alphanumeric code.
- Validate against `classrooms` where `status = 'active'` **and** `school_id = active_tenant_id`.
- On success, insert a record into `classroom_student` with `joined_at = now()`.
- Prevent duplicate enrolments via the composite unique constraint on `[classroom_id, student_id]`.

**Req 1.2 — Invite Link**
- Generate a signed URL: `/join/{class_code}` using Laravel's `URL::signedRoute()`.
- If the visitor is unauthenticated, redirect to registration then complete the join action post-auth.
- If already enrolled, redirect to the classroom stream with a flash notice.

**Req 1.3 — QR Code**
- The classroom settings page renders a scannable SVG QR code pointing to the signed invite URL.
- Use the `simplesoftwareio/simple-qrcode` package; render server-side (never expose raw URLs client-side).
- Provide a "Download PNG" button for the teacher to share offline.

**Edge-Case Guards (Security)**
| Rule | Condition | Response |
|---|---|---|
| Cross-tenant isolation | Student in School A inputs a code belonging to School B | `404 Not Found` — never reveal the code exists |
| Archived class | Class `status = 'archived'` | Explicit validation error message returned |
| Code collision on generation | New random code matches an existing record | Re-generate until unique; max 5 retries then fail gracefully |

---

### Epic 2 — Classroom Stream & Topic Organisation

**Req 2.1 — Unified Stream**
- Chronological card-based feed showing announcements, materials, and assignments.
- Cards must display: type badge, title, timestamp, and a quick-action button.

**Req 2.2 — Topic Clustering**
- Teachers can create, rename, reorder (`sort_order`), and delete topics.
- The stream can be filtered by topic using a sidebar or dropdown.
- Materials and assignments display their parent topic label.

**Req 2.3 — Responsive Viewport**
- Multi-column grid layout on desktop (≥768px).
- Single-column stacked card layout on mobile (<768px).
- No horizontal scroll. All text must remain readable without zooming.

---

### Epic 3 — Assignment, Submission & Grading Engine

**Req 3.1 — Assignment Creation (Teacher)**
- Multi-part form: Title, Description (rich text), optional File Upload (PDF/DOCX only, max 10MB), Due Date via **Flatpickr** datetime picker, Max Score field (default 100), **Grading Type** selector (`Percentage` or `IGCSE Letter`), and **Allow Late Submissions** toggle (default: on).
- File stored via `Storage::disk('private')->put("tenants/{school_id}/assignments/", ...)`. Path saved to `assignments.file_path`.
- Dispatches a queued `NotifyClassroomStudents` job on save (see Epic 4, Req 4.2).

**Req 3.2 — Student Submission Portal**
- Per-assignment status badge: `Missing` (overdue, no submission) · `Submitted` · `Graded` · `Turned In Late`.
- Student may upload one file (PDF/DOCX/PNG/JPG, max 10MB) OR enter a text response. Both cannot be empty.
- **Late submission handling:**
  - If `now() > assignments.due_date` AND `allow_late_submissions = true`: accept the file, set `submissions.status = 'turned_in_late'`.
  - If `now() > assignments.due_date` AND `allow_late_submissions = false`: reject immediately with HTTP 422 and a clear error message.

**Req 3.3 — Grading Interface (Teacher)**
- Split-screen layout:
  - **Left pane**: Renders submitted file (PDF iframe or download link via signed URL) or submitted text.
  - **Right pane**: Grade input (adapts to `assignments.grading_type`):
    - `percentage`: Numeric input `0` – `max_score` → saved to `submissions.grade_numeric`.
    - `igcse_letter`: Dropdown or segmented control (A\*, A, B, C, D, E, U or 9–1) → saved to `submissions.grade_literal`; teacher may optionally enter a numeric equivalent into `grade_numeric` for statistical purposes.
  - Feedback textarea and Save button shared by both modes.
- Saving a non-null `grade_numeric` **or** `grade_literal` sets `graded_at = now()` and fires the `GradePublished` event (see Epic 4, Req 4.1).
- Paginated list of all student submissions for a given assignment shown above the split-screen.

---

### Epic 4 — Real-Time Event Notifications

**Req 4.1 — Grade Published Trigger**
- When `submissions.grade_numeric` **or** `submissions.grade_literal` is saved to a non-null value, fire a Laravel Event (`GradePublished`).
- Listener dispatches:
  1. A queued `Mail` notification to the student's email.
  2. A `database` notification stored in `notifications` table (Laravel default).
- Navbar bell icon shows unread count; clicking marks as read.

**Req 4.2 — New Assignment Alert**
- On assignment creation, dispatch a queued `Job` (`NotifyClassroomStudents`).
- Job iterates all students in `classroom_student` for that `classroom_id`.
- Sends each student a database notification and email summarising the assignment title and due date.

---

### Epic 5 — Administrative Analytics Hub

**Req 5.1 — Teacher Audit (School Admin)**
- Table listing all `teacher` role users within the admin's `school_id`.
- Per teacher metrics: total assignments posted, count of ungraded submissions, average grading turnaround time, and a log of all `teacher_comment` entries for QA review.
- Average Grading Turnaround:

$$\text{Avg Turnaround} = \text{AVG}(\text{submissions.graded\_at} - \text{submissions.created\_at})$$

- Queries must use Eloquent `withCount` and `loadMissing` to prevent N+1.

**Req 5.2 — Student Overview (School Admin)**
- Tabular view of all students in the school:
  - Running GPA: `AVG(submissions.grade_numeric)` per student.
  - Completion ratio:

$$\text{Completion Ratio} = \left(\frac{\text{COUNT(submissions)}}{\text{COUNT(assigned)}}\right) \times 100\%$$

  - Grade-over-time chart (Chart.js) showing individual student trajectory.
- Filterable by classroom.

---

## 4. Data Privacy & Compliance

### Req 6.1 — Privacy-First Parent Digest Engine

Parents do not receive login credentials and have no access to live message streams, submission content, or real-time activity logs. Instead, a weekly scheduled job aggregates a clean performance summary and delivers it as a formatted email.

**Scheduler trigger:** Every Friday at 18:00 via `Schedule::job(SendParentDigests::class)->weeklyOn(5, '18:00')`.

**Aggregation logic per student:**
- Average grade: `AVG(submissions.grade_numeric)` over the past 7 days.
- Missing assignments: count of assignments past `due_date` with no matching submission record.
- Recent feedback snippets: last 3 non-null `teacher_comment` values.

**Dispatch flow:**

```
[ Weekly Cron: Friday 18:00 ] ──> [ Query all active students with parent_email set ]
                                                  │
                                                  ▼
                              [ Per student: aggregate grade + missing count ]
                                                  │
                                                  ▼
                                  [ Dispatch queued DigestMail → parent_email ]
```

- The digest email must **not** include submission file links, chat logs, or any personally identifiable data beyond the student's first name and aggregated metrics.
- Implemented as a queued `Mailable` so delivery failures do not block the scheduler.

---

### Req 6.2 — Hard Purge (Right to Erasure)

An administrative controller action (`AdminController@hardPurgeUser`) provides GDPR-style account removal. This action is restricted to `super_admin` only and requires explicit confirmation (`DELETE /admin/users/{uuid}/purge`).

**Execution sequence:**
1. Resolve the user by `uuid` (never by integer `id` in the URL).
2. Iterate all owned file paths from `submissions`, `assignments`, and `materials` records.
3. Delete binary assets from the storage driver:
   ```php
   Storage::disk('private')->deleteDirectory("tenants/users/{$user->id}");
   // For institutional users also purge school-scoped paths they own
   ```
4. Hard-delete all child DB records via raw queries, bypassing soft-delete:
   ```php
   $user->submissions()->forceDelete();
   $user->classrooms()->each(fn($c) => $c->assignments()->forceDelete());
   $user->classrooms()->forceDelete();
   $user->forceDelete();
   ```
5. Return a `204 No Content` response on success.

> **Safety gate:** The action must require a confirmation token (e.g., the user's email re-entered in the request body) to prevent accidental triggers.

---

## 5. Operational System Flows & Business Rules

### Flow 1 — Secure Class Enrolment Lifecycle

1. **Code Generation:** Teacher clicks "Generate Invite" → system calls `Str::upper(Str::random(6))` and checks for collision against `classrooms.class_code`. Retry up to 5 times; surface an error if all collide.
2. **QR Code:** Generated server-side as an SVG vector. Never rendered via a third-party external URL. Private to the authenticated teacher's session.
3. **Invite Link:** Signed URL via `URL::signedRoute('classroom.join', ['code' => $class_code])`.
4. **Security Guards:**
   - Cross-tenant code input → `404 Not Found` (no tenant existence leak).
   - Archived classroom → validation error with explicit message.
   - Already enrolled → redirect with flash notice.

---

### Flow 2 — Submission & Grading Workflow

1. Student uploads file or text response.
2. Server validates: MIME type (`application/pdf`, `application/vnd.openxmlformats-officedocument.wordprocessingml.document`, `image/png`, `image/jpeg`) and size ≤ 10MB.
3. **Late check:**
   - If `now() ≤ due_date`: proceed normally.
   - If `now() > due_date` AND `allow_late_submissions = false`: abort, return HTTP 422.
   - If `now() > due_date` AND `allow_late_submissions = true`: accept, flag status `'turned_in_late'`.
4. File stored to `Storage::disk('private')->put("tenants/{school_id}/submissions/{filename}")`.
5. DB record created with `status = 'submitted'` (or `'turned_in_late'`).
6. `NotifyTeacherNewSubmission` job dispatched → queued background worker sends in-app alert.
7. Teacher opens grading view → accesses file via **signed temporary URL** (15-min TTL).
8. Teacher saves `grade_numeric` or `grade_literal` → `GradePublished` event fires → listener dispatches:
   - Database notification (in-app bell).
   - Queued email to student.

---

### Flow 3 — Admin Auditing & Analytics

1. **School Admin** navigates to analytics hub (scoped to their `school_id`).
2. **Teacher KPI panel** runs `withCount(['assignments', 'submissions' => fn($q) => $q->whereNull('graded_at')])` per teacher.
3. **Grading turnaround** computed as `AVG(TIMESTAMPDIFF(MINUTE, created_at, graded_at))` per teacher.
4. **Student performance table** runs per-student `AVG(grade_numeric)` and submission/assignment ratio.
5. **Grade timeline chart** pulls `(submissions.graded_at, submissions.grade_numeric)` per student, rendered via Chart.js line chart.

---

### Flow 4 — Parent Digest Lifecycle

1. Laravel Scheduler fires `SendParentDigests` job every **Friday at 18:00**.
2. Job queries all `active` students where `parent_email IS NOT NULL`.
3. For each student, the aggregation service computes:
   - `AVG(grade_numeric)` for submissions in the past 7 days.
   - Count of assignments past `due_date` with no submission record (`Missing`).
   - Last 3 non-null `teacher_comment` snippets.
4. A queued `DigestMail` Mailable is dispatched to `users.parent_email`.
5. The email renders a clean HTML summary containing only the student's first name and the three metrics above — no file links, chat history, or submission content.
6. Failed deliveries are retried up to 3 times via the queue; failures do not block subsequent digest dispatches.

---

## 6. UI/UX Design System

### Colour Tokens

| Token | Light Mode | Dark Mode |
|---|---|---|
| Page background | `bg-gray-50` | `bg-slate-900` |
| Card background | `bg-white` | `bg-slate-800` |
| Primary text | `text-slate-800` | `text-white` |
| Secondary text | `text-slate-500` | `text-slate-400` |
| Primary accent | `bg-indigo-600` | `bg-indigo-500` |
| Danger | `bg-red-500` | `bg-red-400` |

### Spacing & Density
- Card padding: `p-6`
- Vertical rhythm: `space-y-4`
- Card padding: `p-4` mobile → `md:p-8` desktop
- Section gaps: `gap-6` on grid layouts
- Vertical rhythm: `space-y-4`
- Min tap target: **44×44px** (WCAG AA touch compliance)
- All tabular/metrics views must remain legible and scroll-free down to 375px viewport width.

### Dark Mode Toggle
- Toggled by adding/removing the `dark` class on `<html>`.
- Persist preference in **both** `localStorage` (prevents flash on load) and the user's meta field (syncs across devices).
- On page load, read `localStorage` before the DOM renders to apply the class instantly — eliminates the unstyled flash lag.
- A sun/moon icon button in the top navbar handles the toggle.

---

## 7. File Storage Strategy

### Storage Paths
| Content Type | Path | Visibility |
|---|---|---|
| Assignment attachments (institutional) | `tenants/{school_id}/assignments/{filename}` | Private |
| Assignment attachments (independent) | `tenants/users/{user_id}/assignments/{filename}` | Private |
| Student submissions (institutional) | `tenants/{school_id}/submissions/{filename}` | **Private** |
| Student submissions (independent) | `tenants/users/{user_id}/submissions/{filename}` | **Private** |
| Material files (institutional) | `tenants/{school_id}/materials/{filename}` | Private |
| Material files (independent) | `tenants/users/{user_id}/materials/{filename}` | Private |
| School logos / branding | `tenants/{school_id}/branding/{filename}` | Public |

### Read / Write Pattern
```php
// Writing (private disk)
Storage::disk('private')->put("tenants/{$schoolId}/submissions/{$filename}", $contents);

// Serving — generate a signed temporary URL (15-min TTL)
$url = Storage::disk('private')->temporaryUrl(
    $submission->file_path,
    now()->addMinutes(15)
);
```

### Environment Portability
- All `file_path` columns store the **relative path** returned by `Storage::put()` — never an absolute or public URL.
- Switching from local → S3 / Cloudflare R2 / DigitalOcean Spaces requires **only** changing `.env` variables (`FILESYSTEM_DISK`, `AWS_*`). Zero application code changes.
- `php artisan storage:link` is only needed for the public branding disk in local development.

---

## 8. Step-by-Step Implementation Checklist

Execute sequentially. Do not advance until the current step is verified.

- [ ] **STEP 1** — Regenerate all migrations and models per the revised Section 1 schema. New columns: `users.is_independent`, `users.parent_email`, `classrooms.school_id` (nullable), `assignments.allow_late_submissions`, `assignments.grading_type`, `submissions.grade_numeric`, `submissions.grade_literal`; rename `submissions.grade`; update status enum to replace `pending` with `submitted`. Confirm all indices (`class_code`, `candidate_number`) and foreign key cascades are present.
- [ ] **STEP 2** — Implement Laravel Breeze authentication. Build `RoleMiddleware`, `MultiTenantMiddleware` (with `is_independent` bypass), and `ClassroomPolicy@delete` per Section 2. Register all route groups.
- [ ] **STEP 3** — Create database seeders: 1 default School, 1 Super Admin, 1 School Admin, 1 independent Teacher (no school), 1 institutional Teacher, 3 Students. Verify all role-restricted routes and the classroom deletion policy respond correctly.
- [ ] **STEP 4** — Build the master Blade layout with Tailwind CSS. Implement light/dark mode toggle with `localStorage` + user meta persistence.
- [ ] **STEP 5** — Implement Epic 1: class code input, signed invite link, QR code generation. Include all edge-case guards (cross-tenant isolation, archived class, collision retry).
- [ ] **STEP 6** — Build Teacher Dashboard: topic management, material uploads, assignment creation form with `grading_type` selector and `allow_late_submissions` toggle (Epics 2 & 3).
- [ ] **STEP 7** — Build Student Dashboard: classroom stream, submission portal with late-blocking logic, grade/comment view displaying both `grade_numeric` and `grade_literal` (Epic 3).
- [ ] **STEP 8** — Implement the hybrid Grading Engine UI (Req 3.3): percentage mode (numeric input) and IGCSE letter mode (dropdown). Wire `GradePublished` event to fire on save of either grade field (Epic 4).
- [ ] **STEP 9** — Implement Epic 4 notifications: queued `NotifyClassroomStudents` job and `GradePublished` event/listener with database + mail channels.
- [ ] **STEP 10** — Build School Admin Analytics Hub (Epic 5) using `AVG(grade_numeric)`, `COUNT`, and `withCount` Eloquent aggregations.
- [ ] **STEP 11** — Implement the Parent Digest Engine (Req 6.1): `SendParentDigests` job, `DigestMail` Mailable, scheduler registration (`weeklyOn(5, '18:00')`).
- [ ] **STEP 12** — Implement the Hard Purge controller action (Req 6.2): `AdminController@hardPurgeUser`, storage directory deletion, `forceDelete()` cascade, confirmation token gate.

---

## 9. MVP Roadmap & Phase Plan

> This section translates the 12 implementation steps into sequenced delivery phases with clear scope boundaries, acceptance gates, and a defined MVP cut-off. Features marked **✦ MVP** must be complete before any live deployment. Features marked **◇ Post-MVP** are scheduled for a follow-up release.

---

### MVP Scope Definition

| Category | In MVP ✦ | Deferred ◇ |
|---|---|---|
| Auth & roles | Login, register, logout, RoleMiddleware | Social login, 2FA |
| Schema | All revised tables, indices, seed data | Schema migrations for new features |
| UI | Master layout, dark mode toggle | Livewire real-time updates |
| Enrollment | Class code join, signed invite link | QR code download button |
| Teacher flow | Classroom CRUD, topic CRUD, assignment creation, file upload | Material rich-text editor |
| Student flow | Classroom stream, file/text submission, late-blocking | Re-submission on graded work |
| Grading | Percentage mode, IGCSE letter mode, split-screen UI | Bulk grading |
| Notifications | Database bell + unread count | Queued email delivery |
| Admin | — | Full analytics hub (Epic 5) |
| Parent digest | — | `SendParentDigests` scheduler (Req 6.1) |
| Compliance | — | Hard purge controller (Req 6.2) |

---

### Phase 0 — Environment Bootstrap
> **Goal:** Verified, runnable Laravel application connected to the database.

**Tasks:**
1. Confirm PHP 8.2+, Composer, and Node.js are available.
2. Configure `.env`: set `DB_*` credentials, `APP_KEY`, `QUEUE_CONNECTION=database`.
3. Run `composer install` and `npm install`.
4. Install Laravel Breeze: `composer require laravel/breeze && php artisan breeze:install blade`.
5. Install QR code package: `composer require simplesoftwareio/simple-qrcode`.
6. Run `npm run build` to compile Tailwind assets.
7. Run `php artisan migrate` to validate DB connectivity.
8. Run `php artisan serve` and confirm `http://localhost:8000` loads.

**Phase Gate:** `php artisan serve` starts without errors; `/` returns HTTP 200.

---

### Phase 1 — Data Foundation *(Steps 1 → 3)*
> **Goal:** Correct schema in the database, seeded with all test roles, all model relationships wired.

**Covers:** STEP 1, STEP 2 (schema + models only), STEP 3

**Tasks:**
1. Rewrite all migrations to match the Section 1 schema:
   - `schools` — add `uuid`, `branding_config`, updated `status` enum, `deleted_at`.
   - `users` — add `uuid`, `is_independent`, `parent_email`, `candidate_number` (indexed), `status`.
   - `classrooms` — make `school_id` nullable, add `deleted_at`.
   - `assignments` — add `allow_late_submissions`, `grading_type`, `max_score`, `deleted_at`.
   - `submissions` — rename `grade` → `grade_numeric`, add `grade_literal`, update `status` enum (`pending` → `submitted`).
2. Update all Eloquent models: fillable arrays, casts, relationships, SoftDeletes traits.
3. Write `DatabaseSeeder` with: 1 School (`active`), 1 `super_admin`, 1 `school_admin`, 1 independent `teacher` (`school_id = null`, `is_independent = true`), 1 institutional `teacher`, 3 `student` accounts.
4. Run `php artisan migrate:fresh --seed`.

**Phase Gate:** `php artisan migrate:fresh --seed` exits 0; `php artisan tinker` confirms all seeded users and relationships resolve correctly.

---

### Phase 2 — Auth Shell & Middleware *(Steps 2 → 4)*
> **Goal:** All role-restricted routes are guarded; master layout with dark mode is live.

**Covers:** STEP 2 (Breeze + middleware), STEP 3 (route verification), STEP 4 (UI layout)

**Tasks:**
1. Publish Breeze views; customise register form to include `role` selection (student/teacher only for self-service; admin roles seeded only).
2. Implement `RoleMiddleware`: reads `auth()->user()->role`, aborts 403 on mismatch.
3. Implement `MultiTenantMiddleware`: resolves `school_id` from session; if `is_independent = true` sets `teacher_id` scope instead; skips for `super_admin`.
4. Register both middleware aliases in `bootstrap/app.php`.
5. Define route groups in `web.php`: `/teacher/*`, `/student/*`, `/admin/*` with appropriate guards.
6. Implement `ClassroomPolicy@delete`.
7. Build `resources/views/layouts/app.blade.php`:
   - Top navbar: logo, user dropdown, dark mode sun/moon toggle, notification bell placeholder.
   - Sidebar with role-appropriate links.
   - `@yield('content')` main area.
8. Wire dark mode toggle: add/remove `dark` class on `<html>`, persist to `localStorage`.

**Phase Gate:** Visiting `/teacher/dashboard` as a `student` returns 403. Visiting `/student/dashboard` as a `teacher` returns 403. Dark mode toggle persists across page reloads.

---

### Phase 3 — Classroom & Enrollment ✦ MVP *(Step 5)*
> **Goal:** Teacher can create and manage classrooms; students can join via class code and signed link.

**Covers:** STEP 5

**Tasks:**
1. `ClassroomController`: `index`, `create`, `store`, `show`, `archive`, `destroy`.
2. Class code generation: `Str::upper(Str::random(6))` with collision-retry loop (max 5).
3. Enrolment flow: `POST /join` validates code against `classrooms` scoped to tenant (or 404 for cross-tenant). Inserts `classroom_student` record. Handles duplicate enrolment gracefully.
4. Signed invite link: `URL::signedRoute('classroom.join', ['code' => $class_code])` displayed on classroom settings page.
5. ✦ **MVP:** Class code + signed link working end-to-end.
6. ◇ **Post-MVP:** QR code SVG render + PNG download button.

**Phase Gate:** Student can join a classroom by entering the code; joining the same classroom twice returns a flash notice instead of an error; entering a code from another tenant returns 404.

---

### Phase 4 — Assignment & Submission Engine ✦ MVP *(Steps 6 → 7)*
> **Goal:** Teacher creates assignments with all fields; students submit work; late-blocking enforced.

**Covers:** STEP 6 (assignment creation), STEP 7 (student dashboard + submission)

**Tasks:**
1. Teacher dashboard — Classroom stream view: chronological card feed of assignments and materials.
2. Topic management: create, rename, reorder (`sort_order`), delete. Sidebar filter.
3. `AssignmentController@create` / `store`: form with `title`, `description`, `due_date` (Flatpickr), `max_score`, `grading_type` selector, `allow_late_submissions` toggle, optional file upload (PDF/DOCX, 10MB limit).
4. File stored to `Storage::disk('local')->put("tenants/{teacherId|schoolId}/assignments/{uuid}.{ext}")`.
5. Student dashboard — assignment cards with status badge (`Missing` · `Submitted` · `Graded` · `Turned In Late`).
6. `SubmissionController@store`: MIME + size validation; late-blocking (HTTP 422 when `allow_late_submissions = false`); file stored under private submissions path; DB record created.

**Phase Gate:** Teacher creates an assignment; student sees it in their stream with correct status badge; submitting after deadline with `allow_late_submissions = false` returns a validation error; submitting with flag `= true` creates a `turned_in_late` record.

---

### Phase 5 — Grading Engine ✦ MVP *(Step 8)*
> **Goal:** Teacher grades all submission types; correct columns written; `GradePublished` event fires.

**Covers:** STEP 8

**Tasks:**
1. `GradingController@show`: paginated submission list + split-screen view.
2. Left pane: render submitted text or file download link (signed URL, 15-min TTL).
3. Right pane: conditional on `assignments.grading_type`:
   - `percentage`: numeric `<input>` bounded by `max_score` → writes `grade_numeric`.
   - `igcse_letter`: `<select>` with options A\*, A, B, C, D, E, U and 9–1 → writes `grade_literal`; optional numeric companion input → writes `grade_numeric`.
4. On save: set `graded_at = now()`, set `status = 'graded'`, fire `GradePublished` event.
5. `GradePublished` listener: create `database` notification record for student; dispatch queued `GradePublishedMail` (✦ MVP: DB notification only; ◇ Post-MVP: email delivery).
6. Navbar bell icon reads `auth()->user()->unreadNotifications->count()`; mark-as-read on click.

**Phase Gate:** Teacher grades a percentage assignment; `submissions.grade_numeric` updated; student bell icon shows unread count 1. Teacher grades an IGCSE assignment; `submissions.grade_literal` updated. Signed URL for submitted file expires after 15 minutes.

---

### Phase 6 — Queued Notifications ◇ Post-MVP *(Step 9)*
> **Goal:** Email delivery wired for both grade events and new assignment alerts.

**Covers:** STEP 9

**Tasks:**
1. `NotifyClassroomStudents` job: iterates `classroom_student`, sends each student a `NewAssignmentNotification` (database + mail).
2. `GradePublishedMail` Mailable: queued, implements `ShouldQueue`; renders grade summary to student email.
3. Configure `MAIL_*` in `.env`; test with Mailpit or log driver locally.
4. Run `php artisan queue:work` and verify jobs process.

**Phase Gate:** Creating an assignment dispatches notifications visible in `php artisan queue:work` output. Grading a submission sends email to the student's address.

---

### Phase 7 — Admin Analytics Hub ◇ Post-MVP *(Step 10)*
> **Goal:** School Admin has full visibility into teacher KPIs and student performance.

**Covers:** STEP 10

**Tasks:**
1. `AdminController@teachers`: `withCount(['assignments', 'ungradedSubmissions'])`, turnaround computed via raw `AVG(TIMESTAMPDIFF(...))`.
2. `AdminController@students`: per-student `AVG(grade_numeric)`, completion ratio, filterable by classroom.
3. Chart.js grade-over-time line chart: `(graded_at, grade_numeric)` data points.

**Phase Gate:** Admin dashboard displays correct counts; N+1 absent (verified with Laravel Debugbar or Telescope).

---

### Phase 8 — Compliance Layer ◇ Post-MVP *(Steps 11 → 12)*
> **Goal:** Parent digest dispatches weekly; hard purge permanently removes all user data.

**Covers:** STEP 11, STEP 12

**Tasks:**
1. `SendParentDigests` job + `DigestMail` Mailable; register `weeklyOn(5, '18:00')` in scheduler.
2. `AdminController@hardPurgeUser`: `super_admin` only, confirmation token gate, `Storage::deleteDirectory`, `forceDelete()` cascade.

**Phase Gate:** `php artisan schedule:run` dispatches digest emails for students with `parent_email` set. Hard purge removes all DB records and storage files; response is `204 No Content`.

---

### Phase Dependency Summary

```
Phase 0 (Bootstrap)
    │
    ▼
Phase 1 (Data Foundation)
    │
    ▼
Phase 2 (Auth Shell)
    │
    ├──▶ Phase 3 (Classrooms) ──▶ Phase 4 (Assignments) ──▶ Phase 5 (Grading)
    │                                                              │
    │                                                    ┌─────────┴─────────┐
    │                                                    ▼                   ▼
    │                                           Phase 6 (Email)    Phase 7 (Analytics)
    │                                                    │
    └───────────────────────────────────────────▶ Phase 8 (Compliance)
```

**MVP cut-off: end of Phase 5.** Phases 6–8 are independent parallel workstreams that do not block go-live.

---

### Credentials After Seeding

| Role | Email | Password |
|  --- |  ---  |   ---    |
| Super Admin | `superadmin@teach.me` | `password` |
| School Admin | `admin@teach.me` | `password` |
| Independent Teacher | `teacher@teach.me` | `password` |
| Institutional Teacher | `inst.teacher@teach.me` | `password` |
| Student 1 | `student1@teach.me` | `password` |
| Student 2 | `student2@teach.me` | `password` |
| Student 3 | `student3@teach.me` | `password` |
