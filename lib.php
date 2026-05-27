<?php
/**
 * Enrolment plugin implementation for enrol_courseapproval.
 *
 * @package    enrol_courseapproval
 * @copyright  2026 Antigravity
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class enrol_courseapproval_plugin extends enrol_plugin
{

    /**
     * Returns the name of the plugin.
     * @return string
     */
    public function get_name()
    {
        return 'courseapproval';
    }

    /**
     * Returns true if we can add a new instance to this course.
     * @param int $courseid
     * @return bool
     */
    public function can_add_instance($courseid)
    {
        return true;
    }

    /**
     * Permite agregar múltiples instancias de este plugin al mismo curso.
     * Cada instancia puede tener un curso origen diferente.
     * @return bool
     */
    public function allow_multiple_instances()
    {
        return true;
    }

    /**
     * Does this plugin use enrolment keys?
     * @return bool
     */
    public function use_standard_enrolment_actions()
    {
        return false;
    }


    /**
     * Returns true if the plugin allows manual enrolment.
     * @param stdClass $instance
     * @return bool
     */
    public function allow_enrol($instance)
    {
        return false;
    }

    /**
     * Returns true if the plugin allows manual unenrolment.
     * @param stdClass $instance
     * @return bool
     */
    public function allow_unenrol($instance)
    {
        return true;
    }

    /**
     * Returns true if the plugin allows manual changes to user enrolments.
     * @param stdClass $instance
     * @return bool
     */
    public function allow_manage($instance)
    {
        return true;
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     * @param stdClass $instance
     * @return bool
     */
    public function can_delete_instance($instance)
    {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/courseapproval:config', $context);
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance)
    {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/courseapproval:config', $context);
    }

    /**
     * Returns defaults for new enrolment instances.
     * @return array
     */
    public function get_instance_defaults()
    {
        $fields = [];
        $fields['status'] = $this->get_config('status', ENROL_INSTANCE_ENABLED);
        $fields['roleid'] = $this->get_config('roleid', 5);
        $fields['customint1'] = 0; // Source course ID.
        return $fields;
    }

    /**
     * Adds enrolment instance to course.
     * @param stdClass $course
     * @param array $fields
     * @return int instance id
     */
    public function add_instance($course, array $fields = null)
    {
        $fields = (array) $fields;
        if (!isset($fields['customint1'])) {
            $fields['customint1'] = 0; // Source Course ID.
        }
        if (!isset($fields['roleid'])) {
            $fields['roleid'] = $this->get_config('roleid', 5);
        }
        return parent::add_instance($course, $fields);
    }

    /**
     * Add settings form to the enrolment methods page.
     * @param stdClass $instance
     * @param MoodleQuickForm $mform
     * @param context $context
     */
    public function edit_instance_form($instance, MoodleQuickForm $mform, $context)
    {
        global $DB;

        $mform->addElement('header', 'config', get_string('pluginname', 'enrol_courseapproval'));

        // Get current course id safely.
        $currentcourseid = !empty($instance->courseid) ? $instance->courseid : 0;

        // Source Course Selection.
        $options = ['' => get_string('choosedots')];
        $courses = $DB->get_records('course', [], 'fullname ASC', 'id, fullname, shortname');
        foreach ($courses as $course) {
            if ($course->id != $currentcourseid && $course->id != SITEID) {
                $options[$course->id] = format_string($course->fullname) . ' (' . $course->shortname . ')';
            }
        }
        $mform->addElement('select', 'customint1', get_string('sourcecourse', 'enrol_courseapproval'), $options);
        $mform->addHelpButton('customint1', 'sourcecourse', 'enrol_courseapproval');
        $mform->addRule('customint1', get_string('required'), 'required', null, 'client');
        $mform->setType('customint1', PARAM_INT);

        // Role selection.
        $roles = get_default_enrol_roles($context, $this->get_config('roleid'));
        $mform->addElement('select', 'roleid', get_string('assignrole', 'enrol_courseapproval'), $roles);
        $mform->setDefault('roleid', $this->get_config('roleid', 5));
        $mform->setType('roleid', PARAM_INT);

        // Status (enabled/disabled).
        $options = [
            ENROL_INSTANCE_ENABLED => get_string('yes'),
            ENROL_INSTANCE_DISABLED => get_string('no'),
        ];
        $mform->addElement('select', 'status', get_string('status', 'enrol_courseapproval'), $options);
        $mform->setDefault('status', $this->get_config('status', ENROL_INSTANCE_ENABLED));
        $mform->setType('status', PARAM_INT);

        // Lock source course if editing existing instance.
        if (!empty($instance->id)) {
            $mform->freeze('customint1');
        }
    }

    /**
     * Perform custom validation of the data from the edit instance form.
     * @param array $data
     * @param array $files
     * @param stdClass $instance
     * @param context $context
     * @return array of errors
     */
    public function edit_instance_validation($data, $files, $instance, $context)
    {
        global $DB;
        $errors = [];

        if (empty($data['customint1'])) {
            $errors['customint1'] = get_string('required');
        } else {
            // Verify the source course exists and has completion enabled.
            $sourcecourse = $DB->get_record('course', ['id' => $data['customint1']]);
            if (!$sourcecourse) {
                $errors['customint1'] = get_string('invalidcourseid', 'error');
            } else if (empty($sourcecourse->enablecompletion)) {
                $errors['customint1'] = get_string('completionnotenabled', 'enrol_courseapproval');
            }
        }

        return $errors;
    }

    /**
     * Check if a user can be enrolled based on course start date.
     * @param int $courseid
     * @return bool
     */
    public function can_enrol_now($courseid)
    {
        global $DB;
        $course = $DB->get_record('course', ['id' => $courseid], 'startdate', MUST_EXIST);
        return (time() >= $course->startdate);
    }

    /**
     * Check if user has successfully completed a course.
     * @param int $courseid
     * @param int $userid
     * @return bool
     */
    public function is_course_completed($courseid, $userid)
    {
        global $DB;

        // Check course_completions table for completion record.
        $completion = $DB->get_record('course_completions', [
            'course' => $courseid,
            'userid' => $userid,
        ]);

        // timecompleted must be set (not null) for the course to be completed.
        return ($completion && !empty($completion->timecompleted));
    }

    /**
     * Check if user is already enrolled in a course.
     * @param int $courseid
     * @param int $userid
     * @return bool
     */
    public function is_user_enrolled($courseid, $userid)
    {
        $context = context_course::instance($courseid);
        return is_enrolled($context, $userid);
    }

    /**
     * Logic to enrol user or queue them.
     * @param int $destcourseid
     * @param int $userid
     * @param int $enrolid
     */
    public function process_user_enrolment($destcourseid, $userid, $enrolid)
    {
        global $DB;

        // Skip if user is already enrolled.
        if ($this->is_user_enrolled($destcourseid, $userid)) {
            // Clean up any pending record if exists.
            $DB->delete_records('enrol_courseapproval_pending', [
                'courseid' => $destcourseid,
                'userid' => $userid,
            ]);
            return;
        }

        if ($this->can_enrol_now($destcourseid)) {
            // Enrol immediately.
            $instance = $DB->get_record('enrol', ['id' => $enrolid], '*', MUST_EXIST);

            // Use instance role or fall back to config.
            $roleid = !empty($instance->roleid) ? $instance->roleid : $this->get_config('roleid', 5);

            $this->enrol_user($instance, $userid, $roleid);

            // If they were pending, remove them.
            $DB->delete_records('enrol_courseapproval_pending', [
                'courseid' => $destcourseid,
                'userid' => $userid,
            ]);

            // Trigger event for logging.
            $this->log_enrolment($instance, $userid);
        } else {
            // Add to pending table if not already there.
            if (
                !$DB->record_exists('enrol_courseapproval_pending', [
                    'courseid' => $destcourseid,
                    'userid' => $userid,
                ])
            ) {
                $pending = new stdClass();
                $pending->courseid = $destcourseid;
                $pending->userid = $userid;
                $pending->enrolid = $enrolid;
                $pending->timecreated = time();
                $DB->insert_record('enrol_courseapproval_pending', $pending);
            }
        }
    }

    /**
     * Log enrolment for debugging purposes.
     * @param stdClass $instance
     * @param int $userid
     */
    protected function log_enrolment($instance, $userid)
    {
        mtrace("enrol_courseapproval: Enrolled user {$userid} in course {$instance->courseid}");
    }
}

/**
 * Extiende la navegación del curso para inyectar los enlaces de gestión de enrol_courseapproval.
 */
function enrol_courseapproval_extend_navigation_course(navigation_node $navigation, stdClass $course, context_course $context)
{
    if (!is_siteadmin()) {
        return;
    }

    // 1. Enlace a Inscripción Manual Masiva.
    $urlmasiva = new moodle_url('/enrol/courseapproval/view.php', ['id' => $course->id]);
    $node1 = navigation_node::create(
        get_string('bulkenrol', 'enrol_courseapproval'),
        $urlmasiva,
        navigation_node::TYPE_SETTING,
        null,
        'enrol_courseapproval_manual_link',
        new pix_icon('i/enrolments', '')
    );
    $navigation->add_node($node1);

    // 2. Enlace a Inscripción Condicional (Settings del método).
    $urlcondicional = new moodle_url('/enrol/editinstance.php', [
        'type' => 'courseapproval',
        'courseid' => $course->id
    ]);
    $node2 = navigation_node::create(
        get_string('conditionalenrol', 'enrol_courseapproval'),
        $urlcondicional,
        navigation_node::TYPE_SETTING,
        null,
        'enrol_courseapproval_conditional_link',
        new pix_icon('i/settings', '')
    );
    $navigation->add_node($node2);
}

/**
 * Agrega los mismos enlaces al bloque de administración del curso.
 */
function enrol_courseapproval_extend_settings_navigation(settings_navigation $settingsnav, context_course $context)
{
    if (!is_siteadmin() || $context->contextlevel != CONTEXT_COURSE) {
        return;
    }

    // 1. Manual Masiva.
    $url1 = new moodle_url('/enrol/courseapproval/view.php', ['id' => $context->instanceid]);
    $node1 = navigation_node::create(
        get_string('bulkenrol', 'enrol_courseapproval'),
        $url1,
        navigation_node::TYPE_SETTING,
        null,
        'enrol_courseapproval_admin_manual',
        new pix_icon('i/enrolments', '')
    );
    $settingsnav->add_node($node1);

    // 2. Condicional.
    $url2 = new moodle_url('/enrol/editinstance.php', [
        'type' => 'courseapproval',
        'courseid' => $context->instanceid
    ]);
    $node2 = navigation_node::create(
        get_string('conditionalenrol', 'enrol_courseapproval'),
        $url2,
        navigation_node::TYPE_SETTING,
        null,
        'enrol_courseapproval_admin_conditional',
        new pix_icon('i/settings', '')
    );
    $settingsnav->add_node($node2);
}

/**
 * Get students enrolled in a course.
 * @param int $courseid
 * @return array
 */
function enrol_courseapproval_get_source_users($courseid)
{
    global $DB;

    $context = context_course::instance($courseid);
    // Get all users who have (or had) an enrollment in the course,
    // including suspended/expired enrollments ($onlyactive = false).
    $users = get_enrolled_users($context, '', 0, 'u.*', 'lastname, firstname', 0, 0, false);

    return $users;
}
/**
 * Bulk enrol users in a destination course using the manual enrolment plugin.
 * @param int $destcourseid
 * @param array $userids
 * @return array Results summary
 */
function enrol_courseapproval_bulk_enrol($destcourseid, array $userids)
{
    global $DB;
    $results = ['success' => 0, 'skipped' => 0, 'error' => 0];

    $enrol = enrol_get_plugin('manual');
    if (!$enrol) {
        return $results;
    }

    // Find or create manual enrolment instance in destination course.
    $instance = $DB->get_record('enrol', ['courseid' => $destcourseid, 'enrol' => 'manual'], '*', IGNORE_MISSING);
    if (!$instance) {
        $course = $DB->get_record('course', ['id' => $destcourseid], '*', MUST_EXIST);
        $instanceid = $enrol->add_default_instance($course);
        $instance = $DB->get_record('enrol', ['id' => $instanceid]);
    }

    // Obtener el rol configurado en los ajustes del plugin, o usar 5 por defecto.
    $roleid = get_config('enrol_courseapproval', 'roleid') ?: 5;
    $context = context_course::instance($destcourseid);

    foreach ($userids as $userid) {
        if (is_enrolled($context, $userid)) {
            $results['skipped']++;
            continue;
        }

        try {
            $enrol->enrol_user($instance, $userid, $roleid);
            $results['success']++;
        } catch (Exception $e) {
            $results['error']++;
        }
    }

    return $results;
}
