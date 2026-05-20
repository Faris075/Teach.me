CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "schools"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "domain" varchar,
  "status" varchar check("status" in('active', 'suspended')) not null default 'active',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "role" varchar check("role" in('super_admin', 'school_admin', 'teacher', 'student')) not null default 'student',
  "school_id" integer,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("school_id") references "schools"("id") on delete set null
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_expiration_index" on "cache"("expiration");
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE INDEX "cache_locks_expiration_index" on "cache_locks"("expiration");
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "classrooms"(
  "id" integer primary key autoincrement not null,
  "school_id" integer not null,
  "teacher_id" integer not null,
  "name" varchar not null,
  "class_code" varchar not null,
  "status" varchar check("status" in('active', 'archived')) not null default 'active',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("school_id") references "schools"("id") on delete cascade,
  foreign key("teacher_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "classrooms_class_code_unique" on "classrooms"(
  "class_code"
);
CREATE TABLE IF NOT EXISTS "classroom_student"(
  "classroom_id" integer not null,
  "student_id" integer not null,
  "joined_at" datetime not null default CURRENT_TIMESTAMP,
  foreign key("classroom_id") references "classrooms"("id") on delete cascade,
  foreign key("student_id") references "users"("id") on delete cascade,
  primary key("classroom_id", "student_id")
);
CREATE TABLE IF NOT EXISTS "topics"(
  "id" integer primary key autoincrement not null,
  "classroom_id" integer not null,
  "title" varchar not null,
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("classroom_id") references "classrooms"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "materials"(
  "id" integer primary key autoincrement not null,
  "topic_id" integer not null,
  "title" varchar not null,
  "content" text,
  "file_path" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("topic_id") references "topics"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "assignments"(
  "id" integer primary key autoincrement not null,
  "classroom_id" integer not null,
  "topic_id" integer,
  "title" varchar not null,
  "description" text not null,
  "file_path" varchar,
  "due_date" datetime not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("classroom_id") references "classrooms"("id") on delete cascade,
  foreign key("topic_id") references "topics"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "submissions"(
  "id" integer primary key autoincrement not null,
  "assignment_id" integer not null,
  "student_id" integer not null,
  "file_path" varchar,
  "submitted_text" text,
  "grade" numeric,
  "teacher_comment" text,
  "status" varchar check("status" in('pending', 'graded', 'late')) not null default 'pending',
  "graded_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("assignment_id") references "assignments"("id") on delete cascade,
  foreign key("student_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "submissions_assignment_id_student_id_unique" on "submissions"(
  "assignment_id",
  "student_id"
);

INSERT INTO migrations VALUES(1,'0000_01_01_000000_create_schools_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(4,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(5,'2026_01_01_000001_create_classrooms_table',1);
INSERT INTO migrations VALUES(6,'2026_01_01_000002_create_topics_and_materials_table',1);
INSERT INTO migrations VALUES(7,'2026_01_01_000003_create_assignments_and_submissions_table',1);
