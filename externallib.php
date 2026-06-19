<?php
/**
 * External web service functions for enrol_courseapproval.
 *
 * @package    enrol_courseapproval
 * @copyright  2026 Antigravity
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/enrol/courseapproval/lib.php');

class enrol_courseapproval_external extends external_api
{

    // =========================================================================
    // get_instances
    // =========================================================================

    public static function get_instances_parameters()
    {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Destination course ID'),
        ]);
    }

    /**
     * Returns all active/inactive conditional enrolment instances for a destination course.
     */
    public static function get_instances($courseid)
    {
        global $DB;

        $params = self::validate_parameters(self::get_instances_parameters(), ['courseid' => $courseid]);
        $courseid = $params['courseid'];

        $context = context_course::instance($courseid);
        self::validate_context($context);
        require_capability('enrol/courseapproval:config', $context);

        $instances = $DB->get_records('enrol', [
            'enrol'    => 'courseapproval',
            'courseid' => $courseid,
        ]);

        $result = [];
        foreach ($instances as $instance) {
            $sourcecoursename = '';
            if (!empty($instance->customint1)) {
                $sourcecourse = $DB->get_record('course', ['id' => $instance->customint1], 'shortname, fullname');
                if ($sourcecourse) {
                    $sourcecoursename = $sourcecourse->shortname . ' — ' . $sourcecourse->fullname;
                }
            }

            $result[] = [
                'id'               => (int) $instance->id,
                'sourcecourseid'   => (int) $instance->customint1,
                'sourcecoursename' => $sourcecoursename,
                'roleid'           => (int) $instance->roleid,
                'status'           => (int) $instance->status,
            ];
        }

        return $result;
    }

    public static function get_instances_returns()
    {
        return new external_multiple_structure(
            new external_single_structure([
                'id'               => new external_value(PARAM_INT, 'Enrolment instance ID'),
                'sourcecourseid'   => new external_value(PARAM_INT, 'Source course ID'),
                'sourcecoursename' => new external_value(PARAM_TEXT, 'Source course name'),
                'roleid'           => new external_value(PARAM_INT, 'Role assigned on enrolment'),
                'status'           => new external_value(PARAM_INT, '0 = enabled, 1 = disabled'),
            ])
        );
    }

    // =========================================================================
    // add_instance
    // =========================================================================

    public static function add_instance_parameters()
    {
        return new external_function_parameters([
            'courseid'       => new external_value(PARAM_INT, 'Destination course ID'),
            'sourcecourseid' => new external_value(PARAM_INT, 'Source course ID'),
            'roleid'         => new external_value(PARAM_INT, 'Role to assign (default 5 = student)', VALUE_DEFAULT, 5),
        ]);
    }

    /**
     * Creates a conditional enrolment rule from source course → destination course.
     */
    public static function add_instance($courseid, $sourcecourseid, $roleid = 5)
    {
        global $DB;

        $params = self::validate_parameters(self::add_instance_parameters(), [
            'courseid'       => $courseid,
            'sourcecourseid' => $sourcecourseid,
            'roleid'         => $roleid,
        ]);

        $courseid       = $params['courseid'];
        $sourcecourseid = $params['sourcecourseid'];
        $roleid         = $params['roleid'];

        $context = context_course::instance($courseid);
        self::validate_context($context);
        require_capability('enrol/courseapproval:config', $context);

        // Source course must exist and have completion enabled.
        $sourcecourse = $DB->get_record('course', ['id' => $sourcecourseid]);
        if (!$sourcecourse) {
            throw new invalid_parameter_exception('Source course does not exist.');
        }
        if (empty($sourcecourse->enablecompletion)) {
            throw new invalid_parameter_exception('Source course does not have completion enabled.');
        }

        // Prevent duplicate rules for the same source → destination pair.
        $existing = $DB->get_record('enrol', [
            'enrol'      => 'courseapproval',
            'courseid'   => $courseid,
            'customint1' => $sourcecourseid,
        ]);
        if ($existing) {
            throw new invalid_parameter_exception('A conditional enrolment rule already exists for this source course.');
        }

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $plugin = enrol_get_plugin('courseapproval');

        $instanceid = $plugin->add_instance($course, [
            'customint1' => $sourcecourseid,
            'roleid'     => $roleid,
            'status'     => ENROL_INSTANCE_ENABLED,
        ]);

        return (int) $instanceid;
    }

    public static function add_instance_returns()
    {
        return new external_value(PARAM_INT, 'ID of the new enrolment instance');
    }

    // =========================================================================
    // delete_instance
    // =========================================================================

    public static function delete_instance_parameters()
    {
        return new external_function_parameters([
            'instanceid' => new external_value(PARAM_INT, 'Enrolment instance ID to delete'),
        ]);
    }

    /**
     * Deletes a conditional enrolment instance and its pending queue entries.
     */
    public static function delete_instance($instanceid)
    {
        global $DB;

        $params = self::validate_parameters(self::delete_instance_parameters(), ['instanceid' => $instanceid]);
        $instanceid = $params['instanceid'];

        $instance = $DB->get_record('enrol', ['id' => $instanceid, 'enrol' => 'courseapproval'], '*', MUST_EXIST);

        $context = context_course::instance($instance->courseid);
        self::validate_context($context);
        require_capability('enrol/courseapproval:config', $context);

        $plugin = enrol_get_plugin('courseapproval');
        $plugin->delete_instance($instance);

        // Clean up any pending enrollments linked to this instance.
        $DB->delete_records('enrol_courseapproval_pending', ['enrolid' => $instanceid]);

        return true;
    }

    public static function delete_instance_returns()
    {
        return new external_value(PARAM_BOOL, 'True if deleted successfully');
    }

    // =========================================================================
    // get_course_logs
    // =========================================================================

    public static function get_course_logs_parameters()
    {
        return new external_function_parameters([
            'courseid'  => new external_value(PARAM_INT, 'Course ID'),
            'since'     => new external_value(PARAM_INT, 'Unix timestamp — only return entries after this', VALUE_DEFAULT, 0),
            'date'      => new external_value(PARAM_INT, 'Alias for since', VALUE_DEFAULT, 0),
            'userid'    => new external_value(PARAM_INT, 'Filter by user ID (0 = all users)', VALUE_DEFAULT, 0),
            'courseids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Course ID'),
                'Array of course IDs (ignored, use courseid)',
                VALUE_DEFAULT,
                []
            ),
        ]);
    }

    /**
     * Returns all log events for a course from logstore_standard_log since a given timestamp.
     * Used by the attendance dashboard to reconstruct real session times (entry + exit per day).
     */
    public static function get_course_logs($courseid, $since = 0, $date = 0, $userid = 0, $courseids = [])
    {
        global $DB;

        $params = self::validate_parameters(self::get_course_logs_parameters(), [
            'courseid'  => $courseid,
            'since'     => $since,
            'date'      => $date,
            'userid'    => $userid,
            'courseids' => $courseids,
        ]);

        // Acepta 'date' como alias de 'since' para compatibilidad con el servidor.
        if (empty($params['since']) && !empty($params['date'])) {
            $params['since'] = $params['date'];
        }

        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('enrol/courseapproval:config', $context);

        // OJO: get_records_select() indexa el array por la PRIMERA columna del SELECT.
        // Por eso 'id' (clave primaria, única) va primero. Si 'userid' fuera la primera
        // columna, todos los eventos del mismo usuario se sobrescribirían entre sí y solo
        // sobreviviría UNO → entrada == salida == 0 minutos en el dashboard.
        $records = $DB->get_records_select(
            'logstore_standard_log',
            'courseid = :courseid AND timecreated >= :since',
            ['courseid' => $params['courseid'], 'since' => $params['since']],
            'timecreated ASC',
            'id, userid, timecreated'
        );

        // Mapeo a objetos limpios {userid, timecreated} para que el 'id' extra no rompa
        // la validación de get_course_logs_returns().
        $result = [];
        foreach ($records as $record) {
            $result[] = [
                'userid'      => (int) $record->userid,
                'timecreated' => (int) $record->timecreated,
            ];
        }

        return $result;
    }

    public static function get_course_logs_returns()
    {
        return new external_multiple_structure(
            new external_single_structure([
                'userid'      => new external_value(PARAM_INT, 'Moodle user ID'),
                'timecreated' => new external_value(PARAM_INT, 'Unix timestamp of the event'),
            ])
        );
    }
}
