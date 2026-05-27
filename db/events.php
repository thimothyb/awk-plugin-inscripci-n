<?php
/**
 * Event observer registration.
 *
 * @package    enrol_courseapproval
 * @copyright  2026 Antigravity
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\course_completed',
        'callback' => '\enrol_courseapproval\observer::course_completed',
        'priority' => 100,
        'internal' => false,
    ],
];
