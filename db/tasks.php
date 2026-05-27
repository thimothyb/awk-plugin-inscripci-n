<?php
/**
 * Scheduled tasks registration.
 *
 * @package    enrol_courseapproval
 * @copyright  2026 Antigravity
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => '\enrol_courseapproval\task\process_enrollments',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '2', // Run daily at 2:00 AM
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
    ],
];
