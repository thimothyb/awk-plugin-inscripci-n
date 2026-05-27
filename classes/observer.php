<?php
/**
 * Event observer for enrol_courseapproval.
 *
 * @package    enrol_courseapproval
 * @copyright  2026 Antigravity
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_courseapproval;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer class.
 */
class observer
{

    /**
     * Triggered when a course is completed.
     *
     * @param \core\event\course_completed $event
     */
    public static function course_completed(\core\event\course_completed $event)
    {
        global $DB;

        $sourcecourseid = $event->courseid;
        $userid = $event->relateduserid;

        // Verify completion is actually complete (timecompleted is set).
        $completion = $DB->get_record('course_completions', [
            'course' => $sourcecourseid,
            'userid' => $userid,
        ]);

        if (!$completion || empty($completion->timecompleted)) {
            // Not actually completed yet.
            return;
        }

        // Find all enrolment instances where this course is the source course.
        $instances = $DB->get_records('enrol', [
            'enrol' => 'courseapproval',
            'customint1' => $sourcecourseid,
            'status' => ENROL_INSTANCE_ENABLED,
        ]);

        if (!$instances) {
            return;
        }

        $plugin = \enrol_get_plugin('courseapproval');

        foreach ($instances as $instance) {
            $destcourseid = $instance->courseid;

            // Core logic: Check if we can enrol now or queue it.
            $plugin->process_user_enrolment($destcourseid, $userid, $instance->id);
        }
    }
}
