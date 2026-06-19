<?php
/**
 * Scheduled task to process pending enrollments.
 *
 * @package    enrol_courseapproval
 * @copyright  2026 Antigravity
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_courseapproval\task;

defined('MOODLE_INTERNAL') || die();

class process_enrollments extends \core\task\scheduled_task
{

    /**
     * Get a descriptive name for this task (shown to admins).
     * @return string
     */
    public function get_name()
    {
        return get_string('process_enrollments', 'enrol_courseapproval');
    }

    /**
     * Run the task.
     */
    public function execute()
    {
        global $DB;

        // Get all pending enrollments.
        $pending = $DB->get_records('enrol_courseapproval_pending');

        if (empty($pending)) {
            return;
        }

        $plugin = \enrol_get_plugin('courseapproval');

        foreach ($pending as $record) {
            // Check if Course B has started now.
            if ($plugin->can_enrol_now($record->courseid)) {
                // Enrol user.
                $instance = $DB->get_record('enrol', array('id' => $record->enrolid), '*', IGNORE_MISSING);
                if ($instance && $instance->status == ENROL_INSTANCE_ENABLED) {
                    $roleid = $plugin->get_config('roleid', 5);
                    $plugin->enrol_user($instance, $record->userid, $roleid);

                    // Heredar los grupos del curso origen al curso destino.
                    $plugin->sync_user_groups($instance->customint1, $record->courseid, $record->userid);
                }

                // Remove from pending table.
                $DB->delete_records('enrol_courseapproval_pending', array('id' => $record->id));
            }
        }
    }
}
