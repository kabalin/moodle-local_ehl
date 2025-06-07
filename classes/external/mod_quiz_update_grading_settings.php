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

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->libdir.'/gradelib.php');

/**
 * local_ehl external class mod_quiz_update_grading_settings
 *
 * @package   local_ehl
 * @copyright 2021 Ecole hôtelière de Lausanne {@link https://www.ehl.edu/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_update_grading_settings extends external_api {

    /**
     * Update quiz grading settings parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'quizid' => new external_value(PARAM_INT, 'quiz instance id'),
                'attempts' => new external_value(PARAM_INT, 'Number of attempts allowed, 0 for unlimited',
                    VALUE_DEFAULT, -1),
                'grademethod' => new external_value(PARAM_INT, 'Grade method. Valid for more than 1 attempt.'
                    . ' 1 - Highest grade, 2 - Average grade, 3 - First attempt, 4 - Last attempt', VALUE_DEFAULT, -1),
            ]
        );
    }

    /**
     * Update quiz grading settings
     *
     * @param int $quizid
     * @param int $attempts
     * @param int $grademethod
     * @return array returns true in case of grading settings were updated successfully.
     */
    public static function execute(int $quizid, int $attempts, int $grademethod): array {
        global $DB;
        $params = self::validate_parameters(self::execute_parameters(), [
            'quizid' => $quizid,
            'attempts' => $attempts,
            'grademethod' => $grademethod,
        ]);

        // Request and permission validation.
        $cm = get_coursemodule_from_instance('quiz', $params['quizid'], 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

        // We use get_moduleinfo_data, it also verifies form update capability.
        [$cm, $context, $module, $data, $cw] = get_moduleinfo_data($cm, $course);

        // Validate context.
        self::validate_context($context);

        if ($params['grademethod'] !== -1 && !in_array($params['grademethod'], array_keys(quiz_get_grading_options()))) {
            throw new \invalid_parameter_exception('Invalid grademethod value');
        }

        if ($params['attempts'] === 1 && $params['grademethod'] !== -1) {
            throw new \invalid_parameter_exception('grademethod cant be set when 1 attempt is allowed.');
        }

        if ($params['attempts'] > QUIZ_MAX_ATTEMPT_OPTION) {
            throw new \invalid_parameter_exception('Invalid attempts value, it should not exceed ' . QUIZ_MAX_ATTEMPT_OPTION);
        }

        // Customise data. We only update values that were specified.
        $changes = [];
        if ($params['grademethod'] !== -1 && $params['grademethod'] != $data->grademethod) {
            $changes['grademethod'] = "{$data->grademethod} => " . $params['grademethod'];
            $data->grademethod = $params['grademethod'];
        }
        if ($params['attempts'] !== -1 && $params['attempts'] != $data->attempts) {
            $changes['attempts'] = "{$data->attempts} => " . $params['attempts'];
            $data->attempts = $params['attempts'];
        }

        if (!count($changes)) {
            // Nothing to change. New values are matching current ones.
            return ['status' => false];
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

        $fromform = $mform->get_data();
        update_moduleinfo($cm, $fromform, $course, $mform);
        return ['status' => true, 'changes' => json_encode($changes)];
    }

    /**
     * Update quiz grading settings return structure
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Success status'),
            'changes' => new external_value(PARAM_RAW,
                'JSON encoded list of settings that changed', VALUE_DEFAULT, ''),
        ]);
    }
}