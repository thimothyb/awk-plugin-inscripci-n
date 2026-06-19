<?php
/**
 * Web service definitions for enrol_courseapproval.
 *
 * @package    enrol_courseapproval
 * @copyright  2026 Antigravity
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [

    'enrol_courseapproval_get_instances' => [
        'classname'     => 'enrol_courseapproval_external',
        'methodname'    => 'get_instances',
        'classpath'     => 'enrol/courseapproval/externallib.php',
        'description'   => 'Returns all conditional enrolment instances for a given destination course.',
        'type'          => 'read',
        'capabilities'  => 'enrol/courseapproval:config',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'enrol_courseapproval_add_instance' => [
        'classname'     => 'enrol_courseapproval_external',
        'methodname'    => 'add_instance',
        'classpath'     => 'enrol/courseapproval/externallib.php',
        'description'   => 'Creates a new conditional enrolment rule linking a source course to a destination course.',
        'type'          => 'write',
        'capabilities'  => 'enrol/courseapproval:config',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'enrol_courseapproval_delete_instance' => [
        'classname'     => 'enrol_courseapproval_external',
        'methodname'    => 'delete_instance',
        'classpath'     => 'enrol/courseapproval/externallib.php',
        'description'   => 'Deletes a conditional enrolment instance by its ID.',
        'type'          => 'write',
        'capabilities'  => 'enrol/courseapproval:config',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'enrol_courseapproval_get_course_logs' => [
        'classname'     => 'enrol_courseapproval_external',
        'methodname'    => 'get_course_logs',
        'classpath'     => 'enrol/courseapproval/externallib.php',
        'description'   => 'Returns log entries (userid + timecreated) for a course since a given timestamp.',
        'type'          => 'read',
        'capabilities'  => 'enrol/courseapproval:config',
        'ajax'          => true,
        'loginrequired' => true,
    ],

];
