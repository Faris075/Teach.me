# Functional Requirements Specification (FRS)
### Project: Teach.me — LMS Classroom Platform (MVP → Multi-School SaaS)
> **Revision 2** — Expanded with multi-tenancy architecture, production-grade schema, operational flows, and security constraints.

---

## Architectural Constraints

| Concern | Decision |
|---|---|
| Backend | Laravel 11+ (PHP 8.3+) |
| Frontend | Blade + Tailwind CSS / Livewire (Monolith) |
| Database | MySQL 8.0+ or PostgreSQL |
| Coding Standard | PSR-12, clean MVC, Service-Repository pattern for business logic isolation |
| Multi-Tenancy | Single database with `school_id` discriminator; global query scoping via middleware |
| Dark Mode | `tailwind.config.js` → `darkMode: 'class'`; preference persisted in `localStorage` + user meta field |
| File Storage | Laravel `Storage` facade (Flysystem abstraction); local MVP → S3 / Cloudflare R2 / DO Spaces via `.env` swap only |
| File Security | Submissions stored under private paths `/tenants/{school_id}/submissions/`; served via **signed temporary URLs** (15-min TTL) |
| Auth | Laravel Breeze or Jetstream (session-based) |
| Notifications | Laravel queued database + mail notifications; all implement `ShouldQueue` |
| Queue Driver | Redis (production) or `database` driver (local); supervised via `php artisan queue:work` |

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

### 0.2 Submission Processing Pipeline

To handle concurrent load spikes (hundreds of students submitting simultaneously), the submission flow must be fully decoupled through the queue layer:

```
[Student submits file/text]
         │
         ▼
[Server-side validation: MIME type, file size ≤ 10MB]
         │
         ▼
[File written to private storage path]
         │
         ▼
[DB record written — status: 'pending']
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
| `school_id` | BigInt, Nullable, FK → `schools.id` | `NULL` only for `super_admin` |
| `name` | String | |
| `email` | String, Unique | |
| `password` | String | Hashed via `bcrypt` |
| `role` | Enum: `super_admin`, `school_admin`, `teacher`, `student` | |
| `candidate_number` | String, Nullable | IGCSE / national exam tracking |
| `status` | Enum: `active`, `inactive` | Default: `active` |
| `remember_token` | String | |
| `timestamps` | | |
| `deleted_at` | Timestamp, Nullable | SoftDeletes |

---

### `classrooms`
| Column | Type | Notes |
|---|---|---|
| `id` | BigInt PK | |
| `school_id` | BigInt, FK → `schools.id` | |
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
| `file_path` | String, Nullable | Stored under `/tenants/{school_id}/submissions/` |
| `submitted_text` | LongText, Nullable | Text-response alternative |
| `grade` | Decimal(5,2), Nullable | Set on grading |
| `teacher_comment` | Text, Nullable | Qualitative feedback |
| `status` | Enum: `pending`, `graded`, `turned_in_late` | Default: `pending` |
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
- Multi-part form: Title, Description (rich text), optional File Upload (PDF/DOCX only, max 10MB), Due Date via **Flatpickr** datetime picker, and Max Score field (default 100).
- File stored via `Storage::disk('private')->put("tenants/{school_id}/assignments/", ...)`. Path saved to `assignments.file_path`.
- Dispatches a queued `NotifyClassroomStudents` job on save (see Epic 4, Req 4.2).

**Req 3.2 — Student Submission Portal**
- Per-assignment status badge: `Missing` (overdue, no submission) · `Pending` · `Graded` · `Turned In Late`.
- Student may upload one file (PDF/DOCX/PNG/JPG, max 10MB) OR enter a text response. Both cannot be empty.
- Late detection: if `now() > assignments.due_date` at submission time, set `submissions.status = 'turned_in_late'` automatically.

**Req 3.3 — Grading Interface (Teacher)**
- Split-screen layout:
  - **Left pane**: Renders submitted file (PDF iframe or download link via signed URL) or submitted text.
  - **Right pane**: Numeric grade input (`0` – `max_score`), feedback textarea, Save button.
- Saving a non-null grade sets `graded_at = now()` and fires the `GradePublished` event (see Epic 4, Req 4.1).
- Paginated list of all student submissions for a given assignment shown above the split-screen.

---

### Epic 4 — Real-Time Event Notifications

**Req 4.1 — Grade Published Trigger**
- On `submissions.grade` being set to a non-null value, fire a Laravel Event (`GradePublished`).
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
  - Running GPA: `AVG(submissions.grade)` per student.
  - Completion ratio:

$$\text{Completion Ratio} = \left(\frac{\text{COUNT(submissions)}}{\text{COUNT(assigned)}}\right) \times 100\%$$

  - Grade-over-time chart (Chart.js) showing individual student trajectory.
- Filterable by classroom.

---

## 4. Operational System Flows & Business Rules

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
3. File stored to `Storage::disk('private')->put("tenants/{school_id}/submissions/{filename}")`.
4. Late check: if `now() > assignment.due_date`, status is set to `'turned_in_late'`.
5. DB record created with `status = 'pending'` (or `'turned_in_late'`).
6. `NotifyTeacherNewSubmission` job dispatched → queued background worker sends in-app alert.
7. Teacher opens grading view → accesses file via **signed temporary URL** (15-min TTL).
8. Teacher saves grade → `GradePublished` event fires → listener dispatches:
   - Database notification (in-app bell).
   - Queued email to student.

---

### Flow 3 — Admin Auditing & Analytics

1. **School Admin** navigates to analytics hub (scoped to their `school_id`).
2. **Teacher KPI panel** runs `withCount(['assignments', 'submissions' => fn($q) => $q->whereNull('graded_at')])` per teacher.
3. **Grading turnaround** computed as `AVG(TIMESTAMPDIFF(MINUTE, created_at, graded_at))` per teacher.
4. **Student performance table** runs per-student `AVG(grade)` and submission/assignment ratio.
5. **Grade timeline chart** pulls `(submissions.graded_at, submissions.grade)` per student, rendered via Chart.js line chart.

---

## 5. UI/UX Design System

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

## 6. File Storage Strategy

### Storage Paths
| Content Type | Path | Visibility |
|---|---|---|
| Assignment attachments | `tenants/{school_id}/assignments/{filename}` | Private |
| Student submissions | `tenants/{school_id}/submissions/{filename}` | **Private** |
| Material files | `tenants/{school_id}/materials/{filename}` | Private |
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

## 7. Step-by-Step Implementation Checklist

Execute sequentially. Do not advance until the current step is verified.

- [x] **STEP 1** — ~~Generate all migrations, models, and Eloquent relationships per Section 1. Confirm foreign key cascades and SoftDeletes are present.~~ ✅ Complete
- [ ] **STEP 2** — Implement Laravel Breeze authentication. Build `RoleMiddleware` and register route groups per Section 2.
- [ ] **STEP 3** — Create database seeders: 1 default School, 1 Super Admin, 1 School Admin, 1 Teacher, 3 Students. Verify all role-restricted routes respond correctly.
- [ ] **STEP 4** — Build the master Blade layout with Tailwind CSS. Implement light/dark mode toggle with session persistence.
- [ ] **STEP 5** — Implement Epic 1: class code input, signed invite link, QR code generation.
- [ ] **STEP 6** — Build Teacher Dashboard: topic management, material uploads, assignment creation (Epic 2 & 3).
- [ ] **STEP 7** — Build Student Dashboard: classroom stream, submission portal, grade/comment view (Epic 3).
- [ ] **STEP 8** — Implement Epic 4: queued `NotifyClassroomStudents` job and `GradePublished` event/listener with database + mail notifications.
- [ ] **STEP 9** — Build School Admin Analytics Hub (Epic 5) using Eloquent aggregations (`AVG`, `COUNT`, `withCount`).
