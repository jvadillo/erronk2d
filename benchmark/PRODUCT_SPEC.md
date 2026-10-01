# Erronk2D — Functional Product Specification

Specification date: 2026-10-01. Product language: Spanish. Audience: a developer independently rebuilding the product without access to its source.

## 1. Purpose and scope

Erronk2D supports collaborative project learning in vocational education. Teachers organize academic groups, enroll students, prepare reusable rubrics, create multidisciplinary challenges, form teams, assess work, record individual point distributions, enter exams and defense adjustments, and publish individual results. Students complete self and peer assessments and consult their own published results. Administrators manage institutional accounts and catalogs.

The central workspace is a challenge evaluation matrix. A teacher should be able to assess many students, identify missing work, and explain a result without repeatedly leaving that workspace.

This document specifies product behavior, durable business information, browser interactions, and externally observable contracts. Technology, application architecture, deployment topology, and internal code organization are unrestricted. Examples use fictitious data. The catalog appendix is a fixed product data set, not a claim that its curricula remain current indefinitely.

The workspace has four challenge tabs, dedicated rubric editor pages and uniformly sized assessment columns. Permissions derive from roles and assignments, reporting periods belong to individual groups, and each student has one exam and one defense per challenge/module.

### Reading guide

| Area | Sections |
|---|---|
| Domain, access, accounts | 2–5 |
| Academic organization and rubrics | 6–8 |
| Challenges, teams, assessments and arithmetic | 9–14 |
| Publication, evidence and student experience | 15–17 |
| Reports, imports and interaction requirements | 18–21 |
| Browser/service contracts and administration utilities | 22–24 |
| Acceptance examples, boundaries and catalog | 25–27 and Appendix A |

## 2. Vocabulary and durable business information

“Curso académico” means an academic year, for example 2026–2027. “Curso / nivel” means the level within a vocational program, for example 1.º or 2.º. These are different concepts.

“Grupo” is an annual classroom cohort. “Ciclo” is a vocational program. “Módulo” is a taught subject. “Evaluación” is an ordered reporting period within a group. “Reto” is a collaborative project assigned to one group and one period. “Transversales” are personal/teamwork competencies. “Defensa” is an individual assessment that adds or subtracts points from the student's challenge result.

Preserve at least the following information and relationships. These are logical records, not a prescribed database schema.

| Business record | Required information and relationships |
|---|---|
| Account | Stable identity, name, unique email, credential, role (`admin`, `teacher`, `student`), active flag, optional linked Google identity, email verification state, last selected academic year, creation/update times |
| Academic year | Unique name, open/closed state, creation order |
| Cycle | Unique name and code; reusable across years |
| Catalog module | Name, code, level 1–4, optional cycle; code unique within a cycle and level |
| Group | Academic year, name unique in that year, cycle, fixed level, retained cycle name, owner, teaching members |
| Group module | Module identity, retained name/code, active/retired state, responsible teachers specific to that group |
| Reporting period | Group, name, position; ordered uniquely within the group |
| Enrollment | Student and group, active/ended state, withdrawal time; one enrollment relationship per student/group, reactivatable |
| Rubric template | Owner, name, kind (`team` or `transversal`), optional paired cycle/level, ordered criteria, teachers granted use/copy access |
| Criterion | Stable key, name, optional description, positive weight, optional module for technical criteria, ordered scored levels with descriptions |
| Challenge | Name, description, teacher notes, group/period, dates, status, relative period weight, allocation/clamping switches, two sets of percentage weights, participating modules, defense switches, fixed participant list, independent copies of both rubrics and academic labels, revision |
| Team | Challenge, name unique within that challenge, 2–5 participating students |
| Team membership | Challenge/team/student and optional allocated points; at most one team per student/challenge |
| Assessment selection | Challenge, assessment kind, subject team/student, criterion, chosen level, evaluator scope where applicable, last author/time |
| Module grade | One student/module/challenge combination: optional exam, optional defense, defense author/date/notes, explicit not-enrolled flag, last modifying author/time |
| Evidence note | Challenge, student, teacher author, plain-text note, creation time |
| Publication | Challenge, monotonically increasing version, publisher/time, immutable complete evaluation snapshot |
| Audit event | Actor, operation, time, optional challenge/reason, relevant before/after evidence |
| Google access request | Provider identity, name, email, pending/approved/rejected state, reviewing administrator, resulting account if approved |

Accounts, cycles, catalog modules, and rubric templates span academic years. Groups, memberships, responsibilities, enrollments, periods, challenges and assessment activity belong to a particular year. Creating another year does not copy groups, students' enrollments, challenges or grades into it.

Historical identities must remain referentially valid. Withdrawing students, retiring modules, removing teaching access, and editing templates must preserve past grades, authorship and publications. Ordinary screens do not offer destructive deletion of accounts, years, cycles, modules, groups, challenges, publications or evidence notes. The explicitly described team/criterion/period editing and operator reset are separate exceptions.

## 3. Roles and authorization

Authorization depends on role, group membership/ownership, module responsibility, selected year and record state. A teacher's rights are not configured through an individual permission editor. Supplying a permission list or role in an unrelated request must not grant additional rights.

| Capability | Administrator | Group owner | Other teacher in group | Student |
|---|---|---|---|---|
| Create/rename/close/reopen years | Yes | No | No | No |
| Maintain cycles and global module catalog | Yes | No | No | No |
| Create/edit teacher accounts | Yes | No | No | No |
| Edit existing student accounts/activation | Yes | No | No | No |
| Review Google requests | Yes | No | No | No |
| Create group in any open year | Yes | Any active teacher | Any active teacher | No |
| View group/challenges/report | All groups in selected year | Own groups | Member groups | Own participating challenges only |
| Rename group or replace teaching membership | Yes | Yes | No | No |
| Transfer group ownership | Yes | No | No | No |
| Set module responsibilities | Yes | Yes | No | No |
| Add/retire group modules; configure periods | Yes | Yes | Yes | No |
| Create students/enroll/withdraw students | Yes | Yes | Yes | No |
| Create/configure challenges, manage teams | Yes | Yes | Yes | No |
| Grade technical GENERAL criteria | Yes | Yes | Yes | No |
| Grade module-specific criteria, exams, defenses, not-enrolled flags | Yes | Only if responsible for module | Only if responsible for module | No |
| Record shared teacher transversal assessment | Yes | Yes | Yes | No |
| Record allocation | Yes | Yes | Yes | No |
| Edit either challenge rubric | Yes | Yes, regardless of module responsibility | Yes, regardless of module responsibility | No |
| Publish/reopen a challenge | Yes | Yes | Yes | No |
| Read/add teacher evidence | Yes | Yes | Yes | No |
| Self/peer assessment | No impersonation | No impersonation | No impersonation | Own self assessment and other current teammates |
| View published student results | All allowed group rows | All allowed group rows | All allowed group rows | Own result only |
| Read publication/audit history | Yes | Yes | Yes | No |

All academic edits in this table require an open selected year. Finished/published challenges impose additional restrictions. Administration does not bypass either closure.

An active teacher can see any open year in the year selector but only their own/member groups in that year. A closed year is available to a teacher only while they own or belong to a group in it. Removing a teacher from a group removes their module responsibilities and access immediately; historical authorship remains. Responsibility for the same catalog module in another group grants no access here.

Students can select years and access groups where they have current or ended enrollment. They must additionally be in a challenge's fixed participant list to open it. Merely being enrolled does not grant access to every challenge in the group. Ended enrollment can preserve historical challenge access; account deactivation blocks all access.

Enforce these rules on direct URLs, forged identifiers, import/export requests, lookup, previews and all write operations, not just through hidden controls. Out-of-scope groups/challenges are normally reported as not found. Disallowed role/function combinations are forbidden.

## 4. Academic context and closed years

The selected academic year appears persistently in the navigation, including on mobile and with the sidebar collapsed. Closed choices are labeled “Cerrado”.

Selection order is:

1. The current user's valid session choice.
2. Their persisted last-used year when there is no applicable session choice.
3. The newest-created open year available to that user.
4. The newest-created available year, even if closed.
5. No year if none is available.

Year ordering is newest creation time first, with identity descending as a tie-breaker. Remember the resolved choice across login sessions. Never inherit a previous user's session choice. Switching years returns to the challenge dashboard and reloads context.

Every contextual write must carry the year the form was opened under. If it differs from the server's current selected year, or is missing, reject with conflict (409); do not silently redirect the write into another year. If there is no available year or it is closed, reject academic writes with forbidden (403). Concurrent closure and writing must not leave partial academic changes.

A closed year permits consultation and export. It prevents group changes, enrollment changes, challenge creation/configuration/status/reopening, grading, allocation, evidence creation and challenge-rubric edits. An administrator must reopen the year first. Changing a closed year's name also requires reopening.

Global account/catalog/year management and the template library are independent of academic closure. For example, an administrator can create a student without enrolling them, or approve a teacher, with no selected year. Enrolling that student requires a writable year. Exact-email lookup is a read operation and does not require an open year.

When no year is available, show role-specific guidance: administration should configure a first year and catalogs; teachers wait for an available year; students wait for enrollment. Login and permitted global operations still work.

## 5. Authentication, recovery and Google registration

### 5.1 Local login and logout

Provide email/password login. Email must be valid and at most 255 characters; password is required and at most 200 characters. Only active accounts authenticate. Incorrect credentials and inactive-account local login receive the same generic message: “El correo o la contraseña no son correctos.”

Five failed local attempts for a lowercase email/IP pair trigger a 60-second temporary limit. A successful login clears that failure counter. The login endpoint also allows at most 10 requests per minute. Authenticated navigation is session based; renew session identity on login. Return local login to the originally requested protected location or the dashboard. There is no public password-based account signup and no visible remember-me checkbox.

Logout invalidates the session and returns to `/login`. Deactivated accounts cannot continue using an existing session. Session inactivity defaults to 120 minutes and may be configured. Authenticated visitors to guest login/recovery/Google screens are redirected to their home area.

### 5.2 Password recovery

“¿Has olvidado tu contraseña?” opens an email form. For both existing and nonexistent accounts return: “Si la cuenta existe, recibirás un enlace para restablecer la contraseña.” Do not expose account existence through ordinary success feedback.

For an existing account, deliver an Erronk2D email containing a single-use reset link tied to its email and expiring after 60 minutes. New reset links for an account are limited to one per 60 seconds; the recovery endpoint is limited to three requests per minute. A failed mail delivery returns a safe retry/contact-administration message, without provider details, and does not change the password.

The reset page has email, new password, and repeated password fields. Require a valid token/email pair, a matching confirmation, and 10–200 characters. Reject expired, invalid and already-used tokens. Success changes the credential, invalidates the reset token, returns to login, and displays “Contraseña actualizada.” Reset does not activate a disabled account or automatically log the user in. The reset submit endpoint is limited to five requests per minute.

The email subject is “Restablece tu contraseña de Erronk2D”; greet the recipient by name, explain the request, provide “Establecer contraseña”, state the expiry, explain that an unrequested email can be ignored, and sign “El equipo de Erronk2D”. Use the configured public HTTPS origin and sender identity. Imported/Google-created accounts can use this flow to establish a local password. There is no automatic welcome, invitation, publication or approval email.

### 5.3 Optional Google access

Display “Continuar con Google” only when Google access is enabled and its client credentials and callback address are configured. Otherwise direct Google entry/callback requests return safely to login with an unavailable message.

Start an account-selection authorization flow for `openid email profile`. The callback is fixed by configuration, not a user-provided redirect. Bind it to the initiating session with a one-use state and PKCE S256; expire it after 10 minutes. Reject cancellation, missing/wrong/expired/replayed state, missing code, malformed identity, missing provider subject/email, or an email not explicitly verified by Google. Connection/provider failures return safe Spanish feedback. Never expose provider secrets, tokens, raw errors or the PKCE verifier.

After obtaining a verified identity:

1. If its provider subject is already linked, log into that active account. The provider's current name/email does not overwrite the local profile or role. Refuse inactive linked accounts.
2. Otherwise find a local account by case-insensitive exact email. If more than one historical account matches, require administrative resolution. If the matching account is inactive or linked to another identity, refuse linking.
3. For an eligible existing account, show “Vincula tu cuenta”, its read-only email and a local-password confirmation. Do not authenticate or link just because the emails match. The pending link lasts 10 minutes. Recheck activity, email and linkage when confirming. Permit at most five password attempts per account/IP per 60 seconds, plus a 10/minute endpoint limit. Success links the identity, records an audit event, logs in and returns to the dashboard. Cancellation clears the pending flow. Password recovery remains accessible.
4. If no account exists, create a pending access request, not an authenticated account. Normalize email to lowercase; use Google's trimmed name or email fallback, limited to 150 characters. Repeated login with the same pending identity creates no duplicate. A rejected/reviewed request is not automatically reopened. Conflicting identity/email matches require administrative review.

Google start is limited to 10 requests/minute, callback to 20/minute. Provider-supplied or browser-supplied roles/permissions never grant institutional privileges. There is no automatic domain-based approval.

### 5.4 Administrative request review

Only administrators see pending requests, oldest first, with name/email and “Revisar solicitud”. They can approve as student or teacher, or reject. A student requires a group in the open selected year. Teacher approval does not require or assign a group. Administrator is not an approval role option.

Approval creates an active account linked to the verified identity, records email verification and an unguessable initial credential, and creates the required student enrollment. Reject approval if the email or Google identity already belongs to an account; use the existing-account linking flow instead. Rejection creates no account. Store reviewer, decision and resulting account, and audit the decision. A reviewed request cannot be processed twice. Approval/rejection must be atomic. Newly approved users enter on their next Google login.

## 6. Organization: years, catalogs and groups

### 6.1 Organization navigation

Administrators have pages for “Cursos académicos”, “Ciclos”, “Módulos”, “Grupos”, “Profesores”, “Estudiantes”, “Rúbricas”, and “Solicitudes”, in that navigation order. Teachers have “Grupos”, “Estudiantes”, and “Rúbricas”. Students have no Organization menu.

Each section has its own address, title, active navigation state and browser history. `/setup` redirects administrators to courses and teachers to groups. Unknown sections return 404. Some headings use singular “Profesor” and “Estudiante”. Form validation returns to the current section with entered values and errors. Opening another create/edit form must not carry unrelated fields from the previous form.

### 6.2 Years and cycles

Administrators create and rename academic years with a required unique name of at most 100 characters. New years are open. Closing/reopening is explicit; multiple years may be open simultaneously. Renaming a year does not recreate or rename group periods.

Cycles have required unique names (150 characters) and codes (30 characters). List code, name and edit/view-modules actions. Sort alphabetically by name with a reversible direction using Spanish accent-insensitive/numeric collation.

### 6.3 Catalog modules

A module has a required name (150), code (30), level 1–4 and optional cycle. Allow unassigned modules (“Sin ciclo asignado”). The standard edit form changes name/code while preserving cycle and level. Duplicate codes within the same cycle/level are rejected. A cycle can have separate module entries with the same code at different levels.

List modules with code, name, cycle, level and edit action. Filter by cycle, all cycles or unassigned modules, and by level or all levels. Toggle ascending/descending name order. Provide a no-match state.

From a cycle's “Ver módulos” dialog:

- List its modules with name, code and level.
- Removing a module unassigns its cycle; it preserves the module identity, existing group associations and all assessment history.
- Search available unassigned modules by name, ignoring case/accents. Do not show results until a nonblank query is supplied.
- Select destination level and add an unassigned module. Refuse an already-associated module, a conflicting code at that level, or an invalid level.
- Removing a module from an unrelated cycle returns not found.

Catalog changes affect future selections. Existing groups keep their saved module labels. Newly created/imported catalog modules do not automatically appear in existing groups.

### 6.4 Group creation and ownership

Any active teacher may create a group in an open selected year. Require name (100 characters, unique within year), existing cycle, level 1–4, a teaching-member list, and optionally period count 1–12 (default 3). The UI initially selects level 2 and the first available cycle. Creating a group without a configured cycle is unavailable.

The creator is the owner unless an administrator explicitly chooses another active teacher/administrator. Always include the owner among teaching members. Members must be distinct active teacher/administrator accounts; students cannot be teaching members. Ownership transfer is administrator-only.

Creation includes all catalog modules matching cycle and level, retaining their then-current names/codes and the cycle name. Create periods named `1.ª Evaluación`, `2.ª Evaluación`, etc. No module responsibility is inferred merely from ownership or membership.

Only the owner or an administrator may change group name and teaching-member list. Group year, cycle and level remain fixed; create a different group to change them. Removing a member revokes their responsibilities. Transferring ownership always includes the new owner in membership; the old owner remains only if included in the submitted member list.

### 6.5 Group module and responsibility management

Any group teacher can add an eligible catalog module or retire an attached module in an open year. Eligible modules have the same current cycle and level. Retirement hides a module from new-challenge choices but retains its grade history, existing challenge membership, saved labels and responsibilities. Reactivation restores the existing relationship and saved labels. Display retired module codes on the group card. A module newly added to an existing group requires explicit selection.

The group owner/administrator assigns zero or more distinct active teaching members as responsible for each active group module. Responsibility is specific to the annual group. Multiple responsible teachers share the same grades. Having no responsible teacher is allowed in group setup, but blocks completeness of any challenge using that module.

A module unassigned or moved in the global catalog retains old group history. Group module changes validate its current cycle/level; an incompatible catalog move can make that old association unavailable for retirement/reactivation through ordinary controls.

### 6.6 Group periods

Every group owns its ordered periods independently. Any group teacher can configure 1–12 periods in an open year. Require distinct nonblank names, at most 100 characters, and distinct existing identities belonging to that group. Submitted order determines positions.

Periods without challenges can be renamed or removed. A period with any challenge keeps its identity and name and cannot be deleted; it can be repositioned. New periods can be added. A failure must preserve all periods and challenge links. The UI locks names and removal buttons for used periods, prevents removal of the last period, and limits additions to 12.

Group cards show cycle/level, student/module/period counts, period labels, teaching members, expandable rosters, active/retired modules and their responsible teachers. “Editar grupo” and “Responsables” are owner/admin actions; enrollment, module selection and period configuration are available to ordinary group members.

## 7. Accounts and enrollments

Administrators list and edit all teacher or student accounts globally. A teacher's student list is restricted to students with current or ended enrollments in their visible groups of the selected year. Display name, email, activity state and, for students, enrollment groups including “histórico” for ended enrollments. An administrator can see “Sin matrícula en este curso”. Names are sorted alphabetically.

Account creation requires name (150), valid email (255), initial password (10–200), and an activity flag. Normalize email to lowercase and enforce uniqueness case-insensitively across all roles. An existing account should be enrolled by email instead of duplicated. A teacher creating a student must choose a visible group and always creates an active student, regardless of a submitted inactive flag. An administrator may create a student without a group. Only administrators create teachers or edit existing accounts; the student/teacher editing operation cannot change the account's role or edit an administrator through those forms.

On edit, a blank password keeps the current password; a supplied replacement must meet the same length limits. Changing email to a different address removes its Google link and email-verification state. Case-only normalization does not count as a different address. Account edits preserve other accounts and enrollments.

“Matricular por correo” takes a visible group and exact valid student email. A lookup returns only that active student's identity/name/email, or no match. It is case-insensitive, requires a group the caller can access, and is limited to 20 requests/minute. There is no broad search over other groups' students.

Enrolling adds or reactivates that one group relationship. It leaves other enrollments, credentials, names and prior grades intact. A student can be enrolled in several groups in the same or different years. Enrollment defaults to participating in all group modules for future challenges; “No matriculado” is a separate challenge-level assessment exception.

Withdrawal marks an existing enrollment ended and removes the student from the current roster. It preserves the account, other enrollments, old challenge participants/teams/results, and report history. An inactive student can be withdrawn but cannot be enrolled/reactivated until their account is active. Withdrawing a nonexistent relationship is not found. Re-enrollment reuses the relationship rather than accumulating duplicate active enrollments.

## 8. Rubric template library and editor

### 8.1 Availability and reuse

Teachers see their own templates and those explicitly shared with them. Administrators see all templates. A template has kind “Valoración del reto” (`team`) or “Competencias transversales” (`transversal`). It may be general, or scoped to an existing cycle and level; choose both cycle and level together or neither.

Owners and administrators edit originals. Recipients can use and duplicate shared templates but cannot edit the original. Duplication produces an independent copy owned by the caller, with ` (copia)` appended to the name and no inherited sharing recipients. Available templates can be reused across challenges and years. Every challenge receives its own rubric contents; later library changes never alter that copy, assessments or publications.

Library cards show kind, name, own/shared designation, criterion names and count, and authorized edit/copy actions. Sharing targets are active teacher/administrator accounts.

### 8.2 Template validation

| Field | Rule |
|---|---|
| Name | Required, at most 150 characters |
| Kind | `team` or `transversal` |
| Cycle/level | Both absent or existing cycle plus integer level 1–4 |
| Criteria | Ordered list, 1–40 |
| Criterion key | Required, unique in rubric, at most 80; letters, digits, underscore or hyphen |
| Criterion name | Required, at most 150 |
| Criterion description | Optional, at most 2,000 |
| Criterion weight | Positive, at most 100, at most two decimals; exact total 100% |
| Levels per criterion | Ordered list, 2–20 |
| Level score | 0–10 inclusive, at most two decimals |
| Level description | Required, at most 2,000 |
| Team criterion module | Absent/GENERAL or a module belonging to the template's cycle and level |
| Transversal criterion module | Always GENERAL; remove submitted module associations |

All newly saved templates must have the same number of levels and the same score at each column across every criterion. Descriptions vary by criterion. Scores need not be increasing or unique. Criterion names need not be unique; keys do. Reject an inexact percentage sum, including 99.99 or 100.01.

### 8.3 Editor interaction

The library editor is a dedicated page. Present metadata above a horizontally scrollable criterion-by-level table. A row contains module choice for team rubrics, criterion name and optional description, weight, and each level's description. Scores are edited once in column headings. Start a new rubric with one criterion at 100% and four levels scored 4, 6, 8, 10.

Support adding/removing criteria, moving a criterion up/down, adding/removing level columns, and equal distribution of criterion weights. Keep at least one criterion and two levels. Added criteria initially need a weight; added levels default to 10 and blank descriptions. Confirm removal of populated criteria and level columns. Show total percentage and exact missing/excess amount. Equal distribution assigns hundredths to total exactly 100; three equal criteria are 33.34, 33.33, 33.33.

Accept comma decimal entry where text-based score controls are used and normalize it for saving. Changing template kind or cycle/level clears incompatible module selections. Sharing is an expandable area. Provide save/cancel at convenient positions. Validation preserves all entered values. Warn before leaving/reloading with unsaved changes; drafts can be restored when navigating editor history.

Historical library templates with relative weights are presented as percentages totaling 100, allocating rounding residuals by largest fractional remainder, with original row order breaking ties. Historical templates with unequal score columns require explicit “Unificar niveles de la plantilla” before saving as a modern template. Existing challenge copies remain unchanged.

## 9. Dashboard and challenge creation

The dashboard shows permitted challenges in the selected year, newest-created first. Students see only challenges they participate in. Each card includes name, shortened description, status, module codes, group, period, year and team count. Status filters are all, “En curso”, “En evaluación”, and “Publicados”. All includes drafts and finished challenges. Search matches name, group, year and period case-insensitively. Provide separate no-challenges and no-search-matches states, with Organization guidance for teachers and assignment guidance for students.

Authorized staff create a challenge through “Nuevo reto”. Required inputs are name (200), group, period in that group, at least one distinct active group module, one accessible team rubric, one accessible transversal rubric, positive relative weight, and allocation enabled/disabled. Description is optional up to 10,000 characters. Weight defaults to 1; allocation defaults on.

Changing group clears period and selected modules. Module selection determines rubric compatibility. A scoped rubric must match the group's cycle/level; all module-specific team criteria must reference participating modules. Disable incompatible team rubrics and describe required modules. If changing modules invalidates the selected team rubric, clear it. Show only compatible transversal rubrics. Server validation independently rejects foreign periods/modules, retired group modules, wrong rubric kinds, inaccessible rubrics and incompatible scopes, with no partial challenge creation.

On successful creation:

- Fix group, period and participating module identities for the challenge.
- Copy all currently active enrolled students whose accounts are active into its participant list, even if the list is empty.
- Copy both rubric names and criterion/level contents.
- Retain group, cycle, year, period and module labels as they were at creation.
- Start in `draft`, revision 1, with clamping enabled and defenses enabled for every selected module.
- Set module-component percentages to transversal 30, challenge 40, exam 30.
- Set transversal percentages to self 10, peer 60, teacher 30.
- Leave teams, selections, allocations, grades, dates and teacher notes empty.
- Record creation and open the challenge workspace.

Existing participant/module selections do not track later enrollment/catalog changes. There is no normal form to move an existing challenge to another group/period or replace its module list.

## 10. Challenge workspace and settings

Staff see a header with name/description, year/group/period context, back navigation and authorized publication/reopening actions. Show counts of participants, teams, modules, participating teachers, and completed student evaluations. Teacher count/list is the distinct set of displayed active module-responsible teacher names, not all group members. Clicking it opens “Profesorado participante”.

The four tabs, in order, are:

1. “Estudiantes y Equipos” (`tab=teams`).
2. “Evaluación” (`tab=evaluation`), the default for absent/invalid tab values.
3. “Evidencias” (`tab=evidence`).
4. “Configuración” (`tab=settings`).

Tabs have addresses that survive reload and browser back/forward. Switching tabs preserves unsaved team, settings and per-student evidence drafts and the evaluation view. It must not refetch older data over a just-saved grade. Block tab switching while a grade/evidence save is underway. Arrow Left/Right wrap between tabs; Home/End select first/last, updating focus and selected state. On narrower screens tabs form two columns. Ordinary unsaved challenge-tab drafts are not guaranteed across full reload.

Settings expose name, description, optional start/end dates, relative period weight, individual allocation switch, clamping switch, both weight groups, per-module defense switches, teacher observations and correction reason. Date values use `YYYY-MM-DD`; end must be on or after start when supplied. Dates describe the challenge; they do not automatically change its state or close assessment access.

Name is required (200); description max 10,000; observations max 5,000. Relative weight must be positive and use ordinary decimal notation with up to four integer and four fractional digits: effectively 0.0001–9999.9999. Although its numeric ceiling is 10,000, a five-digit literal is outside the accepted format. Relative challenge weights do not need to total 100.

Each percentage group must contain exactly its three named values, each 0–100 with up to four fractional digits, and sum exactly to 100. Never silently normalize invalid percentages. The two groups are `transversal/challenge/exam` and `self/peer/teacher`.

Disabling a module defense is rejected if any nonblank defense has already been recorded there, including zero. Clear those defenses first. Retain existing allocations when changing weighting/distribution or grades; recalculate validity instead of silently rewriting them. Configuration corrections after any prior publication require a nonempty reason. Configuration saves are all-or-nothing and audited.

Closed years or finished/published challenges show read-only settings/team/evidence states and disable mutation controls. Existing values and breakdowns remain inspectable.

## 11. Participants and teams

If a challenge has no participants, staff can “Incorporar estudiantes del grupo” after enrolling students in Organization. This repair is allowed only when it has no participants, teams, memberships, assessments, module-grade records or publications. Copy the current active-account/current-enrollment roster. Reject an empty eligible roster. This is not a way to append late students to a populated challenge.

Team composition is local to the challenge. Require at least one team when saving, distinct nonblank names up to 100 characters, 2–5 student identities per team, membership only from the challenge participant list, and no student repeated anywhere in the submission. It is permissible to save teams while some participants remain unassigned; those students remain pending.

Allow editing/replacing the entire team arrangement only until any assessment, any module-grade record (even one with blank grades), or any nonblank allocation exists. After that, preserve team composition for traceability and reject changes. Evidence notes alone do not freeze teams. Other challenges' teams are unaffected.

The team tab displays numbered cards, editable names, current member counts (`n/5`), member names, remove-member/team controls and an add-team action. New default names are “Equipo 1”, etc. Search/add from the alphabetically sorted unassigned participant list, ignoring accents/case. Adding a member removes them from all other available lists; removing them makes them available again. Clear the search after addition. Disable additions at five and display how many more members are needed below two. Show empty, no-match and all-assigned states. Validate names and sizes before enabling “Guardar equipos”. Unsaved cards survive tab changes.

## 12. Assessments and grade entry

### 12.1 Four assessment kinds

| Kind | Subject | Who writes | Scope and replacement behavior |
|---|---|---|---|
| Team technical (`team`) | Team | Staff; module-specific criteria require module responsibility | One shared selection per team/criterion/challenge |
| Teacher transversal (`teacher`) | Student | Any group teacher/admin | One shared selection per student/criterion/challenge, with no module or per-teacher averaging |
| Self (`self`) | Student | That student | One selection per own criterion/challenge |
| Peer (`peer`) | Student | A different student in the same team | One selection per receiving student/evaluating teammate/criterion/challenge |

Selections identify a level in the relevant copied rubric, not an arbitrary numerical score. Validate subject participation, criterion identity and level range against that rubric. Staff cannot submit self/peer assessments on behalf of students. Students cannot submit team/teacher assessments, assess another team's student, assess themselves as a peer or another student as self.

Self/peer entry is allowed only while the year is open and the challenge is `active` or `evaluating`. Staff assessment entry is allowed in `draft`, `active` and `evaluating`. One student can self-assess before being assigned a team; peer assessment requires team membership. Selecting another level replaces the previous selection and records the new author/time. Repeated selections do not create duplicate logical assessments. Assessment batches accept 1–1,000 entries and commit atomically. The ordinary assessment UI saves one clicked selection at a time.

### 12.2 Rubric assessment interaction

“Ev. técnica” opens technical assessment by team; “Ev. transversales” opens teacher transversal assessment by student, within the evaluation tab. Provide subject selector, Previous/Next, back to matrix and authorized “Editar rúbrica”. Direct links with `evaluation=team` or `evaluation=teacher` and `subject=<id>` restore that view; invalid subjects fall back to the first available subject.

Use criteria as rows and levels as columns, with criterion names, descriptions, weight percentages and technical module/GENERAL tags. Assessment columns, including the name column, have uniform widths. Display a common column score when all criteria share it; otherwise display scores in individual cells and explain that original criterion scores are retained. Selecting a level highlights the whole choice cell and saves automatically. Disabled module criteria remain readable with a responsibility explanation.

Descriptions initially show about four lines. Criterion descriptions can expand/collapse. Long level descriptions provide “Leer más / Leer menos” without changing the selected grade. Keep long text within its column. Provide horizontal scrolling and sticky table headings. Saved selection survives reload and movement between subjects. Empty team/student lists explain what must be configured first.

### 12.3 Exams and defenses

There is one exam and one defense per student/module/challenge. Responsible teachers share and replace these values; they are not separate per-teacher grades. Administrators can enter them without an explicit module assignment.

Exam is nullable or 0–10. Defense is nullable or −10–10, including positive signs, and can only be entered for a defense-enabled participating module. User-entered grades use at most two decimals; reject scientific notation and extra decimal places. Zero is a real recorded grade; null means pending. A request must explicitly provide a value, including explicit null to clear it. Omission is a validation error.

A defense records its current author, date and optional observations (2,000 characters). Default an omitted date to today's UTC date. Clearing the defense clears its author/date; observations are independently nullable. Editing a defense replaces that adjustment instead of accumulating another defense. The base allocation is unchanged. A defense change recalculates the common challenge result and every participating enrolled module result for that student.

Grade batches contain one module, one field (`exam`, `defense`, `not_enrolled`) and 1–500 distinct participating students. Validate every row and permission before committing any row. Each optional date must be a valid `YYYY-MM-DD`.

### 12.4 Not enrolled in a module

“No matriculado” is an explicit boolean for a student/module/challenge. It is neither zero nor pending and is never inferred from blank grades. Only that module's responsible staff/admin can change it.

Before setting it true, both exam and defense must be null; recorded zeros must also be explicitly removed. While true, reject exam/defense entry, exclude that module's defense from the shared challenge result, do not require its exam, and return no module grade. Show `NM` in the matrix and “No matriculado” in details/reports/exports. Clear the flag before entering grades again. It does not cancel the group's enrollment or other module/challenge records.

### 12.5 Matrix editing and productivity

Rows are challenge participants. Columns include student/team, transversal total, team rubric grade, optional allocation, defense sum, the single final challenge result, each module's enabled defense, exam and module final, and completion status. Group headings show component percentages. Emphasize the common challenge result; distinguish module results below 5 visually. Missing values appear as `—`; a completed row is “Listo”, otherwise show the number of pending categories.

Freeze the student column and table headers while scrolling. Filter by student-name substring, team, or only pending rows. Sort by student name or team then name. Provide toggles for transversal, team/allocation and individual defense columns; totals and module exam/final remain available. Show filtered count and an empty-match row.

Exam cells save on blur or Enter. Tab proceeds to the next field; Enter proceeds to the next enabled grade input, Shift+Enter to the previous, and Escape restores the saved value and cancels that edit. Select the input contents on focus. Accept decimal commas in the UI. Keep focused/unsaved input from being overwritten when another save returns. Mark unsaved edits. Serialize rapid writes using the latest returned challenge revision; after a failed queued write, do not send subsequent queued edits against an uncertain state. Display saving, saved, error and refresh states accurately.

Defense cells open a compact editor with adjustment/date/observations; explain that zero means completed without adjustment and blank means pending. Student names, totals and pending indicators open the student's detailed breakdown: self/peer/teacher scores and weights, team grade, allocation/base, every enabled defense, raw/clamped challenge result, module formulas, not-enrolled switches and pending reasons.

“Introducción masiva” selects an authorized module and exam or defense, shows the current filtered/sorted student list and accepts one value per line in that order. Commas are accepted. Require exactly one nonblank line per listed student; blank lines are discarded by this UI rather than serving as placeholders. The entire batch succeeds or fails. Use ordinary cell editing to clear grades. No extra exam/activity catalog is provided.

## 13. Calculation specification

### 13.1 Numeric principles

Calculate with exact decimal/rational meaning, retaining precision through intermediate arithmetic. Round for ordinary presentation to two decimals, half up. Do not perform averaging on already displayed two-decimal values. Detailed grade data retains four-decimal displays for team grade, base, defense total and raw/final challenge values, plus exact results for aggregation. A rational third must remain a third through downstream calculations.

A weighted mean is `sum(value × weight) / sum(weight)` over positive weights. Zero-weight components are deliberately excluded, including their missing values. A missing positive-weight value makes the weighted result pending; never silently drop it, substitute zero or renormalize over completed values. All-zero or empty weights produce pending. Negative weights are invalid.

### 13.2 Rubric grades

For any complete rubric assessment, use the chosen level's score for each criterion and compute its weighted mean. All positive-weight criteria must have a selection. This supports both new percentage weights and retained historical relative weights.

For each team, the technical rubric produces an exact team grade `T`. For each student, the transversal rubric produces:

- `S`: their self assessment.
- `D`: the shared teacher assessment.
- One received grade from each other teammate; `P` is the arithmetic mean of all those received grades.

If any expected teammate's rubric is incomplete, `P` is pending. With no other teammates, `P` is pending. Peer assessment is directional: A's evaluation of B contributes to B, not A. There is no anonymous aggregation that discards who assessed whom.

With configured transversal weights `s, p, d`, compute `X = (S×s + P×p + D×d) / 100`. Defaults are 10/60/30. This is one transversal result for the student/challenge and is reused in every module. Multiple teachers edit the same `D`; do not average their successive edits.

### 13.3 Team points and allocation

Teams must have 2–5 members to supply a valid base. When allocation is disabled, every member's base is the exact `T`. Do not round `T` to four decimals before using it as the no-allocation base.

When enabled, available team points are `roundHalfUp(T × memberCount, 2)`. Staff record the distribution agreed outside the application. Every member must have one allocation, 0–10 inclusive, at most two decimals, and their sum must exactly match the budget. Require the exact set of team member identities; no missing/extra members. A complete technical rubric is required before accepting an allocation.

Show team grade, member count, available points and entered total in the allocation dialog. With no saved distribution, suggest equal hundredth shares and distribute residual cents to the last members in stored team order: 25 points across three members starts as 8.33, 8.33, 8.34. Existing saved values are loaded instead. A rejected sum explains required points and the exact shortage/excess (“falta 1 punto”, “sobra 1 punto”). It saves none of the changes.

Compatibility detail: the allocation submission check first rounds the team rubric grade to four decimals, then rounds its product with member count to two decimals. The displayed budget and subsequent evaluation-validity check use the exact rubric grade before the two-decimal rounding. Usually these agree; at a rare rounding boundary they may differ. Preserve this distinction if exact behavioral compatibility is required. Do not describe all grading as two-decimal arithmetic.

An invalid or missing allocation makes the individual base pending for the team. Changing rubric scores/weights may invalidate a saved distribution; keep its numerical values and require staff to correct it. Students never enter allocations themselves.

### 13.4 Defenses and the common challenge result

For a student, collect the single defense from every participating module whose defense is enabled and whose not-enrolled flag is false. Disabled defenses and not-enrolled modules contribute nothing and need no entered zero.

If any collected defense is blank, the defense total and raw/final challenge result are pending. If there are no collected defenses, the defense total is zero. Otherwise `A = sum(all collected defense adjustments)`.

Given valid base `B`, raw challenge result is `R = B + A`. If clamping is enabled, final challenge result `C = min(10, max(0, R))`; otherwise `C = R`, which may be outside 0–10. Keep both `R` and `C` so the effect of limits can be explained. A missing base makes the challenge result pending even if the defense sum is complete.

There is exactly one `C` per student/challenge. Every participating enrolled module uses it. Defenses are associated with modules for responsibility and evidence, but their adjustments are accumulated into that shared result.

### 13.5 Module, period and year grades

For enrolled module `m`, with its exam `E_m` and configured component percentages `x, c, e`, compute `F_m = (X×x + C×c + E_m×e) / 100`. Defaults are 30/40/30. Only positive-weight components are required for this numeric result. There is no additional clamping or pass/fail gate on `F_m`. A not-enrolled module has no numeric result.

For each student/module/period, include challenges in that period where the student participates and the module participates. Omit explicit not-enrolled challenge/module entries from arithmetic. Other challenges are not zeros. Compute the exact weighted mean of their `F_m` using positive challenge weights, divided by the sum of those weights. A missing included grade leaves the period pending. Display two decimals.

If at least one qualifying challenge is explicitly not enrolled and there are no included challenge values, mark that period “No matriculado”. If there are no qualifying challenges at all, the period is pending instead. With a mixture of excluded and included challenges, average only included challenges and display excluded ones in the breakdown.

The annual module grade is the arithmetic mean of all the group's periods except those explicitly not enrolled. Empty/pending periods remain in that mean and make it pending. If all periods are explicitly not enrolled, the annual result is “No matriculado”. Do not silently drop an empty future period. Retain exact period results when calculating annual means.

## 14. Completeness and diagnostics

Detect global challenge issues for no modules, no participants, participating module without an active responsible teacher, missing team/transversal rubric items, and any team outside 2–5 members.

For each student, list these pending categories as applicable:

- “Sin equipo”.
- “Rúbrica del equipo” when the team rubric is incomplete.
- “Reparto pendiente o inválido” when allocation is enabled and the team's base/distribution is invalid.
- “Autoevaluación”, “Coevaluación”, “Evaluación docente” when the corresponding transversal subweight is positive and its rubric result is missing.
- “Defensa <module code>” for every enabled, enrolled module with a blank defense.
- “Examen <module code>” for every enrolled module with a blank exam and positive exam component weight.

A challenge is complete only when at least one student participates, no global issue exists, and every student's pending list is empty. The summary's completed-student count uses empty pending lists, while publication additionally checks global issues.

Completeness is intentionally stricter than whether some module formula happens to yield a number: team assignment/rubric and enabled defenses still matter when challenge component weight is zero, and positive transversal subweights still require assessments even when the overall transversal component is zero. Both rubric definitions and module responsibility are required regardless of component weights. Not-enrolled flags remove only module-specific exam/defense requirements, not team/transversal requirements.

## 15. States, publication, correction and history

| Status value | Spanish label | Behavior |
|---|---|---|
| `draft` | Borrador | Staff preparation/evaluation allowed; student assessment closed |
| `active` | En curso | Staff editing and student self/peer assessment allowed |
| `evaluating` | En evaluación | Staff editing and student self/peer assessment allowed |
| `finished` | Finalizado | Requires completeness; grading/configuration/evidence read only; no student published result |
| `published` | Publicado | Requires completeness; immutable publication created; current results visible to each student; challenge read only |

While editable, staff can select draft/active/evaluating without a prescribed sequential progression, or finished if complete. Dates do not drive transitions. Publishing can be requested from an editable complete state, and the service also accepts a complete finished challenge. The normal staff UI offers Publish only while editable; a finished challenge may be reopened through the UI before publishing. Publishing an already published challenge is rejected.

Publishing stores a complete snapshot of the configuration, copied rubrics, labels, teams, participants, assessments, component results, exact/detailed results and module metadata as evaluated at that moment. Assign version 1, then 2, etc., and publisher/time. Change current status to published. Freeze changes until explicitly reopened. The snapshot captures the pre-publication evaluation, including its then-current revision/state; the live challenge status/revision changes after publication.

Reopening is allowed only for finished/published challenges, requires a reason of 5–2,000 characters, and returns the challenge to evaluating. Keep all old versions unchanged. Students no longer receive a published result while it is reopened, even if old versions exist. Republishing makes a new version available and never overwrites the old one. Configuration corrections need their own nonempty reason after publication; rubric corrections require a 5–2,000-character reason.

Record successful challenge mutations with actor, action, timestamp, before/after evaluation evidence and optional reason. This includes participation repair, teams, assessments, allocation, grades, configuration, rubric edits, status, reopening and publication. Increment the challenge revision once per successful operation. Reject a stale revision with 409 and no changes, even for an otherwise valid edit. Validation/authorization failures create no successful audit entry and must not advance revision.

Publication history is available to authorized staff through the history service: all publications newest version first, plus at most the latest 100 challenge-event summaries containing identity, actor, action, reason and time. The standard challenge header currently has no visible History action. Preserve history access as a service capability without assuming an exposed history navigation button or a general institution-wide audit UI. Evidence notes form their own history and do not advance the grading revision or create grade-change events.

## 16. Editing a challenge's copied rubric

Any authorized group teacher/admin may edit either copied rubric, even without responsibility for every criterion's module. Require an open year and editable challenge. The editor returns to the same assessment kind and selected subject after saving/canceling. The change applies to all teams/students using that rubric in this challenge.

Hide library-only type/cycle/sharing controls. Limit technical criterion modules to participating challenge modules or GENERAL. Transversal criteria remain GENERAL. Validate name, keys, descriptions, 1–40 criteria, 2–20 levels and 0–10 scores with at most two decimals as in the library.

Challenge criterion weights support retained relative weights up to four decimals and four integer digits, positive only. For a rubric whose original weights do not total 100, permit a change that keeps exactly the same criterion identities and weights (including reordering). Adding/removing criteria or changing any weight requires an exact new total of 100. Existing nonuniform per-criterion level scores/counts may be retained and edited; do not silently force a shared score grid for them.

Maintain criterion and level identity independently of display position. Reordering or adding levels must keep surviving assessment selections pointing to their original level. Renaming/rewording a criterion or changing its score/weight keeps selections and recalculates results. A newly added criterion starts without assessments. Removing a criterion deletes its associated selections; removing a level deletes only selections using that removed level, with later levels remapped. Transversal changes apply to self, peer and shared teacher selections; team changes apply only to team selections. Preserved selections retain their existing author/time when merely repositioned.

Require a read-only impact preview and explicit confirmation before saving. Preview reports:

- Total selections to be deleted and counts by assessment kind.
- Names of subjects whose selections would be deleted.
- Removed criterion names.
- Teams whose technical grades would change.
- Previously valid allocations that would become invalid.
- Students whose module results, transversal total or pending categories would change.

Preview creates no rubric change, deletion, audit event or revision increment. It issues a confirmation token bound to actor, challenge revision, reviewed content/reason and impact. Saving without the token, using changed content, using another actor's token or using a stale revision is a 409 conflict. Editing after preview invalidates the confirmation. The confirmation dialog offers “Seguir editando” and “Confirmar y guardar”; cancelling makes no durable changes.

After confirmed save, apply the edited rubric and necessary selection removals/remaps atomically, preserve allocation values, update all derived results, increment revision and audit before/after evidence. Previous publications, library templates and other challenges remain intact. A stale assessment tab cannot save an old positional level after this operation; require refresh.

## 17. Teacher evidence and student experience

### 17.1 Evidence workspace

Evidence is private teacher observation text about a student in a challenge. All authorized group teachers/admins can read all these notes. Students never receive them, including by requesting the evidence tab or endpoint directly. It is not a file submission or grading component.

The desktop workspace has a student roster and individual detail pane. List current participants plus any former participants with retained notes, alphabetically. Include initials, name, current team or “Sin equipo”, and note count. Show total students and total annotations. Search by student or team, accent/case-insensitively; provide clear search, All, With notes, counts and no-match reset controls.

Selecting a student shows only their notes, newest creation time first and descending identity for ties. Each note shows author name, Spanish-formatted date/time and plain text with line breaks preserved. Display an empty-history state. Existing notes are immutable through normal product actions: there is no edit/delete form.

Adding a note requires an open year, a challenge outside finished/published, and a current participant. Require nonblank trimmed text, maximum 2,000 characters, and show a character count. New notes receive the current authenticated author and timestamp. Save returns to the evidence tab, updates history/counts, clears only that student's successful draft and shows confirmation. Validation failure preserves the draft and shows an error. Keep independent drafts when changing students or challenge tabs; block double submits and student/tab switching during save. A full reload may discard unsaved notes.

Former-participant notes remain readable but cannot receive new notes until that person is a current participant. Team labels reflect current team information, not a historical team label embedded in each note. Empty challenges offer a link to the team tab. Closed challenges/years retain filters and history but show read-only guidance and no composer.

On tablet/narrow screens use a roster/detail navigation flow with “Cambiar estudiante”, focusing the detail heading when selecting and restoring roster focus when returning. At small widths avoid whole-page horizontal overflow.

### 17.2 Student challenge screen

Students see challenge name, description, state, group and period; tabs for “Mi autoevaluación” and each other teammate; and the transversal rubric. Selecting a rubric level autosaves and confirms “Evaluación guardada”. Disable selections while saving, outside active/evaluating, or in a closed year. Explain when student assessment is closed.

Deliver only that student's own self/peer submissions, teammate identities/names needed for assessment, permitted challenge metadata, and module names/responsible-teacher metadata. Do not deliver other students' grades, their received peer selections, staff assessment rows, team-grade matrices, private notes or publication history before publication.

While current status is published, show the student's own row from the latest immutable publication: transversal total, final challenge result, base plus defense total, each module's exam, enabled defense and module final/No matriculado. The own-result data includes the component breakdown (self, peer, teacher, team/base/raw/final) for that student. Never provide other students' publication rows. Reopening removes this result from the student response until republication.

## 18. Reports and exports

### 18.1 Academic follow-up report

Staff open “Evaluaciones” for a selected visible group, defaulting to the first available group. Display every relevant student × every active or retired group catalog module, with ordered period grades and annual mean. Include students with current or ended group enrollments and students retained as challenge participants, including inactive accounts in historical reporting. Sort students by name then identity.

This is a live follow-up report over challenges in all statuses, not a published-results-only report. Use the arithmetic in section 13. Show a notice that pending grades keep their period and year pending. Search by student-name substring and filter by module code. Clicking a period opens details with student/module/period, each contributing/excluded challenge, its relative weight, grade/No matriculado, link to challenge and weighted result. Explain periods with no module challenges.

Module labels come from the group's retained catalog names/codes. The report uses the group's current ordered periods. Retired modules stay in the report. A user cannot read/export another group's report or a report from another selected year.

### 18.2 Report CSV and printing

Download `erronk2d-evaluaciones.csv` for the entire selected group's report, independent of local student/module filters. Use UTF-8 with BOM, semicolon separator and correctly escaped quoted fields. Columns are exactly:

`Estudiante;Módulo;Evaluación;Nota;Media del curso`

Write one row per student/module/period. Repeat annual mean on each of that student's module period rows. Use decimal strings with two fractional digits, `Pendiente` for missing results and `No matriculado` for excluded results. Prefix spreadsheet-dangerous cells beginning with `=`, `+`, `@`, `-`, tab or carriage return with an apostrophe.

“Imprimir / PDF” opens browser printing. Print a clean report with navigation/actions/filter controls hidden, table headers repeated and rows kept together where practical. The visible report filters affect printing. There is no separate server-produced PDF or XLSX export in this scope.

### 18.3 Challenge matrix CSV

Download `erronk2d-reto.csv` using current filtered/sorted student rows. Column visibility toggles do not remove export fields. Include:

`Estudiante`, `Equipo`, `Transversales`, `Reparto`, `Defensas`, `Reto final`, followed by `Examen <code>` and `Final <code>` for each module.

Use UTF-8 BOM, semicolons, quoted/escaped cells and spreadsheet-formula protection. Preserve supplied result precision in this export, so some fields can have four decimals. Missing values use `Pendiente`; not-enrolled modules use `No matriculado` for exam/final. The allocation column remains even when allocation is disabled. Exporting does not publish or change grades.

## 19. CSV/XLSX import

Import students, teachers or catalog modules. Teachers may import students only into their visible group in an open selected year. Administrators may import all three kinds. Student import requires a group even for preview; module import requires an existing cycle and level 1–4. Teacher/module import is global.

The UI is “Importar CSV / Excel”, with kind, destination, file, “Validar y previsualizar”, results and “Confirmar importación”. Changing kind/destination/file clears the previous preview. New accounts can establish a password through recovery; importing does not send email or disclose a reusable default password.

File rules:

- Accept `.csv` and `.xlsx`, at most 2 MiB; at most 1,000 data rows plus one header. The row limit is checked before ignoring blank rows.
- For XLSX, read the first worksheet only. Reject invalid workbooks and archives whose total uncompressed entries exceed 20 MiB. Require scalar text-compatible cells; do not silently turn structured/date objects into account fields.
- For CSV, choose semicolon if the first line contains more semicolons than commas; otherwise comma. Honor quoted cells. Normalize headers to lowercase, strip BOM and surrounding whitespace.
- People require `name,email`; modules require `name,code`. Headers must be unique after normalization. Additional unique columns may be present but do not change roles, credentials or other account fields.
- Ignore fully empty rows. Every nonempty row must have exactly the header's column count. Trim values; preserve names/accents; lowercase email. A truly empty data file is an error.
- Name max 150; email valid/max 255; module code required/max 30. Report source row numbers starting at 2.
- Reject duplicates within a file by case-insensitive email/code. Reject an existing teacher email for teacher import. Student import may reuse only an existing active student; reject another role or inactive account. Module import rejects an existing case-insensitive code in destination cycle/level.

Preview returns valid-row count, all row-level errors, destination group when applicable, the first ten valid rows and `committed=false`, with no writes. A validation result containing row errors may still be a successful HTTP response; `committed` remains false.

Commit revalidates the uploaded file and current access/data. Only an error-free file is applied, atomically. Create new active accounts with unguessable credentials; existing student accounts keep name/email/credentials and all other enrollments. Create/reactivate destination enrollments. Create module entries without adding them to existing groups. Record an import audit count. Return `committed=true`; refresh affected lists. No rows or audit entries are applied if any row fails. The endpoint is limited to 20 requests/minute.

## 20. Shared visual, responsive and accessibility requirements

Use the Erronk2D identity, an E/2 mark and Spanish interface text. The visual language uses a dark forest-green navigation/login panel, pale lime accents, off-white page background, white cards, subtle borders, rounded corners and restrained sans-serif typography. Grades use aligned numerals. Green indicates saved/completed, amber pending/warnings, and red validation errors. Meaning must also be conveyed in text/icons.

Desktop navigation is a fixed left sidebar, approximately 252 px expanded and 80 px collapsed. Remember collapse preference in the browser; initially collapse on widths at/below approximately 900 px if no preference exists. Keep icon labels/tooltips accessible while collapsed. Organization expands a submenu, highlights the current section and closes with Escape, returning focus to its toggle. Profile displays name/role and logout.

At phone widths (approximately 650 px and below), use a bottom navigation bar and a fixed top academic-year selector with content offset below it. Organization opens above the bottom bar and closes after navigation. Ensure page content and modals fit common phone/tablet widths (390, 768, 820 and 1024 px); wide matrices/rubrics scroll inside their own regions. Dashboard cards move from three columns to two then one; group/team cards and forms adapt. Desktop/tablet productivity is primary, with student/evidence flows usable on mobile.

Dialogs have accessible names, modal semantics, trapped Tab/Shift+Tab focus, Escape/close button/backdrop closing and focus restoration. Tab buttons use tablist/tab/tabpanel roles and selected/control associations. Forms have labels; errors use alerts; save/read-only states use status announcements. Tables have meaningful headers/captions, visible keyboard focus, accessible scroll regions, and full accessible names for student/criterion/module actions. Expanding text does not accidentally submit an assessment. Prevent clipping of long names/descriptions.

Use Spanish number presentation with decimal commas and normally exactly two displayed decimals; preserve a distinction between blank, zero and NM. Use Spanish-formatted user-facing dates/times; durable timestamps identify the actual instant, and date-only fields have no invented time component. Browser page titles include the page/challenge name and `Erronk2D`.

## 21. Failure handling, privacy and concurrency

Successful form actions show concise Spanish confirmation. Validation failures preserve submitted values, identify fields or row numbers, and create no partial durable changes. Network failures must not falsely show “Cambios guardados”; show retry/connection guidance. Challenge errors include an “Actualizar datos” refresh action.

Use these observable error categories:

| Situation | Result |
|---|---|
| Guest opens protected browser page | Redirect to login; JSON callers receive authentication failure |
| Inactive session or prohibited role/function | 403 |
| Missing or outside-scope group/challenge/rubric | 404 where scoped lookup applies |
| Invalid value/state/combination, incomplete publication | 422 for JSON; form redirect with errors for browser form flow |
| Stale/missing academic context, stale grading revision or invalid rubric confirmation | 409 |
| Rate limit exceeded | 429, or the explicitly described field-level login/link limiter feedback |

Show academic-context/permitted-operation messages for failed 403/409 page requests and a link back to the current dashboard. Do not overwrite newer grades on conflicts. Protect write requests against cross-site request forgery and protect authenticated sessions in HTTPS deployment. Escape untrusted names, descriptions and notes; there is no rich-HTML authoring feature. Do not expose credentials, Google subjects/tokens or mail-provider responses in normal page data, errors or audit snapshots. Keep secrets out of request URL logs and exported records.

No periodic refresh, push notification or automatic cross-user merge is required. Another user's changes become apparent on reload or through revision conflict. Successful writes return recalculated results immediately. Evidence appends are independent of grading revision, while retaining year/status checks.

## 22. Browser addresses and service contracts

The following addresses are observable navigation/service contracts. They do not imply any internal architectural pattern. All protected operations require an active authenticated session and the access rules above. Contextual writes send `X-Academic-Year` or, when that header is absent, `academic_year_id`. Send CSRF protection on browser mutations. The header takes precedence over a body year field.

### 22.1 Address inventory

| Method/path | Purpose and successful outcome |
|---|---|
| GET `/login` | Local/optional Google login or pending link confirmation |
| POST `/login` | Local authentication; intended location/dashboard redirect |
| GET/POST `/forgot-password` | Recovery form / generic recovery response |
| GET `/reset-password/{token}?email=...` | New-password form |
| POST `/reset-password` | Reset credential; login redirect |
| GET `/auth/google` | Begin configured Google authorization |
| GET `/auth/google/callback` | Resolve identity, link prompt, pending request or dashboard redirect |
| POST `/auth/google/link` | Confirm existing local password and link |
| GET `/auth/google/cancel` | Clear pending OAuth/link state; login redirect |
| POST `/logout` | End session; login redirect |
| POST `/academic-context` | Select available `academic_year_id`; dashboard redirect |
| GET `/` | Role-filtered challenge dashboard |
| POST `/challenges` | Create challenge with section 9 fields; new challenge redirect |
| GET `/challenges/{id}` | Staff workspace or private student screen; tab/evaluation/subject query options |
| POST `/challenges/{id}` | Revision-checked action; JSON `{book: ...}` |
| GET `/challenges/{id}/evidence` | Staff-only redirect to `?tab=evidence` |
| POST `/challenges/{id}/evidence` | Append `student_id,note`; evidence-tab redirect |
| GET `/challenges/{id}/rubrics/{kind}/edit` | Editable copied-rubric page, kind `team` or `transversal`; optional `subject` |
| POST `/challenges/{id}/rubrics/{kind}/preview` | Read-only impact preview and confirmation token |
| GET `/challenges/{id}/history` | Staff-only publication snapshots and recent event summaries |
| GET `/setup` | Role-specific Organization redirect |
| GET `/setup/{section}` | `courses,cycles,modules,classrooms,teachers,students,rubrics,registrations` |
| GET `/setup/rubrics/create` | New template editor |
| GET `/setup/rubrics/{id}/edit` | Owner/admin template editor |
| POST `/setup/{entity}` | Entity operations below; success redirect/flash |
| POST `/students/lookup` | Exact-email lookup; JSON `{student: {id,name,email} or null}` |
| POST `/registrations/{id}` | Pending Google request decision; return with confirmation |
| POST `/imports` | Multipart import preview/commit; JSON result |
| GET `/reports?classroom={id}` | Permitted group follow-up report |
| GET `/reports?classroom={id}&format=csv` | Download report CSV |
| GET `/up` | Public application health response |

No independently authenticated public REST API, bearer-token access, webhook, student-upload endpoint or external gradebook integration is part of the product. JSON services share browser session authorization.

### 22.2 Organization operation fields

An optional `id` selects an existing record for normal create/edit operations. Operation-specific rules override that convention.

| Entity | Input fields |
|---|---|
| `year` | `id?`, `name` |
| `year-status` | `id`, boolean `is_open` |
| `cycle` | `id?`, `name`, `code` |
| `module` | `id?`, `name`, `code`, nullable `cycle_id`, `level` |
| `cycle-module` | `cycle_id`, `module_id`, `level`, boolean `active` |
| `classroom` | `id?`, `name`, `cycle_id`, `level`, `period_count?`, required array `user_ids`, optional `owner_id` |
| `group-module` | `classroom_id`, `module_id`, boolean `active` |
| `group-periods` | `classroom_id`, ordered `periods[{id?,name}]` |
| `responsibility` | `classroom_id`, `module_id`, required distinct array `teacher_ids` (empty allowed) |
| `student` | `id?`, `name`, `email`, `password`, boolean `active`, context-dependent `classroom_id` |
| `teacher` | `id?`, `name`, `email`, `password`, boolean `active` |
| `enrollment` | `classroom_id`, `email`, boolean `active` |
| `rubric` | `id?`, `name`, `kind`, nullable paired `cycle_id,level`, `shared_user_ids?`, ordered `items` |
| `rubric-copy` | Existing `id` only needed to identify copy source |

Rubric items contain `key,name,description?,module_id?,weight,levels[{score,description}]`. Omitting sharing on template edit keeps current recipients; supplying it replaces the list. Template save returns to the library; other setup operations return to the originating page. Unknown entity is 404.

### 22.3 Challenge actions

Challenge creation accepts `name,description?,classroom_id,period_id,module_ids,team_rubric_id,transversal_rubric_id,weight,distribution_enabled` plus academic context. The two rubric references identify library templates to copy.

Every action on an existing challenge requires an integer `revision >= 1` matching the current challenge. Its academic context must also match. Successful staff response is the newly calculated evaluation book; student response is always filtered to the private student contract.

| `action` | Additional fields |
|---|---|
| `participants` | None |
| `teams` | `teams[{name,students:[studentId,...]}]` |
| `allocation` | `team_id`, `allocations` mapping every member identity to its decimal grade |
| `grades` | `field`, `module_id`, `entries[{student_id,value,date?,notes?}]` |
| `assess` | `kind` = `team/teacher/self/peer`, `entries[{subject_id,criterion,level}]`; level is zero-based |
| `configure` | `name,description?,notes?,weight,distribution_enabled,clamp_grade,starts_at?,ends_at?,component_weights,transversal_weights,defenses`, optional correction `reason` |
| `status` | `status` = `draft/active/evaluating/finished` |
| `publish` | None |
| `reopen` | `reason` |
| `rubric` | `kind` = `team/transversal`, edited `rubric`, `reason`, `preview_token` |

`defenses` maps participating module identities to actual booleans; unknown modules or nonboolean values are invalid. It can update a subset; omitted module switches keep their state. Normal UI submits all.

Copied-rubric preview takes `revision,kind,reason,rubric:{name,items}`; the URL kind determines the preview kind. Each level includes `source_index`: its zero-based identity in the opened rubric revision, or null for a new level. Reject missing, repeated or nonexistent origins per criterion. After confirmation, this origin is not part of the stored rubric's public level content. Preview returns `{impact,token}`; impact fields are `removed_assessments,removed_by_kind,affected_subjects,removed_criteria,changed_team_grades,invalid_allocations,changed_results`.

### 22.4 Evaluation response meaning

The staff `book` contains:

- `challenge`: editable/display metadata, state, revision, weights, defense-independent switches and copied rubrics.
- Retained `classroom,year,period,cycle` labels.
- `modules`: identity, code/name, defense-enabled flag and active responsible teacher identities/names.
- `teams`: identity/name, members and allocations, four-decimal grade, exact rubric value, two-decimal points budget and distribution-valid flag.
- `rows`: student identity/name/team; team grade, allocation, four-decimal base; self/peer/teacher/transversal results; defense total; raw/final challenge values; per-module exam/defense metadata, not-enrolled flag, two-decimal final and exact final; pending reasons.
- `issues`, assessment selections with kind/subject/evaluator scope/criterion/level/last author/time, and `complete`.

The student `book` contains only restricted challenge metadata (identity, name, description, status, revision and transversal rubric), group/year/period, teammate identities/names, their own submitted self/peer assessments, module metadata, and their own latest-publication `result` or null. Do not use the staff payload and merely hide it visually.

History returns `{publications,events}`. Import takes multipart `kind` (`student/teacher/module`), `file`, boolean `commit`, and destination `classroom_id` for students or `cycle_id,level` for modules. It returns `{count,errors:[{row,message}],classroom,preview,committed}`. Student lookup takes `classroom_id,email`. Registration review takes `decision` (`approve/reject`), approval `role` (`student/teacher`), and student-approval `classroom_id`.

Local login and recovery use `email,password` and `email`, respectively. Password reset uses `token,email,password,password_confirmation`; Google linking uses the local `password` and the pending session identity. Unknown challenge action is 422. Successful JSON writes must not expose hidden account credentials or provider identifiers.

## 23. Email, integrations, jobs and configuration-visible behavior

Google identity and password-recovery delivery are the only user workflow integrations. Email must be configurable with sender name/address and public origin; the delivery provider is replaceable. Google can be disabled without removing local login. There is no student email-domain allowlist.

There are no product-specific scheduled reminders, queued academic jobs, asynchronous import/export progress screens, grade recomputation jobs, deadline transitions or publication notifications. Imports, grading and publication complete during the requesting workflow. Generic hosting/queue/cache options do not constitute additional product features.

Application dates default to UTC; browser timestamps are localized for display. Deployment must support HTTPS links, authenticated sessions, CSRF protection, upload limits and safe error pages. A public health address does not reveal academic data. Technical deployment technology and mail-provider SDKs are outside this specification.

## 24. Operator utilities and demonstration behavior

These capabilities belong to an operator/admin maintenance surface, not ordinary student/teacher navigation. Their interface need not be a particular programming-language command.

### 24.1 First administrator

Provide an interactive bootstrap operation for the first administrator only. Refuse if any administrator already exists. Ask for name, email and hidden password/confirmation, using the same 10–200-character password and name/email limits. Lowercase email, create an active administrator and audit creation without recording credentials. Refuse noninteractive password handling for this bootstrap flow. This does not supply a UI for creating additional administrators.

### 24.2 Repeatable fictitious accounts

A repeatable demonstration-account operation maintains 40 marked student and 10 marked teacher accounts, named “Estudiante de prueba 01” through 40 and “Profesor de prueba 01” through 10 initially. Reserved addresses follow `student1@demo.erronk2d.test` … `student40@demo.erronk2d.test` and `teacher1@demo.erronk2d.test` … `teacher10@demo.erronk2d.test`.

Create only missing marked identities with unguessable credentials. Preserve existing names, changed emails, passwords, academic activity and manually created accounts; reactivate existing marked accounts. Replenish deleted marked accounts. A reserved-email collision with an unmarked account or a marked account with the wrong role rejects the entire operation. Prevent concurrent runs. Send no mail. This operation creates no year, group, module, rubric or enrollment.

### 24.3 Full local demonstration

Separately provide an optional full demonstration for an empty isolated development/test installation. Require a supplied demo password of at least 12 characters; never embed a production credential. Refuse populated installations and production use.

The demonstration contains an administrator, four teachers, 20 students, a pending Google request, an open year, a DAW level-2 group with three periods, four modules (PROG, DWEC, DWES, DIW), group-specific responsibilities (including two teachers sharing PROG), private/shared templates, and six example challenges plus one empty challenge. Use five four-person teams per populated challenge, with membership varying between challenges. Provide completed evaluating work, a published challenge, a challenge without allocation/defenses, partly completed work, active unassessed work and a draft. Two challenges belong to each period with relative weights 2 and 3. The separate empty draft has modules and copied rubrics but no participants/teams, allowing participant-repair exercises.

Technical sample criteria cover code quality, user experience, server architecture, design/accessibility and presentation/documentation. Transversal criteria cover autonomy, teamwork, involvement and responsibility. Example levels are 4/6/8/10. Include historical relative weights of 1 to exercise compatibility, allocations such as 7/8/8/9, exams 6–9 and signed/zero defenses. Fictitious names/titles are content examples, not an account authentication contract.

### 24.4 Catalog provisioning

Provide preview and explicit execution of the fixed catalog in Appendix A, optionally limited by one or more catalog cycle codes. Preview reports new/existing cycle and module counts and writes nothing. A complete empty-database load adds 181 cycles and 2,691 modules. Repeat execution adds no duplicates and preserves customized existing names/codes and all existing group selections.

Match cycles by normalized code or normalized official name/alias. Normalize case, accents and runs of whitespace. More than one matching existing cycle is ambiguous and aborts the complete import. Within the selected cycle and same level, match a module by normalized code or name; multiple matches abort likewise. Missing entries are added; matched entries are retained unchanged. Reject unknown requested cycle codes, malformed/incomplete data and duplicate module code/level pairs in the supplied catalog. Prevent concurrent execution; any failure rolls back all additions. Do not fetch or update the catalog automatically.

Display its scope: “Currículos del ámbito de gestión del Ministerio de Educación. No equivalen a la distribución de módulos de Euskadi.” It is a frozen selection of official tables dated 2026-09-15, including coded modules/projects with an explicit level; it excludes optional subjects without codes and titles without complete tables. `LOCAL-` cycle codes are stable catalog identifiers where an official cycle code was not supplied.

### 24.5 One-time academic reset

An exceptional operator utility previews record counts without mutation, and can explicitly clear academic activity after a verified backup and, in production, maintenance mode. Require an active administrator and all 50 correctly marked demonstration identities. Preserve all administrator accounts and the 40/10 marked accounts with their current credentials/names; remove other nonadministrator accounts and all academic years, catalogs, groups, enrollments, rubrics, challenges, evidence, grades, publications and prior audit activity. Clear registration requests, sessions, recovery tokens and remembered academic context. Record one reset event.

Reject unmet prerequisites or concurrent execution atomically. Once a successful reset event exists, later invocations are no-ops, preserving academic data created since the reset. This is a legacy exceptional operation, not a normal startup action or user-facing Delete All feature.

## 25. Worked calculation examples

### 25.1 Shared challenge grade across modules

Team rubric grade is 8; three members receive 7, 8, 9, totaling 24. For the first student, module A defense is +0.50 and module B defense is −0.25. The defense sum is +0.25 and the common challenge result is 7.25. With transversal 8 and exams A=7, B=9:

- Module A: `8×0.30 + 7.25×0.40 + 7×0.30 = 7.40`.
- Module B: `8×0.30 + 7.25×0.40 + 9×0.30 = 8.00`.

Changing A's defense to +1.00 replaces +0.50; common challenge result becomes 7.75, while allocation stays 7 and both module results increase by 0.20.

### 25.2 Pending, zero and limits

Base 7 plus one enabled blank defense is pending. Base 7 plus an entered zero defense is 7. Base 7 with all defenses disabled is also 7. Base 9 plus defense 2 has raw result 11 and clamped result 10; disabling clamping makes final result 11. Base 1 plus −2 clamps to zero.

Transversal self=8, peer=6, teacher=10 yields `8×0.10 + 6×0.60 + 10×0.30 = 7.40`. If one required teammate has not assessed the student, peer and transversal become pending. If peer subweight is zero, missing peer assessments do not prevent the weighted transversal result.

### 25.3 Precision and allocation

An exact team grade of 25/3 yields 25.00 points for three members; 8.33/8.33/8.34 is valid and 8.33/8.33/8.33 is not. When allocation is disabled, an exact rubric grade 1/3 remains 1/3 as the base, even though its detailed display is 0.3333. Two-decimal half-up presentation of 1.005 is 1.01.

### 25.4 Period and annual aggregation

Challenge results 6 and 8 with weights 1 and 3 produce period grade 7.50. If the second grade is pending, the period is pending. A result 7.30 and result 10 with weights 1 and 3 yield exact 9.325, displayed 9.33. Annual aggregation uses 9.325, not 9.33.

Period grades 6, 8, 10 yield annual 8.00. Periods 6, pending, 10 yield pending. A completely not-enrolled period is excluded; an empty period remains pending. A module omitted from a challenge contributes nothing; a participating module with a missing required grade contributes pending.

## 26. Acceptance scenarios

An independent rebuild should satisfy all of these observable outcomes in addition to the rules above:

1. An active teacher with no assignments selects the newest open year and creates a group; another unrelated teacher cannot open it. Adding that teacher makes it visible; removing them revokes it without deleting their old grades.
2. Changing year in one tab causes an older form in another tab to fail with a context conflict and no data changes. Closing the year blocks writes even for administration; reopening restores eligibility.
3. One student enrolls in two groups. Withdrawing from one preserves the other and their former challenge results. A newly created challenge excludes inactive and withdrawn students.
4. Renaming catalog module/cycle/year/group labels preserves labels frozen into earlier challenges/publications. Adding a catalog module does not silently modify existing group selections.
5. A period used by a challenge cannot be renamed/deleted. Adding/removing unused periods leaves old period identities and challenge links intact and affects future report completeness appropriately.
6. A private template is invisible to an unrelated teacher. Sharing permits use/copy, not original editing. Copying creates an unshared independent template. Editing the original never changes a challenge copy.
7. A challenge creation form retains name/description after an incompatible rubric error and creates no challenge until all selections are valid.
8. Two valid teams may leave another participant unassigned; publication is blocked. Duplicate membership, 1-person or 6-person teams are rejected. Graded teams cannot be rearranged.
9. A teacher can read all group grades but writes module grades/technical module criteria only for assigned modules. A second responsible teacher edits the same record, preserving a single current value.
10. A student can save self and teammate assessments during active/evaluating, but cannot assess another team, impersonate another evaluator or read received individual peer selections.
11. Blank and zero defenses behave differently. Invalid allocations and multirow grade batches roll back wholly. Rapid adjacent cell edits both persist, or show a clear conflict/error without false success.
12. Enabling No matriculado with existing grades fails. Clearing those grades then enabling it excludes that module's defense/exam requirement while retaining all other grading obligations.
13. An incomplete challenge cannot finish/publish. A complete publication is immutable. Reopening requires a reason, hides the student result, and a correction plus republication creates version 2 while version 1 is unchanged.
14. A rubric preview deletes nothing. Removing a middle level on confirmation deletes only assessments using it and shifts surviving selections safely. Another stale tab cannot save its old indices.
15. Text-only criterion correction preserves selections. Score/weight correction recalculates results and warns about newly invalid allocations. New criteria cause pending assessments. Changes remain local to the selected challenge.
16. Evidence drafts follow each student, survive tab changes, and persist after save errors. Notes are visible to authorized teachers only, including historical notes about former participants. Closed workspaces permit consultation only.
17. Reports include former students and retired modules, pending empty periods, all challenge states and explicit not-enrolled exclusions. CSV uses protected, correctly escaped text and the specified fields.
18. CSV/XLSX preview writes nothing. One invalid row prevents the whole commit. Existing active students are enrolled without changing their account. Duplicate addresses with different case are recognized.
19. Google registration creates only one pending request, grants no access, requires administrative role/group assignment, and cannot take over an existing account by email. Linking requires the local password and expires safely.
20. Recovery uses a generic response, a single-use expiring link and a confirmed minimum-ten-character password. Provider failure leaves the old credential intact and exposes no provider secret.
21. At phone/tablet widths, navigation/context stay usable; tables scroll inside their region; tabs/dialogs/selectors work by keyboard; expanded descriptions do not select a different grade.
22. Repeating catalog/demo-account provisioning creates no duplicates and preserves edits; ambiguous/colliding provisioning leaves no partial additions.

## 27. Scope boundaries and interpretation

The product supplies one institutional workspace with scoped annual groups; it has no institution/tenant selector, parent role, chat, online team negotiation, peer anonymity settings, attendance, student file uploads, arbitrary activity catalogs, automated deadline enforcement, scheduled reminders, manual final-grade overrides, configurable pass thresholds, general deletion UI, XLSX export or generated-PDF service. CSV download and browser Print/PDF are the export features.

A group owner is not automatically responsible for module grades. A complete numeric module result does not by itself guarantee publication completeness. Historical challenge labels/rubrics/publications are snapshots; current student names, group responsibility lists and evidence author names can reflect current accounts. Reports are live; student published grades are frozen. These distinctions are part of the behavior to reproduce.

The history endpoint is functional without a visible header action. Storage structures and incidental transport metadata are unrestricted beyond the business contracts above.

## Appendix A. Fixed academic catalog

This appendix makes the catalog provisioning feature reproducible without the original source. Preserve codes as text, including leading zeros. Each subsection defines a cycle's stable code, display name, matching aliases and its ordered module data. “Level” is course/level within that cycle. Source links are provenance recorded in the fixed data; provisioning does not fetch them.

Qualification categories are `fpb` (Formación Profesional Básica), `fpgm` (Formación Profesional de Grado Medio), and `fpgs` (Formación Profesional de Grado Superior). Code provenance `todofp` identifies a code taken from TodoFP; `internal` identifies a fixed `LOCAL-` catalog code.

### A.1. 121_0101 — Técnico en Producción Agroecológica

- Family: Agraria; qualification category: `fpgm`.
- Matching aliases: Producción Agroecológica.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/agraria/produccion-agroecologica.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0408 | Infraestructuras e instalaciones agrícolas |
| 1 | 0409 | Principios de sanidad vegetal |
| 1 | 0405 | Fundamentos Zootécnicos |
| 1 | 0407 | Taller y equipos de tracción |
| 1 | 0406 | Implantación de cultivos ecológicos |
| 1 | 0404 | Fundamentos Agronómicos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0413 | Comercialización de productos agroecológicos |
| 2 | 0412 | Manejo sanitario del agrosistema |
| 2 | 0411 | Producción ganadera ecológica |
| 2 | 0410 | Producción vegetal ecológica |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.2. 121_0102 — Técnico en Producción Agropecuaria

- Family: Agraria; qualification category: `fpgm`.
- Matching aliases: Producción Agropecuaria.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/agraria/produccion-agropecuaria.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0408 | Infraestructuras e instalaciones agrícolas |
| 1 | 0409 | Principios de sanidad vegetal |
| 1 | 0407 | Taller y equipos de tracción |
| 1 | 0405 | Fundamentos Zootécnicos |
| 1 | 0475 | Implantación de cultivos |
| 1 | 0404 | Fundamentos Agronómicos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0479 | Control fitosanitario |
| 2 | 0477 | Producción de leche, huevos y animales para vida |
| 2 | 0478 | Producción de carne y otras producciones ganaderas |
| 2 | 0476 | Producción agrícola |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.3. 121_0103 — Técnico en Jardinería y Floristería

- Family: Agraria; qualification category: `fpgm`.
- Matching aliases: Jardinería y Floristería.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/agraria/jardineria-floristeria.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0409 | Principios de sanidad vegetal |
| 1 | 0576 | Implantación de jardines y zonas verdes |
| 1 | 0407 | Taller y equipos de tracción |
| 1 | 0404 | Fundamentos Agronómicos |
| 1 | 0578 | Producción de plantas y tepes en vivero |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0408 | Infraestructuras e instalaciones agrícolas |
| 2 | 0581 | Técnicas de venta en jardinería y floristería |
| 2 | 0580 | Establecimientos de floristería |
| 2 | 0579 | Composiciones florales y con plantas |
| 2 | 0479 | Control fitosanitario |
| 2 | 0577 | Mantenimiento y mejora de jardines y zonas verdes |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.4. 121_0104 — Técnico en Aprovechamiento y Conservación del Medio Natural

- Family: Agraria; qualification category: `fpgm`.
- Matching aliases: Aprovechamiento y Conservación del Medio Natural.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/agraria/aprovechamiento-conservacion-medio-natural.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0409 | Principios de sanidad vegetal |
| 1 | 0404 | Fundamentos Agronómicos |
| 1 | 0837 | Maquinaria e instalaciones forestales |
| 1 | 0832 | Repoblaciones forestales y tratamientos selvícolas |
| 1 | 0835 | Producción de planta forestal en vivero |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0838 | Uso público en espacios naturales |
| 2 | 0836 | Prevención de incendios forestales |
| 2 | 0834 | Conservación de las especies cinegéticas y piscícolas |
| 2 | 0479 | Control fitosanitario |
| 2 | 0833 | Aprovechamiento del medio natural |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.5. 121_0201 — Técnico en Cultivos Acuícolas

- Family: Marítimo Pesquera; qualification category: `fpgm`.
- Matching aliases: Cultivos Acuícolas.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/maritimo-pesquera/cultivos-acuicolas.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0705 | Técnicas de engorde de moluscos |
| 1 | 0704 | Técnicas de engorde de peces |
| 1 | 0706 | Instalaciones y equipos de cultivo |
| 1 | 0703 | Técnicas de cultivos auxiliares |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0709 | Técnicas de cultivo de crustáceos |
| 2 | 0708 | Técnicas de criadero de moluscos |
| 2 | 0707 | Técnicas de criadero de peces |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.6. 121_0202 — Técnico en Mantenimiento y Control de la Maquinaria de Buques y Embarcaciones

- Family: Marítimo Pesquera; qualification category: `fpgm`.
- Matching aliases: Mantenimiento y Control de la Maquinaria de Buques y Embarcaciones.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/maritimo-pesquera/mnto-control-maquinaria-buques-embarcaciones.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1175 | Mantenimiento de las instalaciones y máquinas eléctricas en buques y embarcaciones |
| 1 | 1173 | Procedimientos de mecanizado y soldadura en buques y embarcaciones |
| 1 | 1172 | Mantenimiento de la planta propulsora y maquinaria auxiliar |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1710 | Itinerario personal para la Empleabilidad II |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 1177 | Procedimientos de guardia de máquinas |
| 2 | 1033 | Atención sanitaria a bordo |
| 2 | 1032 | Seguridad marítima |
| 2 | 1174 | Regulación y mantenimiento de automatismos en buques y embarcaciones |
| 2 | 1176 | Instalación y mantenimiento de maquinaria de frío y climatización en buques y embarcaciones |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1711 | Inglés Profesional II (GM) |
| 2 | 1713 | Proyecto Intermodular |

### A.7. 121_0203 — Técnico en Navegación y Pesca de Litoral

- Family: Marítimo Pesquera; qualification category: `fpgm`.
- Matching aliases: Navegación y Pesca de Litoral.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/maritimo-pesquera/navegacion-pesca-litoral.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1033 | Atención sanitaria a bordo |
| 1 | 1032 | Seguridad marítima |
| 1 | 1035 | Técnicas de maniobra |
| 1 | 1029 | Pesca de litoral |
| 1 | 1027 | Técnicas de navegación y comunicaciones |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 1030 | Despacho y administración del buque |
| 2 | 1028 | Procedimientos de guardia |
| 2 | 1034 | Instalaciones y servicios |
| 2 | 1036 | Estabilidad, trimado y estiba del buque |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1711 | Inglés Profesional II (GM) |
| 2 | 1713 | Proyecto Intermodular |

### A.8. 121_0204 — Técnico en Operaciones Subacuáticas e Hiperbáricas

- Family: Marítimo Pesquera; qualification category: `fpgm`.
- Matching aliases: Operaciones Subacuáticas e Hiperbáricas.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/maritimo-pesquera/oper-subacuaticas-hiperbaricas.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0763 | Fisiopatología del buceo y emergencias |
| 1 | 0759 | Instalaciones y equipos hiperbáricos |
| 1 | 0765 | Maniobra y propulsión |
| 1 | 0758 | Intervención hiperbárica con aire y nitrox |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1710 | Itinerario personal para la Empleabilidad II |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 1248 | Inmersión desde campana húmeda |
| 2 | 0761 | Corte y soldadura |
| 2 | 0764 | Navegación |
| 2 | 0760 | Reparaciones y reflotamientos |
| 2 | 0762 | Construcción y obra hidráulica |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.9. 121_0301 — Técnico en Panadería, Repostería y Confitería

- Family: Industrias Alimentarias; qualification category: `fpgm`.
- Matching aliases: Panadería, Repostería y Confitería.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/industrias-alimentarias/panaderia-reposteria-confiteria.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0031 | Seguridad e higiene en la manipulación de alimentos |
| 1 | 0032 | Presentación y venta de productos de panadería y pastelería |
| 1 | 0030 | Operaciones y control de almacén en la industria alimentaria |
| 1 | 0024 | Materias primas y procesos en panadería, pastelería y repostería |
| 1 | 0026 | Procesos básicos de pastelería y repostería |
| 1 | 0025 | Elaboraciones de panadería-bollería |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0029 | Productos de obrador |
| 2 | 0027 | Elaboraciones de confitería y otras especialidades |
| 2 | 0028 | Postres en restauración |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.10. 121_0302 — Técnico en Aceites de Oliva y Vinos

- Family: Industrias Alimentarias; qualification category: `fpgm`.
- Matching aliases: Aceites de Oliva y Vinos.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/industrias-alimentarias/aceites-oliva-vinos.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0030 | Operaciones y control de almacén en la industria alimentaria |
| 1 | 0116 | Principios de mantenimiento electromecánico |
| 1 | 0316 | Materias primas y productos en la industria oleícola, vinícola y de otras bebidas |
| 1 | 0317 | Extracción de aceites de oliva |
| 1 | 0318 | Elaboración de vinos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0321 | Análisis sensorial |
| 2 | 0146 | Venta y comercialización de productos alimentarios |
| 2 | 0031 | Seguridad e higiene en la manipulación de alimentos |
| 2 | 0319 | Acondicionamiento de aceites de oliva |
| 2 | 0320 | Elaboración de otras bebidas y derivados |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.11. 121_0303 — Técnico en Elaboración de Productos Alimenticios

- Family: Industrias Alimentarias; qualification category: `fpgm`.
- Matching aliases: Elaboración de Productos Alimenticios.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/industrias-alimentarias/elaboracion-productos-alimenticios.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0141 | Materias primas en la industria alimentaria |
| 1 | 0145 | Procesos tecnológicos en la industria alimentaria |
| 1 | 0142 | Operaciones de acondicionado de materias primas |
| 1 | 0143 | Tratamientos de transformación y conservación |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0146 | Venta y comercialización de productos alimentarios |
| 2 | 0031 | Seguridad e higiene en la manipulación de alimentos |
| 2 | 0030 | Operaciones y control de almacén en la industria alimentaria |
| 2 | 0116 | Principios de mantenimiento electromecánico |
| 2 | 0144 | Procesado de productos alimenticios |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.12. 121_0401 — Técnico en Planta Química

- Family: Química; qualification category: `fpgm`.
- Matching aliases: Planta Química.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/quimica/planta-quimica.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0109 | Parámetros químicos |
| 1 | 0112 | Control de procesos químicos industriales |
| 1 | 0113 | Operaciones de generación y transferencia de energía en proceso químico |
| 1 | 0110 | Operaciones unitarias en planta química |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0116 | Principios de mantenimiento electromecánico |
| 2 | 0115 | Tratamiento de aguas |
| 2 | 0111 | Operaciones de reacción en planta química |
| 2 | 0114 | Transporte de materiales en la industria química |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.13. 121_0402 — Técnico en Operaciones de Laboratorio

- Family: Química; qualification category: `fpgm`.
- Matching aliases: Operaciones de Laboratorio.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/quimica/operaciones-laboratorio.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0116 | Principios de mantenimiento electromecánico |
| 1 | 1253 | Seguridad y organización en el laboratorio |
| 1 | 1251 | Pruebas fisicoquímicas |
| 1 | 1250 | Muestreo y operaciones unitarias de laboratorio |
| 1 | 1249 | Química aplicada |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 1252 | Servicios auxiliares en el laboratorio |
| 2 | 1257 | Almacenamiento y distribución en el laboratorio |
| 2 | 1254 | Técnicas básicas de microbiología y bioquímica |
| 2 | 1256 | Ensayos de materiales |
| 2 | 1255 | Operaciones de análisis químico |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.14. 121_0501 — Técnico en Estética y Belleza

- Family: Imagen Personal; qualification category: `fpgm`.
- Matching aliases: Estética y Belleza.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/imagen-personal/estetica-belleza.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0640 | Imagen corporal y hábitos saludables |
| 1 | 0638 | Análisis estético |
| 1 | 0636 | Estética de manos y pies |
| 1 | 0641 | Cosmetología para estética y belleza |
| 1 | 0633 | Técnicas de higiene facial y corporal |
| 1 | 0634 | Maquillaje |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0642 | Perfumería y cosmética natural |
| 2 | 0643 | Marketing y venta en imagen personal |
| 2 | 0637 | Técnicas de uñas artificiales |
| 2 | 0635 | Depilación mecánica y decoloración del vello |
| 2 | 0639 | Actividades en cabina de estética |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.15. 121_0502 — Técnico en Peluquería y Cosmética Capilar

- Family: Imagen Personal; qualification category: `fpgm`.
- Matching aliases: Peluquería y Cosmética Capilar.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/imagen-personal/peluqueria-cosmetica-capilar.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0636 | Estética de manos y pies |
| 1 | 0842 | Peinados y recogidos |
| 1 | 0843 | Coloración capilar |
| 1 | 0844 | Cosmética para peluquería |
| 1 | 0849 | Análisis capilar |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0643 | Marketing y venta en imagen personal |
| 2 | 0640 | Imagen corporal y hábitos saludables |
| 2 | 0845 | Técnicas de corte del cabello |
| 2 | 0846 | Cambios de forma permanente del cabello |
| 2 | 0848 | Peluquería y estilismo masculino |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.16. 121_0601 — Técnico en Emergencias Sanitarias

- Family: Sanidad; qualification category: `fpgm`.
- Matching aliases: Emergencias Sanitarias.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/sanidad/emergencias-sanitarias.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0052 | Mantenimiento mecánico preventivo del vehículo |
| 1 | 0054 | Dotación sanitaria |
| 1 | 0055 | Atención sanitaria inicial en situaciones de emergencia |
| 1 | 0057 | Evacuación y traslado de pacientes |
| 1 | 0058 | Apoyo psicológico en situaciones de emergencia |
| 1 | 0061 | Anatomofisiología y patología básicas |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0060 | Teleemergencia |
| 2 | 0059 | Planes de emergencias y dispositivos de riesgo previsibles |
| 2 | 0053 | Logística sanitaria en emergencias |
| 2 | 0056 | Atención sanitaria especial en situaciones de emergencia |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.17. 121_0602 — Técnico en Farmacia y Parafarmacia

- Family: Sanidad; qualification category: `fpgm`.
- Matching aliases: Farmacia y Parafarmacia.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/sanidad/farmacia-parafarmacia.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0099 | Disposición y venta de productos |
| 1 | 0061 | Anatomofisiología y patología básicas |
| 1 | 0101 | Dispensación de productos farmacéuticos |
| 1 | 0100 | Oficina de farmacia |
| 1 | 0103 | Operaciones básicas de laboratorio |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0020 | Primeros auxilios |
| 2 | 0105 | Promoción de la salud |
| 2 | 0104 | Formulación magistral |
| 2 | 0102 | Dispensación de productos parafarmacéuticos |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.18. 121_0701 — Técnico en Emergencias y Protección Civil

- Family: Seguridad y Medio Ambiente; qualification category: `fpgm`.
- Matching aliases: Emergencias y Protección Civil.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/seguridad-medio-ambiente/emergencias-proteccion-civil.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1533 | Inspección de establecimientos, eventos e instalaciones para la prevención de incendios y emergencias |
| 1 | 1528 | Mantenimiento y comprobación del funcionamiento de los medios materiales empleados en la prevención de riesgos de incendios y emergencias |
| 1 | 0055 | Atención sanitaria inicial en situaciones de emergencia |
| 1 | 1530 | Intervención operativa en extinción de incendios urbanos |
| 1 | 1532 | Intervención operativa en actividades de salvamento y rescate |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0058 | Apoyo psicológico en situaciones de emergencia |
| 2 | 1531 | Intervención operativa en sucesos de origen natural, tecnológico y antrópico |
| 2 | 1534 | Coordinación de equipos y unidades de emergencias |
| 2 | 1529 | Vigilancia e intervención operativa en incendios forestales |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.19. 121_0702 — Técnico en Seguridad

- Family: Seguridad y Medio Ambiente; qualification category: `fpgm`.
- Matching aliases: Seguridad.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/seguridad-medio-ambiente/tecnico-seguridad-gm.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0020 | Primeros auxilios |
| 1 | 1674 | Ordenamiento jurídico en seguridad |
| 1 | 1679 | Vigilancia y protección |
| 1 | 1677 | Acondicionamiento físico y defensa personal |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 1680 | Guarderío rural, cinegético, fluvial y marítimo |
| 2 | 1675 | Habilidades sociopersonales |
| 2 | 1676 | Tecnología aplicada a la seguridad |
| 2 | 1678 | Vigilancia e intervención operativa básica en incendios |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1711 | Inglés Profesional II (GM) |
| 2 | 1713 | Proyecto Intermodular |

### A.20. 121_0801 — Técnico en Mecanizado

- Family: Fabricación Mecánica; qualification category: `fpgm`.
- Matching aliases: Mecanizado.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/fabricacion-mecanica/mecanizado.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0007 | Interpretación gráfica |
| 1 | 0001 | Procesos de mecanizado |
| 1 | 0002 | Mecanizado por control numérico |
| 1 | 0004 | Fabricación por arranque de viruta |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 2 | 0156 | Inglés profesional (GM) |
| 2 | 0006 | Metrología y ensayos |
| 2 | 0005 | Sistemas automatizados |
| 2 | 0003 | Fabricación por abrasión, electroerosión, corte y conformado, y por procesos especiales |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.21. 121_0802 — Técnico en Soldadura y Calderería

- Family: Fabricación Mecánica; qualification category: `fpgm`.
- Matching aliases: Soldadura y Calderería.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/fabricacion-mecanica/soldadura-caldereria.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0007 | Interpretación gráfica |
| 1 | 0092 | Mecanizado |
| 1 | 0091 | Trazado, corte y conformado |
| 1 | 0093 | Soldadura en atmósfera natural |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0006 | Metrología y ensayos |
| 2 | 0095 | Montaje |
| 2 | 0094 | Soldadura en atmósfera protegida |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.22. 121_0803 — Técnico en Conformado por Moldeo de Metales y Polímeros

- Family: Fabricación Mecánica; qualification category: `fpgm`.
- Matching aliases: Conformado por Moldeo de Metales y Polímeros.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/fabricacion-mecanica/conformado-moldeo-metales-polimeros.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0007 | Interpretación gráfica |
| 1 | 0726 | Preparación de materias primas |
| 1 | 0722 | Preparación de máquinas e instalaciones de procesos automáticos |
| 1 | 0723 | Elaboración de moldes y modelos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0006 | Metrología y ensayos |
| 2 | 0724 | Conformado por moldeo cerrado |
| 2 | 0725 | Conformado por moldeo abierto |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.23. 121_0901 — Técnico en Instalaciones de Producción de Calor

- Family: Instalación y Mantenimiento; qualification category: `fpgm`.
- Matching aliases: Instalaciones de Producción de Calor.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/instalacion-mantenimiento/instalaciones-produccion-calor.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0036 | Máquinas y equipos térmicos |
| 1 | 0037 | Técnicas de montaje de instalaciones |
| 1 | 0038 | Instalaciones eléctricas y automatismos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0392 | Montaje y mantenimiento de instalaciones de energía solar |
| 2 | 0393 | Montaje y mantenimiento de instalaciones de gas y combustibles líquidos |
| 2 | 0266 | Configuración de instalaciones caloríficas |
| 2 | 0310 | Montaje y mantenimiento de instalaciones de agua |
| 2 | 0302 | Montaje y mantenimiento de instalaciones caloríficas |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.24. 121_0902 — Técnico en Instalaciones Frigoríficas y de Climatización

- Family: Instalación y Mantenimiento; qualification category: `fpgm`.
- Matching aliases: Instalaciones Frigoríficas y de Climatización.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/instalacion-mantenimiento/instalaciones-frigorificas-climatizacion.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0036 | Máquinas y equipos térmicos |
| 1 | 0037 | Técnicas de montaje de instalaciones |
| 1 | 0038 | Instalaciones eléctricas y automatismos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0039 | Configuración de instalaciones de frío y climatización |
| 2 | 0040 | Montaje y mantenimiento de equipos de refrigeración comercial |
| 2 | 0042 | Montaje y mantenimiento de instalaciones de climatización, ventilación y extracción |
| 2 | 0041 | Montaje y mantenimiento de instalaciones frigoríficas industriales |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.25. 121_0903 — Técnico en Mantenimiento Electromecánico

- Family: Instalación y Mantenimiento; qualification category: `fpgm`.
- Matching aliases: Mantenimiento Electromecánico.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/instalacion-mantenimiento/mantenimiento-electromecanico.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0950 | Técnicas de unión y montaje |
| 1 | 0949 | Técnicas de fabricación |
| 1 | 0952 | Automatismos neumáticos e hidráulicos |
| 1 | 0951 | Electricidad y automatismos eléctricos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0954 | Montaje y mantenimiento eléctrico-electrónico |
| 2 | 0953 | Montaje y mantenimiento mecánico |
| 2 | 0955 | Montaje y mantenimiento de líneas automatizadas |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.26. 121_1001 — Técnico en Instalaciones Eléctricas y Automáticas

- Family: Electricidad y Electrónica; qualification category: `fpgm`.
- Matching aliases: Instalaciones Eléctricas y Automáticas.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/electricidad-electronica/instalaciones-electricas-automaticas.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0233 | Electrónica |
| 1 | 0234 | Electrotecnia |
| 1 | 0232 | Automatismos industriales |
| 1 | 0235 | Instalaciones eléctricas interiores |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0239 | Instalaciones solares fotovoltaicas |
| 2 | 0236 | Instalaciones de distribución |
| 2 | 0240 | Máquinas eléctricas |
| 2 | 0238 | Instalaciones domóticas |
| 2 | 0237 | Infraestructuras comunes de telecomunicación en viviendas y edificios |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.27. 121_1002 — Técnico en Instalaciones de Telecomunicaciones

- Family: Electricidad y Electrónica; qualification category: `fpgm`.
- Matching aliases: Instalaciones de Telecomunicaciones.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/electricidad-electronica/instalaciones-telecomunicaciones.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0237 | Infraestructuras comunes de telecomunicación en viviendas y edificios |
| 1 | 0360 | Equipos microinformáticos |
| 1 | 0362 | Instalaciones eléctricas básicas |
| 1 | 0359 | Electrónica aplicada |
| 1 | 0361 | Infraestructuras de redes de datos y sistemas de telefonía |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0238 | Instalaciones domóticas |
| 2 | 0365 | Instalaciones de radiocomunicaciones |
| 2 | 0364 | Circuito cerrado de televisión y seguridad electrónica |
| 2 | 0363 | Instalaciones de megafonía y sonorización |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.28. 121_1101 — Técnico en Redes y Estaciones de Tratamiento de Aguas

- Family: Energía y Agua; qualification category: `fpgm`.
- Matching aliases: Redes y Estaciones de Tratamiento de Aguas.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/energia-agua/redes-estaciones-trata-aguas.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1559 | Replanteo en Redes de Agua |
| 1 | 1567 | Hidráulica y redes de agua |
| 1 | 1561 | Instalaciones eléctricas en redes de agua |
| 1 | 0310 | Montaje y mantenimiento de instalaciones de agua |
| 1 | 1562 | Técnicas de mecanizado y unión |
| 1 | 1565 | Construcción en redes y estaciones de tratamiento de agua |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 1568 | Mantenimiento de redes |
| 2 | 1564 | Calidad del agua |
| 2 | 1566 | Mantenimiento de equipos e instalaciones |
| 2 | 1560 | Estaciones de tratamiento de aguas |
| 2 | 1563 | Montaje y puesta en servicio de redes de agua |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.29. 121_1201 — Técnico en Carrocería

- Family: Transporte y Mantenimiento de Vehículos; qualification category: `fpgm`.
- Matching aliases: Carrocería.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/transporte-mantenimiento-vehiculos/carroceria.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0260 | Mecanizado básico |
| 1 | 0254 | Elementos amovibles |
| 1 | 0255 | Elementos metálicos y sintéticos |
| 1 | 0256 | Elementos fijos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0258 | Elementos estructurales del vehículo |
| 2 | 0257 | Preparación de superficies |
| 2 | 0259 | Embellecimiento de superficies |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.30. 121_1202 — Técnico en Electromecánica de Vehículos Automóviles

- Family: Transporte y Mantenimiento de Vehículos; qualification category: `fpgm`.
- Matching aliases: Electromecánica de Vehículos Automóviles.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/transporte-mantenimiento-vehiculos/electromecanica-vehiculos-automoviles.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0260 | Mecanizado básico |
| 1 | 0458 | Sistemas de seguridad y confortabilidad |
| 1 | 0452 | Motores |
| 1 | 0456 | Sistemas de carga y arranque |
| 1 | 0454 | Circuitos de fluidos. Suspensión y dirección |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0457 | Circuitos eléctricos auxiliares del vehículo |
| 2 | 0455 | Sistemas de transmisión y frenado |
| 2 | 0453 | Sistemas auxiliares del motor |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.31. 121_1203 — Técnico en Electromecánica de Maquinaria

- Family: Transporte y Mantenimiento de Vehículos; qualification category: `fpgm`.
- Matching aliases: Electromecánica de Maquinaria.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/transporte-mantenimiento-vehiculos/electromecanica-maquinaria.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0260 | Mecanizado básico |
| 1 | 0717 | Equipos y aperos |
| 1 | 0718 | Circuitos eléctricos, electrónicos y de confortabilidad |
| 1 | 0742 | Sistemas auxiliares del motor diésel |
| 1 | 0452 | Motores |
| 1 | 0456 | Sistemas de carga y arranque |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0715 | Sistemas de fuerza y detención |
| 2 | 0716 | Sistemas de accionamiento de equipos y aperos |
| 2 | 0714 | Sistemas de suspensión y guiado |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.32. 121_1204 — Técnico en Conducción de Vehículos de Transporte por Carretera

- Family: Transporte y Mantenimiento de Vehículos; qualification category: `fpgm`.
- Matching aliases: Conducción de Vehículos de Transporte por Carretera.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/transporte-mantenimiento-vehiculos/conduccion-vehiculos-transporte-carretera.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1210 | Mantenimiento básico de vehículos |
| 1 | 1206 | Entorno normativo, económico y social del transporte |
| 1 | 1209 | Operaciones de almacenaje |
| 1 | 1204 | Conducción inicial |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0020 | Primeros auxilios |
| 2 | 1208 | Servicios de transporte de viajeros |
| 2 | 1207 | Servicios de transporte de mercancias |
| 2 | 1205 | Conducción racional y segura |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1711 | Inglés Profesional II (GM) |
| 2 | 1713 | Proyecto Intermodular |

### A.33. 121_1205 — Técnico en Mantenimiento de Material Rodante Ferroviario

- Family: Transporte y Mantenimiento de Vehículos; qualification category: `fpgm`.
- Matching aliases: Mantenimiento de Material Rodante Ferroviario.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/transporte-mantenimiento-vehiculos/mnto-material-rodante-ferroviario.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0260 | Mecanizado básico |
| 1 | 0974 | Sistemas de frenos en material rodante ferroviario |
| 1 | 0976 | Sistemas lógicos de material rodante ferroviario |
| 1 | 0977 | Confortabilidad y climatización |
| 1 | 0452 | Motores |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0978 | Bogie, tracción y choque |
| 2 | 0742 | Sistemas auxiliares del motor diésel |
| 2 | 0975 | Circuitos auxiliares |
| 2 | 0973 | Tracción eléctrica |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.34. 121_1206 — Técnico en Montaje de Estructuras e Instalación de Sistemas Aeronáuticos

- Family: Fabricación Mecánica; qualification category: `fpgm`.
- Matching aliases: Montaje de Estructuras e Instalación de Sistemas Aeronáuticos.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/fabricacion-mecanica/montaje-de-estr-e-instalacion-de-sist-aeronauticos.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1445 | Preparación y sellado de superficies |
| 1 | 0260 | Mecanizado básico |
| 1 | 0801 | Montaje estructural aeronáutico |
| 1 | 1444 | Instalaciones eléctricas y electrónicas |
| 1 | 1599 | Sistemas mecánicos y de fluidos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 1600 | Protección y pintado de aeronaves |
| 2 | 1601 | Sistemas de distribución de corriente, telecomunicaciones y aviónica |
| 2 | 1602 | Sistemas de mandos de vuelo, trenes de aterrizaje y de propulsión |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.35. 121_1208 — Técnico en Mantenimiento de Embarcaciones de Recreo

- Family: Transporte y Mantenimiento de Vehículos; qualification category: `fpgm`.
- Matching aliases: Mantenimiento de Embarcaciones de Recreo.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/transporte-mantenimiento-vehiculos/mtmo-embarcaciones-recreo.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1618 | Preparación de embarcaciones de recreo para trabajos de mantenimiento |
| 1 | 1622 | Tratamientos superficiales y pintado de embarcaciones de recreo |
| 1 | 1620 | Mantenimiento de sistemas de refrigeración y de climatización en embarcaciones de recreo |
| 1 | 0260 | Mecanizado básico |
| 1 | 1621 | Mantenimiento de superficies y elementos de materiales compuestos de embarcaciones de recreo |
| 1 | 1619 | Mantenimiento del sistema de propulsión y equipos auxiliares de las embarcaciones de recreo |
| 1 | 1175 | Mantenimiento de las instalaciones y máquinas eléctricas en buques y embarcaciones |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 1625 | Mantenimiento de cubiertas de madera y adaptación/reparación de mobiliario en embarcaciones de recreo |
| 2 | 1623 | Mantenimiento de instalaciones de equipos electrónicos e informáticos de embarcaciones de recreo |
| 2 | 1624 | Mantenimiento de aparejos de embarcaciones de recreo |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1711 | Inglés Profesional II (GM) |
| 2 | 1713 | Proyecto Intermodular |

### A.36. 121_1301 — Técnico en Piedra Natural

- Family: Industrias Extractivas; qualification category: `fpgm`.
- Matching aliases: Piedra Natural.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/industrias-extractivas/piedra-natural.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0887 | Corte de bloques |
| 1 | 0892 | Conocimiento y extracción de la piedra |
| 1 | 0895 | Tecnologías de mecanizado en piedra natural |
| 1 | 0889 | Elaboración de piezas |
| 1 | 0890 | Modelos en obras de piedra |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0891 | Comercialización de obras de piedra |
| 2 | 0888 | Tratamientos superficiales |
| 2 | 0893 | Talla y montaje de piedra natural |
| 2 | 0894 | Restauración de piedra natural |
| 2 | 0896 | Montaje de piedra natural |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.37. 121_1302 — Técnico en Excavaciones y Sondeos

- Family: Industrias Extractivas; qualification category: `fpgm`.
- Matching aliases: Excavaciones y Sondeos.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/industrias-extractivas/excavaciones-sondeos.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1078 | Estabilización de taludes |
| 1 | 0847 | Sondeos |
| 1 | 1081 | Operación y manejo de maquinaria de excavación |
| 1 | 0850 | Trabajos geotécnicos |
| 1 | 0881 | Perforaciones |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 1031 | Técnicas de voladuras |
| 2 | 1077 | Sostenimiento |
| 2 | 1080 | Operaciones de carga y transporte en excavaciones |
| 2 | 1079 | Excavaciones con arranque selectivo |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.38. 121_1401 — Técnico en Construcción

- Family: Edificación y Obra Civil; qualification category: `fpgm`.
- Matching aliases: Construcción.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/edificacion-obra-civil/construccion.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0996 | Interpretación de planos de construcción |
| 1 | 0998 | Revestimientos |
| 1 | 0995 | Construcción |
| 1 | 1000 | Hormigón armado |
| 1 | 0999 | Encofrados |
| 1 | 0997 | Fábricas |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 1005 | Impermeabilizaciones y aislamientos |
| 2 | 1002 | Obras de urbanización |
| 2 | 1001 | Organización de trabajos de construcción |
| 2 | 1004 | Cubiertas |
| 2 | 1003 | Solados, alicatados y chapados |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.39. 121_1402 — Técnico en Obras de Interior, Decoración y Rehabilitación

- Family: Edificación y Obra Civil; qualification category: `fpgm`.
- Matching aliases: Obras de Interior, Decoración y Rehabilitación.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/edificacion-obra-civil/obras-interior-decoracion-rehabilitacion.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0996 | Interpretación de planos de construcción |
| 1 | 0995 | Construcción |
| 1 | 1194 | Revestimientos continuos |
| 1 | 1195 | Particiones prefabricadas |
| 1 | 1197 | Techos suspendidos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 1003 | Solados, alicatados y chapados |
| 2 | 1196 | Mamparas y suelos técnicos |
| 2 | 1198 | Revestimientos ligeros |
| 2 | 1200 | Organización de trabajos de interior, decoración y rehabilitación |
| 2 | 1199 | Pintura decorativa en construcción |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.40. 121_1501 — Técnico en Fabricación de Productos Cerámicos

- Family: Vidrio y Cerámica; qualification category: `fpgm`.
- Matching aliases: Fabricación de Productos Cerámicos.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/vidrio-ceramica/fabr-productos-ceramicos.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0116 | Principios de mantenimiento electromecánico |
| 1 | 0418 | Procesos de fabricación de fritas y pigmentos cerámicos |
| 1 | 0417 | Procesos de fabricación de pastas cerámicas |
| 1 | 0419 | Procesos de preparación de esmaltes cerámicos |
| 1 | 0420 | Procesos de fabricación de productos cerámicos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0421 | Normativa cerámica |
| 2 | 0422 | Control de materiales y procesos cerámicos |
| 2 | 0423 | Técnicas y ensayos de desarrollo de productos |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.41. 121_1601 — Técnico en Carpintería y Mueble

- Family: Madera, Mueble y Corcho; qualification category: `fpgm`.
- Matching aliases: (none).
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/madera-mueble-corcho/carpinteria-mueble.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0542 | Control de almacén |
| 1 | 0538 | Materiales en carpintería y mueble |
| 1 | 0539 | Soluciones constructivas |
| 1 | 0541 | Operaciones básicas de mobiliario |
| 1 | 0540 | Operaciones básicas de carpintería |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0546 | Montaje de carpintería y mueble |
| 2 | 0547 | Acabados en carpintería y mueble |
| 2 | 0545 | Mecanizado por control numérico en carpintería y mueble |
| 2 | 0543 | Documentación técnica |
| 2 | 0544 | Mecanizado de madera y derivados |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.42. 121_1602 — Técnico en Instalación y Amueblamiento

- Family: Madera, Mueble y Corcho; qualification category: `fpgm`.
- Matching aliases: Instalación y Amueblamiento.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/madera-mueble-corcho/instalacion-amueblamiento.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0542 | Control de almacén |
| 1 | 0538 | Materiales en carpintería y mueble |
| 1 | 0539 | Soluciones constructivas |
| 1 | 0541 | Operaciones básicas de mobiliario |
| 1 | 0540 | Operaciones básicas de carpintería |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0781 | Instalación de estructuras de madera |
| 2 | 0778 | Planificación de la instalación |
| 2 | 0779 | Instalación de mobiliario |
| 2 | 0780 | Instalación de carpintería |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.43. 121_1701 — Técnico en Confección y Moda

- Family: Textil, Confección y Piel; qualification category: `fpgm`.
- Matching aliases: Confección y Moda.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/textil-confeccion-piel/confeccion-moda.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0264 | Moda y tendencias |
| 1 | 0116 | Principios de mantenimiento electromecánico |
| 1 | 0275 | Materias textiles y piel |
| 1 | 0267 | Corte de materiales |
| 1 | 0269 | Confección industrial |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0271 | Información y atención al cliente |
| 2 | 0265 | Patrones |
| 2 | 0270 | Acabados en confección |
| 2 | 0268 | Confección a medida |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.44. 121_1702 — Técnico en Calzado y Complementos de Moda

- Family: Textil, Confección y Piel; qualification category: `fpgm`.
- Matching aliases: Calzado y Complementos de Moda.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/textil-confeccion-piel/calzado-complementos-moda.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0611 | Calzado y tendencias |
| 1 | 0116 | Principios de mantenimiento electromecánico |
| 1 | 0275 | Materias textiles y piel |
| 1 | 0267 | Corte de materiales |
| 1 | 0269 | Confección industrial |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional(GM) |
| 2 | 0605 | Procesos de elaboración de calzado a medida |
| 2 | 0603 | Montado y acabado de artículos de marroquinería |
| 2 | 0607 | Transformación de calzado para espectáculos |
| 2 | 0604 | Montado y acabado de calzado |
| 2 | 0606 | Técnicas de fabricación de calzado a medida y ortopédico |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.45. 121_1703 — Técnico en Fabricación y Ennoblecimiento de Productos Textiles

- Family: Textil, Confección y Piel; qualification category: `fpgm`.
- Matching aliases: Fabricación y Ennoblecimiento de Productos Textiles.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/textil-confeccion-piel/fabr-ennoblecimiento-prod-textiles.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0116 | Principios de mantenimiento electromecánico |
| 1 | 1040 | Aplicación de aprestos |
| 1 | 0275 | Materias textiles y piel |
| 1 | 1045 | Técnicas de tejeduría de calada |
| 1 | 1042 | Preparación y tintura |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 1046 | Técnicas de tejeduría de punto por recogida |
| 2 | 1047 | Técnicas de tejeduría de punto por urdimbre |
| 2 | 1041 | Acabados textiles |
| 2 | 1044 | Fabricación de hilatura y telas no tejidas |
| 2 | 1043 | Estampación |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.46. 121_1801 — Técnico en Postimpresión y Acabados Gráficos

- Family: Artes Gráficas; qualification category: `fpgm`.
- Matching aliases: Postimpresión y Acabados Gráficos.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/artes-graficas/postimpresion-acabados-graficos.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1215 | Encuadernación en grapa |
| 1 | 1222 | Formación de envases |
| 1 | 1218 | Materiales para postimpresión |
| 1 | 1220 | Elaboración de tapas y archivadores |
| 1 | 1214 | Guillotinado y plegado |
| 1 | 1217 | Troquelado |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 1221 | Tratamiento superficial del impreso |
| 2 | 0879 | Impresión en flexografía |
| 2 | 1216 | Encuadernación en rústica y tapa dura |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.47. 121_1802 — Técnico en Preimpresión Digital

- Family: Artes Gráficas; qualification category: `fpgm`.
- Matching aliases: Preimpresión Digital.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/artes-graficas/preimpresion-digital.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0871 | Identificación de materiales en preimpresión |
| 1 | 0866 | Tratamiento de textos |
| 1 | 0872 | Ensamblado de publicaciones electrónicas |
| 1 | 0867 | Tratamiento de imagen en mapa de bits |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0873 | Ilustración vectorial |
| 2 | 0868 | Imposición y obtención digital de la forma impresora |
| 2 | 0870 | Compaginación |
| 2 | 0869 | Impresión digital |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.48. 121_1803 — Técnico en Impresión Gráfica

- Family: Artes Gráficas; qualification category: `fpgm`.
- Matching aliases: Impresión Gráfica.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/artes-graficas/impresion-grafica.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0883 | Impresión en bajorrelieve |
| 1 | 0880 | Impresión en serigrafía |
| 1 | 0882 | Preparación de materiales para impresión |
| 1 | 0877 | Preparación y regulación de máquinas offset |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0878 | Desarrollo de la tirada offset |
| 2 | 0879 | Impresión en flexografía |
| 2 | 0869 | Impresión digital |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.49. 121_1901 — Técnico en Vídeo Disc-Jockey y Sonido

- Family: Imagen y Sonido; qualification category: `fpgm`.
- Matching aliases: Vídeo Disc-Jockey y Sonido.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/imagen-sonido/video-discjockey-sonido.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1299 | Captación y grabación de sonido |
| 1 | 1304 | Toma y edición digital de imagen |
| 1 | 1298 | Instalación y montaje de equipos de sonido |
| 1 | 1301 | Preparación de sesiones de vídeo disc-jockey |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 1303 | Animación visual en vivo |
| 2 | 1302 | Animación musical en vivo |
| 2 | 1300 | Control, edición y mezcla de sonido |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.50. 121_2001 — Técnico en Sistemas Microinformáticos y Redes

- Family: Informática y Comunicaciones; qualification category: `fpgm`.
- Matching aliases: Sistemas Microinformáticos y Redes.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/informatica-comunicaciones/sistemas-microniformaticos-redes.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0222 | Sistemas operativos monopuesto |
| 1 | 0225 | Redes locales |
| 1 | 0221 | Montaje y mantenimiento de equipo |
| 1 | 0223 | Aplicaciones ofimáticas |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0228 | Aplicaciones web |
| 2 | 0226 | Seguridad informática |
| 2 | 0227 | Servicios en red |
| 2 | 0224 | Sistemas operativos en red |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.51. 121_2101 — Técnico en Gestión Administrativa

- Family: Administración y Gestión; qualification category: `fpgm`.
- Matching aliases: Gestión Administrativa.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/administracion-gestion/gestion-administrativa.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0439 | Empresa y Administración |
| 1 | 0441 | Técnica contable |
| 1 | 0438 | Operaciones administrativas de compra-venta |
| 1 | 0437 | Comunicación empresarial y atención al cliente |
| 1 | 0440 | Tratamiento informático de la información |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0443 | Tratamiento de la documentación contable |
| 2 | 0442 | Operaciones administrativas de recursos humanos |
| 2 | 0448 | Operaciones auxiliares de gestión de tesorería |
| 2 | 0446 | Empresa en el aula |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1711 | Inglés Profesional II (GM) |
| 2 | 1713 | Proyecto Intermodular |

### A.52. 121_2201 — Técnico en Actividades Comerciales

- Family: Comercio y Marketing; qualification category: `fpgm`.
- Matching aliases: Actividades Comerciales.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/comercio-marketing/actividades-comerciales.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1234 | Servicios de atención comercial |
| 1 | 1229 | Gestión de compras |
| 1 | 1233 | Aplicaciones informáticas para el comercio |
| 1 | 1226 | Marketing en la actividad comercial |
| 1 | 1231 | Dinamización del punto de venta |
| 1 | 1232 | Procesos de venta |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 1228 | Técnicas de almacén |
| 2 | 1230 | Venta técnica |
| 2 | 1235 | Comercio electrónico |
| 2 | 1227 | Gestión de un pequeño comercio |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1711 | Inglés Profesional II (GM) |
| 2 | 1713 | Proyecto Intermodular |

### A.53. 121_2202 — Técnico en Comercialización de Productos Alimentarios

- Family: Comercio y Marketing; qualification category: `fpgm`.
- Matching aliases: Comercialización de Productos Alimentarios.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/comercio-marketing/comercializacion-productos-alimentarios.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1606 | Constitución de pequeños negocios alimentarios |
| 1 | 1608 | Dinamización del punto de venta en comercios de alimentación |
| 1 | 1607 | Mercadotecnia del comercio alimentario |
| 1 | 1610 | Seguridad y calidad alimentaria en el comercio |
| 1 | 1614 | Ofimática aplicada al comercio alimentario |
| 1 | 1609 | Atención comercial en negocios alimentarios |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 1615 | Gestión de un comercio alimentario |
| 2 | 1611 | Preparación y acondicionamiento de productos frescos y transformados |
| 2 | 1613 | Comercio electrónico en negocios alimentarios |
| 2 | 1612 | Logística de productos alimentarios |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1711 | Inglés Profesional II (GM) |
| 2 | 1713 | Proyecto Intermodular |

### A.54. 121_2301 — Técnico en Atención a Personas en Situación de Dependencia

- Family: Servicios Socioculturales y a la Comunidad; qualification category: `fpgm`.
- Matching aliases: Atención a Personas en Situación de Dependencia.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/servicios-socioculturales-comunidad/atencion-personas-situacion-dependencia.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0020 | Primeros auxilios |
| 1 | 0217 | Atención higiénica |
| 1 | 0210 | Organización de la atención a las personas en situación de dependencia |
| 1 | 0213 | Atención y apoyo psicosocial |
| 1 | 0214 | Apoyo a la comunicación |
| 1 | 0215 | Apoyo domiciliario |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0831 | Teleasistencia |
| 2 | 0211 | Destrezas sociales |
| 2 | 0212 | Características y necesidades de las personas en situación de dependencia |
| 2 | 0216 | Atención sanitaria |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.55. 121_2402 — Técnico en Servicios en Restauración

- Family: Hostelería y Turismo; qualification category: `fpgm`.
- Matching aliases: Servicios en Restauración.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/hosteleria-turismo/servicios-restauracion.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0031 | Seguridad e higiene en la manipulación de alimentos |
| 1 | 0155 | Técnicas de comunicación en restauración |
| 1 | 0150 | Operaciones básicas en bar-cafetería |
| 1 | 0151 | Operaciones básicas en restaurante |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0045 | Ofertas gastronómicas |
| 2 | 0154 | El vino y su servicio |
| 2 | 0152 | Servicios en bar-cafetería |
| 2 | 0153 | Servicios en restaurante y eventos especiales |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1711 | Inglés Profesional II (GM) |
| 2 | 1713 | Proyecto Intermodular |

### A.56. 121_2501 — Técnico en Actividades Ecuestres

- Family: Actividades Físicas y Deportivas; qualification category: `fpgm`.
- Matching aliases: Actividades Ecuestres.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/actividades-fisicas-deportivas/actividades-ecuestres.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1329 | Organización de itinerarios |
| 1 | 1319 | Alimentación, manejo general y primeros auxilios de équidos |
| 1 | 1320 | Mantenimiento físico, cuidados e higiene equina |
| 1 | 1321 | Reproducción, cría y recría de équidos |
| 1 | 1325 | Técnicas de equitación |
| 1 | 1323 | Desbrave y doma a la cuerda de potros |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0020 | Primeros auxilios |
| 2 | 1328 | Atención a grupos |
| 2 | 1326 | Exhibiciones y concursos de ganado equino |
| 2 | 1327 | Guía ecuestre |
| 2 | 1322 | Herrado de équidos |
| 2 | 1324 | Adiestramiento |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.57. 121_2502 — Técnico en Guía en el Medio Natural y de Tiempo Libre

- Family: Actividades Físicas y Deportivas; qualification category: `fpgm`.
- Matching aliases: Guía en el Medio Natural y de Tiempo Libre.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/actividades-fisicas-deportivas/guia-medio-natural-tiempo-libre.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1328 | Atención a grupos |
| 1 | 1336 | Técnicas de natación |
| 1 | 1329 | Organización de itinerarios |
| 1 | 1335 | Técnicas de tiempo libre |
| 1 | 1334 | Guía de bicicleta |
| 1 | 1325 | Técnicas de equitación |
| 1 | 1333 | Guía de baja y media montaña |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 1327 | Guía ecuestre |
| 2 | 1338 | Guía en el medio natural acuático |
| 2 | 1337 | Socorrismo en el medio natural |
| 2 | 1339 | Maniobras con cuerdas |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.58. 122_0101 — Técnico Superior en Gestión Forestal y del Medio Natural

- Family: Agraria; qualification category: `fpgs`.
- Matching aliases: Gestión Forestal y del Medio Natural.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/agraria/gestion-forestal-medio-natural.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0690 | Botánica agronómica |
| 1 | 0692 | Fitopatología |
| 1 | 0811 | Gestión y organización del vivero forestal |
| 1 | 0812 | Gestión cinegética |
| 1 | 0816 | Defensa contra incendios forestales |
| 1 | 0810 | Gestión de los aprovechamientos del medio forestal |
| 1 | 0694 | Maquinaria e instalaciones agroforestales |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0790 | Técnicas de educación ambiental |
| 2 | 0693 | Topografía agraria |
| 2 | 0813 | Gestión de la pesca continental |
| 2 | 0815 | Gestión de la conservación del medio natural |
| 2 | 0814 | Gestión de montes |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0817 | Proyecto Intermodular |

### A.59. 122_0102 — Técnico Superior en Paisajismo y Medio Rural

- Family: Agraria; qualification category: `fpgs`.
- Matching aliases: Paisajismo y Medio Rural.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/agraria/paisajismo-medio-rural.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0693 | Topografía agraria |
| 1 | 0690 | Botánica agronómica |
| 1 | 0692 | Fitopatología |
| 1 | 0697 | Diseño de jardines y restauración del paisaje |
| 1 | 0694 | Maquinaria e instalaciones agroforestales |
| 1 | 0691 | Gestión y organización del vivero |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0698 | Conservación de jardines y céspedes deportivos |
| 2 | 0695 | Planificación de cultivos |
| 2 | 0696 | Gestión de cultivos |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0699 | Proyecto Intermodular |

### A.60. 122_0103 — Técnico Superior en Ganadería y Asistencia en Sanidad Animal

- Family: Agraria; qualification category: `fpgs`.
- Matching aliases: Ganadería y Asistencia en Sanidad Animal.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/agraria/ganaderia-asistencia-sanidad-animal.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1278 | Maquinaria e instalaciones ganaderas |
| 1 | 1276 | Gestión de la recría de caballos |
| 1 | 1281 | Bioseguridad |
| 1 | 1275 | Gestión de la producción animal |
| 1 | 1274 | Organización y control de la reproducción y cría |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1282 | Gestión de centros veterinarios |
| 2 | 1280 | Asistencia a la atención veterinaria |
| 2 | 1279 | Saneamiento ganadero |
| 2 | 1277 | Organización y supervisión de la doma y manejo de équidos |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1283 | Proyecto Intermodular |

### A.61. 122_0201 — Técnico Superior en Transporte Marítimo y Pesca de Altura

- Family: Marítimo Pesquera; qualification category: `fpgs`.
- Matching aliases: Transporte Marítimo y Pesca de Altura.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/maritimo-pesquera/transporte-maritimo-pesca-altura.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0800 | Control de las emergencias |
| 1 | 0803 | Administración y gestión del buque y de la actividad pesquera |
| 1 | 0798 | Maniobra y estiba |
| 1 | 0799 | Navegación, gobierno y comunicaciones del buque |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0802 | Organización de la asistencia sanitaria a bordo |
| 2 | 0805 | Pesca de altura y gran altura |
| 2 | 0804 | Guardia de puente |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1712 | Inglés Profesional II (GS) |
| 2 | 0808 | Proyecto Intermodular |

### A.62. 122_0202 — Técnico Superior en Acuicultura

- Family: Marítimo Pesquera; qualification category: `fpgs`.
- Matching aliases: Acuicultura.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/maritimo-pesquera/acuicultura.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1020 | Técnicas analíticas y métodos de control sanitario en acuicultura |
| 1 | 1015 | Técnicas y gestión de la producción de cultivos auxiliares |
| 1 | 1019 | Instalaciones, innovación y sistemas de automatización en acuicultura |
| 1 | 1016 | Técnicas y gestión de la producción de peces |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1022 | Acuarofilia |
| 2 | 1018 | Técnicas y gestión de la producción de crustáceos |
| 2 | 1021 | Gestión medioambiental de los procesos acuícolas |
| 2 | 1017 | Técnicas y gestión de la producción de moluscos |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1025 | Proyecto Intermodular |

### A.63. 122_0203 — Técnico Superior en Organización del Mantenimiento de Maquinaria de Buques y Embarcaciones

- Family: Marítimo Pesquera; qualification category: `fpgs`.
- Matching aliases: Organización del Mantenimiento de Maquinaria de Buques y Embarcaciones.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/maritimo-pesquera/org-mnto-maquinaria-buques-embarcaciones.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0800 | Control de las emergencias |
| 1 | 1308 | Organización del mantenimiento de planta propulsora y maquinaria auxiliar de buques |
| 1 | 1309 | Organización del mantenimiento en seco de buques y embarcaciones y montaje de motores térmicos |
| 1 | 1312 | Organización del mantenimiento y montaje de instalaciones frigoríficas y sistemas de climatización de buques y embarcaciones |
| 1 | 1314 | Organización de la guardia de máquinas |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0802 | Organización de la asistencia sanitaria a bordo |
| 2 | 1310 | Programación y mantenimiento de automatismos hidráulicos y neumáticos en buques y embarcaciones |
| 2 | 1311 | Organización del mantenimiento y montaje de instalaciones y sistemas eléctricos de buques y embarcaciones |
| 2 | 1313 | Planificación del mantenimiento de maquinaria de buques y embarcaciones |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1712 | Inglés Profesional II (GS) |
| 2 | 1315 | Proyecto Intermodular |

### A.64. 122_0301 — Técnico Superior en Vitivinicultura

- Family: Industrias Alimentarias; qualification category: `fpgs`.
- Matching aliases: Vitivinicultura.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/industrias-alimentarias/vitivinicultura.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0085 | Legislación vitivinícola y seguridad alimentaria |
| 1 | 0077 | Viticultura |
| 1 | 0079 | Procesos bioquímicos |
| 1 | 0081 | Análisis enológico |
| 1 | 0078 | Vinificaciones |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0083 | Cata y cultura vitivinícola |
| 2 | 0084 | Comercialización y logística en la industria alimentaria |
| 2 | 0086 | Gestión de calidad y ambiental en la industria alimentaria |
| 2 | 0082 | Industrias derivadas |
| 2 | 0080 | Estabilización, crianza y envasado |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0087 | Proyecto Intermodular |

### A.65. 122_0302 — Técnico Superior en Procesos y Calidad en la Industria Alimentaria

- Family: Industrias Alimentarias; qualification category: `fpgs`.
- Matching aliases: Procesos y Calidad en la Industria Alimentaria.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/industrias-alimentarias/procesos-calidad-industria-alimentaria.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0466 | Organización de la producción alimentaria |
| 1 | 0464 | Análisis de alimentos |
| 1 | 0191 | Mantenimiento electromecánico en industrias de proceso |
| 1 | 0463 | Biotecnología alimentaria |
| 1 | 0462 | Tecnología alimentaria |
| 1 | 0465 | Tratamientos de preparación y conservación de los alimentos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0467 | Control microbiológico y sensorial de los alimentos |
| 2 | 0084 | Comercialización y logística en la industria alimentaria |
| 2 | 0468 | Nutrición y Seguridad Alimentaria |
| 2 | 0470 | Innovación alimentaria |
| 2 | 0086 | Gestión de calidad y ambiental en la industria alimentaria |
| 2 | 0469 | Procesos integrados en la industria alimentaria |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0471 | Proyecto Intermodular |

### A.66. 122_0401 — Técnico Superior en Laboratorio de Análisis y de Control de Calidad

- Family: Química; qualification category: `fpgs`.
- Matching aliases: Laboratorio de Análisis y de Control de Calidad.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/quimica/lab-analisis-control-calidad.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0069 | Ensayos fisicoquímicos |
| 1 | 0070 | Ensayos microbiológicos |
| 1 | 0065 | Muestreo y preparación de la muestra |
| 1 | 0066 | Análisis químicos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0072 | Calidad y seguridad en el laboratorio |
| 2 | 0068 | Ensayos físicos |
| 2 | 0071 | Ensayos biotecnológicos |
| 2 | 0067 | Análisis instrumental |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0073 | Proyecto Intermodular |

### A.67. 122_0402 — Técnico Superior en Química Industrial

- Family: Química; qualification category: `fpgs`.
- Matching aliases: Química Industrial.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/quimica/quimica-industrial.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0192 | Formulación y preparación de mezclas |
| 1 | 0187 | Generación y recuperación de energía |
| 1 | 0188 | Operaciones básicas en la industria química |
| 1 | 0190 | Regulación y control de proceso químico |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0194 | Prevención de riesgos en industrias químicas |
| 2 | 0185 | Organización y gestión en industrias químicas |
| 2 | 0193 | Acondicionado y almacenamiento de productos químicos |
| 2 | 0186 | Transporte de sólidos y fluidos |
| 2 | 0189 | Reactores químicos |
| 2 | 0191 | Mantenimiento electromecánico en industrias de proceso |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0195 | Proyecto Intermodular |

### A.68. 122_0403 — Técnico Superior en Fabricación de Productos Farmacéuticos, Biotecnológicos y Afines

- Family: Química; qualification category: `fpgs`.
- Matching aliases: Fabricación de Productos Farmacéuticos, Biotecnológicos y Afines.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/quimica/fab-prod-farmaceuticos-biotec-afines.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1387 | Organización y gestión de la fabricación de productos farmacéuticos, biotecnológicos y afines |
| 1 | 1391 | Seguridad en la industria farmacéutica, biotecnológica y afines |
| 1 | 1389 | Operaciones básicas en la industria farmacéutica, biotecnológica y afines |
| 1 | 1390 | Principios de biotecnología |
| 1 | 1392 | Áreas y servicios auxiliares en la industria farmacéutica, biotecnológica y afines |
| 1 | 1388 | Control de calidad de productos farmacéuticos, biotecnológicos y afines |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1396 | Acondicionamiento y almacenamiento de productos farmacéuticos, biotecnológicos y afines |
| 2 | 1395 | Regulación y control en la industria farmacéutica, biotecnológica y afines |
| 2 | 1393 | Técnicas de producción biotecnológica |
| 2 | 0191 | Mantenimiento electromecánico en industrias de proceso |
| 2 | 1394 | Técnicas de producción farmacéutica y afines |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1397 | Proyecto Intermodular |

### A.69. 122_0501 — Técnico Superior en Estética Integral y Bienestar

- Family: Imagen Personal; qualification category: `fpgs`.
- Matching aliases: Estética Integral y Bienestar.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/imagen-personal/estetica-integral-bienestar.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0749 | Micropigmentación |
| 1 | 0750 | Procesos fisiológicos y de higiene en imagen personal |
| 1 | 0751 | Dermoestética |
| 1 | 0744 | Aparatología estética |
| 1 | 0752 | Cosmética aplicada a estética y bienestar |
| 1 | 0753 | Tratamientos estéticos integrales |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0745 | Estética hidrotermal |
| 2 | 0746 | Depilación avanzada |
| 2 | 0747 | Masaje estético |
| 2 | 0748 | Drenaje estético y técnicas por presión |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0754 | Proyecto Intermodular |

### A.70. 122_0502 — Técnico Superior en Estilismo y Dirección de Peluquería

- Family: Imagen Personal; qualification category: `fpgs`.
- Matching aliases: Estilismo y Dirección de Peluquería.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/imagen-personal/estilismo-direccion-peluqueria.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1070 | Estudio de la imagen |
| 1 | 1064 | Dermotricología |
| 1 | 1065 | Recursos técnicos y cosméticos |
| 1 | 1067 | Procedimientos y técnicas de peluquería |
| 1 | 1071 | Dirección y comercialización |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0750 | Procesos fisiológicos y de higiene en imagen personal |
| 2 | 1072 | Peluquería en cuidados especiales |
| 2 | 1066 | Tratamientos capilares |
| 2 | 1068 | Peinados para producciones audiovisuales y de moda |
| 2 | 1069 | Estilismo en peluquería |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1073 | Proyecto Intermodular |

### A.71. 122_0503 — Técnico Superior en Asesoría de Imagen Personal y Corporativa

- Family: Imagen Personal; qualification category: `fpgs`.
- Matching aliases: Asesoría de Imagen Personal y Corporativa.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/imagen-personal/asesoria-imagen-personal-corporativa.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1181 | Asesoría cosmética |
| 1 | 1182 | Diseño de imagen integral |
| 1 | 1187 | Asesoría estética |
| 1 | 1184 | Asesoría de peluquería |
| 1 | 1183 | Estilismo en vestuario y complementos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional(GS) |
| 2 | 1186 | Usos sociales |
| 2 | 1188 | Habilidades comunicativas |
| 2 | 1189 | Imagen corporativa |
| 2 | 1071 | Dirección y comercialización |
| 2 | 1185 | Protocolo y organización de eventos |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1190 | Proyecto Intermodular |

### A.72. 122_0504 — Técnico Superior en Caracterización y Maquillaje Profesional

- Family: Imagen Personal; qualification category: `fpgs`.
- Matching aliases: Caracterización y Maquillaje Profesional.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/imagen-personal/caracterizacion-maquillaje-profesional.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1269 | Productos de caracterización y maquillaje |
| 1 | 1262 | Maquillaje profesional |
| 1 | 1268 | Diseño gráfico aplicado |
| 1 | 1266 | Posticería |
| 1 | 1264 | Creación de prótesis faciales y corporales |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1263 | Efectos especiales a través del maquillaje |
| 2 | 1267 | Diseño digital de personajes 2D 3D |
| 2 | 1265 | Peluquería para caracterización |
| 2 | 0685 | Planificación y proyectos |
| 2 | 1261 | Caracterización de personajes |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1270 | Proyecto Intermodular |

### A.73. 122_0505 — Técnico Superior en Termalismo y Bienestar

- Family: Imagen Personal; qualification category: `fpgs`.
- Matching aliases: Termalismo y Bienestar.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/imagen-personal/termalismo-bienestar.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0212 | Características y necesidades de las personas en situación de dependencia |
| 1 | 1123 | Actividades de ocio y tiempo libre |
| 1 | 1136 | Valoración de la condición física e intervención en accidentes |
| 1 | 0747 | Masaje estético |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1124 | Dinamización grupal |
| 2 | 1152 | Técnicas de hidrocinesia |
| 2 | 0745 | Estética hidrotermal |
| 2 | 1151 | Acondicionamiento físico en el agua |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1712 | Inglés Profesional II (GS) |
| 2 | 1647 | Proyecto Intermodular |

### A.74. 122_0601 — Técnico Superior en Audiología Protésica

- Family: Sanidad; qualification category: `fpgs`.
- Matching aliases: Audiología Protésica.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/sanidad/audiologia-protesica.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0204 | Atención al hipoacúsico |
| 1 | 0201 | Acústica y elementos de protección sonora |
| 1 | 0200 | Tecnología electrónica en audioprótesis |
| 1 | 0199 | Características anatomosensoriales auditivas |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0205 | Audición y comunicación verbal |
| 2 | 0202 | Elaboración de moldes y protectores auditivos |
| 2 | 0203 | Elección y adaptación de prótesis auditivas |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0206 | Proyecto Intermodular |

### A.75. 122_0602 — Técnico Superior en Prótesis Dentales

- Family: Sanidad; qualification category: `fpgs`.
- Matching aliases: Prótesis Dentales.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/sanidad/protesis-dentales.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0821 | Laboratorio de prótesis dentales |
| 1 | 0854 | Diseño funcional de prótesis |
| 1 | 0858 | Prótesis parciales removibles metálicas, de resina y mixta |
| 1 | 0855 | Prótesis completas |
| 1 | 0856 | Aparatos de ortodoncia y férulas oclusales |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0860 | Prótesis sobre implantes |
| 2 | 0857 | Restauraciones y estructuras metálicas en prótesis fija |
| 2 | 0859 | Restauraciones y recubrimientos estéticos |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0861 | Proyecto Intermodular |

### A.76. 122_0603 — Técnico Superior en Ortoprótesis y Productos de Apoyo

- Family: Sanidad; qualification category: `fpgs`.
- Matching aliases: Ortoprótesis y Productos de Apoyo.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/sanidad/ortoprotesis-productos-apoyo.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0061 | Anatomofisiología y patología básicas |
| 1 | 0326 | Diseño y moldeado anatómico |
| 1 | 0325 | Tecnología industrial aplicada a la actividad ortoprotésica |
| 1 | 0331 | Biomecánica y patología aplicada |
| 1 | 0328 | Elaboración y adaptación de productos ortésicos a medida |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0327 | Adaptación de ortesis prefabricadas |
| 2 | 0332 | Atención psicosocial |
| 2 | 0330 | Adaptación de productos de apoyo |
| 2 | 0329 | Elaboración y adaptación de prótesis externas |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0333 | Proyecto Intermodular |

### A.77. 122_0604 — Técnico Superior en Anatomía Patológica y Citodiagnóstico

- Family: Sanidad; qualification category: `fpgs`.
- Matching aliases: Anatomía Patológica y Citodiagnóstico.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/sanidad/anatomia-patologica-citodiagnostico.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1379 | Necropsias |
| 1 | 1368 | Técnicas generales de laboratorio |
| 1 | 1369 | Biología molecular y citogenética |
| 1 | 1367 | Gestión de muestras biológicas |
| 1 | 1370 | Fisiopatología general |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1380 | Procesamiento citológico y tisular |
| 2 | 1381 | Citología ginecológica |
| 2 | 1382 | Citología general |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1383 | Proyecto Intermodular |

### A.78. 122_0605 — Técnico Superior en Documentación y Administración Sanitarias

- Family: Sanidad; qualification category: `fpgs`.
- Matching aliases: Documentación y Administración Sanitarias.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/sanidad/documentacion-administracion-sanitarias.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1515 | Gestión de pacientes |
| 1 | 1518 | Archivo y documentación sanitarios |
| 1 | 1519 | Sistemas de información y clasificación sanitarios |
| 1 | 1516 | Terminología clínica y patología |
| 1 | 1517 | Extracción de diagnósticos y procedimientos |
| 1 | 0649 | Ofimática y proceso de la información |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1521 | Atención psicosocial al paciente/usuario |
| 2 | 1522 | Validación y explotación de datos |
| 2 | 1523 | Gestión administrativa sanitaria |
| 2 | 1520 | Codificación sanitaria |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1524 | Proyecto Intermodular |

### A.79. 122_0606 — Técnico Superior en Higiene Bucodental

- Family: Sanidad; qualification category: `fpgs`.
- Matching aliases: Higiene Bucodental.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/sanidad/higiente-bucodental.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0730 | Recepción y logística en la clínica dental |
| 1 | 0732 | Exploración de la cavidad oral |
| 1 | 0731 | Estudio de la cavidad oral |
| 1 | 0733 | Intervención bucodental |
| 1 | 1370 | Fisiopatología general |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0020 | Primeros auxilios |
| 2 | 0734 | Epidemiología en salud oral |
| 2 | 0735 | Educación para la salud oral |
| 2 | 0737 | Prótesis y ortodoncia |
| 2 | 0736 | Conservadora, periodoncia, cirugía e implantes |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0738 | Proyecto Intermodular |

### A.80. 122_0607 — Técnico Superior en Imagen para el Diagnóstico y Medicina Nuclear

- Family: Sanidad; qualification category: `fpgs`.
- Matching aliases: Imagen para el Diagnóstico y Medicina Nuclear.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/sanidad/imagen-diagnostico-medicina-nuclear.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1345 | Atención técnico-sanitaria al paciente |
| 1 | 1348 | Protección radiológica |
| 1 | 1346 | Fundamentos físicos y equipos |
| 1 | 1347 | Anatomía por la imagen |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1710 | Itinerario personal para la Empleabilidad II |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1350 | Técnicas de radiología especial |
| 2 | 1354 | Técnicas de radiofarmacia |
| 2 | 1351 | Técnicas de tomografía computarizada y ecografía |
| 2 | 1352 | Técnicas de imagen por resonancia magnética |
| 2 | 1349 | Técnicas de radiología simple |
| 2 | 1353 | Técnicas de imagen en medicina nuclear |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1355 | Proyecto Intermodular |

### A.81. 122_0608 — Técnico Superior en Laboratorio Clínico y Biomédico

- Family: Sanidad; qualification category: `fpgs`.
- Matching aliases: Laboratorio Clínico y Biomédico.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/sanidad/laboratorio-clinico-biomedico.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1368 | Técnicas generales de laboratorio |
| 1 | 1369 | Biología molecular y citogenética |
| 1 | 1370 | Fisiopatología general |
| 1 | 1367 | Gestión de muestras biológicas |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1710 | Itinerario personal para la Empleabilidad II |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1372 | Técnicas de inmunodiagnóstico |
| 2 | 1373 | Microbiología clínica |
| 2 | 1374 | Técnicas de análisis hematológico |
| 2 | 1371 | Análisis bioquímico |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1375 | Proyecto Intermodular |

### A.82. 122_0609 — Técnico Superior en Radioterapia y Dosimetría

- Family: Sanidad; qualification category: `fpgs`.
- Matching aliases: Radioterapia y Dosimetría.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/sanidad/radioterapia-dosimetria.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1348 | Protección radiológica |
| 1 | 1345 | Atención técnico-sanitaria al paciente |
| 1 | 1346 | Fundamentos físicos y equipos |
| 1 | 1347 | Anatomía por la imagen |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1710 | Itinerario personal para la Empleabilidad II |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1360 | Dosimetría física y clínica |
| 2 | 1359 | Simulación del tratamiento |
| 2 | 1362 | Tratamientos con braquiterapia |
| 2 | 1361 | Tratamientos con teleterapia |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1363 | Proyecto Intermodular |

### A.83. 122_0701 — Técnico Superior en Educación y Control Ambiental

- Family: Seguridad y Medio Ambiente; qualification category: `fpgs`.
- Matching aliases: Educación y Control Ambiental.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/seguridad-medio-ambiente/educacion-control-ambiental.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0789 | Métodos y productos cartográficos |
| 1 | 0790 | Técnicas de educación ambiental |
| 1 | 0785 | Estructura y dinámica del medio ambiente |
| 1 | 0787 | Actividades humanas y problemática ambiental |
| 1 | 0786 | Medio natural |
| 1 | 0788 | Gestión ambiental |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0793 | Desenvolvimiento en el medio |
| 2 | 0017 | Habilidades sociales |
| 2 | 0792 | Actividades de uso público |
| 2 | 0791 | Programas de educación ambiental |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0794 | Proyecto Intermodular |

### A.84. 122_0702 — Técnico Superior en Coordinación de Emergencias y Protección Civil

- Family: Seguridad y Medio Ambiente; qualification category: `fpgs`.
- Matching aliases: Coordinación de Emergencias y Protección Civil.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/seguridad-medio-ambiente/coord-emergencias-proteccion-civil.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1504 | Supervisión de la intervención en riesgos producidos por fenómenos naturales |
| 1 | 1510 | Gestión de recursos de emergencias y protección civil |
| 1 | 1505 | Supervisión de la intervención en riesgos tecnológicos y antrópicos |
| 1 | 1506 | Supervisión de la intervención en incendios forestales y quemas prescritas |
| 1 | 1502 | Evaluación de riesgos y medidas preventivas |
| 1 | 1509 | Supervisión de las acciones de apoyo a las personas afectadas por desastres y catástrofes |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1507 | Supervisión de la intervención en operaciones de incendios urbanos y emergencias ordinarias |
| 2 | 1503 | Planificación y desarrollo de acciones formativas, informativas y divulgativas en protección civil y emergencias |
| 2 | 1501 | Planificación en emergencias y protección civil |
| 2 | 1508 | Supervisión de la intervención en operaciones de salvamento y rescate |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1511 | Proyecto Intermodular |

### A.85. 122_0703 — Técnico Superior en Química y Salud Ambiental

- Family: Seguridad y Medio Ambiente; qualification category: `fpgs`.
- Matching aliases: Química y Salud Ambiental.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/seguridad-medio-ambiente/quimica-salud-ambiental.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1546 | Sistemas de gestión ambiental |
| 1 | 1549 | Control de residuos |
| 1 | 1552 | Contaminación ambiental y atmosférica |
| 1 | 1554 | Unidad de salud ambiental |
| 1 | 1548 | Control de aguas |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1547 | Educación para la salud y el medio ambiente |
| 2 | 1550 | Salud y riesgos del medio construido |
| 2 | 1553 | Control de organismos nocivos |
| 2 | 1551 | Control y seguridad alimentaria |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1555 | Proyecto Intermodular |

### A.86. 122_0801 — Técnico Superior en Programación de la Producción en Fabricación Mecánica

- Family: Fabricación Mecánica; qualification category: `fpgs`.
- Matching aliases: Programación de la Producción en Fabricación Mecánica.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/fabricacion-mecanica/programacion-produccion-fab-mecanica.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0007 | Interpretación gráfica |
| 1 | 0160 | Definición de procesos de mecanizado, conformado y montaje |
| 1 | 0164 | Ejecución de procesos de fabricación |
| 1 | 0002 | Mecanizado por control numérico |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0161 | Fabricación asistida por ordenador (CAM) |
| 2 | 0163 | Programación de la producción |
| 2 | 0165 | Gestión de la calidad, prevención de riesgos laborales y protección ambiental |
| 2 | 0166 | Verificación de productos |
| 2 | 0162 | Programación de sistemas automáticos de fabricación mecánica |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0167 | Proyecto Intermodular |

### A.87. 122_0802 — Técnico Superior en Construcciones Metálicas

- Family: Fabricación Mecánica; qualification category: `fpgs`.
- Matching aliases: Construcciones Metálicas.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/fabricacion-mecanica/construcciones-metalicas.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0247 | Definición de procesos de construcciones metálicas |
| 1 | 0245 | Representación gráfica en fabricación mecánica |
| 1 | 0248 | Procesos de mecanizado, corte y conformado en construcciones metálicas |
| 1 | 0246 | Diseño de construcciones metálicas |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0163 | Programación de la producción |
| 2 | 0165 | Gestión de la calidad, prevención de riesgos laborales y protección ambiental |
| 2 | 0162 | Programación de sistemas automáticos de fabricación mecánica |
| 2 | 0249 | Procesos de unión y montaje en construcciones metálicas |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0250 | Proyecto Intermodular |

### A.88. 122_0803 — Técnico Superior en Diseño en Fabricación Mecánica

- Family: Fabricación Mecánica; qualification category: `fpgs`.
- Matching aliases: Diseño en Fabricación Mecánica.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/fabricacion-mecanica/diseno-fabricacion-mecanica.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0429 | Diseño de moldes y modelos de fundición |
| 1 | 0245 | Representación gráfica en fabricación mecánica |
| 1 | 0432 | Técnicas de fabricación mecánica |
| 1 | 0427 | Diseño de productos mecánicos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0430 | Diseño de moldes para productos poliméricos |
| 2 | 0431 | Automatización de la fabricación |
| 2 | 0428 | Diseño de útiles de procesado de chapa y estampación |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0433 | Proyecto Intermodular |

### A.89. 122_0804 — Técnico Superior en Programación de la Producción en Moldeo de Metales y Polímeros

- Family: Fabricación Mecánica; qualification category: `fpgs`.
- Matching aliases: Programación de la Producción en Moldeo de Metales y Polímeros.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/fabricacion-mecanica/programacion-produccion-moldeo-metales-polimeros.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0163 | Programación de la producción |
| 1 | 0007 | Interpretación gráfica |
| 1 | 0530 | Caracterización de materiales |
| 1 | 0162 | Programación de sistemas automáticos de fabricación mecánica |
| 1 | 0531 | Moldeo cerrado |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0165 | Gestión de la calidad, prevención de riesgos laborales y protección ambiental |
| 2 | 0533 | Verificación de productos conformados |
| 2 | 0532 | Moldeo abierto |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0534 | Proyecto Intermodular |

### A.90. 122_0901 — Técnico Superior en Desarrollo de Proyectos de Instalaciones Térmicas y de Fluidos

- Family: Instalación y Mantenimiento; qualification category: `fpgs`.
- Matching aliases: Desarrollo de Proyectos de Instalaciones Térmicas y de Fluidos.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/instalacion-mantenimiento/desarrollo-proyectos-inst-termicas-fluidos.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0124 | Energías renovables y eficiencia energética |
| 1 | 0123 | Representación gráfica de instalaciones |
| 1 | 0120 | Sistemas eléctricos y automáticos |
| 1 | 0121 | Equipos e instalaciones térmicas |
| 1 | 0122 | Procesos de montaje de instalaciones |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0128 | Planificación del montaje de instalaciones |
| 2 | 0125 | Configuración de instalaciones de climatización, calefacción y ACS |
| 2 | 0126 | Configuración de instalaciones frigoríficas |
| 2 | 0127 | Configuración de instalaciones de fluidos |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0129 | Proyecto Intermodular |

### A.91. 122_0902 — Técnico Superior en Mantenimiento de Instalaciones Térmicas y de Fluidos

- Family: Instalación y Mantenimiento; qualification category: `fpgs`.
- Matching aliases: Mantenimiento de Instalaciones Térmicas y de Fluidos.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/instalacion-mantenimiento/mnto-inst-termicas-fluidos.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0124 | Energías renovables y eficiencia energética |
| 1 | 0123 | Representación gráfica de instalaciones |
| 1 | 0120 | Sistemas eléctricos y automáticos |
| 1 | 0121 | Equipos e instalaciones térmicas |
| 1 | 0122 | Procesos de montaje de instalaciones |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0133 | Gestión del montaje, de la calidad y del mantenimiento |
| 2 | 0134 | Configuración de instalaciones térmicas y de fluidos |
| 2 | 0135 | Mantenimiento de instalaciones frigoríficas y de climatización |
| 2 | 0136 | Mantenimiento de instalaciones caloríficas y de fluidos |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0137 | Proyecto Intermodular |

### A.92. 122_0903 — Técnico Superior en Mecatrónica Industrial

- Family: Instalación y Mantenimiento; qualification category: `fpgs`.
- Matching aliases: Mecatrónica Industrial.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/instalacion-mantenimiento/mecatronica-industrial.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0936 | Sistemas hidráulicos y neumáticos |
| 1 | 0938 | Elementos de máquinas |
| 1 | 0940 | Representación gráfica de sistemas mecatrónicos |
| 1 | 0935 | Sistemas mecánicos |
| 1 | 0937 | Sistemas eléctricos y electrónicos |
| 1 | 0939 | Procesos de fabricación |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0944 | Simulación de sistemas mecatrónicos |
| 2 | 0942 | Procesos y gestión de mantenimiento y calidad |
| 2 | 0941 | Configuración de sistemas mecatrónicos |
| 2 | 0943 | Integración de sistemas |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0945 | Proyecto Intermodular |

### A.93. 122_1001 — Técnico Superior en Sistemas Electrotécnicos y Automatizados

- Family: Electricidad y Electrónica; qualification category: `fpgs`.
- Matching aliases: Sistemas Electrotécnicos y Automatizados.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/electricidad-electronica/sistemas-electrotecnicos-automatizados.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0519 | Documentación técnica en instalaciones eléctricas |
| 1 | 0602 | Gestión del montaje y del mantenimiento de instalaciones eléctricas |
| 1 | 0522 | Desarrollo de redes eléctricas y centros de transformación |
| 1 | 0520 | Sistemas y circuitos eléctricos |
| 1 | 0524 | Configuración de instalaciones eléctricas |
| 1 | 0523 | Configuración de instalaciones domóticas y automáticas |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0517 | Procesos en instalaciones de infraestructuras comunes de telecomunicaciones |
| 2 | 0521 | Técnicas y procesos en instalaciones domóticas y automáticas |
| 2 | 0518 | Técnicas y procesos en instalaciones eléctricas |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0526 | Proyecto Intermodular |

### A.94. 122_1002 — Técnico Superior en Sistemas de Telecomunicaciones e Informáticos

- Family: Electricidad y Electrónica; qualification category: `fpgs`.
- Matching aliases: Sistemas de Telecomunicaciones e Informáticos.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/electricidad-electronica/sistemas-telecomunicaciones-informaticos.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0601 | Gestión de proyectos de instalaciones de telecomunicaciones |
| 1 | 0525 | Configuración de infraestructuras de sistemas de telecomunicaciones |
| 1 | 0551 | Elementos de sistemas de telecomunicaciones |
| 1 | 0713 | Sistemas de telefonía fija y móvil |
| 1 | 0553 | Técnicas y procesos en infraestructuras de telecomunicaciones |
| 1 | 0552 | Sistemas informáticos y redes locales |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0557 | Sistemas integrados y hogar digital |
| 2 | 0555 | Redes telemáticas |
| 2 | 0556 | Sistemas de radiocomunicaciones |
| 2 | 0554 | Sistemas de producción audiovisual |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0558 | Proyecto Intermodular |

### A.95. 122_1003 — Técnico Superior en Mantenimiento Electrónico

- Family: Electricidad y Electrónica; qualification category: `fpgs`.
- Matching aliases: Mantenimiento Electrónico.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/electricidad-electronica/mantenimiento-electronico.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1055 | Mantenimiento de equipos de electrónica industrial |
| 1 | 1052 | Equipos microprogramables |
| 1 | 1058 | Técnicas y procesos de montaje y mantenimiento de equipos electrónicos |
| 1 | 1051 | Circuitos electrónicos analógicos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1059 | Infraestructuras y desarrollo del mantenimiento electrónico |
| 2 | 1056 | Mantenimiento de equipos de audio |
| 2 | 1057 | Mantenimiento de equipos de vídeo |
| 2 | 1053 | Mantenimiento de equipos de radiocomunicaciones |
| 2 | 1054 | Mantenimiento de equipos de voz y datos |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1060 | Proyecto Intermodular |

### A.96. 122_1004 — Técnico Superior en Automatización y Robótica Industrial

- Family: Electricidad y Electrónica; qualification category: `fpgs`.
- Matching aliases: Automatización y Robótica Industrial.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/electricidad-electronica/automatizacion-robotica-industrial.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0963 | Documentación técnica |
| 1 | 0964 | Informática industrial |
| 1 | 0959 | Sistemas eléctricos, neumáticos e hidráulicos |
| 1 | 0961 | Sistemas de medida y regulación |
| 1 | 0960 | Sistemas secuenciales programables |
| 1 | 0962 | Sistemas de potencia |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0966 | Robótica industrial |
| 2 | 0965 | Sistemas programables avanzados |
| 2 | 0967 | Comunicaciones industriales |
| 2 | 0968 | Integración de sistemas de automatización industrial |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0969 | Proyecto Intermodular |

### A.97. 122_1005 — Técnico Superior en Electromedicina Clínica

- Family: Electricidad y Electrónica; qualification category: `fpgs`.
- Matching aliases: Electromedicina Clínica.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/electricidad-electronica/electromedicina-clinica.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1592 | Tecnología sanitaria en el ámbito clínico |
| 1 | 1585 | Instalaciones eléctricas |
| 1 | 1586 | Sistemas electromecánicos y de fluidos |
| 1 | 1587 | Sistemas electrónicos y fotónicos |
| 1 | 1589 | Sistemas de monitorización, registro y cuidados críticos |
| 1 | 1588 | Sistemas de radiodiagnóstico, radioterapia e imagen médica |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1591 | Sistemas de rehabilitación y pruebas funcionales |
| 2 | 1590 | Sistemas de laboratorio y hemodiálisis |
| 2 | 1594 | Gestión del montaje y mantenimiento de sistemas de electromedicina |
| 2 | 1593 | Planificación de la adquisición de sistemas de electromedicina |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1595 | Proyecto Intermodular |

### A.98. 122_1101 — Técnico Superior en Eficiencia Energética y Energía Solar Térmica

- Family: Energía y Agua; qualification category: `fpgs`.
- Matching aliases: Eficiencia Energética y Energía Solar Térmica.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/energia-agua/eficiencia-energetica-solar-termica.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0354 | Promoción del uso eficiente de la energía y del agua |
| 1 | 0123 | Representación gráfica de instalaciones |
| 1 | 0122 | Procesos de montaje de instalaciones |
| 1 | 0121 | Equipos e instalaciones térmicas |
| 1 | 0349 | Eficiencia energética de instalaciones |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0353 | Gestión del montaje y mantenimiento de instalaciones solares térmicas |
| 2 | 0350 | Certificación energética de edificios |
| 2 | 0351 | Gestión eficiente del agua en edificación |
| 2 | 0352 | Configuración de instalaciones solares térmicas |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0355 | Proyecto Intermodular |

### A.99. 122_1102 — Técnico Superior en Energías Renovables

- Family: Energía y Agua; qualification category: `fpgs`.
- Matching aliases: Energías Renovables.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/energia-agua/energias-renovables.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0671 | Prevención de riesgos eléctricos |
| 1 | 0682 | Gestión del montaje de instalaciones solares fotovoltaicas |
| 1 | 0668 | Sistemas eléctricos en centrales |
| 1 | 0669 | Subestaciones eléctricas |
| 1 | 0670 | Telecontrol y automatismos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0680 | Sistemas de energías renovables |
| 2 | 0681 | Configuración de instalaciones solares fotovoltaicas |
| 2 | 0684 | Operación y mantenimiento de parques eólicos |
| 2 | 0683 | Gestión del montaje de parques eólicos |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 0686 | Proyecto Intermodular |

### A.100. 122_1103 — Técnico Superior en Centrales Eléctricas

- Family: Energía y Agua; qualification category: `fpgs`.
- Matching aliases: Centrales Eléctricas.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/energia-agua/centrales-electricas.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0675 | Coordinación de equipos humanos |
| 2 | 0674 | Mantenimiento de centrales eléctricas |
| 1 | 0668 | Sistemas eléctricos en centrales |
| 1 | 0672 | Centrales de producción eléctrica |
| 1 | 0673 | Operación en centrales eléctricas |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0671 | Prevención de riesgos eléctricos |
| 2 | 0669 | Subestaciones eléctricas |
| 2 | 0670 | Telecontrol y automatismos |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0676 | Proyecto Intermodular |

### A.101. 122_1104 — Técnico Superior en Gestión del Agua

- Family: Energía y Agua; qualification category: `fpgs`.
- Matching aliases: Gestión del Agua.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/energia-agua/gestion-agua.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1576 | Sistemas eléctricos en instalaciones de agua |
| 1 | 1573 | Calidad y tratamiento de aguas |
| 1 | 1580 | Técnicas de montaje en instalaciones de agua |
| 1 | 1572 | Planificación y replanteo |
| 1 | 1575 | Configuración de redes de agua |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0309 | Técnicas de comunicación y de relaciones |
| 2 | 1574 | Gestión eficiente del agua |
| 2 | 1579 | Gestión de operaciones, calidad y medioambiente |
| 2 | 1577 | Automatismos y telecontrol en instalaciones de agua |
| 2 | 1578 | Operaciones en redes e instalaciones de agua |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1581 | Proyecto Intermodular |

### A.102. 122_1201 — Técnico Superior en Automoción

- Family: Transporte y Mantenimiento de Vehículos; qualification category: `fpgs`.
- Matching aliases: Automoción.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/transporte-mantenimiento-vehiculos/automocion.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0296 | Estructuras del vehículo |
| 1 | 0294 | Elementos amovibles y fijos no estructurales |
| 1 | 0293 | Motores térmicos y sus sistemas auxiliares |
| 1 | 0291 | Sistemas eléctricos y de seguridad y confortabilidad |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0309 | Técnicas de comunicación y de relaciones |
| 2 | 0297 | Gestión y logística del mantenimiento de vehículos |
| 2 | 0295 | Tratamiento y recubrimiento de superficies |
| 2 | 0292 | Sistemas de transmisión de fuerza y trenes de rodaje |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0298 | Proyecto Intermodular |

### A.103. 122_1203 — Técnico Superior en Mantenimiento Aeromecánico de Aviones con Motor de Turbina

- Family: Transporte y Mantenimiento de Vehículos; qualification category: `fpgs`.
- Matching aliases: Mantenimiento Aeromecánico de Aviones con Motor de Turbina.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/transporte-mantenimiento-vehiculos/mtmo-aeromecanica-aviones-motor-turbina.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1425 | Fundamentos de electricidad |
| 1 | 1426 | Fundamentos de electrónica en aeromecánica |
| 1 | 1436 | Factores humanos |
| 1 | 1439 | Aerodinámica, estructuras y sistemas de mandos de vuelo de aviones con motor de turbina |
| 1 | 1440 | Aerodinámica, estructuras y sistemas hidráulicos, neumáticos y tren de aterrizaje del avión |
| 1 | 1441 | Aerodinámica, estructuras y sistemas de oxígeno, aguas y protección de aviones |
| 1 | 1435 | Aerodinámica básica |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional(GS) |
| 2 | 1430 | Materiales, equipos y herramientas en aeromecánica |
| 2 | 1437 | Legislación aeronáutica |
| 2 | 1428 | Técnicas digitales y Sistemas de instrumentos electrónicos en aeromecánica |
| 2 | 1433 | Prácticas de mantenimiento con elementos de aviónica y servicios de las aeronaves |
| 2 | 1438 | Aerodinámica, estructuras y sistemas eléctricos y de aviónica de aviones con motor de turbina |
| 2 | 1455 | Motores de turbinas de gas |
| 2 | 1457 | Hélices |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1458 | Proyecto Intermodular |
| 3 | 1432 | Prácticas de mantenimiento con elementos mecánicos de la aeronave |

### A.104. 122_1205 — Técnico Superior en Mantenimiento Aeromecánico de Helicópteros con Motor de Turbina

- Family: Transporte y Mantenimiento de Vehículos; qualification category: `fpgs`.
- Matching aliases: Mantenimiento Aeromecánico de Helicópteros con Motor de Turbina.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/transporte-mantenimiento-vehiculos/mtmo-aeromecanico-helicopteros-motor-turbina.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1425 | Fundamentos de electricidad |
| 1 | 1426 | Fundamentos de electrónica en aeromecánica |
| 1 | 1430 | Materiales, equipos y herramientas en aeromecánica |
| 1 | 1437 | Legislación aeronáutica |
| 1 | 1435 | Aerodinámica básica |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional(GS) |
| 2 | 1436 | Factores humanos |
| 2 | 1433 | Prácticas de mantenimiento con elementos de aviónica y servicios de las aeronaves |
| 2 | 1455 | Motores de turbinas de gas |
| 2 | 1446 | Aerodinámica, estructuras y sistemas de instrumentación, aviónica y luces |
| 2 | 1447 | Aerodinámica, estructuras y teoría de vuelo, mandos de vuelo, sistema de conducción de potencia y rotores |
| 2 | 1428 | Técnicas digitales y sistemas de instrumentos electrónicos en aeromecánica |
| 2 | 1449 | Aerodinámica, estructuras, tren de aterrizaje, equipamiento y accesorios de helicópteros |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1466 | Proyecto Intermodular |
| 3 | 1432 | Prácticas de mantenimiento con elementos mecánicos de la aeronave |
| 3 | 1448 | Aerodinámica, estructuras y sistemas hidráulico, combustible, neumáticos y de protección en helicópteros |

### A.105. 122_1206 — Técnico Superior en Mantenimiento de Sistemas Electrónicos y Aviónicos de Aeronaves

- Family: Transporte y Mantenimiento de Vehículos; qualification category: `fpgs`.
- Matching aliases: Mantenimiento de Sistemas Electrónicos y Aviónicos de Aeronaves.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/transporte-mantenimiento-vehiculos/mtmo-sistemas-electronicos-avionicos-aeronaves.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1425 | Fundamentos de electricidad |
| 1 | 1427 | Fundamentos de electrónica en aviónica |
| 1 | 1437 | Legislación aeronáutica |
| 1 | 1431 | Materiales, equipos y herramientas en aviónica |
| 1 | 1435 | Aerodinámica básica |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional(GS) |
| 2 | 1436 | Factores humanos |
| 2 | 1429 | Técnicas digitales y sistemas de instrumentos electrónicos en aviónica |
| 2 | 1450 | Aerodinámica, estructuras, sistemas de mandos de vuelo, potencia hidráulica, tren de aterrizaje y célula de aeronaves |
| 2 | 1451 | Aerodinámica, estructuras y sistemas de instrumentación, generación eléctrica, luces y mantenimiento a bordo de aeronaves |
| 2 | 1452 | Aerodinámica, estructuras y sistemas de comunicación, cabina de pasaje e información de aeronaves |
| 2 | 1453 | Aerodinámica, estructuras y sistemas de navegación y de vuelo automático de aeronaves |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1474 | Proyecto Intermodular |
| 3 | 1454 | Propulsión |
| 3 | 1475 | Aerodinámica, estructuras y sistemas neumáticos, combustible, de oxígeno, aguas y protección de aeronaves |
| 3 | 1434 | Prácticas de mantenimiento en aviónica |

### A.106. 122_1401 — Técnico Superior en Proyectos de Edificación

- Family: Edificación y Obra Civil; qualification category: `fpgs`.
- Matching aliases: Proyectos de Edificación.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/edificacion-obra-civil/proyectos-edificacion.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0562 | Estructuras de construcción |
| 1 | 0565 | Replanteos de construcción |
| 1 | 0567 | Diseño y construcción de edificios |
| 1 | 0568 | Instalaciones en edificación |
| 1 | 0563 | Representaciones de construcción |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0569 | Eficiencia energética en edificación |
| 2 | 0566 | Planificación de construcción |
| 2 | 0564 | Mediciones y valoraciones de construcción |
| 2 | 0571 | Desarrollo de proyectos de edificación no residencial |
| 2 | 0570 | Desarrollo de proyectos de edificación residencial |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0572 | Proyecto Intermodular |

### A.107. 122_1402 — Técnico Superior en Proyectos de Obra Civil

- Family: Edificación y Obra Civil; qualification category: `fpgs`.
- Matching aliases: Proyectos de Obra Civil.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/edificacion-obra-civil/proyectos-obra-civil.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0562 | Estructuras de construcción |
| 1 | 0565 | Replanteos de construcción |
| 1 | 0770 | Redes y servicios en obra civil |
| 1 | 0769 | Urbanismo y obra civil |
| 1 | 0563 | Representaciones de construcción |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0566 | Planificación de construcción |
| 2 | 0564 | Mediciones y valoraciones de construcción |
| 2 | 0772 | Desarrollo de proyectos urbanísticos |
| 2 | 0773 | Desarrollo de proyectos de obras lineales |
| 2 | 0771 | Levantamientos topográficos |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0774 | Proyecto Intermodular |

### A.108. 122_1403 — Técnico Superior en Organización y Control de Obras de Construcción

- Family: Edificación y Obra Civil; qualification category: `fpgs`.
- Matching aliases: Organización y Control de Obras de Construcción.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/edificacion-obra-civil/org-control-obras-construccion.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0566 | Planificación de construcción |
| 1 | 0562 | Estructuras de construcción |
| 1 | 1287 | Documentación de proyectos y obras de construcción |
| 1 | 0565 | Replanteos de construcción |
| 1 | 1289 | Procesos constructivos en obra civil |
| 1 | 1288 | Procesos constructivos en edificación |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1292 | Control de ejecución en obra civil |
| 2 | 0564 | Mediciones y valoraciones de construcción |
| 2 | 1291 | Control de ejecución en obras de edificación |
| 2 | 1290 | Control de estructuras de construcción |
| 2 | 1293 | Rehabilitación y conservación de obras de construcción |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1294 | Proyecto Intermodular |

### A.109. 122_1501 — Técnico Superior en Desarrollo y Fabricación de Productos Cerámicos

- Family: Vidrio y Cerámica; qualification category: `fpgs`.
- Matching aliases: Desarrollo y Fabricación de Productos Cerámicos.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/vidrio-ceramica/desarrollo-fabricacion-prod-ceramicos.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0163 | Programación de la producción |
| 1 | 0308 | Control de procesos de fabricación de productos cerámicos |
| 1 | 0165 | Gestión de la calidad, prevención de riesgos laborales y protección ambiental |
| 1 | 0307 | Fabricación de fritas, pigmentos y esmaltes cerámicos |
| 1 | 0306 | Fabricación de pastas cerámicas y productos cerámicos conformados |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0311 | Cerámicas avanzadas |
| 2 | 0305 | Desarrollo de productos cerámicos |
| 2 | 0303 | Desarrollo de pastas cerámicas |
| 2 | 0304 | Desarrollo de fritas, pigmentos y esmaltes cerámicos |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0312 | Proyecto Intermodular |

### A.110. 122_1601 — Técnico Superior en Diseño y Amueblamiento

- Family: Madera, Mueble y Corcho; qualification category: `fpgs`.
- Matching aliases: Diseño y Amueblamiento.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/madera-mueble-corcho/diseno-amueblamiento.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0982 | Procesos en industrias de carpintería y mueble |
| 1 | 0984 | Representación en carpintería y mobiliario |
| 1 | 0985 | Prototipos en carpintería y mueble |
| 1 | 0986 | Desarrollo de producto en carpintería y mueble |
| 1 | 0983 | Fabricación en carpintería y mueble |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0989 | Diseño de carpintería y mueble |
| 2 | 0990 | Gestión de la producción en carpintería y mueble |
| 2 | 0988 | Instalaciones de carpintería y mobiliario |
| 2 | 0987 | Automatización en carpintería y mueble |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0991 | Proyecto Intermodular |

### A.111. 122_1701 — Técnico Superior en Patronaje y Moda

- Family: Textil, Confección y Piel; qualification category: `fpgs`.
- Matching aliases: Patronaje y Moda.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/textil-confeccion-piel/patronaje-moda.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0264 | Moda y tendencias |
| 1 | 0276 | Materiales en textil, confección y piel |
| 1 | 0278 | Procesos en confección industrial |
| 1 | 0277 | Técnicas en confección |
| 1 | 0285 | Patronaje industrial en textil y piel |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0280 | Organización de la producción en confección industrial |
| 2 | 0286 | Industrialización y escalado de patrones |
| 2 | 0165 | Gestión de la calidad, prevención de riesgos laborales y protección ambiental |
| 2 | 0284 | Elaboración de prototipos |
| 2 | 0283 | Análisis de diseños en textil y piel |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0287 | Proyecto Intermodular |

### A.112. 122_1702 — Técnico Superior en Diseño y Producción de Calzado y Complementos

- Family: Textil, Confección y Piel; qualification category: `fpgs`.
- Matching aliases: Diseño y Producción de Calzado y Complementos.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/textil-confeccion-piel/diseno-prod-calzado-complementos.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0611 | Calzado y tendencias |
| 1 | 0276 | Materiales en textil, confección y piel |
| 1 | 0593 | Diseño técnico de calzado y complementos |
| 1 | 0595 | Industrialización de patrones de calzado |
| 1 | 0594 | Ajuste y patronaje de calzado y complementos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0280 | Organización de la producción en confección industrial |
| 2 | 0596 | Procesos de producción de calzado |
| 2 | 0284 | Elaboración de prototipos |
| 2 | 0165 | Gestión de la calidad, prevención de riesgos laborales y protección ambiental |
| 2 | 0283 | Análisis de diseños en textil y piel |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0597 | Proyecto Intermodular |

### A.113. 122_1703 — Técnico Superior en Vestuario a Medida y de Espectáculos

- Family: Textil, Confección y Piel; qualification category: `fpgs`.
- Matching aliases: Vestuario a Medida y de Espectáculos.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/textil-confeccion-piel/vestuario-medida-espectaculos.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0589 | Moda y tendencias en el vestir |
| 1 | 0276 | Materiales en textil, confección y piel |
| 1 | 0591 | Confección de vestuario a medida |
| 1 | 0585 | Técnicas de modelaje y patronaje de vestuario a medida |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0586 | Gestión de recursos de vestuario a medida |
| 2 | 0587 | Vestuario de espectáculos |
| 2 | 0590 | Diseño de vestuario a medida |
| 2 | 0588 | Sastrería clásica |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0592 | Proyecto Intermodular |

### A.114. 122_1704 — Técnico Superior en Diseño Técnico en Textil y Piel

- Family: Textil, Confección y Piel; qualification category: `fpgs`.
- Matching aliases: Diseño Técnico en Textil y Piel.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/textil-confeccion-piel/diseno-tecnico-textil-piel.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0279 | Muestras de artículos en textil y piel |
| 1 | 0281 | Procesos y análisis de hilatura |
| 1 | 0444 | Procesos de ennoblecimiento y estampación |
| 1 | 0282 | Procesos y análisis de tejidos y no tejidos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0445 | Procesos en tintura y acabado de pieles |
| 2 | 0283 | Análisis de diseños en textil y piel |
| 2 | 0447 | Diseño técnico de textiles |
| 2 | 0450 | Diseño técnico de acabados de pieles |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0659 | Proyecto Intermodular |

### A.115. 122_1801 — Técnico Superior en Diseño y Gestión de la Producción Gráfica

- Family: Artes Gráficas; qualification category: `fpgs`.
- Matching aliases: Diseño y Gestión de la Producción Gráfica.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/artes-graficas/diseno-gestion-produccion-grafica.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1480 | Comercialización de productos gráficos y atención al cliente |
| 1 | 1417 | Materiales de producción gráfica |
| 1 | 1479 | Diseño de productos gráficos |
| 1 | 1478 | Organización de los procesos de preimpresión digital |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional(GS) |
| 2 | 1539 | Gestión del color |
| 2 | 1538 | Gestión de la producción en la industria gráfica |
| 2 | 1541 | Organización de los procesos de postimpresión, transformados y acabados |
| 2 | 1540 | Organización de los procesos de impresión gráfica |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1542 | Proyecto Intermodular |

### A.116. 122_1802 — Técnico Superior en Diseño y Edición de Publicaciones Impresas y Multimedia

- Family: Artes Gráficas; qualification category: `fpgs`.
- Matching aliases: Diseño y Edición de Publicaciones Impresas y Multimedia.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/artes-graficas/diseno-edicion-public-impresas-multimedia.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1480 | Comercialización de productos gráficos y atención al cliente |
| 1 | 1417 | Materiales de producción gráfica |
| 1 | 1479 | Diseño de productos gráficos |
| 1 | 1478 | Organización de los procesos de preimpresión digital |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1483 | Diseño estructural de envase y embalaje |
| 2 | 1481 | Gestión de la producción en procesos de edición |
| 2 | 1482 | Producción editorial |
| 2 | 1484 | Diseño y planificación de proyectos editoriales multimedia |
| 2 | 1485 | Desarrollo y publicación de productos editoriales multimedia |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1486 | Proyecto Intermodular |

### A.117. 122_1901 — Técnico Superior en Sonido para Audiovisuales y Espectáculos

- Family: Imagen y Sonido; qualification category: `fpgs`.
- Matching aliases: Sonido para Audiovisuales y Espectáculos.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/imagen-sonido/sonido-audiovisuales-espectaculos.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1096 | Planificación de proyectos de sonido |
| 1 | 1103 | Electroacústica |
| 1 | 1104 | Comunicación y expresión sonora |
| 1 | 1097 | Instalaciones de sonido |
| 1 | 1098 | Sonido para audiovisuales |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1101 | Ajustes de sistemas de sonorización |
| 2 | 1102 | Postproducción de sonido |
| 2 | 1100 | Grabación en estudio |
| 2 | 1099 | Control de sonido en directo |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1105 | Proyecto Intermodular |

### A.118. 122_1902 — Técnico Superior en Producción de Audiovisuales y Espectáculos

- Family: Imagen y Sonido; qualification category: `fpgs`.
- Matching aliases: Producción de Audiovisuales y Espectáculos.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/imagen-sonido/produccion-audiovisuales-espectaculos.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0920 | Recursos expresivos audiovisuales y escénicos |
| 1 | 0918 | Planificación de proyectos de espectáculos y eventos |
| 1 | 0910 | Medios técnicos audiovisuales y escénicos |
| 1 | 0915 | Planificación de proyectos audiovisuales |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0917 | Gestión de proyectos de televisión y radio |
| 2 | 0919 | Gestión de proyectos de espectáculos y eventos |
| 2 | 0916 | Gestión de proyectos de cine, vídeo y multimedia |
| 2 | 0921 | Administración y promoción de audiovisuales y espectáculos |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0922 | Proyecto Intermodular |

### A.119. 122_1903 — Técnico Superior en Realización de Proyectos Audiovisuales y Espectáculos

- Family: Imagen y Sonido; qualification category: `fpgs`.
- Matching aliases: Realización de Proyectos Audiovisuales y Espectáculos.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/imagen-sonido/realizacion-proy-audiovisuales-espectaculos.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0902 | Planificación de la realización en cine y vídeo |
| 1 | 0908 | Planificación de la regiduría de espectáculos y eventos |
| 1 | 0904 | Planificación de la realización en televisión |
| 1 | 0906 | Planificación del montaje y postproducción de audiovisuales |
| 1 | 0910 | Medios técnicos audiovisuales y escénicos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0903 | Procesos de realización en cine y vídeo |
| 2 | 0905 | Procesos de realización en televisión |
| 2 | 0909 | Procesos de regiduría de espectáculos y eventos |
| 2 | 0907 | Realización del montaje y postproducción de audiovisuales |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0911 | Proyecto Intermodular |

### A.120. 122_1904 — Técnico Superior en Animaciones 3D, Juegos y Entornos Interactivos

- Family: Imagen y Sonido; qualification category: `fpgs`.
- Matching aliases: Animaciones 3D, Juegos y Entornos Interactivos.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/imagen-sonido/animaciones3d-juegos-entornos-interactivos.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1086 | Diseño, dibujo y modelado para animación |
| 1 | 1088 | Color, iluminación y acabados 2D y 3D |
| 1 | 1090 | Realización de proyectos multimedia interactivos |
| 1 | 1087 | Animación de elementos 2D y 3D |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1085 | Proyectos de animación audiovisual 2D y 3D |
| 2 | 1089 | Proyectos de juegos y entornos interactivos |
| 2 | 0907 | Realización del montaje y postproducción de audiovisuales |
| 2 | 1091 | Desarrollo de entornos interactivos multidispositivo |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1093 | Proyecto Intermodular |

### A.121. 122_1905 — Técnico Superior en Iluminación, Captación y Tratamiento de Imagen

- Family: Imagen y Sonido; qualification category: `fpgs`.
- Matching aliases: Iluminación, Captación y Tratamiento de Imagen.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/imagen-sonido/iluminacion-captacion-tratamiento-imagen.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1163 | Proyectos fotográficos |
| 1 | 1162 | Control de la iluminación |
| 1 | 1161 | Luminotecnia |
| 1 | 1160 | Proyectos de iluminación |
| 1 | 1158 | Planificación de cámara en audiovisuales |
| 1 | 1167 | Grabación y edición de reportajes audiovisuales |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1166 | Procesos finales fotográficos |
| 2 | 1165 | Tratamiento fotográfico digital |
| 2 | 1164 | Toma fotográfica |
| 2 | 1159 | Toma de imagen audiovisual |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1168 | Proyecto Intermodular |

### A.122. 122_2001 — Técnico Superior en Administración de Sistemas Informáticos en Red

- Family: Informática y Comunicaciones; qualification category: `fpgs`.
- Matching aliases: Administración de Sistemas Informáticos en Red.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/informatica-comunicaciones/admin-sist-informaticos-red.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0371 | Fundamentos de Hardware |
| 1 | 0373 | Lenguajes de Marcas y Sistemas de Gestión de Información |
| 1 | 0372 | Gestión de Bases de Datos |
| 1 | 0370 | Planificación y Administración de Redes |
| 1 | 0369 | Implantación de Sistemas Operativos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0377 | Administración de Sistemas Gestores de Bases de Datos |
| 2 | 0378 | Seguridad y Alta disponibilidad |
| 2 | 0376 | Implantación de Aplicaciones Web |
| 2 | 0374 | Administración de Sistemas Operativos |
| 2 | 0375 | Servicios de Red e Internet |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0379 | Proyecto Intermodular |

### A.123. 122_2002 — Técnico Superior en Desarrollo de Aplicaciones Multiplataforma

- Family: Informática y Comunicaciones; qualification category: `fpgs`.
- Matching aliases: Desarrollo de Aplicaciones Multiplataforma.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/informatica-comunicaciones/des-aplicaciones-multiplataforma.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0487 | Entornos de desarrollo |
| 1 | 0373 | Lenguajes de marcas y sistemas de gestión de información |
| 1 | 0483 | Sistemas informáticos |
| 1 | 0484 | Bases de Datos |
| 1 | 0485 | Programación |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0490 | Programación de servicios y procesos |
| 2 | 0489 | Programación multimedia y dispositivos móviles |
| 2 | 0491 | Sistemas de gestión empresarial |
| 2 | 0488 | Desarrollo de interfaces |
| 2 | 0486 | Acceso a datos |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0492 | Proyecto Intermodular |

### A.124. 122_2003 — Técnico Superior en Desarrollo de Aplicaciones Web

- Family: Informática y Comunicaciones; qualification category: `fpgs`.
- Matching aliases: Desarrollo de Aplicaciones Web.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/informatica-comunicaciones/des-aplicaciones-web.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0487 | Entornos de desarrollo |
| 1 | 0373 | Lenguajes de marcas y sistemas de gestión de información |
| 1 | 0483 | Sistemas informáticos |
| 1 | 0484 | Bases de Datos |
| 1 | 0485 | Programación |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0614 | Despliegue de aplicaciones web |
| 2 | 0612 | Desarrollo web en entorno cliente |
| 2 | 0615 | Diseño de interfaces web |
| 2 | 0613 | Desarrollo web en entorno servidor |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0616 | Proyecto Intermodular |

### A.125. 122_2101 — Técnico Superior en Asistencia a la Dirección

- Family: Administración y Gestión; qualification category: `fpgs`.
- Matching aliases: Asistencia a la Dirección.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/administracion-gestion/asistencia-direccion.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0648 | Recursos humanos y responsabilidad social corporativa |
| 1 | 0647 | Gestión de la documentación jurídica y empresarial |
| 1 | 0650 | Proceso integral de la actividad comercial |
| 1 | 0651 | Comunicación y atención al cliente |
| 1 | 0649 | Ofimática y proceso de la información |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 1 | 0180 | Segunda lengua extranjera |
| 2 | 0663 | Gestión avanzada de la información |
| 2 | 0661 | Protocolo empresarial |
| 2 | 0662 | Organización de eventos empresariales |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1712 | Inglés Profesional II (GS) |
| 2 | 0664 | Proyecto Intermodular |

### A.126. 122_2102 — Técnico Superior en Administración y Finanzas

- Family: Administración y Gestión; qualification category: `fpgs`.
- Matching aliases: Administración y Finanzas.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/administracion-gestion/administracion-finanzas.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0647 | Gestión de la documentación jurídica y empresarial |
| 1 | 0648 | Recursos humanos y responsabilidad social corporativa |
| 1 | 0650 | Proceso integral de la actividad comercial |
| 1 | 0655 | Gestión logística y comercial |
| 1 | 0651 | Comunicación y atención al cliente |
| 1 | 0649 | Ofimática y proceso de la información |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0652 | Gestión de recursos humanos |
| 2 | 0656 | Simulación empresarial |
| 2 | 0653 | Gestión financiera |
| 2 | 0654 | Contabilidad y fiscalidad |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1712 | Inglés Profesional II (GS) |
| 2 | 0657 | Proyecto Intermodular |

### A.127. 122_2201 — Técnico Superior en Comercio Internacional

- Family: Comercio y Marketing; qualification category: `fpgs`.
- Matching aliases: Comercio Internacional.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/comercio-marketing/comercio-internacional.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0625 | Logística de almacenamiento |
| 1 | 0622 | Transporte internacional de mercancías |
| 1 | 0623 | Gestión económica y financiera de la empresa |
| 1 | 0627 | Gestión administrativa del comercio internacional |
| 1 | 0825 | Financiación internacional |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0827 | Comercio digital internacional |
| 2 | 0822 | Sistema de información de mercados |
| 2 | 0826 | Medios de pago internacionales |
| 2 | 0824 | Negociación internacional |
| 2 | 0823 | Marketing internacional |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1712 | Inglés Profesional II (GS) |
| 2 | 0828 | Proyecto Intermodular |

### A.128. 122_2202 — Técnico Superior en Gestión de Ventas y Espacios Comerciales

- Family: Comercio y Marketing; qualification category: `fpgs`.
- Matching aliases: Gestión de Ventas y Espacios Comerciales.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/comercio-marketing/gestion-ventas-espacios-comerciales.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1010 | Investigación comercial |
| 1 | 0931 | Marketing digital |
| 1 | 0623 | Gestión económica y financiera de la empresa |
| 1 | 0625 | Logística de almacenamiento |
| 1 | 0930 | Políticas de marketing |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0626 | Logística de aprovisionamiento |
| 2 | 0928 | Organización de equipos de ventas |
| 2 | 0926 | Escaparatismo y diseño de espacios comerciales |
| 2 | 0927 | Gestión de productos y promociones en el punto de venta |
| 2 | 0929 | Técnicas de venta y negociación |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1712 | Inglés Profesional II (GS) |
| 2 | 0932 | Proyecto Intermodular |

### A.129. 122_2203 — Técnico Superior en Marketing y Publicidad

- Family: Comercio y Marketing; qualification category: `fpgs`.
- Matching aliases: Marketing y Publicidad.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/comercio-marketing/marketing-publicidad.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1010 | Investigación comercial |
| 1 | 1109 | Lanzamiento de productos y servicios |
| 1 | 0623 | Gestión económica y financiera de la empresa |
| 1 | 0931 | Marketing digital |
| 1 | 0930 | Políticas de marketing |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1011 | Trabajo de campo en la investigación comercial |
| 2 | 1009 | Relaciones públicas y organización de eventos de marketing |
| 2 | 1110 | Atención al cliente, consumidor y usuario |
| 2 | 1008 | Medios y soportes de comunicación |
| 2 | 1007 | Diseño y elaboración de material de comunicación |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1712 | Inglés Profesional II (GS) |
| 2 | 1012 | Proyecto Intermodular |

### A.130. 122_2204 — Técnico Superior en Transporte y Logística

- Family: Comercio y Marketing; qualification category: `fpgs`.
- Matching aliases: Transporte y Logística.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/comercio-marketing/transporte-logistica.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0625 | Logística de almacenamiento |
| 1 | 0622 | Transporte internacional de mercancías |
| 1 | 0623 | Gestión económica y financiera de la empresa |
| 1 | 0624 | Comercialización del transporte y la logística |
| 1 | 0627 | Gestión administrativa del comercio internacional |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0626 | Logística de aprovisionamiento |
| 2 | 0628 | Organización del transporte de viajeros |
| 2 | 0629 | Organización del transporte de mercancías |
| 2 | 0621 | Gestión administrativa del transporte y la logística |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1712 | Inglés Profesional II (GS) |
| 2 | 0630 | Proyecto Intermodular |

### A.131. 122_2301 — Técnico Superior en Educación Infantil

- Family: Servicios Socioculturales y a la Comunidad; qualification category: `fpgs`.
- Matching aliases: Educación Infantil.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/servicios-socioculturales-comunidad/educacion-infantil.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0014 | Expresión y comunicación |
| 1 | 0012 | Autonomía personal y salud infantil |
| 1 | 0015 | Desarrollo cognitivo y motor |
| 1 | 0011 | Didáctica de la Educación Infantil |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0020 | Primeros auxilios |
| 2 | 0016 | Desarrollo socioafectivo |
| 2 | 0017 | Habilidades sociales |
| 2 | 0018 | Intervención con familias y atención a menores en riesgo social |
| 2 | 0013 | El juego infantil y su metodología |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0019 | Proyecto Intermodular |

### A.132. 122_2302 — Técnico Superior en Animación Sociocultural y Turística

- Family: Servicios Socioculturales y a la Comunidad; qualification category: `fpgs`.
- Matching aliases: Animación Sociocultural y Turística.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/servicios-socioculturales-comunidad/animacion-sociocultural-turistica.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1124 | Dinamización grupal |
| 1 | 1128 | Desarrollo comunitario |
| 1 | 0344 | Metodología de la intervención social |
| 1 | 1123 | Actividades de ocio y tiempo libre |
| 1 | 1131 | Contexto de la animación sociocultural |
| 1 | 1125 | Animación y gestión cultural |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0020 | Primeros auxilios |
| 2 | 1129 | Información juvenil |
| 2 | 1130 | Intervención socioeducativa con jóvenes |
| 2 | 1126 | Animación turística |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1712 | Inglés Profesional II (GS) |
| 2 | 1132 | Proyecto Intermodular |

### A.133. 122_2303 — Técnico Superior en Integración Social

- Family: Servicios Socioculturales y a la Comunidad; qualification category: `fpgs`.
- Matching aliases: Integración Social.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/servicios-socioculturales-comunidad/integracion-social.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0337 | Contexto de la intervención social |
| 1 | 0344 | Metodología de la intervención social |
| 1 | 0340 | Mediación comunitaria |
| 1 | 0338 | Inserción sociolaboral |
| 1 | 0342 | Promoción de la autonomía personal |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0020 | Primeros auxilios |
| 2 | 0341 | Apoyo a la intervención educativa |
| 2 | 0017 | Habilidades sociales |
| 2 | 0339 | Atención a las unidades de convivencia |
| 2 | 0343 | Sistemas aumentativos y alternativos de comunicación |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 0345 | Proyecto Intermodular |

### A.134. 122_2304 — Técnico Superior en Promoción de Igualdad de Género

- Family: Servicios Socioculturales y a la Comunidad; qualification category: `fpgs`.
- Matching aliases: Promoción de Igualdad de Género.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/servicios-socioculturales-comunidad/promocion-igualdad-genero.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0020 | Primeros auxilios |
| 1 | 0017 | Habilidades sociales |
| 1 | 1128 | Desarrollo comunitario |
| 1 | 1405 | Participación social de las mujeres |
| 1 | 0344 | Metodología de la intervención social |
| 1 | 1404 | Ámbitos de intervención para la promoción de igualdad |
| 1 | 1402 | Prevención de la violencia de género |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1406 | Intervención socioeducativa para la igualdad |
| 2 | 1401 | Información y comunicación con perspectiva de género |
| 2 | 1403 | Promoción del empleo femenino |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1407 | Proyecto Intermodular |

### A.135. 122_2305 — Técnico Superior en Mediación Comunicativa

- Family: Servicios Socioculturales y a la Comunidad; qualification category: `fpgs`.
- Matching aliases: Mediación Comunicativa.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/servicios-socioculturales-comunidad/mediacion-comunicativa.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1111 | Metodología de la integración social de las personas con dificultades de comunicación, lenguaje y habla |
| 1 | 1117 | Intervención con personas con dificultades de comunicación |
| 1 | 1112 | Sensibilización social y participación |
| 1 | 1118 | Técnicas de intervención comunicativa |
| 1 | 1114 | Contexto de la mediación comunicativa con personas sordociegas |
| 1 | 1115 | Lengua de signos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0020 | Primeros auxilios |
| 2 | 0017 | Habilidades sociales |
| 2 | 1116 | Ámbitos de aplicación de la lengua de signos |
| 2 | 0343 | Sistemas aumentativos y alternativos de comunicación |
| 2 | 1113 | Intervención socioeducativa con personas sordociegas |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1119 | Proyecto Intermodular |

### A.136. 122_2306 — Técnico Superior en Formación para la Movilidad Segura y Sostenible

- Family: Servicios Socioculturales y a la Comunidad; qualification category: `fpgs`.
- Matching aliases: Formación para la Movilidad Segura y Sostenible.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/servicios-socioculturales-comunidad/formacion-movilidad-segura-sostenible.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1652 | Organización de la formación de conductores |
| 1 | 1653 | Técnicas de conducción |
| 1 | 1657 | Seguridad vial |
| 1 | 1659 | Movilidad segura y sostenible |
| 1 | 1651 | Tráfico, circulación de vehículos y transporte por carretera |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0020 | Primeros auxilios |
| 2 | 1654 | Tecnología básica del automóvil |
| 2 | 1658 | Didáctica de la formación para la seguridad vial |
| 2 | 1656 | Educación vial |
| 2 | 1655 | Didáctica de la enseñanza práctica de la conducción |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1660 | Proyecto Intermodular |

### A.137. 122_2401 — Técnico Superior en Gestión de Alojamientos Turísticos

- Family: Hostelería y Turismo; qualification category: `fpgs`.
- Matching aliases: Gestión de Alojamientos Turísticos.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/hosteleria-turismo/gestion-alojamientos-turisticos.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0171 | Estructura del mercado turístico |
| 1 | 0175 | Gestión del departamento de pisos |
| 1 | 0172 | Protocolo y relaciones públicas |
| 1 | 0173 | Marketing turístico |
| 1 | 0176 | Recepción y reservas |
| 1 | 0180 | Segunda lengua extranjera |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0177 | Recursos humanos en el alojamiento |
| 2 | 0178 | Comercialización de eventos |
| 2 | 0174 | Dirección de alojamientos turísticos |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1712 | Inglés Profesional II (GS) |
| 2 | 0181 | Proyecto Intermodular |

### A.138. 122_2402 — Técnico Superior en Guía, Información y Asistencias Turísticas

- Family: Hostelería y Turismo; qualification category: `fpgs`.
- Matching aliases: Guía, Información y Asistencias Turísticas.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/hosteleria-turismo/guia-informacion-asistencias-turisticas.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0172 | Protocolo y relaciones públicas |
| 1 | 0171 | Estructura del mercado turístico |
| 1 | 0384 | Recursos turísticos |
| 1 | 0173 | Marketing turístico |
| 1 | 0383 | Destinos turísticos |
| 1 | 0180 | Segunda lengua extranjera |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0385 | Servicios de información turística |
| 2 | 0387 | Diseño de productos turísticos |
| 2 | 0386 | Procesos de asistencia y guía |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1712 | Inglés Profesional II (GS) |
| 2 | 0388 | Proyecto Intermodular |

### A.139. 122_2403 — Técnico Superior en Agencias de Viajes y Gestión de Eventos

- Family: Hostelería y Turismo; qualification category: `fpgs`.
- Matching aliases: Agencias de Viajes y Gestión de Eventos.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/hosteleria-turismo/agencias-viajes-gestion-eventos.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0172 | Protocolo y relaciones públicas |
| 1 | 0384 | Recursos turísticos |
| 1 | 0171 | Estructura del mercado turístico |
| 1 | 0173 | Marketing turístico |
| 1 | 0383 | Destinos turísticos |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 1 | 0180 | Segunda lengua extranjera |
| 2 | 0397 | Gestión de productos turísticos |
| 2 | 0399 | Dirección de entidades de intermediación turística |
| 2 | 0398 | Venta de servicios turísticos |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1712 | Inglés Profesional II (GS) |
| 2 | 0400 | Proyecto Intermodular |

### A.140. 122_2404 — Técnico Superior en Dirección de Cocina

- Family: Hostelería y Turismo; qualification category: `fpgs`.
- Matching aliases: Dirección de Cocina.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/hosteleria-turismo/direccion-cocina.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0496 | Control del aprovisionamiento de materias primas |
| 1 | 0501 | Gestión de la calidad y de la seguridad e higiene alimentaria |
| 1 | 0502 | Gastronomía y nutrición |
| 1 | 0497 | Procesos de preelaboración y conservación en cocina |
| 1 | 0499 | Procesos de elaboración culinaria |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0504 | Recursos humanos y dirección de equipos en restauración |
| 2 | 0503 | Gestión administrativa y comercial en restauración |
| 2 | 0498 | Elaboraciones de pastelería y repostería en cocina |
| 2 | 0500 | Gestión de la producción en cocina |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1712 | Inglés Profesional II (GS) |
| 2 | 0505 | Proyecto Intermodular |

### A.141. 122_2405 — Técnico Superior en Dirección de Servicios de Restauración

- Family: Hostelería y Turismo; qualification category: `fpgs`.
- Matching aliases: Dirección de Servicios de Restauración.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/hosteleria-turismo/direccion-servicios-restauracion.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0496 | Control del aprovisionamiento de materias primas |
| 1 | 0501 | Gestión de la calidad y de la seguridad e higiene alimentaria |
| 1 | 0502 | Gastronomía y nutrición |
| 1 | 0509 | Procesos de servicios en bar-cafetería |
| 1 | 0510 | Procesos de servicios en restaurante |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional(GS) |
| 2 | 0180 | Segunda lengua extranjera |
| 2 | 0503 | Gestión administrativa y comercial en restauración |
| 2 | 0504 | Recursos humanos y dirección de equipos en restauración |
| 2 | 0512 | Planificación y dirección de servicios y eventos en restauración |
| 2 | 0511 | Sumillería |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1712 | Inglés Profesional II (GS) |
| 2 | 0513 | Proyecto Intermodular |

### A.142. 122_2501 — Técnico Superior en Acondicionamiento Físico

- Family: Actividades Físicas y Deportivas; qualification category: `fpgs`.
- Matching aliases: Acondicionamiento Físico.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/actividades-fisicas-deportivas/acondicionamiento-fisico.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1149 | Actividades básicas de acondicionamiento físico con soporte musical |
| 1 | 1150 | Actividades especializadas de acondicionamiento físico con soporte musical |
| 1 | 1136 | Valoración de la condición física e intervención en accidentes |
| 1 | 1148 | Fitness en sala de entrenamiento polivalente |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 0017 | Habilidades sociales |
| 2 | 1151 | Acondicionamiento físico en el agua |
| 2 | 1152 | Técnicas de hidrocinesia |
| 2 | 1153 | Control postural, bienestar y mantenimiento funcional |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1154 | Proyecto Intermodular |

### A.143. 122_2502 — Técnico Superior en Enseñanza y Animación Sociodeportiva

- Family: Actividades Físicas y Deportivas; qualification category: `fpgs`.
- Matching aliases: Enseñanza y Animación Sociodeportiva.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/actividades-fisicas-deportivas/ensenanza-animacion-sociodeportiva.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1143 | Metodología de la enseñanza de actividades físico-deportivas |
| 1 | 1141 | Actividades físico-deportivas de implementos |
| 1 | 1138 | Juegos y actividades físico-recreativas y de animación turística |
| 1 | 1139 | Actividades físico-deportivas individuales |
| 1 | 1140 | Actividades físico-deportivas de equipo |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1136 | Valoración de la condición física e intervención en accidentes |
| 2 | 1137 | Planificación de la animación sociodeportiva |
| 2 | 1124 | Dinamización grupal |
| 2 | 1142 | Actividades físico-deportivas para la inclusión social |
| 2 | 1123 | Actividades de ocio y tiempo libre |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1144 | Proyecto Intermodular |

### A.144. 122_2601 — Técnico Superior Artista Fallero y Construcción de Escenografías

- Family: Artes y Artesanías; qualification category: `fpgs`.
- Matching aliases: Técnico Superior Artista Fallero y Construcción de Escenografías.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/artes-artesanias/artista-fallero.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1241 | Organización de la plantá de fallas |
| 1 | 1239 | Organización de la producción de figuras corpóreas y ninots |
| 1 | 1127 | Diseño técnico de escenografías y fallas |
| 1 | 1238 | Organización de la producción de estructuras y maquinaria escénica |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1243 | Ambientación y servicio de espectáculos |
| 2 | 1219 | Planificación de la producción |
| 2 | 1242 | Organización del montaje de decorados |
| 2 | 1240 | Organización de la producción de utilería |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1244 | Proyecto Intermodular |

### A.145. 123_0101 — Título Profesional Básico en Agro-jardinería y Composiciones Florales

- Family: Agraria; qualification category: `fpb`.
- Matching aliases: Agro-jardinería y Composiciones Florales.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/agraria/agrojardineria-composiciones-florales.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3051 | Operaciones auxiliares de preparación del terreno, plantación y siembra de cultivos |
| 1 | 3055 | Operaciones básicas en instalación de jardines, parques y zonas verdes |
| 1 | 3056 | Operaciones básicas para el mantenimiento de jardines, parques y zonas verdes |
| 1 | 3053 | Operaciones básicas de producción y mantenimiento de plantas en viveros y centros de jardinería |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3057 | Materiales de floristería |
| 2 | 3054 | Operaciones auxiliares en la elaboración de composiciones con flores y plantas |
| 2 | 3050 | Actividades de riego, abonado y tratamientos en cultivos |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.146. 123_0102 — Título Profesional Básico en Actividades Agropecuarias

- Family: Agraria; qualification category: `fpb`.
- Matching aliases: Actividades Agropecuarias.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/agraria/actividades-agropecuarias.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3051 | Operaciones auxiliares de preparación del terreno, plantación y siembra de cultivos |
| 1 | 3114 | Operaciones básicas de manejo de la producción ganadera |
| 1 | 3052 | Operaciones auxiliares de obtención y recolección de cultivos |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3115 | Operaciones auxiliares de mantenimiento e higiene en instalaciones ganaderas |
| 2 | 3111 | Envasado y distribución de materias primas agroalimentarias |
| 2 | 3113 | Operaciones auxiliares de cría y alimentación del ganado |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.147. 123_0103 — Título Profesional Básico en Aprovechamientos Forestales

- Family: Agraria; qualification category: `fpb`.
- Matching aliases: Aprovechamientos Forestales.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/agraria/aprovechamientos-forestales.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3121 | Recolección de productos forestales |
| 1 | 3053 | Operaciones básicas de producción y mantenimiento de plantas en viveros y centros de jardinería |
| 1 | 3119 | Trabajos de aprovechamientos forestales |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3056 | Operaciones básicas para el mantenimiento de jardines, parques y zonas verdes |
| 2 | 3118 | Repoblación e infraestructuras forestales |
| 2 | 3120 | Silvicultura y plagas |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.148. 123_0201 — Título Profesional Básico en Actividades Marítimo-Pesqueras

- Family: Marítimo Pesquera; qualification category: `fpb`.
- Matching aliases: Actividades Marítimo-Pesqueras.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/maritimo-pesquera/actividades-maritimo-pesquera.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3143 | Seguridad y primeros auxilios en barcos de pesca |
| 1 | 3142 | Mantenimiento de motores en barcos de pesca |
| 1 | 3140 | Mantenimiento de equipos auxiliares en barcos de pesca |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3138 | Actividades en cubiertas de barcos de pesca |
| 2 | 3141 | Pesca con artes de enmalle y marisqueo |
| 2 | 3139 | Pesca con palangre, arrastre y cerco |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.149. 123_0301 — Título Profesional Básico en Industrias Alimentarias

- Family: Industrias Alimentarias; qualification category: `fpb`.
- Matching aliases: Industrias Alimentarias.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/industrias-alimentarias/industrias-alimentarias.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3070 | Operaciones auxiliares de almacenaje |
| 1 | 3133 | Operaciones auxiliares en la industria alimentaria |
| 1 | 3134 | Elaboración de productos alimentarios |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3135 | Limpieza y mantenimiento de instalaciones y equipos |
| 2 | 3136 | Operaciones básicas de laboratorio |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.150. 123_0501 — Título Profesional Básico en Peluquería y Estética

- Family: Imagen Personal; qualification category: `fpb`.
- Matching aliases: Peluquería y Estética.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/imagen-personal/peluqueria-estetica.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3060 | Preparación del entorno profesional |
| 1 | 3062 | Depilación mecánica y decoloración del vello superfluo |
| 1 | 3065 | Cambio de color del cabello |
| 1 | 3064 | Lavado y cambios de forma del cabello |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3005 | Atención al cliente |
| 2 | 3061 | Cuidados estéticos básicos de uñas |
| 2 | 3063 | Maquillaje |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.151. 123_0801 — Título Profesional Básico en Fabricación y Montaje

- Family: Fabricación Mecánica; qualification category: `fpb`.
- Matching aliases: Fabricación y Montaje.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/fabricacion-mecanica/fabricacion-montaje.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3021 | Soldadura y carpintería metálica |
| 1 | 3020 | Operaciones básicas de fabricación |
| 1 | 3023 | Redes de evacuación |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3022 | Carpintería de aluminio y PVC |
| 2 | 3024 | Fontanería y calefacción básica |
| 2 | 3025 | Montaje de equipos de climatización |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.152. 123_0802 — Título Profesional Básico en Fabricación de Elementos Metálicos

- Family: Electricidad y Electrónica; qualification category: `fpb`.
- Matching aliases: Fabricación de Elementos Metálicos.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/electricidad-electronica/fabricacion-elementos-metalicos.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3021 | Soldadura y carpintería metálica |
| 1 | 3020 | Operaciones básicas de fabricación |
| 1 | 3073 | Operaciones básicas de calderería ligera |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3022 | Carpintería de aluminio y PVC |
| 2 | 3015 | Equipos eléctricos y electrónicos |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.153. 123_0901 — Título Profesional Básico en Mantenimiento de Viviendas

- Family: Instalación y Mantenimiento; qualification category: `fpb`.
- Matching aliases: Mantenimiento de Viviendas.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/instalacion-mantenimiento/mantenimiento-viviendas.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3025 | Montaje de equipos de climatización |
| 1 | 3024 | Fontanería y calefacción básica |
| 1 | 3090 | Operaciones de conservación en la vivienda y montaje de accesorios |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3023 | Redes de evacuación |
| 2 | 3088 | Mantenimiento básico de instalaciones electrotécnicas en viviendas |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.154. 123_1001 — Título Profesional Básico en Electricidad y Electrónica

- Family: Electricidad y Electrónica; qualification category: `fpb`.
- Matching aliases: Electricidad y Electrónica.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/electricidad-electronica/electricidad-electronica.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3015 | Equipos eléctricos y electrónicos |
| 1 | 3013 | Instalaciones eléctricas y domóticas |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3016 | Instalación y mantenimiento de redes para transmisión de datos |
| 2 | 3014 | Instalaciones de telecomunicaciones |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.155. 123_1002 — Título Profesional Básico en Instalaciones Electrotécnicas y Mecánica

- Family: Electricidad y Electrónica; qualification category: `fpb`.
- Matching aliases: Instalaciones Electrotécnicas y Mecánica.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/electricidad-electronica/instalaciones-electrotecnicas-mecanica.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3020 | Operaciones básicas de fabricación |
| 1 | 3021 | Soldadura y carpintería metálica |
| 1 | 3013 | Instalaciones eléctricas y domóticas |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3022 | Carpintería de aluminio y PVC |
| 2 | 3014 | Instalaciones de telecomunicaciones |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.156. 123_1201 — Título Profesional Básico en Mantenimiento de Vehículos

- Family: Transporte y Mantenimiento de Vehículos; qualification category: `fpb`.
- Matching aliases: Mantenimiento de Vehículos.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/transporte-mantenimiento-vehiculos/mantenimiento-vehiculos.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3045 | Preparación de superficies |
| 1 | 3043 | Mecanizado y soldadura |
| 1 | 3044 | Amovibles |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3046 | Electricidad del vehículo |
| 2 | 3047 | Mecánica del vehículo |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.157. 123_1202 — Título Profesional Básico en Mantenimiento de Embarcaciones Deportivas y de Recreo

- Family: Marítimo Pesquera; qualification category: `fpb`.
- Matching aliases: Mantenimiento de Embarcaciones Deportivas y de Recreo.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/maritimo-pesquera/mnto-embarcaciones-deportivas-recreo.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3040 | Protección y embellecimiento de superficies de embarcaciones |
| 1 | 3043 | Mecanizado y soldadura |
| 1 | 3028 | Reparación estructural básica de embarcaciones deportivas |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3068 | Mantenimiento básico de aparejos de embarcaciones deportivas |
| 2 | 3066 | Mantenimiento básico de sistemas eléctricos e informáticos |
| 2 | 3048 | Manteminiento básico de la planta propulsora y equipos asociados |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.158. 123_1401 — Título Profesional Básico en Reforma y Mantenimiento de Edificios

- Family: Edificación y Obra Civil; qualification category: `fpb`.
- Matching aliases: Reforma y Mantenimiento de Edificios.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/edificacion-obra-civil/reforma-mantenimiento-edificios.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3087 | Trabajos de pavimentación exterior y de urbanización |
| 1 | 3086 | Reformas y mantenimiento básico de edificios |
| 1 | 3082 | Albañilería básica |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3083 | Guarnecidos y enlucidos |
| 2 | 3084 | Falsos techos |
| 2 | 3085 | Pintura y empapelado |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.159. 123_1501 — Título Profesional Básico en Vidriería y Alfarería

- Family: Vidrio y Cerámica; qualification category: `fpb`.
- Matching aliases: Vidriería y Alfarería.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/vidrio-ceramica/vidreria-alfareria.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3005 | Atención al cliente |
| 1 | 3105 | Reproducción de moldes |
| 1 | 3106 | Conformado de piezas cerámicas |
| 1 | 3107 | Acabado de productos cerámicos |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3108 | Mecanizados manuales y aplicaciones superficiales |
| 2 | 3109 | Termoformado, fusing y vidrieras |
| 2 | 3110 | Mecanizados manuales y semiautomáticos con vidrio fundido y tubos de vidrio |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.160. 123_1601 — Título Profesional Básico en Carpintería y Mueble

- Family: Madera, Mueble y Corcho; qualification category: `fpb`.
- Matching aliases: (none).
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/madera-mueble-corcho/fpb-carpinteria-mueble.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3077 | Materiales y productos textiles |
| 1 | 3075 | Instalación de elementos de carpintería y mueble |
| 1 | 3074 | Operaciones básicas de mecanizado de madera y derivados |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3005 | Atención al cliente |
| 2 | 3076 | Acabados básicos de la madera |
| 2 | 3078 | Tapizado de muebles |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.161. 123_1701 — Título Profesional Básico en Arreglo y Reparación de Artículos Textiles y de Piel

- Family: Textil, Confección y Piel; qualification category: `fpb`.
- Matching aliases: Arreglo y Reparación de Artículos Textiles y de Piel.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/textil-confeccion-piel/arreglo-reparacion-articulos-textil-piel.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3091 | Reparación de artículos de marroquinería y elaboración de pequeños artículos de guarnicionería |
| 1 | 3092 | Reparación de calzado y actividades complementarias |
| 1 | 3101 | Confección de artículos textiles para decoración |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3005 | Atención al cliente |
| 2 | 3077 | Materiales y productos textiles |
| 2 | 3095 | Arreglos y adaptaciones en prendas de vestir y ropa de hogar |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.162. 123_1702 — Título Profesional Básico en Tapicería y Cortinaje

- Family: Textil, Confección y Piel; qualification category: `fpb`.
- Matching aliases: Tapicería y Cortinaje.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/textil-confeccion-piel/tapiceria-cortinaje.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3077 | Materiales y productos textiles |
| 1 | 3101 | Confección de artículos textiles para decoración |
| 1 | 3100 | Confección y montaje de cortinas y estores |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3005 | Atención al cliente |
| 2 | 3099 | Tapizado de murales y entelado de superficies |
| 2 | 3078 | Tapizado de muebles |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.163. 123_1801 — Título Profesional Básico en Artes Gráficas

- Family: Artes Gráficas; qualification category: `fpb`.
- Matching aliases: Artes Gráficas.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/artes-graficas/artes-graficas.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3005 | Atención al cliente |
| 1 | 3123 | Informática básica aplicada en industrias gráficas |
| 1 | 3124 | Trabajos de reprografía |
| 1 | 3125 | Acabados en reprografía y finalización de productos gráficos |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3126 | Operaciones de almacén en industrias gráficas |
| 2 | 3128 | Manipulados en industrias gráficas |
| 2 | 3127 | Operaciones de producción gráfica |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.164. 123_2001 — Título Profesional Básico en Informática y Comunicaciones

- Family: Informática y Comunicaciones; qualification category: `fpb`.
- Matching aliases: Informática y Comunicaciones.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/informatica-comunicaciones/informatica-comunicaciones.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3029 | Montaje y mantenimiento de sistemas y componentes informáticos |
| 1 | 3030 | Operaciones auxiliares para la configuración y la explotación |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3015 | Equipos eléctricos y electrónicos |
| 2 | 3016 | Instalación y mantenimiento de redes para transmisión de datos |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.165. 123_2002 — Título Profesional Básico en Informática de Oficina

- Family: Administración y Gestión; qualification category: `fpb`.
- Matching aliases: Informática de Oficina.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/administracion-gestion/informatica-oficina.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3029 | Montaje y mantenimiento de sistemas y componentes informáticos |
| 1 | 3030 | Operaciones auxiliares para la configuración y la explotación |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3016 | Instalación y mantenimiento de redes para transmisión de datos |
| 2 | 3031 | Ofimática y archivo de documentos |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.166. 123_2101 — Título Profesional Básico en Servicios Administrativos

- Family: Administración y Gestión; qualification category: `fpb`.
- Matching aliases: Servicios Administrativos.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/administracion-gestion/servicios-administrativos.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3004 | Archivo y comunicación |
| 1 | 3003 | Técnicas administrativas básicas |
| 1 | 3001 | Tratamiento informático de datos |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3005 | Atención al cliente |
| 2 | 3006 | Preparación de pedidos y venta de productos |
| 2 | 3002 | Aplicaciones básicas de ofimática |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.167. 123_2201 — Título Profesional Básico en Servicios Comerciales

- Family: Comercio y Marketing; qualification category: `fpb`.
- Matching aliases: Servicios Comerciales.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/comercio-marketing/servicios-comerciales.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3070 | Operaciones auxiliares de almacenaje |
| 1 | 3069 | Técnicas básicas de merchandising |
| 1 | 3001 | Tratamiento informático de datos |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3005 | Atención al cliente |
| 2 | 3006 | Preparación de pedidos y venta de productos |
| 2 | 3002 | Aplicaciones básicas de ofimática |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.168. 123_2301 — Título Profesional Básico en Actividades Domésticas y Limpieza de Edificios

- Family: Servicios Socioculturales y a la Comunidad; qualification category: `fpb`.
- Matching aliases: Actividades Domésticas y Limpieza de Edificios.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/servicios-socioculturales-comunidad/activ-domesticas-limpieza-edificios.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3145 | Actividades de apoyo a personas no dependientes en la unidad convivencial |
| 1 | 3102 | Cocina doméstica |
| 1 | 3098 | Mantenimiento de prendas de vestir y ropa de hogar |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3116 | Limpieza con máquinas |
| 2 | 3146 | Seguridad en el ámbito doméstico |
| 2 | 3104 | Limpieza de domicilios particulares, edificios, oficinas y locales |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.169. 123_2402 — Título Profesional Básico en Alojamiento y Lavandería

- Family: Hostelería y Turismo; qualification category: `fpb`.
- Matching aliases: Alojamiento y Lavandería.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/hosteleria-turismo/alojamiento-lavanderia.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3077 | Materiales y productos textiles |
| 1 | 3093 | Lavado y secado de ropa |
| 1 | 3094 | Planchado y embolsado de ropa |
| 1 | 3130 | Puesta a punto de habitaciones y zonas comunes en alojamiento |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3005 | Atención al cliente |
| 2 | 3039 | Preparación y montaje de materiales para colectividades y catering |
| 2 | 3131 | Lavandería y mantenimiento de lencería en el alojamiento |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.170. 123_2403 — Título Profesional Básico en Actividades de Panadería y Pastelería

- Family: Hostelería y Turismo; qualification category: `fpb`.
- Matching aliases: Actividades de Panadería y Pastelería.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/hosteleria-turismo/actividades-panaderia-pasteleria.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3005 | Atención al cliente |
| 1 | 3133 | Operaciones auxiliares en la industria alimentaria |
| 1 | 3017 | Procesos básicos de pastelería |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3026 | Dispensación en panadería y panadería |
| 2 | 3007 | Procesos básicos de panadería |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.171. 123_2501 — Título Profesional Básico en Acceso y Conservación en Instalaciones Deportivas

- Family: Actividades Físicas y Deportivas; qualification category: `fpb`.
- Matching aliases: Acceso y Conservación en Instalaciones Deportivas.
- Code provenance: todofp.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/actividades-fisicas-deportivas/acceso-y-conservacion-en-instalaciones-deportivas.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3148 | Acceso de usuarios y organización de la instalación físico-deportiva |
| 1 | 3004 | Archivo y comunicación |
| 1 | 3150 | Reparación de averías y reposición de enseres |
| 1 | 3003 | Técnicas administrativas básicas |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3005 | Atención al cliente |
| 2 | 3151 | Operaciones básicas de prevención en las instalaciones deportivas |
| 2 | 3149 | Asistencia en la organización de espacios, actividades y reparto de material en la instalación físico-deportiva |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.172. LOCAL-FPB-5cd056abcc8f — Título Profesional Básico en Cocina y Restauración

- Family: Hostelería y Turismo; qualification category: `fpb`.
- Matching aliases: Cocina y Restauración.
- Code provenance: internal.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13180).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/hosteleria-turismo/cocina-restauracion.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 3005 | Atención al cliente |
| 1 | 3036 | Aprovisionamiento y conservación de materias primas e higiene en la manipulación |
| 1 | 3035 | Procesos básicos de producción culinaria |
| 1 | 3034 | Técnicas elementales de preelaboración |
| 1 | 3161 | Comunicación y Ciencias Sociales I |
| 1 | 3163 | Ciencias Aplicadas I |
| 1 | 3159 | Itinerario personal para la empleabilidad |
| 2 | 3039 | Preparación y montaje de materiales para colectividades y catering |
| 2 | 3038 | Procesos básicos de preparación de alimentos y bebidas |
| 2 | 3037 | Técnicas elementales de servicio |
| 2 | 3162 | Comunicación y Ciencias Sociales II |
| 2 | 3164 | Ciencias Aplicadas II |
| 2 | 3160 | Proyecto intermodular de aprendizaje colaborativo |

### A.173. LOCAL-FPGM-5f9016bca78a — Técnico en Sanidad Ambiental Aplicada

- Family: Seguridad y Medio Ambiente; qualification category: `fpgm`.
- Matching aliases: Sanidad Ambiental Aplicada.
- Code provenance: internal.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-24102).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/seguridad-medio-ambiente/sanidad-ambiental-aplicada.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1697 | Preparación y traslado de materiales en el control de organismos nocivos |
| 1 | 1698 | Control de organismos nocivos, artrópodos y roedores |
| 1 | 1704 | Fundamentos científicos en la sanidad ambiental |
| 1 | 1703 | Control de organismos nocivos mediante desinfección |
| 1 | 1700 | Control de Legionella y otros organismos nocivos en instalaciones de riesgo |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 1701 | Control de organismos nocivos en piscinas y otras instalaciones acuáticas |
| 2 | 1702 | Contro de aves-plaga |
| 2 | 1699 | Control de organismos que alteran la madera y sus derivados |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos (GM) |
| 2 | 1713 | Proyecto Intermodular |

### A.174. LOCAL-FPGM-69aa0c5991b2 — Técnico en Procesado y Transformación de la Madera

- Family: Madera, Mueble y Corcho; qualification category: `fpgm`.
- Matching aliases: Procesado y Transformación de la Madera.
- Code provenance: internal.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/madera-mueble-corcho/tec-procesado-transformacion-madera.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1637 | Recepción y almacén en industrias de la madera |
| 1 | 0538 | Materiales en carpintería y mueble |
| 1 | 1639 | Tratamientos de la madera |
| 1 | 1638 | Aserrado y despiece de la madera |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 1641 | Acabados de tableros |
| 2 | 1640 | Fabricación de tableros |
| 2 | 1643 | Automatización del mecanizado de la madera |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.175. LOCAL-FPGM-7a067fce3734 — Técnico en Servicios Funerarios

- Family: Servicios Socioculturales y a la Comunidad; qualification category: `fpgm`.
- Matching aliases: Servicios Funerarios.
- Code provenance: internal.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2025-14086).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/servicios-socioculturales-comunidad/servicios-funerarios.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1686 | Prestaciones de servicios funerarios |
| 1 | 1690 | Mantenimiento de instalaciones y gestión de almacén |
| 1 | 1691 | Tanatoestética |
| 1 | 1693 | Ofimática aplicada |
| 1 | 0156 | Inglés profesional (GM) |
| 1 | 1709 | Itinerario personal para la empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 2 | 1687 | Protocolo y ceremonias funerarias |
| 2 | 1688 | Cremación |
| 2 | 1689 | Información y operaciones administrativas y de contabilidad de servicios funerarios |
| 2 | 1692 | Transporte, manipulación y exposición del féretro |
| 2 | 0211 | Destrezas sociales |
| 2 | 1664 | Digitalización aplicada a los sectores productivos (GM) |
| 2 | 1710 | Itinerario personal para la empleabilidad II |
| 2 | 1713 | Proyecto Intermodular (GM) |

### A.176. LOCAL-FPGM-c1ecee0eda38 — Técnico en Mantenimiento de Estructuras de Madera y Mobiliario de Embarcaciones de Recreo

- Family: Transporte y Mantenimiento de Vehículos; qualification category: `fpgm`.
- Matching aliases: Mantenimiento de Estructuras de Madera y Mobiliario de Embarcaciones de Recreo.
- Code provenance: internal.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/transporte-mantenimiento-vehiculos/mtmo-estructuras-mobiliario-embarcaciones-recreo.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1618 | Preparación de embarcaciones de recreo para trabajos de mantenimiento |
| 1 | 0538 | Materiales en carpintería y mueble |
| 1 | 0539 | Soluciones constructivas |
| 1 | 0541 | Operaciones básicas de mobiliario |
| 1 | 0540 | Operaciones básicas de carpintería |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0542 | Control de almacén |
| 2 | 1633 | Mantenimiento de elementos interiores de madera y mobiliario de embarcaciones de recreo |
| 2 | 1630 | Mecanizado de elementos de carpintería de ribera |
| 2 | 1631 | Mantenimiento de cubiertas y cascos de madera en embarcaciones de recreo |
| 2 | 1632 | Mantenimiento de elementos estructurales de madera de embarcaciones de recreo |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.177. LOCAL-FPGM-d6ad154784ba — Técnico en Cocina y Gastronomía

- Family: Hostelería y Turismo; qualification category: `fpgm`.
- Matching aliases: Cocina y Gastronomía.
- Code provenance: internal.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13179).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/hosteleria-turismo/cocina-gastronomia.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 0026 | Procesos básicos de pastelería y repostería |
| 1 | 0046 | Preelaboración y conservación de alimentos |
| 1 | 0047 | Técnicas culinarias |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0156 | Inglés profesional (GM) |
| 2 | 0031 | Seguridad e higiene en la manipulación de alimentos |
| 2 | 0045 | Ofertas gastronómicas |
| 2 | 0028 | Postres en restauración |
| 2 | 0048 | Productos culinarios |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1664 | Digitalización aplicada a los sectores productivos |
| 2 | 1713 | Proyecto Intermodular |

### A.178. LOCAL-FPGS-18ceb4f25d83 — Técnico Superior en Prevención de Riesgos Profesionales

- Family: Seguridad y Medio Ambiente; qualification category: `fpgs`.
- Matching aliases: Prevención de Riesgos Profesionales.
- Code provenance: internal.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-20842).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/seguridad-medio-ambiente/prevencion-riesgos-profesionales.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1413 | Riesgos físicos ambientales |
| 1 | 1414 | Riesgos químicos y biológicos ambientales |
| 1 | 1411 | Estructura de la empresa y prevención de riesgos |
| 1 | 1420 | Riesgos relacionados con la seguridad vial |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1415 | Riesgos ergonómicos y psicosociales |
| 2 | 1418 | Ruidos y vibraciones |
| 2 | 1412 | Condiciones de seguridad y seguridad industrial |
| 2 | 1416 | Situaciones de emergencia |
| 2 | 1419 | Gestión de la prevención y responsabilidad jurídica |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1421 | Proyecto Intermodular de prevención de riesgos profesionales |

### A.179. LOCAL-FPGS-458209dda819 — Técnico Superior en Diseño y construcción artesanal de instrumentos musicales de cuerda

- Family: Artes y Artesanías; qualification category: `fpgs`.
- Matching aliases: Diseño y construcción artesanal de instrumentos musicales de cuerda.
- Code provenance: internal.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2026-8022).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/artes-artesanias/construccion-artesanal-instrumentos-de-cuerda.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1750 | Organización de un taller artesano de instrumentos musicales |
| 1 | 1766 | Maderas y materiales para la construcción artesanal de instrumentos musicales |
| 1 | 1767 | Tintes y barnices para la construcción artesanal de instrumentos musicales |
| 1 | 1768 | Diseño del proyecto de construcción artesanal de instrumentos musicales de cuerda |
| 1 | 1769 | Construcción artesanal de guitarras, bandurrias y/o laúdes españoles |
| 1 | 1770 | Ensamblado y montaje artesanal de elementos y piezas de guitarras, bandurrias y laúdes españoles |
| 1 | 1709 | Itinerario personal para la Empleabilidad I |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1771 | Construcción artesanal de piezas de instrumentos musicales de arco |
| 2 | 1772 | Ensamblado y montaje artesanal de elementos y piezas de instrumentos musicales de arco |
| 2 | 1773 | Construcción artesanal de arcos de instrumentos musicales de cuerda |
| 2 | 1774 | Mantenimiento y reparación de arcos de instrumentos musicales de cuerda |
| 1 | 1792 | Acústica musical |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1775 | Proyecto intermodular de Construcción artesanal de instrumentos musicales de cuerda |

### A.180. LOCAL-FPGS-8f0fdc6722a4 — Técnico Superior en Mantenimiento Aeromecánico de Aviones con Motor de Pistón

- Family: Transporte y Mantenimiento de Vehículos; qualification category: `fpgs`.
- Matching aliases: Mantenimiento Aeromecánico de Aviones con Motor de Pistón.
- Code provenance: internal.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/transporte-mantenimiento-vehiculos/mtmo-aeromecanico-aviones-motor-piston.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1425 | Fundamentos de electricidad |
| 1 | 1426 | Fundamentos de electrónica en aeromecánica |
| 1 | 1430 | Materiales, equipos y herramientas en aeromecánica |
| 1 | 1437 | Legislación aeronáutica |
| 1 | 1435 | Aerodinámica básica |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional (GS) |
| 2 | 1428 | Técnicas digitales y Sistemas de instrumentos electrónicos en aeromecánica |
| 2 | 1433 | Prácticas de mantenimiento con elementos de aviónica y servicios de las aeronaves |
| 2 | 1436 | Factores humanos |
| 2 | 1457 | Hélices |
| 2 | 1441 | Aerodinámica, estructuras y sistemas de oxígeno, aguas y protección de aviones |
| 2 | 1442 | Aerodinámica, estructuras y sistemas eléctricos y de aviónica de aviones con motor de pistón |
| 2 | 1443 | Aerodinámica, estructuras y sistemas de mandos de vuelo de aviones con motor de pistón |
| 2 | 1440 | Aerodinámica, estructuras y sistemas hidráulicos, neumáticos y tren de aterrizaje del avión |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1462 | Proyecto Intermodular |
| 3 | 1456 | Motores de pistón |
| 3 | 1432 | Prácticas de mantenimiento con elementos mecánicos de la aeronave |

### A.181. LOCAL-FPGS-fc2434efdbff — Técnico Superior en Mantenimiento Aeromecánico de Helicópteros con Motor de Pistón

- Family: Transporte y Mantenimiento de Vehículos; qualification category: `fpgs`.
- Matching aliases: Mantenimiento Aeromecánico de Helicópteros con Motor de Pistón.
- Code provenance: internal.
- Curriculum: [recorded source](https://www.boe.es/buscar/doc.php?id=BOE-A-2024-13181).
- Qualification: [recorded source](https://www.todofp.es/que-estudiar/familias-profesionales/transporte-mantenimiento-vehiculos/mtmo-aeromecanico-helicopteros-motor-piston.html).

| Level | Module code | Module name |
| --- | --- | --- |
| 1 | 1425 | Fundamentos de electricidad |
| 1 | 1426 | Fundamentos de electrónica en aeromecánica |
| 1 | 1430 | Materiales, equipos y herramientas en aeromecánica |
| 1 | 1437 | Legislación aeronáutica |
| 1 | 1435 | Aerodinámica básica |
| 1 | 1708 | Sostenibilidad aplicada al sistema productivo |
| 1 | 0179 | Inglés profesional(GS) |
| 2 | 1436 | Factores humanos |
| 2 | 1428 | Técnicas digitales y sistemas de instrumentos electrónicos en aeromecánica |
| 2 | 1433 | Prácticas de mantenimiento con elementos de aviónica y servicios de las aeronaves |
| 2 | 1446 | Aerodinámica, estructuras y sistemas de instrumentación, aviónica y luces |
| 2 | 1447 | Aerodinámica, estructuras y teoría de vuelo, mandos de vuelo, sistema de conducción de potencia y rotores |
| 2 | 1448 | Aerodinámica, estructuras y sistemas hidráulico, combustible, neumáticos y de protección en helicópteros |
| 2 | 1449 | Aerodinámica, estructuras, tren de aterrizaje, equipamiento y accesorios de helicópteros |
| 2 | 1710 | Itinerario personal para la Empleabilidad II |
| 2 | 1665 | Digitalización aplicada a los sectores productivos (GS) |
| 2 | 1470 | Proyecto Intermodular |
| 3 | 1456 | Motores de pistón |
| 3 | 1432 | Prácticas de mantenimiento con elementos mecánicos de la aeronave |
