<?php
/**
 * Bulk enrollment view - BYPASS MODE
 * Procesa la inscripción manualmente ignorando la validación estricta de Moodle.
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/enrol/courseapproval/lib.php');
require_once($CFG->dirroot . '/enrol/courseapproval/enroll_form.php');

require_login();

// Restricción estricta: Solo administradores del sitio.
if (!is_siteadmin()) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('accessrestricted', 'enrol_courseapproval'));
}

$systemcontext = context_system::instance();
$PAGE->set_url(new moodle_url('/enrol/courseapproval/view.php'));
$PAGE->set_context($systemcontext);
$PAGE->set_title(get_string('bulkenrol', 'enrol_courseapproval'));
$PAGE->set_heading(get_string('bulkenrol', 'enrol_courseapproval'));

// -------------------------------------------------------------------------
// 1. PROCESAMIENTO MANUAL (Bypass de validación)
// -------------------------------------------------------------------------
// Verificamos si se pulsó el botón "Enrol now" (que tiene name="submitbutton")
if (optional_param('submitbutton', null, PARAM_TEXT)) {

    $destcourse = optional_param('destcourse', 0, PARAM_INT);
    $userids = [];

    // Recorremos todos los datos crudos enviados por el navegador
    foreach ($_POST as $key => $value) {
        // Buscamos campos que empiecen por "user_" (ej: user_15)
        if (preg_match('/^user_(\d+)$/', $key, $matches)) {
            // Si el valor es 1 (marcado), lo guardamos
            if ($value == 1) {
                $userids[] = (int) $matches[1];
            }
        }
    }

    if ($destcourse > 0 && !empty($userids)) {
        // Realizar la inscripción directamente
        $results = enrol_courseapproval_bulk_enrol($destcourse, $userids);

        $message = get_string('enrolsuccess', 'enrol_courseapproval', (object) $results);
        \core\notification::success($message);

        // Redirigir para limpiar el formulario
        redirect(new moodle_url('/enrol/courseapproval/view.php'));
        exit; // Detener ejecución aquí
    } else {
        if (empty($userids)) {
            \core\notification::warning("No seleccionaste ningún usuario.");
        }
    }
}

// -------------------------------------------------------------------------
// 2. MOSTRAR FORMULARIO
// -------------------------------------------------------------------------

$step = optional_param('step', 1, PARAM_INT);
$sourcecourseid = optional_param('sourcecourse', 0, PARAM_INT);
$destcourseid = optional_param('destcourse', 0, PARAM_INT);

// Si llegamos aquí por get_data() del paso 1, avanzamos
$mform = new enrol_courseapproval_enroll_form(null, [
    'step' => $step,
    'sourcecourseid' => $sourcecourseid,
    'destcourseid' => $destcourseid,
]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/enrol/courseapproval/view.php'));
} else if ($data = $mform->get_data()) {
    // Si el formulario estándar procesa el paso 1 correctamente
    if ($data->step == 1) {
        // Reconstruir formulario para el paso 2
        $mform = new enrol_courseapproval_enroll_form(null, [
            'step' => 2,
            'sourcecourseid' => $data->sourcecourse,
            'destcourseid' => $data->destcourse,
        ]);
    }
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();