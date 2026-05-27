<?php
/**
 * Language strings for the enrol_courseapproval plugin.
 *
 * @package    enrol_courseapproval
 * @copyright  2026 Antigravity
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Course Approval Enrolment';
$string['pluginname_desc'] = 'Enrols users in this course after they complete a specified source course, respecting the start date.';

// Capabilities.
$string['courseapproval:config'] = 'Configure course approval enrolment instances';
$string['courseapproval:manage'] = 'Manage course approval enrolments';
$string['courseapproval:unenrol'] = 'Unenrol users from course approval enrolments';

// Form and settings.
$string['sourcecourse'] = 'Source Course';
$string['sourcecourse_help'] = 'The course that the student must complete to be automatically enrolled in this course. The source course must have completion tracking enabled.';
$string['assignrole'] = 'Assign role';
$string['defaultrole'] = 'Default role';
$string['defaultrole_desc'] = 'Select the role that should be assigned to users during course approval enrolment.';
$string['status'] = 'Enable enrolments';
$string['status_desc'] = 'Allow course approval enrolments by default.';
$string['instancedefaults'] = 'Enrolment instance defaults';

// Messages.
$string['enrolmentstarted'] = 'Enrolment started';
$string['enrolmentpending'] = 'Waiting for course start date to enrol student.';
$string['completionnotenabled'] = 'The selected source course does not have completion tracking enabled.';

// Tasks.
$string['process_enrollments'] = 'Process pending course approval enrolments';

// Bulk enrolment.
$string['bulkenrol'] = 'Inscripción manual masiva';
$string['conditionalenrol'] = 'Inscripción condicional';
$string['selectcourses'] = 'Select Courses';
$string['selectusers'] = 'Select Users';
$string['destcourse'] = 'Destination Course';
$string['enrolsuccess'] = 'Successfully enrolled {$a->success} users. Skipped {$a->skipped} (already enrolled). Errors: {$a->error}.';
$string['enrolerror'] = 'An error occurred during enrolment.';
$string['nousersselected'] = 'No users selected.';
$string['alreadyenrolled'] = 'Already enrolled';
$string['enrolnow'] = 'Enrol now';
$string['next'] = 'Next';
$string['noenrolledusers'] = 'No enrolled users found in the source course.';
