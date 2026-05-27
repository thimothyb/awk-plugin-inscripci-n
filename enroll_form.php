<?php
/**
 * Bulk enrollment form for enrol_courseapproval.
 *
 * @package    enrol_courseapproval
 * @copyright  2026 Antigravity
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class enrol_courseapproval_enroll_form extends moodleform
{
    public function definition()
    {
        global $DB, $CFG;
        $mform = $this->_form;
        $step = $this->_customdata['step'] ?? 1;

        if ($step == 1) {
            $this->definition_step_1($mform);
        } else {
            $this->definition_step_2($mform);
        }
    }

    protected function definition_step_1($mform)
    {
        global $DB;
        $mform->addElement('header', 'courses', get_string('selectcourses', 'enrol_courseapproval'));

        $courses = $DB->get_records_select('course', 'id > ?', [SITEID], 'fullname ASC', 'id, fullname, shortname');
        $courseoptions = [];
        foreach ($courses as $course) {
            $courseoptions[$course->id] = format_string($course->fullname) . ' (' . $course->shortname . ')';
        }

        $mform->addElement('autocomplete', 'sourcecourse', get_string('sourcecourse', 'enrol_courseapproval'), $courseoptions);
        $mform->addRule('sourcecourse', get_string('required'), 'required', null, 'client');
        $mform->setType('sourcecourse', PARAM_INT);

        $mform->addElement('autocomplete', 'destcourse', get_string('destcourse', 'enrol_courseapproval'), $courseoptions);
        $mform->addRule('destcourse', get_string('required'), 'required', null, 'client');
        $mform->setType('destcourse', PARAM_INT);

        $this->add_action_buttons(false, get_string('next', 'enrol_courseapproval'));
        $mform->addElement('hidden', 'step', 1);
        $mform->setType('step', PARAM_INT);
    }

    protected function definition_step_2($mform)
    {
        global $DB;
        $sourcecourseid = $this->_customdata['sourcecourseid'];
        $destcourseid = $this->_customdata['destcourseid'];

        $sourcecourse = $DB->get_record('course', ['id' => $sourcecourseid], '*', MUST_EXIST);
        $destcourse = $DB->get_record('course', ['id' => $destcourseid], '*', MUST_EXIST);

        $mform->addElement('header', 'users', get_string('selectusers', 'enrol_courseapproval'));
        $mform->addElement(
            'static',
            'info',
            '',
            get_string('sourcecourse', 'enrol_courseapproval') . ': ' . format_string($sourcecourse->fullname) . '<br>' .
            get_string('destcourse', 'enrol_courseapproval') . ': ' . format_string($destcourse->fullname)
        );

        $users = enrol_courseapproval_get_source_users($sourcecourseid);

        if (empty($users)) {
            $mform->addElement('static', 'nousers', '', get_string('noenrolledusers', 'enrol_courseapproval'));
        } else {
            $destcontext = context_course::instance($destcourseid);
            foreach ($users as $user) {
                $fullname = fullname($user);
                $isenrolled = is_enrolled($destcontext, $user->id);
                $label = $fullname . ($isenrolled ? ' (' . get_string('alreadyenrolled', 'enrol_courseapproval') . ')' : '');

                // AQUÍ ESTÁ EL CAMBIO CLAVE: Quitamos los corchetes []
                $elementname = 'user_' . $user->id;

                $checkbox = $mform->addElement('advcheckbox', $elementname, '', $label, null, [0, 1]);
                $mform->setType($elementname, PARAM_INT);
                if ($isenrolled) {
                    $checkbox->updateAttributes(['disabled' => 'disabled']);
                }
            }
        }

        $this->add_action_buttons(true, get_string('enrolnow', 'enrol_courseapproval'));
        $mform->addElement('hidden', 'sourcecourse', $sourcecourseid);
        $mform->setType('sourcecourse', PARAM_INT);
        $mform->addElement('hidden', 'destcourse', $destcourseid);
        $mform->setType('destcourse', PARAM_INT);
        $mform->addElement('hidden', 'step', 2);
        $mform->setType('step', PARAM_INT);
    }
}