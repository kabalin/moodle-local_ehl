<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_ehl\external;

defined('MOODLE_INTERNAL') || die();

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->libdir.'/gradelib.php');

/**
 * local_ehl external class mod_quiz_update_timing_settings
 *
 * @package   local_ehl
 * @copyright 2021 Ecole hôtelière de Lausanne {@link https://www.ehl.edu/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_update_timing_settings extends external_api {

    /**
     * Update quiz timing settings parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'quizid' => new external_value(PARAM_INT, 'quiz instance id'),
                'timeopen' => new external_value(PARAM_INT, 'Open date in Unix time stamp', VALUE_DEFAULT, -1),
                'timeclose' => new external_value(PARAM_INT, 'Close date in Unix time stamp', VALUE_DEFAULT, -1),
                'timelimit' => new external_value(PARAM_INT, 'Specify timelimit, setting to 0 disables limit', VALUE_DEFAULT, -1),
                'overduehandling' => new external_value(PARAM_ALPHA, 'Overdue handling setting: autosubmit, graceperiod, autoabandon', VALUE_DEFAULT, ''),
                'graceperiod' => new external_value(PARAM_INT, 'Grace period in seconds. Can only be set when Overdue handling is set to graceperiod.', VALUE_DEFAULT, -1),
            ]
        );
    }

    /**
     * Update quiz timing settings
     *
     * @param int $quizid
     * @param int $timeopen
     * @param int $timeclose
     * @param int $timelimit
     * @param string $overduehandling
     * @param int $graceperiod
     * @return array returns true in case of timing settings were updated successfully.
     */
    public static function execute(int $quizid, int $timeopen, int $timeclose, int $timelimit, string $overduehandling, int $graceperiod): array {
        global $DB, $CFG;
        $result = false;
        $params = self::validate_parameters(self::execute_parameters(), [
            'quizid' => $quizid,
            'timeopen' => $timeopen,
            'timeclose' => $timeclose,
            'timelimit' => $timelimit,
            'overduehandling' => $overduehandling,
            'graceperiod' => $graceperiod,
        ]);

        // Request and permission validation.
        $cm = get_coursemodule_from_instance('quiz', $params['quizid'], 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

        // We use get_moduleinfo_data, it also verifies form update capability.
        [$cm, $context, $module, $data, $cw] = get_moduleinfo_data($cm, $course);

        // Validate context.
        self::validate_context($context);

        if ($params['overduehandling'] && !in_array($params['overduehandling'], ['autosubmit', 'graceperiod', 'autoabandon'])) {
            throw new \invalid_parameter_exception('Invalid overduehandling value');
        }

        if ($params['graceperiod'] !== -1 && $params['overduehandling'] !== 'graceperiod') {
            throw new \invalid_parameter_exception('graceperiod can only be set when overduehandling=graceperiod.');
        }

        // Customise data. We only update values that were specified.
        if ($params['timeopen'] !== -1) {
            $data->timeopen = $params['timeopen'];
        }
        if ($params['timeclose'] !== -1) {
            $data->timeclose = $params['timeclose'];
        }
        if ($params['timelimit'] !== -1) {
            $data->timelimit = $params['timelimit'];
        }
        if ($params['overduehandling']) {
            $data->overduehandling = $params['overduehandling'];
        }
        if ($params['graceperiod'] !== -1) {
            $data->graceperiod = $params['graceperiod'];
        }
        if ($data->availabilityconditionsjson === null) {
            // Null is stored in DB for empty availability tree.
            // Set to empty string to make validation happy, normally JS sets this empty tree JSON in the web form.
            $data->availabilityconditionsjson = '';
        }

        // Get form instance.
        $mform = new \local_ehl\form\mod_quiz_mod_form($data, $cw->section, $cm, $course);
        $mform->set_data($data);

        // Validate form.
        if (!$mform->is_validated()) {
            $errors = $mform->get_quick_form()->_errors;
            $errordescr = [];
            foreach ($errors as $key => $value) {
                $errordescr[] = $key.' - '.$value;
            }
            throw new \invalid_parameter_exception("Invalid parameters: " . implode(';', $errordescr));
        }

        // TODO: fix review options, they are missing.
        $fromform = $mform->get_data();
        update_moduleinfo($cm, $fromform, $course, $mform);
        $result = true;

        return ['status' => $result];
    }

    /**
     * Update quiz timing settings return structure
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Success status')
        ]);
    }
}