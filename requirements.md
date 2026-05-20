# Functional Requirements Specification (FRS)
### Project: Teach.me — LMS Classroom Platform (MVP → Multi-School SaaS)

---

## Architectural Constraints

| Concern | Decision |
|---|---|
| Backend | Laravel 11+ (PHP 8.3+) |
| Frontend | Blade + Tailwind CSS / Livewire (Monolith) |
| Database | MySQL 8.0+ or PostgreSQL |
| Coding Standard | PSR-12, clean MVC, Service-Repository pattern for business logic isolation |
| Dark Mode | `tailwind.config.js` → `darkMode: 'class'` |
| File Storage | Laravel `Storage` facade → `storage/app/public` (local MVP); swap via `.env` for S3/cloud in Phase 2 |
| Auth | Laravel Breeze or Jetstream (session-based) |
| Notifications | Laravel queued database + mail notifications (`php artisan queue:work`) |

---

## 1. Database Schema & Data Models

> All tables must use `BigInt` primary keys, `timestamps()`, and `SoftDeletes` where specified.
> Foreign keys must cascade on delete unless noted.

### `schools`
| Column | Type | Notes |
|---|---|---|
| `id` | BigInt PK | |
| `name` | String | |
| `domain` | String, Nullable | For future subdomain routing |
| `status` | Enum: `active`, `suspended` | |
| `timestamps` | | |

> Phase 1: Seed a single default school record. All users point to it.

---

### `users`
| Column | Type | Notes |
|---|---|---|
| `id` | BigInt PK | |
| `name` | String | |
| `email` | String, Unique | |
| `password` | String | Hashed via `bcrypt` |
| `role` | Enum: `super_admin`, `school_admin`, `teacher`, `student` | |
| `school_id` | BigInt, Nullable, FK → `schools.id` | Null = Super Admin |
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
| `class_code` | String, Unique | 6–8 alphanumeric characters, auto-generated |
| `status` | Enum: `active`, `archived` | |
| `timestamps` | | |

---

### `classroom_student` *(Pivot)*
| Column | Type | Notes |
|---|---|---|
| `classroom_id` | BigInt, FK → `classrooms.id` | Cascades on delete |
| `student_id` | BigInt, FK → `users.id` | Cascades on delete |
| `joined_at` | Timestamp | Set on insert |

---

### `topics`
| Column | Type | Notes |
|---|---|---|
| `id` | BigInt PK | |
| `classroom_id` | BigInt, FK → `classrooms.id` | |
| `title` | String | e.g., "Directed Writing" |
| `sort_order` | Integer | Teacher-controlled ordering |
| `timestamps` | | |

---

### `materials`
| Column | Type | Notes |
|---|---|---|
| `id` | BigInt PK | |
| `topic_id` | BigInt, FK → `topics.id` | |
| `title` | String | |
| `content` | Text, Nullable | Rich-text or plain body |
| `file_path` | String, Nullable | `storage/app/public` relative path |
| `timestamps` | | |

---

### `assignments`
| Column | Type | Notes |
|---|---|---|
| `id` | BigInt PK | |
| `classroom_id` | BigInt, FK → `classrooms.id` | |
| `topic_id` | BigInt, Nullable, FK → `topics.id` | Optional grouping |
| `title` | String | |
| `description` | Text | |
| `file_path` | String, Nullable | Attached resource (PDF/DOCX) |
| `due_date` | DateTime | |
| `timestamps` | | |

---

### `submissions`
| Column | Type | Notes |
|---|---|---|
| `id` | BigInt PK | |
| `assignment_id` | BigInt, FK → `assignments.id` | |
| `student_id` | BigInt, FK → `users.id` | |
| `file_path` | String, Nullable | Student-uploaded file |
| `submitted_text` | Text, Nullable | Text-response alternative |
| `grade` | Decimal(5,2), Nullable | Set on grading |
| `teacher_comment` | Text, Nullable | Qualitative feedback |
| `status` | Enum: `pending`, `graded`, `late` | Default: `pending` |
| `graded_at` | Timestamp, Nullable | Set when grade saved |
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
- Validate against `classrooms` where `status = 'active'`.
- On success, insert a record into `classroom_student` with `joined_at = now()`.
- Prevent duplicate enrolments (unique constraint on `[classroom_id, student_id]`).

**Req 1.2 — Invite Link**
- Generate a signed URL: `/join/{class_code}` using Laravel's `URL::signedRoute()`.
- If the visitor is unauthenticated, redirect to registration then complete the join action.
- If already enrolled, redirect to the classroom stream with a flash notice.

**Req 1.3 — QR Code**
- The classroom settings page renders a scannable QR code pointing to the signed invite URL.
- Use the `simplesoftwareio/simple-qrcode` package.
- Provide a "Download PNG" button for the teacher to share offline.

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
- Multi-part form: Title, Description (rich text), optional File Upload (PDF/DOCX only, max 10MB), Due Date via **Flatpickr** datetime picker.
- File stored via `Storage::disk('public')->put(...)`. Path saved to `assignments.file_path`.
- Dispatches a queued job on save (see Epic 4, Req 4.2).

**Req 3.2 — Student Submission Portal**
- Per-assignment status indicator: `Missing` (overdue, no submission) · `Pending` · `Graded`.
- Student may upload one file OR enter a text response. Both fields cannot be empty on submit.
- Late submissions (past `due_date`) set `submissions.status = 'late'` automatically.

**Req 3.3 — Grading Interface (Teacher)**
- Split-screen layout:
  - **Left pane**: Renders submitted file (PDF iframe or download link) or submitted text.
  - **Right pane**: Numeric grade input (0–100), feedback textarea, Save button.
- Saving a non-null grade sets `graded_at = now()` and triggers the grade notification event (see Epic 4, Req 4.1).
- Paginated list of all student submissions for a given assignment above the split-screen.

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
- Table listing all `teacher` role users in the admin's `school_id`.
- Per teacher: total assignments posted, count of ungraded submissions, list of all written `teacher_comment` entries for QA review.
- Queries must use Eloquent with `withCount` and `loadMissing` to avoid N+1.

**Req 5.2 — Student Overview (School Admin)**
- Tabular view of all students in the school:
  - Running GPA: `AVG(submissions.grade)` per student.
  - Completion ratio: `COUNT(submitted) / COUNT(assigned)`.
  - Grade-over-time chart (use Chart.js or a Blade-compatible charting library).
- Filterable by classroom.

---

## 4. UI/UX Design System

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
- Section gaps: `gap-6` on grid layouts
- Designed for legibility on small touch screens (min tap target: 44×44px)

### Dark Mode Toggle
- Toggled by adding/removing the `dark` class on `<html>`.
- Persist preference in the authenticated user's session or a `settings` column on the `users` table.
- A sun/moon icon button in the top navbar handles the toggle.

---

## 5. File Storage Strategy

```php
// Writing
Storage::disk('public')->put('assignments/' . $filename, $fileContents);

// Reading
asset(Storage::url($assignment->file_path));
```

- All `file_path` columns store the **relative path** returned by `Storage::put()`.
- Running `php artisan storage:link` exposes files under `public/storage/`.
- Switching to S3 in Phase 2 requires only: `FILESYSTEM_DISK=s3` in `.env`. No application code changes.

---

## 6. Step-by-Step Implementation Checklist

Execute sequentially. Do not advance until the current step is verified.

- [ ] **STEP 1** — Generate all migrations, models, and Eloquent relationships per Section 1. Confirm foreign key cascades and SoftDeletes are present.
- [ ] **STEP 2** — Implement Laravel Breeze authentication. Build `RoleMiddleware` and register route groups per Section 2.
- [ ] **STEP 3** — Create database seeders: 1 default School, 1 Super Admin, 1 School Admin, 1 Teacher, 3 Students. Verify all role-restricted routes respond correctly.
- [ ] **STEP 4** — Build the master Blade layout with Tailwind CSS. Implement light/dark mode toggle with session persistence.
- [ ] **STEP 5** — Implement Epic 1: class code input, signed invite link, QR code generation.
- [ ] **STEP 6** — Build Teacher Dashboard: topic management, material uploads, assignment creation (Epic 2 & 3).
- [ ] **STEP 7** — Build Student Dashboard: classroom stream, submission portal, grade/comment view (Epic 3).
- [ ] **STEP 8** — Implement Epic 4: queued `NotifyClassroomStudents` job and `GradePublished` event/listener with database + mail notifications.
- [ ] **STEP 9** — Build School Admin Analytics Hub (Epic 5) using Eloquent aggregations (`AVG`, `COUNT`, `withCount`).
