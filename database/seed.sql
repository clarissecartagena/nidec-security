-- Seed data for local/dev testing -- 56 reports across all workflow stages (includes 6 showcase demo reports).
-- Safe to re-run (deletes all rows then reinserts).
--
-- Test password for ALL seeded users:  Password123!
--
-- GA Manager / President (role: ga_president)
--   k.enriquez      / Password123! -- Karen F. Enriquez     (GA President, id: 300553)
--
-- GA Staff (role: ga_staff)
--   l.acosta        / Password123! -- Liza Acosta           (GA Staff,   id: 401157)
--   c.buenconsejo   / Password123! -- Cherry Buenconsejo    (GA Staff,   id: 1200385)
--
-- Security (role: security)
--   b.esteban       / Password123! -- Benjamin D. Esteban   (Security, NCFL External, id: 8810183)
--   e.corrales      / Password123! -- Efren M. Corrales     (Security, NCFL Internal, id: 8810305)
--   c.provido       / Password123! -- Christian Provido     (Security, NPFL Internal, id: 8810279)
--   j.ruazol        / Password123! -- Jayson Ruazol         (Security, NPFL External, id: 8810222)
--
-- PIC / Person-In-Charge (role: department)
--   a.mendoza       / Password123! -- Ana Mendoza          (PIC, Facilities & Maintenance, id: 4)
--   c.bautista      / Password123! -- Carlos Bautista      (PIC, Information Technology,   id: 5)
--   e.cruz          / Password123! -- Elena Cruz           (PIC, Human Resources,           id: 6)
--   r.villanueva    / Password123! -- Roberto Villanueva   (PIC, Operations,                id: 7)
--   m.torres        / Password123! -- Maricel Torres       (PIC, Quality Assurance,         id: 8)

SET NAMES utf8mb4;

-- ------------------------------------------------------------------
-- Wipe in child -> parent order to satisfy FK constraints
-- ------------------------------------------------------------------
DELETE FROM notifications;
DELETE FROM report_status_history;
DELETE FROM report_attachments;
DELETE FROM security_final_checks;
DELETE FROM department_actions;
DELETE FROM ga_president_approvals;
DELETE FROM ga_staff_reviews;
DELETE FROM reports;
DELETE FROM users;
DELETE FROM departments;

ALTER TABLE notifications          AUTO_INCREMENT = 1;
ALTER TABLE report_status_history  AUTO_INCREMENT = 1;
ALTER TABLE report_attachments     AUTO_INCREMENT = 1;
ALTER TABLE security_final_checks  AUTO_INCREMENT = 1;
ALTER TABLE department_actions     AUTO_INCREMENT = 1;
ALTER TABLE ga_president_approvals AUTO_INCREMENT = 1;
ALTER TABLE ga_staff_reviews       AUTO_INCREMENT = 1;
ALTER TABLE reports                AUTO_INCREMENT = 1;
ALTER TABLE departments            AUTO_INCREMENT = 1;

-- bcrypt of 'Password123!'
SET @PWD := '$2y$10$.iMox6CY98Js2VAK2aUdx.SaLjZQN5h18NNJzBfER4hdk7Z4.RdFu';

-- ==================================================================
-- DEPARTMENTS  (16 rows)
-- ==================================================================
INSERT INTO departments (id, name, is_active, created_at) VALUES
  ( 1, 'Quality Assurance',       1, '2026-01-15 08:00:00'),
  ( 2, 'Engineering Component',   1, '2026-01-15 08:00:00'),
  ( 3, 'Production Component',    1, '2026-01-15 08:00:00'),
  ( 4, 'Engineering Module',      1, '2026-01-15 08:00:00'),
  ( 5, 'L Office-Maint',          1, '2026-01-15 08:00:00'),
  ( 6, 'Administration',          1, '2026-01-15 08:00:00'),
  ( 7, 'Others',                  1, '2026-01-15 08:00:00'),
  ( 8, 'NCFL',                    1, '2026-01-15 08:00:00'),
  ( 9, 'Purchasing',              1, '2026-01-15 08:00:00'),
  (10, 'L Office-QC',             1, '2026-01-15 08:00:00'),
  (11, 'PSD Office-HR',           1, '2026-01-15 08:00:00'),
  (12, 'Accounting and Finance',  1, '2026-01-15 08:00:00'),
  (13, 'Technical Support',       1, '2026-01-15 08:00:00'),
  (14, 'Production',              1, '2026-01-15 08:00:00'),
  (15, 'QA Commin',               1, '2026-01-15 08:00:00'),
  (16, 'Process Control',         1, '2026-01-15 08:00:00');

-- ==================================================================
-- USERS  (12 rows)
-- employee_no map:
--   '300553'  = Karen F. Enriquez   (ga_president)
--   '401157'  = Liza Acosta         (ga_staff)
--   '1200385' = Cherry Buenconsejo  (ga_staff)
--   '4'       = Ana Mendoza         (department, dept_id 1: Quality Assurance)
--   '5'       = Carlos Bautista     (department, dept_id 2: Engineering Component)
--   '6'       = Elena Cruz          (department, dept_id 3: Production Component)
--   '7'       = Roberto Villanueva  (department, dept_id 4: Engineering Module)
--   '8'       = Maricel Torres      (department, dept_id 5: L Office-Maint)
--   '8810183' = Benjamin Esteban    (security, NCFL External)
--   '8810305' = Efren Corrales      (security, NCFL Internal)
--   '8810279' = Christian Provido   (security, NPFL Internal)
--   '8810222' = Jayson Ruazol       (security, NPFL External)
-- ==================================================================
INSERT INTO users (employee_no, name, username, password_hash, role, department_id, security_type, entity, account_status, created_by_role, created_by_employee_no, created_at) VALUES
  ('300553',  'Karen F. Enriquez',          'k.enriquez',    @PWD, 'ga_president', NULL, NULL, NULL, 'active', 'system',       NULL,      '2026-01-15 09:00:00'),
  ('401157',  'Liza Acosta',                'l.acosta',      @PWD, 'ga_staff',     NULL, NULL, NULL, 'active', 'system',       '300553',  '2026-01-15 09:05:00'),
  ('1200385', 'Cherry Novelyn Buenconsejo', 'c.buenconsejo', @PWD, 'ga_staff',     NULL, NULL, NULL, 'active', 'ga_president', '300553',  '2026-01-15 09:10:00');

INSERT INTO users (employee_no, name, username, password_hash, role, department_id, security_type, entity, account_status, created_by_role, created_by_employee_no, created_at) VALUES
  (4, 'Ana Mendoza',        'a.mendoza',    @PWD, 'department', 1, NULL, NULL, 'active', 'ga_staff', '300553',  '2026-01-15 09:20:00'),
  (5, 'Carlos Bautista',    'c.bautista',   @PWD, 'department', 2, NULL, NULL, 'active', 'ga_staff', '300553',  '2026-01-15 09:21:00'),
  (6, 'Elena Cruz',         'e.cruz',       @PWD, 'department', 3, NULL, NULL, 'active', 'ga_staff', '401157',  '2026-01-15 09:22:00'),
  (7, 'Roberto Villanueva', 'r.villanueva', @PWD, 'department', 4, NULL, NULL, 'active', 'ga_staff', '401157',  '2026-01-15 09:23:00'),
  (8, 'Maricel Torres',     'm.torres',     @PWD, 'department', 5, NULL, NULL, 'active', 'ga_staff', '1200385', '2026-01-15 09:24:00');

-- NOTE: security_type is REQUIRED for the PDF template to work correctly.
--   internal -> ARAGON header;  external -> SISCO header
INSERT INTO users (employee_no, name, username, password_hash, role, department_id, security_type, entity, account_status, created_by_role, created_by_employee_no, created_at) VALUES
  ('8810183', 'Benjamin D. Esteban', 'b.esteban',  @PWD, 'security', NULL, 'external', 'NCFL', 'active', 'ga_staff', '300553', '2026-01-15 09:30:00'),
  ('8810305', 'Efren M. Corrales',   'e.corrales', @PWD, 'security', NULL, 'internal', 'NCFL', 'active', 'ga_staff', '300553', '2026-01-15 09:31:00'),
  ('8810279', 'Christian Provido',   'c.provido',  @PWD, 'security', NULL, 'internal', 'NPFL', 'active', 'ga_staff', '401157', '2026-01-15 09:32:00'),
  ('8810222', 'Jayson Ruazol',       'j.ruazol',   @PWD, 'security', NULL, 'external', 'NPFL', 'active', 'ga_staff', '401157', '2026-01-15 09:33:00');

-- ==================================================================
-- REPORTS  (56 rows)
-- submitted_by employee_no map (security users):
--   '8810305' = Efren Corrales   (NCFL Internal)
--   '8810183' = Benjamin Esteban (NCFL External)
--   '8810279' = Christian Provido(NPFL Internal)
--   '8810222' = Jayson Ruazol    (NPFL External)
-- ==================================================================
INSERT INTO reports (
  id, report_no, subject, category, location, severity, building, responsible_department_id,
  details, actions_taken, remarks,
  submitted_by, submitted_at, current_reviewer,
  fix_due_date, resolved_by_security, resolved_at,
  returned_by_security, returned_at, security_remarks,
  reopen_count, status, updated_at
) VALUES

-- BLOCK 1: submitted_to_ga_staff (1-8)
(1,  'SR-2026-0001', 'Blocked Emergency Exit',          'Fire Safety',    'NCFL Warehouse Exit A',        'high',     'NCFL', 1, 'Emergency exit route blocked by stacked pallets. Cannot be fully opened.',           'Area cleared temporarily; warning signs placed.',            'Request permanent keep-clear floor marking.', '8810305', '2026-03-01 07:15:00', 'ga_staff',   NULL, NULL, NULL, NULL, NULL, NULL, 0, 'submitted_to_ga_staff', '2026-03-01 07:15:00'),
(2,  'SR-2026-0002', 'Server Room Door Left Ajar',       'IT Security',    'NCFL Server Room B3',          'critical', 'NCFL', 2, 'Server room door propped open with a door stopper for most of the afternoon.',       'Door wedge removed; access badge re-programmed.',            'Audit badge access logs for unauthorized entry.', '8810305', '2026-03-01 09:30:00', 'ga_staff',   NULL, NULL, NULL, NULL, NULL, NULL, 0, 'submitted_to_ga_staff', '2026-03-01 09:30:00'),
(3,  'SR-2026-0003', 'Wet Floor Near Electrical Panel',  'Electrical',     'NPFL Sub-station Corridor',    'high',     'NPFL', 1, 'Water pooling from roof leak is within 50 cm of a live electrical panel.',           'Bucket placed; area cordoned off.',                          'Roof and panel must be inspected ASAP.', '8810222', '2026-03-01 11:00:00', 'ga_staff',   NULL, NULL, NULL, NULL, NULL, NULL, 0, 'submitted_to_ga_staff', '2026-03-01 11:00:00'),
(4,  'SR-2026-0004', 'Visitor Badge Printer Offline',    'Access Control', 'NPFL Main Reception',          'low',      'NPFL', 2, 'Badge printer not detected by workstation; visitors must be logged manually.',      'Manual logbook implemented as interim measure.',             'IT to reinstall driver and run test batch.', '8810279', '2026-03-01 13:45:00', 'ga_staff',   NULL, NULL, NULL, NULL, NULL, NULL, 0, 'submitted_to_ga_staff', '2026-03-01 13:45:00'),
(5,  'SR-2026-0005', 'Expired First Aid Kit',             'Compliance',     'NCFL Production Floor C',      'low',      'NCFL', 5, 'First aid kit found expired; 11 of 14 items past use-by date.',                     NULL,                                                         'Replace entire kit and schedule quarterly check.', '8810305', '2026-02-28 08:00:00', 'ga_staff',   NULL, NULL, NULL, NULL, NULL, NULL, 0, 'submitted_to_ga_staff', '2026-02-28 08:00:00'),
(6,  'SR-2026-0006', 'Unauthorized Vehicle in Lot',      'Access Control', 'NPFL Parking Zone D',          'medium',   'NPFL', 4, 'Unregistered vehicle parked in restricted zone for over 8 hours.',                  'Vehicle owner identified and warned.',                       'Review parking permit system and signage.', '8810183', '2026-02-28 10:20:00', 'ga_staff',   NULL, NULL, NULL, NULL, NULL, NULL, 0, 'submitted_to_ga_staff', '2026-02-28 10:20:00'),
(7,  'SR-2026-0007', 'CCTV Camera Not Recording',        'Surveillance',   'NCFL Gate 2',                  'medium',   'NCFL', 1, 'CCTV camera powered but not writing to NVR. Gap of ~36 hours in footage.',           'Camera reset attempted; issue persists.',                    'Repair or replace camera module urgently.', '8810305', '2026-02-27 14:10:00', 'ga_staff',   NULL, NULL, NULL, NULL, NULL, NULL, 0, 'submitted_to_ga_staff', '2026-02-27 14:10:00'),
(8,  'SR-2026-0008', 'Missing Safety Goggles at Station','Compliance',     'NPFL Assembly Line 4',         'medium',   'NPFL', 5, 'Safety goggles missing from 6 of 10 workstations on assembly line 4.',              NULL,                                                         'Restock goggles and implement sign-out sheet.', '8810222', '2026-02-27 16:00:00', 'ga_staff',   NULL, NULL, NULL, NULL, NULL, NULL, 0, 'submitted_to_ga_staff', '2026-02-27 16:00:00'),

-- BLOCK 2: submitted_to_ga_president (9-14)
(9,  'SR-2026-0009', 'Broken Perimeter Fence Section',   'Structural',     'NPFL North Perimeter',         'high',     'NPFL', 1, '3-meter section of perimeter fence collapsed after heavy rain.',                    'Temporary barrier erected.',                                 'Permanent repair and structural assessment needed.', '8810222', '2026-02-25 08:00:00', 'ga_president', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'submitted_to_ga_president', '2026-02-25 14:00:00'),
(10, 'SR-2026-0010', 'HVAC Failure in Server Room',      'Environmental',  'NCFL Server Room A1',          'critical', 'NCFL', 2, 'HVAC unit shutdown; server room reached 38C. Equipment auto-shutdown triggered.',   'Portable cooler deployed; servers back online.',             'Replace HVAC unit before next heatwave.', '8810305', '2026-02-25 09:30:00', 'ga_president', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'submitted_to_ga_president', '2026-02-25 15:30:00'),
(11, 'SR-2026-0011', 'Fire Door Propped Open',            'Fire Safety',    'NCFL Building B Stairwell',    'high',     'NCFL', 1, 'Fire door on floor 2 propped open with a wooden block; alarm bypassed.',            'Block removed immediately.',                                 'Review building fire safety compliance.', '8810183', '2026-02-24 10:00:00', 'ga_president', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'submitted_to_ga_president', '2026-02-24 16:00:00'),
(12, 'SR-2026-0012', 'Tailgating Incident at Turnstile', 'Access Control', 'NPFL Main Entrance Turnstile', 'high',     'NPFL', 4, 'Three individuals gained entry without badging by following an authorized person.', 'Incident logged; individuals identified on CCTV.',           'Retrain staff and add signage on tailgating.', '8810222', '2026-02-24 11:30:00', 'ga_president', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'submitted_to_ga_president', '2026-02-24 17:30:00'),
(13, 'SR-2026-0013', 'Chemical Storage Non-Compliance',   'Compliance',     'NCFL Chemical Store Room 3',   'critical', 'NCFL', 5, 'Incompatible chemicals stored adjacently; no secondary containment.',               'Area sealed; QA notified immediately.',                      'Full audit and corrective racking required.', '8810305', '2026-02-23 08:45:00', 'ga_president', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'submitted_to_ga_president', '2026-02-23 14:45:00'),
(14, 'SR-2026-0014', 'Biometric Reader Malfunction',     'Access Control', 'NPFL Lab Entry 2',             'medium',   'NPFL', 2, 'Biometric reader rejecting valid thumbprints; staff propping door to compensate.',  'Guard stationed at door as interim.',                        'Replace or recalibrate reader; audit propping.', '8810279', '2026-02-23 13:00:00', 'ga_president', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'submitted_to_ga_president', '2026-02-23 18:00:00'),

-- BLOCK 3: sent_to_department (15-19)
(15, 'SR-2026-0015', 'Drainage Blockage - Compound',     'Structural',     'NPFL Rear Compound',           'medium',   'NPFL', 1, 'Main drainage channel blocked; standing water after rain spilling toward workshop.', 'Temporary pump deployed.',                                  'Clear drain and schedule preventive maintenance.', '8810222', '2026-02-22 07:00:00', 'department', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'sent_to_department', '2026-02-22 15:00:00'),
(16, 'SR-2026-0016', 'Network Switch Failure',            'IT Security',    'NCFL Floor 3 Comms Room',      'high',     'NCFL', 2, 'Core network switch failed; 40+ workstations offline for 4 hours.',                 'Failover switch activated; investigating root cause.',       'Replace failed unit and review redundancy plan.', '8810305', '2026-02-22 09:15:00', 'department', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'sent_to_department', '2026-02-22 17:00:00'),
(17, 'SR-2026-0017', 'Generator Fuel Level Critical',    'Electrical',     'NCFL Generator Room',          'high',     'NCFL', 4, 'Backup generator fuel at 8%. Scheduled refill was missed last cycle.',              'Emergency refuel arranged for tomorrow.',                    'Add automated low-fuel alert to BMS.', '8810183', '2026-02-22 11:00:00', 'department', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'sent_to_department', '2026-02-22 17:30:00'),
(18, 'SR-2026-0018', 'Loading Dock Gate Broken',         'Structural',     'NPFL Loading Dock 2',          'medium',   'NPFL', 4, 'Electric gate motor failed; gate stuck half-open, cannot be secured.',              'Gate chained; guard posted overnight.',                      'Repair motor and test fail-safe locking.', '8810222', '2026-02-21 14:00:00', 'department', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'sent_to_department', '2026-02-21 20:00:00'),
(19, 'SR-2026-0019', 'HR Records Room Door Lock Broken', 'Access Control', 'NCFL HR Office Annex',         'medium',   'NCFL', 3, 'Door lock cylinder broken; sensitive HR files accessible without authorization.',   'Door secured with padlock as interim.',                      'Replace lock and review who has key access.', '8810183', '2026-02-21 16:30:00', 'department', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'sent_to_department', '2026-02-21 22:00:00'),

-- BLOCK 4: under_department_fix (20-27)
(20, 'SR-2026-0020', 'Sprinkler Head Damaged',           'Fire Safety',    'NCFL Warehouse East',          'critical', 'NCFL', 1, 'Sprinkler head physically damaged by forklift; inactive zone until repaired.',      'Affected zone wet-standby. Forklift driver documented.',     'Replace head and inspect adjacent heads.', '8810305', '2026-02-20 06:30:00', 'department', '2026-03-08 17:00:00', NULL, NULL, NULL, NULL, NULL, 0, 'under_department_fix', '2026-02-21 09:00:00'),
(21, 'SR-2026-0021', 'Parking Lot Lighting Failure',     'Electrical',     'NPFL Parking Zone A',          'low',      'NPFL', 1, 'Six floodlights in parking zone A are non-functional; safety risk at night.',       'Temporary portable lights deployed.',                        'Replace lamp drivers and inspect wiring.', '8810222', '2026-02-20 18:00:00', 'department', '2026-03-10 17:00:00', NULL, NULL, NULL, NULL, NULL, 0, 'under_department_fix', '2026-02-21 10:00:00'),
(22, 'SR-2026-0022', 'Water Leak from Ceiling',          'Structural',     'NCFL Admin Block Room 201',    'medium',   'NCFL', 1, 'Ceiling water stain spreading; active drip during rain.',                           'Bucket placed; ceiling tiles removed for inspection.',       'Roof waterproofing repair required.', '8810183', '2026-02-19 09:00:00', 'department', '2026-03-15 17:00:00', NULL, NULL, NULL, NULL, NULL, 0, 'under_department_fix', '2026-02-20 10:00:00'),
(23, 'SR-2026-0023', 'CCTV Blind Spot at Main Gate',    'Surveillance',   'NPFL Main Gate',               'medium',   'NPFL', 2, 'New fence repositioning created a 5-meter blind spot not covered by any camera.',  NULL,                                                         'Install additional camera to eliminate blind spot.', '8810222', '2026-02-19 10:30:00', 'department', '2026-03-12 17:00:00', NULL, NULL, NULL, NULL, NULL, 0, 'under_department_fix', '2026-02-20 11:00:00'),
(24, 'SR-2026-0024', 'Emergency Phone Line Dead',        'Access Control', 'NCFL Fire Assembly Point B',   'high',     'NCFL', 4, 'Emergency phone at assembly point B completely dead; no dial tone.',                'Redirect signage to nearby phone installed.',                'Test all emergency phones monthly.', '8810305', '2026-02-18 08:00:00', 'department', '2026-03-06 17:00:00', NULL, NULL, NULL, NULL, NULL, 0, 'under_department_fix', '2026-02-19 09:00:00'),
(25, 'SR-2026-0025', 'Forklift Operator Without Badge',  'Access Control', 'NPFL Dock Area 3',             'medium',   'NPFL', 4, 'Forklift operator operating in restricted zone without displaying required badge.',  'Operator warned; supervisor notified.',                      'Briefing for all dock operators; random audits.', '8810222', '2026-02-18 11:00:00', 'department', '2026-03-05 17:00:00', NULL, NULL, NULL, NULL, NULL, 0, 'under_department_fix', '2026-02-19 12:00:00'),
(26, 'SR-2026-0026', 'Minor Chemical Spill in Lab',      'Environmental',  'NCFL Lab 4',                   'high',     'NCFL', 5, 'Isopropyl alcohol spill approx 500 mL; staff evacuated; no injuries.',             'Spill contained with absorbent material.',                   'Review SDS compliance and spill kit stocks.', '8810305', '2026-02-17 14:00:00', 'department', '2026-03-07 17:00:00', NULL, NULL, NULL, NULL, NULL, 0, 'under_department_fix', '2026-02-18 10:00:00'),
(27, 'SR-2026-0027', 'Roof Drainage Overflow',           'Structural',     'NPFL Production Hall B',       'medium',   'NPFL', 1, 'Roof drainage overflow sends water down exterior wall; internal seepage observed.', 'Sandbags placed inside; roof inspection requested.',         'Clear drain channels and seal wall penetration.', '8810222', '2026-02-17 09:00:00', 'department', '2026-03-20 17:00:00', NULL, NULL, NULL, NULL, NULL, 0, 'under_department_fix', '2026-02-18 11:00:00'),

-- BLOCK 5: for_security_final_check (28-32)
(28, 'SR-2026-0028', 'Broken Door Lock Replaced',        'Access Control', 'NCFL Storeroom B7',            'medium',   'NCFL', 2, 'Door lock cylinder on storeroom B7 was broken; unauthorized access possible.',     'Lock fully replaced by IT facilities vendor.',               'Access log audit completed post-fix.', '8810305', '2026-02-15 08:00:00', 'security',   NULL, NULL, NULL, NULL, NULL, NULL, 0, 'for_security_final_check', '2026-02-22 14:00:00'),
(29, 'SR-2026-0029', 'Stairwell Lighting Replaced',       'Electrical',     'NPFL Stairwell 3',             'low',      'NPFL', 1, 'Stairwell 3 lights failed; safety risk for staff using stairs at shift end.',      'All 4 light fittings replaced and tested.',                  'Mark stairwell for scheduled annual check.', '8810222', '2026-02-15 09:00:00', 'security',   NULL, NULL, NULL, NULL, NULL, NULL, 0, 'for_security_final_check', '2026-02-22 16:00:00'),
(30, 'SR-2026-0030', 'Improper Waste Disposal',           'Environmental',  'NCFL Rear Yard Bins',          'medium',   'NCFL', 5, 'Hazardous waste mixed with general waste in rear yard bins.',                       'Bins separated; hazardous waste removed by contractor.',     'Monthly disposal audit required.', '8810305', '2026-02-14 07:30:00', 'security',   NULL, NULL, NULL, NULL, NULL, NULL, 0, 'for_security_final_check', '2026-02-21 10:00:00'),
(31, 'SR-2026-0031', 'Pest Infestation Rear Canteen',     'Environmental',  'NPFL Staff Canteen',           'low',      'NPFL', 1, 'Rodent droppings found in canteen store room; canteen temporarily closed.',         'Pest control vendor engaged; canteen sealed.',               'Follow-up inspection in 2 weeks after treatment.', '8810222', '2026-02-13 08:00:00', 'security',   NULL, NULL, NULL, NULL, NULL, NULL, 0, 'for_security_final_check', '2026-02-20 09:00:00'),
(32, 'SR-2026-0032', 'Window Lock Broken Office Block',   'Structural',     'NCFL Office Block Floor 1',    'medium',   'NCFL', 2, 'Three ground-floor window locks broken; windows cannot be secured after hours.',   'Windows taped shut as interim; facilities notified.',        'Replace lock mechanisms on all three windows.', '8810305', '2026-02-12 10:00:00', 'security',   NULL, NULL, NULL, NULL, NULL, NULL, 0, 'for_security_final_check', '2026-02-19 12:00:00'),

-- BLOCK 6: returned_to_department (33-36, reopen_count=1)
(33, 'SR-2026-0033', 'Access Log Gaps Detected',          'Access Control', 'NPFL Production Access Gate', 'high',     'NPFL', 2, '2-hour access log gap detected; system appeared to be logging but saving to /dev/null.', 'Vendor contacted; logs partially recovered.',           'Full log integrity audit required post-fix.', '8810222', '2026-02-10 08:00:00', 'department', NULL, NULL, NULL, '8810222', '2026-02-28 10:00:00', 'Fix incomplete - log gaps still present in test run.', 1, 'returned_to_department', '2026-02-28 10:00:00'),
(34, 'SR-2026-0034', 'Exposed Electrical Panel',          'Electrical',     'NCFL Maintenance Bay 2',       'critical', 'NCFL', 1, 'Cover plate missing from live 400V panel; direct exposure risk to staff.',          'Area immediately barricaded.',                               'Replace panel cover and inspect all bays.', '8810305', '2026-02-09 09:30:00', 'department', NULL, NULL, NULL, '8810305', '2026-02-27 14:00:00', 'Cover installed but panel earth bond still loose - not safe.', 1, 'returned_to_department', '2026-02-27 14:00:00'),
(35, 'SR-2026-0035', 'Unsafe Ladder Storage',             'Structural',     'NPFL Maintenance Workshop',    'medium',   'NPFL', 4, 'Ladders stored horizontally on unsecured brackets; risk of falling on staff.',      'Ladders roped off; storage area marked for review.',         'Install vertical secure ladder rack.', '8810222', '2026-02-08 11:00:00', 'department', NULL, NULL, NULL, '8810222', '2026-03-01 09:00:00', 'Bracket installed but wrong specification - still unstable.', 1, 'returned_to_department', '2026-03-01 09:00:00'),
(36, 'SR-2026-0036', 'AC Unit Oil Leak',                  'Environmental',  'NCFL Production Floor A',      'medium',   'NCFL', 1, 'AC unit compressor leaking oil onto production floor below; slip risk.',            'Drip tray placed; production line halted below unit.',       'Service AC unit and verify oil seal integrity.', '8810305', '2026-02-07 13:00:00', 'department', NULL, NULL, NULL, '8810305', '2026-03-02 11:00:00', 'Oil leak resumed within 48 hours of reported seal fix.', 1, 'returned_to_department', '2026-03-02 11:00:00'),

-- BLOCK 7: resolved (37-46)
(37, 'SR-2026-0037', 'Broken Window Repaired',            'Structural',     'NPFL Visitor Centre',          'low',      'NPFL', 1, 'Ground floor window cracked; potential security breach point.',                     'Window replaced with laminated safety glass.',               'Check all ground-floor windows quarterly.', '8810222', '2026-02-05 08:00:00', NULL, NULL, '8810222', '2026-02-17 10:00:00', NULL, NULL, NULL, 0, 'resolved', '2026-02-17 10:00:00'),
(38, 'SR-2026-0038', 'Fire Drill Not Conducted',          'Compliance',     'NCFL Entire Site',             'medium',   'NCFL', 5, 'Scheduled quarterly fire drill was skipped without documentation.',                 'Drill rescheduled and completed with 98% attendance.',       'Update drill schedule and enforce sign-off.', '8810305', '2026-02-04 09:00:00', NULL, NULL, '8810305', '2026-02-14 11:00:00', NULL, NULL, NULL, 0, 'resolved', '2026-02-14 11:00:00'),
(39, 'SR-2026-0039', 'Exit Light Battery Dead',           'Fire Safety',    'NPFL Production Hall A',       'medium',   'NPFL', 1, 'Three emergency exit lights not illuminating during power-off test.',               'Batteries replaced in all three units.',                     'Test all emergency lights every 6 months.', '8810222', '2026-02-04 10:30:00', NULL, NULL, '8810222', '2026-02-13 14:00:00', NULL, NULL, NULL, 0, 'resolved', '2026-02-13 14:00:00'),
(40, 'SR-2026-0040', 'Laptop Theft from Open Office',     'Access Control', 'NCFL Open Office Zone B',      'high',     'NCFL', 2, 'Laptop reported stolen; no CCTV coverage on desk area.',                           'Incident reported to police; CCTV extended.',                'Cable locks mandatory for all unattended laptops.', '8810305', '2026-02-03 14:00:00', NULL, NULL, '8810305', '2026-02-12 16:00:00', NULL, NULL, NULL, 0, 'resolved', '2026-02-12 16:00:00'),
(41, 'SR-2026-0041', 'Unsafe Chemical Disposal',          'Environmental',  'NPFL Chemical Yard',           'critical', 'NPFL', 5, 'Contractor observed disposing of chemical waste directly into site drain.',         'Contractor immediately stopped and escorted off site.',      'Ban contractor; report to environmental agency.', '8810222', '2026-02-02 07:30:00', NULL, NULL, '8810222', '2026-02-10 10:00:00', NULL, NULL, NULL, 0, 'resolved', '2026-02-10 10:00:00'),
(42, 'SR-2026-0042', 'Perimeter Fence Gap Found',         'Structural',     'NCFL East Perimeter',          'medium',   'NCFL', 1, '1.5-meter gap in perimeter fencing discovered during patrol.',                      'Temporary hoarding installed overnight.',                    'Install permanent fence panel and inspect fence.', '8810305', '2026-02-01 06:00:00', NULL, NULL, '8810305', '2026-02-09 14:00:00', NULL, NULL, NULL, 0, 'resolved', '2026-02-09 14:00:00'),
(43, 'SR-2026-0043', 'Visitor Overstay Incident',         'Access Control', 'NPFL Meeting Room Block',      'medium',   'NPFL', 4, 'Visitor badge valid for 1 day; visitor accessed site on day 3 without re-badge.',  'Visitor system updated with stricter expiry check.',         'Audit visitor software configuration.', '8810222', '2026-01-30 11:00:00', NULL, NULL, '8810222', '2026-02-07 09:00:00', NULL, NULL, NULL, 0, 'resolved', '2026-02-07 09:00:00'),
(44, 'SR-2026-0044', 'Trip Hazard Raised Flooring',       'Structural',     'NCFL Corridor 4B',             'low',      'NCFL', 1, 'Raised floor panel edge creating trip hazard; one minor incident reported.',        'Panel reseated and taped; area marked.',                     'Inspect all raised floor panels monthly.', '8810305', '2026-01-29 09:00:00', NULL, NULL, '8810305', '2026-02-05 15:00:00', NULL, NULL, NULL, 0, 'resolved', '2026-02-05 15:00:00'),
(45, 'SR-2026-0045', 'Unauthorized Access Attempt',       'Access Control', 'NPFL R&D Lab Entry',           'critical', 'NPFL', 2, 'Individual attempted to tailgate into R&D lab; badge rejected, forced door.',      'Security detained individual; police called.',               'Review physical security layering on R&D entry.', '8810222', '2026-01-28 22:00:00', NULL, NULL, '8810222', '2026-02-04 10:00:00', NULL, NULL, NULL, 0, 'resolved', '2026-02-04 10:00:00'),
(46, 'SR-2026-0046', 'Minor Gas Leak in Plant Room',      'Environmental',  'NCFL Plant Room 1',            'high',     'NCFL', 4, 'Gas detector alarmed in plant room; small LPG leak at valve fitting.',              'Plant room evacuated; supply valve closed.',                 'Replace valve fitting and recalibrate sensor.', '8810305', '2026-01-28 07:00:00', NULL, NULL, '8810305', '2026-02-03 12:00:00', NULL, NULL, NULL, 0, 'resolved', '2026-02-03 12:00:00'),

-- BLOCK 8: rejected (47-50)
(47, 'SR-2026-0047', 'False Alarm CCTV Movement Alert',  'Surveillance',   'NPFL Roof Area',               'low',      'NPFL', 1, 'CCTV motion alert triggered repeatedly by pigeons on roof camera.',                NULL, NULL, '8810222', '2026-01-27 10:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'rejected', '2026-01-28 14:00:00'),
(48, 'SR-2026-0048', 'Minor Vandalism to Notice Board',  'Structural',     'NCFL Staff Corridor 2',        'low',      'NCFL', 3, 'Notice board defaced with marker pen; cosmetic damage only.',                       NULL, NULL, '8810305', '2026-01-26 14:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'rejected', '2026-01-27 11:00:00'),
(49, 'SR-2026-0049', 'Missing Procedure Paperwork',      'Compliance',     'NPFL QA Office',               'low',      'NPFL', 5, 'One procedure document not filed in QA binder; procedural non-compliance.',        NULL, NULL, '8810222', '2026-01-25 09:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'rejected', '2026-01-26 10:00:00'),
(50, 'SR-2026-0050', 'Parking Dispute Between Staff',    'Access Control', 'NCFL Car Park B',              'low',      'NCFL', 3, 'Two staff members in dispute over an allocated parking space.',                     NULL, NULL, '8810305', '2026-01-25 11:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'rejected', '2026-01-26 11:00:00');

-- ==================================================================
-- GA STAFF REVIEWS
-- Reports 9-50 passed through GA Staff (42 rows).
-- reviewed_by: 401157 = Liza Acosta, 1200385 = Cherry Buenconsejo
-- ==================================================================
INSERT INTO ga_staff_reviews (id, report_id, reviewed_by, decision, notes, reviewed_at) VALUES
  (1,  9,  '401157',  'forwarded', 'Reviewed and forwarded.', '2026-02-25 12:00:00'),
  (2,  10, '1200385', 'forwarded', 'Reviewed and forwarded.', '2026-02-25 13:30:00'),
  (3,  11, '401157',  'forwarded', 'Reviewed and forwarded.', '2026-02-24 14:00:00'),
  (4,  12, '1200385', 'forwarded', 'Reviewed and forwarded.', '2026-02-24 15:30:00'),
  (5,  13, '401157',  'forwarded', 'Reviewed and forwarded.', '2026-02-23 12:45:00'),
  (6,  14, '1200385', 'forwarded', 'Reviewed and forwarded.', '2026-02-23 16:00:00'),
  (7,  15, '401157',  'forwarded', 'Reviewed and forwarded.', '2026-02-22 13:00:00'),
  (8,  16, '1200385', 'forwarded', 'Reviewed and forwarded.', '2026-02-22 14:30:00'),
  (9,  17, '401157',  'forwarded', 'Reviewed and forwarded.', '2026-02-22 15:00:00'),
  (10, 18, '1200385', 'forwarded', 'Reviewed and forwarded.', '2026-02-21 17:00:00'),
  (11, 19, '401157',  'forwarded', 'Reviewed and forwarded.', '2026-02-21 20:00:00'),
  (12, 20, '1200385', 'forwarded', 'Reviewed and forwarded.', '2026-02-20 10:00:00'),
  (13, 21, '401157',  'forwarded', 'Reviewed and forwarded.', '2026-02-20 20:00:00'),
  (14, 22, '1200385', 'forwarded', 'Reviewed and forwarded.', '2026-02-19 11:00:00'),
  (15, 23, '401157',  'forwarded', 'Reviewed and forwarded.', '2026-02-19 12:30:00'),
  (16, 24, '1200385', 'forwarded', 'Reviewed and forwarded.', '2026-02-18 10:00:00'),
  (17, 25, '401157',  'forwarded', 'Reviewed and forwarded.', '2026-02-18 13:00:00'),
  (18, 26, '1200385', 'forwarded', 'Reviewed and forwarded.', '2026-02-17 16:00:00'),
  (19, 27, '401157',  'forwarded', 'Reviewed and forwarded.', '2026-02-17 11:00:00'),
  (20, 28, '1200385', 'forwarded', 'Reviewed and forwarded.', '2026-02-15 10:00:00'),
  (21, 29, '401157',  'forwarded', 'Reviewed and forwarded.', '2026-02-15 11:00:00'),
  (22, 30, '1200385', 'forwarded', 'Reviewed and forwarded.', '2026-02-14 09:30:00'),
  (23, 31, '401157',  'forwarded', 'Reviewed and forwarded.', '2026-02-13 10:00:00'),
  (24, 32, '1200385', 'forwarded', 'Reviewed and forwarded.', '2026-02-12 12:00:00'),
  (25, 33, '401157',  'forwarded', 'Reviewed and forwarded.', '2026-02-10 10:00:00'),
  (26, 34, '1200385', 'forwarded', 'Reviewed and forwarded.', '2026-02-09 11:30:00'),
  (27, 35, '401157',  'forwarded', 'Reviewed and forwarded.', '2026-02-08 13:00:00'),
  (28, 36, '1200385', 'forwarded', 'Reviewed and forwarded.', '2026-02-07 15:00:00'),
  (29, 37, '401157',  'forwarded', 'Reviewed and forwarded.', '2026-02-05 10:00:00'),
  (30, 38, '1200385', 'forwarded', 'Reviewed and forwarded.', '2026-02-04 11:00:00'),
  (31, 39, '401157',  'forwarded', 'Reviewed and forwarded.', '2026-02-04 12:30:00'),
  (32, 40, '1200385', 'forwarded', 'Reviewed and forwarded.', '2026-02-03 15:00:00'),
  (33, 41, '401157',  'forwarded', 'Reviewed and forwarded.', '2026-02-02 09:00:00'),
  (34, 42, '1200385', 'forwarded', 'Reviewed and forwarded.', '2026-02-01 08:00:00'),
  (35, 43, '401157',  'forwarded', 'Reviewed and forwarded.', '2026-01-30 13:00:00'),
  (36, 44, '1200385', 'forwarded', 'Reviewed and forwarded.', '2026-01-29 11:00:00'),
  (37, 45, '401157',  'forwarded', 'Reviewed and forwarded.', '2026-01-29 00:00:00'),
  (38, 46, '1200385', 'forwarded', 'Reviewed and forwarded.', '2026-01-28 09:00:00'),
  (39, 47, '401157',  'forwarded', 'Reviewed and forwarded.', '2026-01-27 12:00:00'),
  (40, 48, '1200385', 'forwarded', 'Reviewed and forwarded.', '2026-01-26 16:00:00'),
  (41, 49, '401157',  'forwarded', 'Reviewed and forwarded.', '2026-01-25 11:00:00'),
  (42, 50, '1200385', 'forwarded', 'Reviewed and forwarded.', '2026-01-25 13:00:00');

-- ==================================================================
-- GA PRESIDENT APPROVALS
-- decided_by: 300553 = Karen F. Enriquez (GA President)
-- Reports 15-46 approved; reports 47-50 rejected.
-- ==================================================================
INSERT INTO ga_president_approvals (id, report_id, decided_by, decision, notes, decided_at) VALUES
  (1,  15, '300553', 'approved', 'Approved; assign to Facilities.', '2026-02-22 15:00:00'),
  (2,  16, '300553', 'approved', 'Approved; assign to IT.',          '2026-02-22 16:30:00'),
  (3,  17, '300553', 'approved', 'Approved; assign to Operations.',  '2026-02-22 17:30:00'),
  (4,  18, '300553', 'approved', 'Approved; assign to Operations.',  '2026-02-21 21:00:00'),
  (5,  19, '300553', 'approved', 'Approved; assign to HR.',          '2026-02-21 22:30:00'),
  (6,  20, '300553', 'approved', 'Approved; assign to Facilities.',  '2026-02-20 12:00:00'),
  (7,  21, '300553', 'approved', 'Approved; assign to Facilities.',  '2026-02-20 22:00:00'),
  (8,  22, '300553', 'approved', 'Approved; assign to Facilities.',  '2026-02-19 13:00:00'),
  (9,  23, '300553', 'approved', 'Approved; assign to IT.',          '2026-02-19 14:30:00'),
  (10, 24, '300553', 'approved', 'Approved; assign to Operations.',  '2026-02-18 12:00:00'),
  (11, 25, '300553', 'approved', 'Approved; assign to Operations.',  '2026-02-18 15:00:00'),
  (12, 26, '300553', 'approved', 'Approved; assign to QA.',          '2026-02-17 17:30:00'),
  (13, 27, '300553', 'approved', 'Approved; assign to Facilities.',  '2026-02-17 13:00:00'),
  (14, 28, '300553', 'approved', 'Approved; assign to IT.',          '2026-02-15 12:00:00'),
  (15, 29, '300553', 'approved', 'Approved; assign to Facilities.',  '2026-02-15 13:00:00'),
  (16, 30, '300553', 'approved', 'Approved; assign to QA.',          '2026-02-14 11:30:00'),
  (17, 31, '300553', 'approved', 'Approved; assign to Facilities.',  '2026-02-13 12:00:00'),
  (18, 32, '300553', 'approved', 'Approved; assign to IT.',          '2026-02-12 14:00:00'),
  (19, 33, '300553', 'approved', 'Approved; assign to IT.',          '2026-02-10 12:00:00'),
  (20, 34, '300553', 'approved', 'Approved; assign to Facilities.',  '2026-02-09 13:30:00'),
  (21, 35, '300553', 'approved', 'Approved; assign to Operations.',  '2026-02-08 15:00:00'),
  (22, 36, '300553', 'approved', 'Approved; assign to Facilities.',  '2026-02-07 17:00:00'),
  (23, 37, '300553', 'approved', 'Approved; assign to Facilities.',  '2026-02-05 12:00:00'),
  (24, 38, '300553', 'approved', 'Approved; assign to QA.',          '2026-02-04 13:00:00'),
  (25, 39, '300553', 'approved', 'Approved; assign to Facilities.',  '2026-02-04 14:30:00'),
  (26, 40, '300553', 'approved', 'Approved; assign to IT.',          '2026-02-03 17:00:00'),
  (27, 41, '300553', 'approved', 'Approved; assign to QA.',          '2026-02-02 11:00:00'),
  (28, 42, '300553', 'approved', 'Approved; assign to Facilities.',  '2026-02-01 10:00:00'),
  (29, 43, '300553', 'approved', 'Approved; assign to Operations.',  '2026-01-30 15:00:00'),
  (30, 44, '300553', 'approved', 'Approved; assign to Facilities.',  '2026-01-29 13:00:00'),
  (31, 45, '300553', 'approved', 'Approved; assign to IT.',          '2026-01-29 02:00:00'),
  (32, 46, '300553', 'approved', 'Approved; assign to Operations.',  '2026-01-28 11:00:00'),
  (33, 47, '300553', 'rejected', 'Not within scope of security reporting. Handle via FM ticket.', '2026-01-28 14:00:00'),
  (34, 48, '300553', 'rejected', 'Cosmetic issue; not a security concern. Close and handle internally.', '2026-01-27 11:00:00'),
  (35, 49, '300553', 'rejected', 'Procedural filing issue belongs in QA system, not security reports.', '2026-01-26 10:00:00'),
  (36, 50, '300553', 'rejected', 'Personnel dispute; refer to HR. Not a security incident.', '2026-01-26 11:00:00');

-- ==================================================================
-- DEPARTMENT ACTIONS
-- acted_by employee_no: '4'=Ana Mendoza, '5'=Carlos Bautista, '6'=Elena Cruz,
--                        '7'=Roberto Villanueva, '8'=Maricel Torres
-- ==================================================================
INSERT INTO department_actions (id, report_id, action_type, timeline_days, timeline_start, timeline_due, remarks, acted_by, acted_at) VALUES
  (1,  20, 'timeline', 16, '2026-02-21 09:00:00', '2026-03-08 17:00:00', 'Replacement sprinkler head on order; engineer booked.', '4', '2026-02-21 09:00:00'),
  (2,  21, 'timeline', 18, '2026-02-21 10:00:00', '2026-03-10 17:00:00', 'Rewiring required; electrician scheduled.', '4', '2026-02-21 10:00:00'),
  (3,  22, 'timeline', 23, '2026-02-20 10:00:00', '2026-03-15 17:00:00', 'Roofing contractor engaged; scaffolding being erected.', '4', '2026-02-20 10:00:00'),
  (4,  23, 'timeline', 20, '2026-02-20 11:00:00', '2026-03-12 17:00:00', 'Camera procurement in progress; IT vendor booked.', '5', '2026-02-20 11:00:00'),
  (5,  24, 'timeline', 15, '2026-02-19 09:00:00', '2026-03-06 17:00:00', 'Telecom port repair booked.', '7', '2026-02-19 09:00:00'),
  (6,  25, 'timeline', 14, '2026-02-19 12:00:00', '2026-03-05 17:00:00', 'Briefing and audit process being designed.', '7', '2026-02-19 12:00:00'),
  (7,  26, 'timeline', 17, '2026-02-18 10:00:00', '2026-03-07 17:00:00', 'Spill kit restocked; procedure update underway.', '8', '2026-02-18 10:00:00'),
  (8,  27, 'timeline', 30, '2026-02-18 11:00:00', '2026-03-20 17:00:00', 'Drainage contractor surveying; major repair required.', '4', '2026-02-18 11:00:00'),
  (9,  28, 'done', NULL, NULL, NULL, 'New lock fitted and tested; access log clean.', '5', '2026-02-22 13:00:00'),
  (10, 29, 'done', NULL, NULL, NULL, 'All four fittings replaced; luminance tested.', '4', '2026-02-22 15:00:00'),
  (11, 30, 'done', NULL, NULL, NULL, 'Segregation done; contractor signed disposal manifest.', '8', '2026-02-21 09:00:00'),
  (12, 31, 'done', NULL, NULL, NULL, 'Pest treatment complete; canteen deep-cleaned.', '4', '2026-02-20 08:00:00'),
  (13, 32, 'done', NULL, NULL, NULL, 'All three window locks replaced and tested.', '5', '2026-02-19 11:00:00'),
  (14, 33, 'done', NULL, NULL, NULL, 'Log server reconfigured; vendor confirmed fix.', '5', '2026-02-25 14:00:00'),
  (15, 34, 'done', NULL, NULL, NULL, 'Cover plate installed; zone marked safe.', '4', '2026-02-24 13:00:00'),
  (16, 35, 'done', NULL, NULL, NULL, 'Ladder brackets refitted per FM standard.', '7', '2026-02-27 10:00:00'),
  (17, 36, 'done', NULL, NULL, NULL, 'Compressor oil seal replaced by HVAC vendor.', '4', '2026-02-29 10:00:00'),
  (18, 37, 'done', NULL, NULL, NULL, 'Window replaced and seal inspected.', '4', '2026-02-14 10:00:00'),
  (19, 38, 'done', NULL, NULL, NULL, 'Drill conducted 2026-02-12; 98% attendance recorded.', '8', '2026-02-12 17:00:00'),
  (20, 39, 'done', NULL, NULL, NULL, 'Batteries replaced and exit lights tested.', '4', '2026-02-11 13:00:00'),
  (21, 40, 'done', NULL, NULL, NULL, 'CCTV extended; cable locks issued to all staff.', '5', '2026-02-10 15:00:00'),
  (22, 41, 'done', NULL, NULL, NULL, 'Remediation verified; agency notification sent.', '8', '2026-02-08 09:00:00'),
  (23, 42, 'done', NULL, NULL, NULL, 'Permanent panel installed and inspected.', '4', '2026-02-07 13:00:00'),
  (24, 43, 'done', NULL, NULL, NULL, 'Visitor software updated; expiry logic re-tested.', '7', '2026-02-05 08:00:00'),
  (25, 44, 'done', NULL, NULL, NULL, 'Floor panel secured; monthly checks scheduled.', '4', '2026-02-03 14:00:00'),
  (26, 45, 'done', NULL, NULL, NULL, 'Mantrap installed; security review completed.', '5', '2026-02-03 09:00:00'),
  (27, 46, 'done', NULL, NULL, NULL, 'Valve replaced; gas sensor calibrated and certified.', '7', '2026-02-02 11:00:00');

-- ==================================================================
-- SECURITY FINAL CHECKS
-- checked_by IDs:
--   8810305 = Efren Corrales   (NCFL Internal)
--   8810183 = Benjamin Esteban (NCFL External)
--   8810279 = Christian Provido(NPFL Internal)
--   8810222 = Jayson Ruazol    (NPFL External)
-- ==================================================================
INSERT INTO security_final_checks (id, report_id, decision, remarks, checked_by, checked_at, closed_at) VALUES
  (1,  33, 'returned',  'Log gaps still present in verification test run.', '8810222', '2026-02-28 10:00:00', NULL),
  (2,  34, 'returned',  'Earth bond still loose; panel not electrically safe.', '8810305', '2026-02-27 14:00:00', NULL),
  (3,  35, 'returned',  'Wrong bracket spec used; rack remains unstable under load.', '8810222', '2026-03-01 09:00:00', NULL),
  (4,  36, 'returned',  'Oil leak resumed within 48 hours of seal fix.', '8810305', '2026-03-02 11:00:00', NULL),
  (5,  37, 'confirmed', 'New glass verified; frame seal inspected.', '8810222', '2026-02-17 10:00:00', '2026-02-17 10:00:00'),
  (6,  38, 'confirmed', 'Drill attendance records sighted and signed off.', '8810305', '2026-02-14 11:00:00', '2026-02-14 11:00:00'),
  (7,  39, 'confirmed', 'All three exit lights illuminated during power-off test.', '8810222', '2026-02-13 14:00:00', '2026-02-13 14:00:00'),
  (8,  40, 'confirmed', 'CCTV footage confirmed; cable locks sighted on all desks.', '8810305', '2026-02-12 16:00:00', '2026-02-12 16:00:00'),
  (9,  41, 'confirmed', 'Remediation photos reviewed; agency receipt confirmed.', '8810222', '2026-02-10 10:00:00', '2026-02-10 10:00:00'),
  (10, 42, 'confirmed', 'Fence panel installed; padlocked inspection cover confirmed.', '8810305', '2026-02-09 14:00:00', '2026-02-09 14:00:00'),
  (11, 43, 'confirmed', 'Visitor system re-tested; expiry correctly enforced.', '8810222', '2026-02-07 09:00:00', '2026-02-07 09:00:00'),
  (12, 44, 'confirmed', 'Panel secured; no movement under 80 kg load test.', '8810305', '2026-02-05 15:00:00', '2026-02-05 15:00:00'),
  (13, 45, 'confirmed', 'Mantrap operational; access log clean; police report filed.', '8810222', '2026-02-04 10:00:00', '2026-02-04 10:00:00'),
  (14, 46, 'confirmed', 'New valve certified; sensor alarm tested at 10% LEL.', '8810305', '2026-02-03 12:00:00', '2026-02-03 12:00:00');

-- ==================================================================
-- REPORT STATUS HISTORY
-- changed_by ID map:
--   Security submitters:  8810305, 8810183, 8810279, 8810222
--   GA Staff reviewers:   401157, 1200385
--   GA President:         300553
--   PIC actors:           4, 5, 6, 7, 8
-- ==================================================================
INSERT INTO report_status_history (report_id, status, changed_by, notes, changed_at) VALUES
  -- Reports 1-8: just submitted
  (1,  'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-03-01 07:15:00'),
  (2,  'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-03-01 09:30:00'),
  (3,  'submitted_to_ga_staff', '8810222', 'Submitted by Security.',                '2026-03-01 11:00:00'),
  (4,  'submitted_to_ga_staff', '8810279', 'Submitted by Security.',                '2026-03-01 13:45:00'),
  (5,  'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-02-28 08:00:00'),
  (6,  'submitted_to_ga_staff', '8810183', 'Submitted by Security.',                '2026-02-28 10:20:00'),
  (7,  'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-02-27 14:10:00'),
  (8,  'submitted_to_ga_staff', '8810222', 'Submitted by Security.',                '2026-02-27 16:00:00'),
  -- Reports 9-14: submitted -> forwarded to president
  (9,  'submitted_to_ga_staff', '8810222', 'Submitted by Security.',                '2026-02-25 08:00:00'),
  (9,  'submitted_to_ga_president', '401157', 'Forwarded to GA President.',            '2026-02-25 12:00:00'),
  (10, 'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-02-25 09:30:00'),
  (10, 'submitted_to_ga_president', '1200385', 'Forwarded to GA President.',            '2026-02-25 13:30:00'),
  (11, 'submitted_to_ga_staff', '8810183', 'Submitted by Security.',                '2026-02-24 10:00:00'),
  (11, 'submitted_to_ga_president', '401157', 'Forwarded to GA President.',            '2026-02-24 14:00:00'),
  (12, 'submitted_to_ga_staff', '8810222', 'Submitted by Security.',                '2026-02-24 11:30:00'),
  (12, 'submitted_to_ga_president', '1200385', 'Forwarded to GA President.',            '2026-02-24 15:30:00'),
  (13, 'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-02-23 08:45:00'),
  (13, 'submitted_to_ga_president', '401157', 'Forwarded to GA President.',            '2026-02-23 12:45:00'),
  (14, 'submitted_to_ga_staff', '8810279', 'Submitted by Security.',                '2026-02-23 13:00:00'),
  (14, 'submitted_to_ga_president', '1200385', 'Forwarded to GA President.',            '2026-02-23 16:00:00'),
  -- Reports 15-19: -> sent_to_department
  (15, 'submitted_to_ga_staff', '8810222', 'Submitted by Security.',                '2026-02-22 07:00:00'),
  (15, 'submitted_to_ga_president', '401157', 'Forwarded to GA President.',            '2026-02-22 13:00:00'),
  (15, 'sent_to_department', '300553', 'Approved and sent to Facilities.',      '2026-02-22 15:00:00'),
  (16, 'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-02-22 09:15:00'),
  (16, 'submitted_to_ga_president', '1200385', 'Forwarded to GA President.',            '2026-02-22 14:30:00'),
  (16, 'sent_to_department', '300553', 'Approved and sent to IT.',              '2026-02-22 16:30:00'),
  (17, 'submitted_to_ga_staff', '8810183', 'Submitted by Security.',                '2026-02-22 11:00:00'),
  (17, 'submitted_to_ga_president', '401157', 'Forwarded to GA President.',            '2026-02-22 15:00:00'),
  (17, 'sent_to_department', '300553', 'Approved and sent to Operations.',      '2026-02-22 17:30:00'),
  (18, 'submitted_to_ga_staff', '8810222', 'Submitted by Security.',                '2026-02-21 14:00:00'),
  (18, 'submitted_to_ga_president', '1200385', 'Forwarded to GA President.',            '2026-02-21 17:00:00'),
  (18, 'sent_to_department', '300553', 'Approved and sent to Operations.',      '2026-02-21 21:00:00'),
  (19, 'submitted_to_ga_staff', '8810183', 'Submitted by Security.',                '2026-02-21 16:30:00'),
  (19, 'submitted_to_ga_president', '401157', 'Forwarded to GA President.',            '2026-02-21 20:00:00'),
  (19, 'sent_to_department', '300553', 'Approved and sent to HR.',              '2026-02-21 22:30:00'),
  -- Reports 20-27: -> under_department_fix
  (20, 'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-02-20 06:30:00'),
  (20, 'sent_to_department', '300553', 'Approved and sent to Facilities.',      '2026-02-20 12:00:00'),
  (20, 'under_department_fix', '4', 'Fix timeline set: 16 day(s).',          '2026-02-21 09:00:00'),
  (21, 'submitted_to_ga_staff', '8810222', 'Submitted by Security.',                '2026-02-20 18:00:00'),
  (21, 'sent_to_department', '300553', 'Approved and sent to Facilities.',      '2026-02-20 22:00:00'),
  (21, 'under_department_fix', '4', 'Fix timeline set: 18 day(s).',          '2026-02-21 10:00:00'),
  (22, 'submitted_to_ga_staff', '8810183', 'Submitted by Security.',                '2026-02-19 09:00:00'),
  (22, 'sent_to_department', '300553', 'Approved and sent to Facilities.',      '2026-02-19 13:00:00'),
  (22, 'under_department_fix', '4', 'Fix timeline set: 23 day(s).',          '2026-02-20 10:00:00'),
  (23, 'submitted_to_ga_staff', '8810222', 'Submitted by Security.',                '2026-02-19 10:30:00'),
  (23, 'sent_to_department', '300553', 'Approved and sent to IT.',              '2026-02-19 14:30:00'),
  (23, 'under_department_fix', '5', 'Fix timeline set: 20 day(s).',          '2026-02-20 11:00:00'),
  (24, 'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-02-18 08:00:00'),
  (24, 'sent_to_department', '300553', 'Approved and sent to Operations.',      '2026-02-18 12:00:00'),
  (24, 'under_department_fix', '7', 'Fix timeline set: 15 day(s).',          '2026-02-19 09:00:00'),
  (25, 'submitted_to_ga_staff', '8810222', 'Submitted by Security.',                '2026-02-18 11:00:00'),
  (25, 'sent_to_department', '300553', 'Approved and sent to Operations.',      '2026-02-18 15:00:00'),
  (25, 'under_department_fix', '7', 'Fix timeline set: 14 day(s).',          '2026-02-19 12:00:00'),
  (26, 'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-02-17 14:00:00'),
  (26, 'sent_to_department', '300553', 'Approved and sent to QA.',              '2026-02-17 17:30:00'),
  (26, 'under_department_fix', '8', 'Fix timeline set: 17 day(s).',          '2026-02-18 10:00:00'),
  (27, 'submitted_to_ga_staff', '8810222', 'Submitted by Security.',                '2026-02-17 09:00:00'),
  (27, 'sent_to_department', '300553', 'Approved and sent to Facilities.',      '2026-02-17 13:00:00'),
  (27, 'under_department_fix', '4', 'Fix timeline set: 30 day(s).',          '2026-02-18 11:00:00'),
  -- Reports 28-32: -> for_security_final_check
  (28, 'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-02-15 08:00:00'),
  (28, 'sent_to_department', '300553', 'Approved and sent to IT.',              '2026-02-15 12:00:00'),
  (28, 'for_security_final_check', '5', 'Marked as DONE by Department.',         '2026-02-22 13:00:00'),
  (29, 'submitted_to_ga_staff', '8810222', 'Submitted by Security.',                '2026-02-15 09:00:00'),
  (29, 'sent_to_department', '300553', 'Approved and sent to Facilities.',      '2026-02-15 13:00:00'),
  (29, 'for_security_final_check', '4', 'Marked as DONE by Department.',         '2026-02-22 15:00:00'),
  (30, 'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-02-14 07:30:00'),
  (30, 'sent_to_department', '300553', 'Approved and sent to QA.',              '2026-02-14 11:30:00'),
  (30, 'for_security_final_check', '8', 'Marked as DONE by Department.',         '2026-02-21 09:00:00'),
  (31, 'submitted_to_ga_staff', '8810222', 'Submitted by Security.',                '2026-02-13 08:00:00'),
  (31, 'sent_to_department', '300553', 'Approved and sent to Facilities.',      '2026-02-13 12:00:00'),
  (31, 'for_security_final_check', '4', 'Marked as DONE by Department.',         '2026-02-20 08:00:00'),
  (32, 'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-02-12 10:00:00'),
  (32, 'sent_to_department', '300553', 'Approved and sent to IT.',              '2026-02-12 14:00:00'),
  (32, 'for_security_final_check', '5', 'Marked as DONE by Department.',         '2026-02-19 11:00:00'),
  -- Reports 33-36: -> returned_to_department
  (33, 'submitted_to_ga_staff', '8810222', 'Submitted by Security.',                '2026-02-10 08:00:00'),
  (33, 'sent_to_department', '300553', 'Approved and sent to IT.',              '2026-02-10 12:00:00'),
  (33, 'for_security_final_check', '5', 'Marked as DONE by Department.',         '2026-02-25 14:00:00'),
  (33, 'returned_to_department', '8810222', 'Log gaps still present in verification.', '2026-02-28 10:00:00'),
  (34, 'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-02-09 09:30:00'),
  (34, 'sent_to_department', '300553', 'Approved and sent to Facilities.',      '2026-02-09 13:30:00'),
  (34, 'for_security_final_check', '4', 'Marked as DONE by Department.',         '2026-02-24 13:00:00'),
  (34, 'returned_to_department', '8810305', 'Earth bond still loose; not electrically safe.', '2026-02-27 14:00:00'),
  (35, 'submitted_to_ga_staff', '8810222', 'Submitted by Security.',                '2026-02-08 11:00:00'),
  (35, 'sent_to_department', '300553', 'Approved and sent to Operations.',      '2026-02-08 15:00:00'),
  (35, 'for_security_final_check', '7', 'Marked as DONE by Department.',         '2026-02-27 10:00:00'),
  (35, 'returned_to_department', '8810222', 'Wrong bracket spec; rack still unstable.', '2026-03-01 09:00:00'),
  (36, 'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-02-07 13:00:00'),
  (36, 'sent_to_department', '300553', 'Approved and sent to Facilities.',      '2026-02-07 17:00:00'),
  (36, 'for_security_final_check', '4', 'Marked as DONE by Department.',         '2026-02-29 10:00:00'),
  (36, 'returned_to_department', '8810305', 'Oil leak resumed after seal fix.',      '2026-03-02 11:00:00'),
  -- Reports 37-46: -> resolved
  (37, 'submitted_to_ga_staff', '8810222', 'Submitted by Security.',                '2026-02-05 08:00:00'),
  (37, 'sent_to_department', '300553', 'Approved and sent to Facilities.',      '2026-02-05 12:00:00'),
  (37, 'for_security_final_check', '4', 'Marked as DONE by Department.',         '2026-02-14 10:00:00'),
  (37, 'resolved', '8810222', 'Security confirmed and closed.',        '2026-02-17 10:00:00'),
  (38, 'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-02-04 09:00:00'),
  (38, 'sent_to_department', '300553', 'Approved and sent to QA.',              '2026-02-04 13:00:00'),
  (38, 'for_security_final_check', '8', 'Marked as DONE by Department.',         '2026-02-12 17:00:00'),
  (38, 'resolved', '8810305', 'Security confirmed and closed.',        '2026-02-14 11:00:00'),
  (39, 'submitted_to_ga_staff', '8810222', 'Submitted by Security.',                '2026-02-04 10:30:00'),
  (39, 'sent_to_department', '300553', 'Approved and sent to Facilities.',      '2026-02-04 14:30:00'),
  (39, 'for_security_final_check', '4', 'Marked as DONE by Department.',         '2026-02-11 13:00:00'),
  (39, 'resolved', '8810222', 'Security confirmed and closed.',        '2026-02-13 14:00:00'),
  (40, 'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-02-03 14:00:00'),
  (40, 'sent_to_department', '300553', 'Approved and sent to IT.',              '2026-02-03 17:00:00'),
  (40, 'for_security_final_check', '5', 'Marked as DONE by Department.',         '2026-02-10 15:00:00'),
  (40, 'resolved', '8810305', 'Security confirmed and closed.',        '2026-02-12 16:00:00'),
  (41, 'submitted_to_ga_staff', '8810222', 'Submitted by Security.',                '2026-02-02 07:30:00'),
  (41, 'sent_to_department', '300553', 'Approved and sent to QA.',              '2026-02-02 11:00:00'),
  (41, 'for_security_final_check', '8', 'Marked as DONE by Department.',         '2026-02-08 09:00:00'),
  (41, 'resolved', '8810222', 'Security confirmed and closed.',        '2026-02-10 10:00:00'),
  (42, 'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-02-01 06:00:00'),
  (42, 'sent_to_department', '300553', 'Approved and sent to Facilities.',      '2026-02-01 10:00:00'),
  (42, 'for_security_final_check', '4', 'Marked as DONE by Department.',         '2026-02-07 13:00:00'),
  (42, 'resolved', '8810305', 'Security confirmed and closed.',        '2026-02-09 14:00:00'),
  (43, 'submitted_to_ga_staff', '8810222', 'Submitted by Security.',                '2026-01-30 11:00:00'),
  (43, 'sent_to_department', '300553', 'Approved and sent to Operations.',      '2026-01-30 15:00:00'),
  (43, 'for_security_final_check', '7', 'Marked as DONE by Department.',         '2026-02-05 08:00:00'),
  (43, 'resolved', '8810222', 'Security confirmed and closed.',        '2026-02-07 09:00:00'),
  (44, 'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-01-29 09:00:00'),
  (44, 'sent_to_department', '300553', 'Approved and sent to Facilities.',      '2026-01-29 13:00:00'),
  (44, 'for_security_final_check', '4', 'Marked as DONE by Department.',         '2026-02-03 14:00:00'),
  (44, 'resolved', '8810305', 'Security confirmed and closed.',        '2026-02-05 15:00:00'),
  (45, 'submitted_to_ga_staff', '8810222', 'Submitted by Security.',                '2026-01-28 22:00:00'),
  (45, 'sent_to_department', '300553', 'Approved and sent to IT.',              '2026-01-29 02:00:00'),
  (45, 'for_security_final_check', '5', 'Marked as DONE by Department.',         '2026-02-03 09:00:00'),
  (45, 'resolved', '8810222', 'Security confirmed and closed.',        '2026-02-04 10:00:00'),
  (46, 'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-01-28 07:00:00'),
  (46, 'sent_to_department', '300553', 'Approved and sent to Operations.',      '2026-01-28 11:00:00'),
  (46, 'for_security_final_check', '7', 'Marked as DONE by Department.',         '2026-02-02 11:00:00'),
  (46, 'resolved', '8810305', 'Security confirmed and closed.',        '2026-02-03 12:00:00'),
  -- Reports 47-50: -> rejected
  (47, 'submitted_to_ga_staff', '8810222', 'Submitted by Security.',                '2026-01-27 10:00:00'),
  (47, 'submitted_to_ga_president', '401157', 'Forwarded to GA President.',            '2026-01-27 12:00:00'),
  (47, 'rejected', '300553', 'Not within scope; rejected by GA President.', '2026-01-28 14:00:00'),
  (48, 'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-01-26 14:00:00'),
  (48, 'submitted_to_ga_president', '1200385', 'Forwarded to GA President.',            '2026-01-26 16:00:00'),
  (48, 'rejected', '300553', 'Cosmetic issue; rejected by GA President.', '2026-01-27 11:00:00'),
  (49, 'submitted_to_ga_staff', '8810222', 'Submitted by Security.',                '2026-01-25 09:00:00'),
  (49, 'submitted_to_ga_president', '401157', 'Forwarded to GA President.',            '2026-01-25 11:00:00'),
  (49, 'rejected', '300553', 'QA procedural issue; rejected by GA President.', '2026-01-26 10:00:00'),
  (50, 'submitted_to_ga_staff', '8810305', 'Submitted by Security.',                '2026-01-25 11:00:00'),
  (50, 'submitted_to_ga_president', '1200385', 'Forwarded to GA President.',            '2026-01-25 13:00:00'),
  (50, 'rejected', '300553', 'Personnel dispute; rejected by GA President.', '2026-01-26 11:00:00');

-- ==================================================================
-- NOTIFICATIONS
-- user_id map:
--   300553  = Karen Enriquez    (GA President)
--   401157  = Liza Acosta       (GA Staff)
--   1200385 = Cherry Buenconsejo(GA Staff)
--   4=Ana, 5=Carlos, 6=Elena, 7=Roberto, 8=Maricel (PICs)
--   8810305 = Efren  (NCFL Internal Security)
--   8810183 = Benjamin (NCFL External Security)
--   8810279 = Christian (NPFL Internal Security)
--   8810222 = Jayson (NPFL External Security)
-- ==================================================================
INSERT INTO notifications (user_id, report_id, message, is_read, created_at) VALUES
  -- GA Staff notified of 8 new submissions
  ('401157',  1,  'New Report Submitted and Waiting for Review', 0, '2026-03-01 07:16:00'),
  ('1200385', 1,  'New Report Submitted and Waiting for Review', 0, '2026-03-01 07:16:00'),
  ('401157',  2,  'New Report Submitted and Waiting for Review', 0, '2026-03-01 09:31:00'),
  ('1200385', 2,  'New Report Submitted and Waiting for Review', 0, '2026-03-01 09:31:00'),
  ('401157',  3,  'New Report Submitted and Waiting for Review', 0, '2026-03-01 11:01:00'),
  ('1200385', 3,  'New Report Submitted and Waiting for Review', 0, '2026-03-01 11:01:00'),
  ('401157',  4,  'New Report Submitted and Waiting for Review', 0, '2026-03-01 13:46:00'),
  ('1200385', 4,  'New Report Submitted and Waiting for Review', 0, '2026-03-01 13:46:00'),
  ('401157',  5,  'New Report Submitted and Waiting for Review', 0, '2026-02-28 08:01:00'),
  ('1200385', 5,  'New Report Submitted and Waiting for Review', 0, '2026-02-28 08:01:00'),
  ('401157',  6,  'New Report Submitted and Waiting for Review', 0, '2026-02-28 10:21:00'),
  ('1200385', 6,  'New Report Submitted and Waiting for Review', 0, '2026-02-28 10:21:00'),
  ('401157',  7,  'New Report Submitted and Waiting for Review', 0, '2026-02-27 14:11:00'),
  ('1200385', 7,  'New Report Submitted and Waiting for Review', 0, '2026-02-27 14:11:00'),
  ('401157',  8,  'New Report Submitted and Waiting for Review', 0, '2026-02-27 16:01:00'),
  ('1200385', 8,  'New Report Submitted and Waiting for Review', 0, '2026-02-27 16:01:00'),
  -- GA President notified of 6 pending decisions
  ('300553', 9,  'Report Waiting for Final GA Approval', 0, '2026-02-25 12:01:00'),
  ('300553', 10, 'Report Waiting for Final GA Approval', 0, '2026-02-25 13:31:00'),
  ('300553', 11, 'Report Waiting for Final GA Approval', 0, '2026-02-24 14:01:00'),
  ('300553', 12, 'Report Waiting for Final GA Approval', 0, '2026-02-24 15:31:00'),
  ('300553', 13, 'Report Waiting for Final GA Approval', 0, '2026-02-23 12:46:00'),
  ('300553', 14, 'Report Waiting for Final GA Approval', 0, '2026-02-23 16:01:00'),
  -- Department PICs notified of new assignments (reports 15-19)
  (4, 15, 'New Report Assigned to Your Department', 0, '2026-02-22 15:01:00'),
  (5, 16, 'New Report Assigned to Your Department', 0, '2026-02-22 16:31:00'),
  (7, 17, 'New Report Assigned to Your Department', 0, '2026-02-22 17:31:00'),
  (7, 18, 'New Report Assigned to Your Department', 0, '2026-02-21 21:01:00'),
  (6, 19, 'New Report Assigned to Your Department', 0, '2026-02-21 22:31:00'),
  -- Security notified when timeline was set (reports 20-27)
  ('8810305', 20, 'Department Set Fix Timeline. Due: Mar 08, 2026', 1, '2026-02-21 09:01:00'),
  ('8810222', 21, 'Department Set Fix Timeline. Due: Mar 10, 2026', 0, '2026-02-21 10:01:00'),
  ('8810183', 22, 'Department Set Fix Timeline. Due: Mar 15, 2026', 0, '2026-02-20 10:01:00'),
  ('8810222', 23, 'Department Set Fix Timeline. Due: Mar 12, 2026', 0, '2026-02-20 11:01:00'),
  ('8810305', 24, 'Department Set Fix Timeline. Due: Mar 06, 2026', 0, '2026-02-19 09:01:00'),
  ('8810222', 25, 'Department Set Fix Timeline. Due: Mar 05, 2026', 0, '2026-02-19 12:01:00'),
  ('8810305', 26, 'Department Set Fix Timeline. Due: Mar 07, 2026', 0, '2026-02-18 10:01:00'),
  ('8810222', 27, 'Department Set Fix Timeline. Due: Mar 20, 2026', 0, '2026-02-18 11:01:00'),
  -- 24h due-soon warning for report 25 (due tomorrow)
  (7, 25, 'Fix Timeline Due Soon (within 24 hours)', 0, '2026-03-04 08:00:00'),
  -- Security notified of work done (reports 28-32)
  ('8810305', 28, 'Department Marked Report as Fixed. Please Verify', 0, '2026-02-22 13:01:00'),
  ('8810222', 29, 'Department Marked Report as Fixed. Please Verify', 0, '2026-02-22 15:01:00'),
  ('8810305', 30, 'Department Marked Report as Fixed. Please Verify', 0, '2026-02-21 09:01:00'),
  ('8810222', 31, 'Department Marked Report as Fixed. Please Verify', 0, '2026-02-20 08:01:00'),
  ('8810305', 32, 'Department Marked Report as Fixed. Please Verify', 0, '2026-02-19 11:01:00'),
  -- Department PICs re-notified after Security returned (reports 33-36)
  (5, 33, 'Report Returned. Issue Not Resolved (Return #1)', 0, '2026-02-28 10:01:00'),
  (4, 34, 'Report Returned. Issue Not Resolved (Return #1)', 0, '2026-02-27 14:01:00'),
  (7, 35, 'Report Returned. Issue Not Resolved (Return #1)', 0, '2026-03-01 09:01:00'),
  (4, 36, 'Report Returned. Issue Not Resolved (Return #1)', 0, '2026-03-02 11:01:00'),
  -- Security notified of approval
  ('8810305', 9,  'Report Approved by GA President. Assigned to Department for Resolution', 1, '2026-02-25 15:01:00'),
  ('8810305', 10, 'Report Approved by GA President. Assigned to Department for Resolution', 1, '2026-02-25 15:01:00');

-- ==================================================================
-- SHOWCASE DEMO REPORTS (6 rows)
-- Purpose: quick demo dataset for supervisor presentation
--   • 3 reports tagged to NCFL
--   • 3 reports tagged to NPFL
--   • uses canonical department IDs from current department list
-- ==================================================================
INSERT INTO reports (
  id, report_no, subject, category, location, severity, building, responsible_department_id,
  details, actions_taken, remarks,
  submitted_by, submitted_at, current_reviewer,
  fix_due_date, resolved_by_security, resolved_at,
  returned_by_security, returned_at, security_remarks,
  reopen_count, status, updated_at
) VALUES
  (51, 'SR-2026-0051', 'NCFL Demo - Access Gate Delay',    'Access Control', 'NCFL Gate 1',                'medium',   'NCFL',  1,
   'Demo sample: visitor gate turnaround exceeded standard due to badge scanner lag.',
   'Guard performed manual verification while scanner was rebooted.',
   'Presentation sample report for NCFL flow.', '8810305', '2026-03-10 08:15:00', 'ga_staff',
   NULL, NULL, NULL,
   NULL, NULL, NULL,
   0, 'submitted_to_ga_staff', '2026-03-10 08:15:00'),

  (52, 'SR-2026-0052', 'NCFL Demo - Loading Bay Obstruction', 'Workplace Safety', 'NCFL Loading Bay A',     'high',     'NCFL', 14,
   'Demo sample: material pallet blocked safety lane at loading bay entrance.',
   'Area isolated and obstruction moved to marked staging point.',
   'Presentation sample report for NCFL production area.', '8810183', '2026-03-10 09:40:00', 'ga_staff',
   NULL, NULL, NULL,
   NULL, NULL, NULL,
   0, 'submitted_to_ga_staff', '2026-03-10 09:40:00'),

  (53, 'SR-2026-0053', 'NCFL Demo - QA Storage Label Issue', 'Compliance', 'NCFL QA Storage Room',        'low',      'NCFL', 15,
   'Demo sample: QA storage labels were outdated, causing stock mismatch during audit.',
   'Labels reprinted and temporary log sheet issued.',
   'Presentation sample report for QA process control.', '8810305', '2026-03-10 11:05:00', 'ga_staff',
   NULL, NULL, NULL,
   NULL, NULL, NULL,
   0, 'submitted_to_ga_staff', '2026-03-10 11:05:00'),

  (54, 'SR-2026-0054', 'NPFL Demo - Perimeter Lighting Gap', 'Surveillance', 'NPFL Perimeter West',       'medium',   'NPFL', 16,
   'Demo sample: dark section on perimeter reduced camera clarity during night rounds.',
   'Temporary mobile light tower deployed for patrol hours.',
   'Presentation sample report for NPFL perimeter controls.', '8810222', '2026-03-10 12:20:00', 'ga_staff',
   NULL, NULL, NULL,
   NULL, NULL, NULL,
   0, 'submitted_to_ga_staff', '2026-03-10 12:20:00'),

  (55, 'SR-2026-0055', 'NPFL Demo - Purchasing Dock Queue', 'Access Control', 'NPFL Purchasing Dock',     'low',      'NPFL', 9,
   'Demo sample: receiving queue caused supplier trucks to idle in restricted lane.',
   'Marshalling cone lane set up and guard assisted re-routing.',
   'Presentation sample report for purchasing logistics.', '8810279', '2026-03-10 13:35:00', 'ga_staff',
   NULL, NULL, NULL,
   NULL, NULL, NULL,
   0, 'submitted_to_ga_staff', '2026-03-10 13:35:00'),

  (56, 'SR-2026-0056', 'NPFL Demo - Engineering Module Alarm Test', 'Fire Safety', 'NPFL Engineering Module', 'medium', 'NPFL', 4,
   'Demo sample: scheduled fire alarm test flagged delayed response on zone siren 3.',
   'Technician acknowledged issue and initiated panel diagnostics.',
   'Presentation sample report for engineering module.', '8810222', '2026-03-10 15:00:00', 'ga_staff',
   NULL, NULL, NULL,
   NULL, NULL, NULL,
   0, 'submitted_to_ga_staff', '2026-03-10 15:00:00');

INSERT INTO report_status_history (report_id, status, changed_by, notes, changed_at) VALUES
  (51, 'submitted_to_ga_staff', '8810305', 'Showcase demo report submitted by Security.', '2026-03-10 08:15:00'),
  (52, 'submitted_to_ga_staff', '8810183', 'Showcase demo report submitted by Security.', '2026-03-10 09:40:00'),
  (53, 'submitted_to_ga_staff', '8810305', 'Showcase demo report submitted by Security.', '2026-03-10 11:05:00'),
  (54, 'submitted_to_ga_staff', '8810222', 'Showcase demo report submitted by Security.', '2026-03-10 12:20:00'),
  (55, 'submitted_to_ga_staff', '8810279', 'Showcase demo report submitted by Security.', '2026-03-10 13:35:00'),
  (56, 'submitted_to_ga_staff', '8810222', 'Showcase demo report submitted by Security.', '2026-03-10 15:00:00');

INSERT INTO notifications (user_id, report_id, message, is_read, created_at) VALUES
  ('401157',  51, 'New Report Submitted and Waiting for Review', 0, '2026-03-10 08:16:00'),
  ('1200385', 51, 'New Report Submitted and Waiting for Review', 0, '2026-03-10 08:16:00'),
  ('401157',  52, 'New Report Submitted and Waiting for Review', 0, '2026-03-10 09:41:00'),
  ('1200385', 52, 'New Report Submitted and Waiting for Review', 0, '2026-03-10 09:41:00'),
  ('401157',  53, 'New Report Submitted and Waiting for Review', 0, '2026-03-10 11:06:00'),
  ('1200385', 53, 'New Report Submitted and Waiting for Review', 0, '2026-03-10 11:06:00'),
  ('401157',  54, 'New Report Submitted and Waiting for Review', 0, '2026-03-10 12:21:00'),
  ('1200385', 54, 'New Report Submitted and Waiting for Review', 0, '2026-03-10 12:21:00'),
  ('401157',  55, 'New Report Submitted and Waiting for Review', 0, '2026-03-10 13:36:00'),
  ('1200385', 55, 'New Report Submitted and Waiting for Review', 0, '2026-03-10 13:36:00'),
  ('401157',  56, 'New Report Submitted and Waiting for Review', 0, '2026-03-10 15:01:00'),
  ('1200385', 56, 'New Report Submitted and Waiting for Review', 0, '2026-03-10 15:01:00');