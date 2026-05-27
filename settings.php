<?php
/**
 * Settings for the enrol_courseapproval plugin.
 *
 * @package    enrol_courseapproval
 * @copyright  2026 Antigravity
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // Default role setting.
    $options = get_default_enrol_roles(context_system::instance());
    $student = get_archetype_roles('student');
    $student = reset($student);
    $settings->add(new admin_setting_configselect(
        'enrol_courseapproval/roleid',
        get_string('defaultrole', 'enrol_courseapproval'),
        get_string('defaultrole_desc', 'enrol_courseapproval'),
        $student->id ?? 5,
        $options
    ));

    // Enrol instance defaults.
    $settings->add(new admin_setting_heading(
        'enrol_courseapproval/instancedefaults',
        get_string('instancedefaults', 'enrol_courseapproval'),
        ''
    ));

    $options = [
        ENROL_INSTANCE_ENABLED  => get_string('yes'),
        ENROL_INSTANCE_DISABLED => get_string('no'),
    ];
    $settings->add(new admin_setting_configselect(
        'enrol_courseapproval/status',
        get_string('status', 'enrol_courseapproval'),
        get_string('status_desc', 'enrol_courseapproval'),
        ENROL_INSTANCE_ENABLED,
        $options
    ));
}
