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
 * local_ehl external class mod_quiz_update_review_settings
 *
 * @package   local_ehl
 * @copyright 2021 Ecole hôtelière de Lausanne {@link https://www.ehl.edu/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_update_review_settings extends external_api {

    /**
     * Update quiz review settings parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'quizid' => new external_value(PARAM_INT, 'quiz instance id'),
                'attempt' => new external_single_structure([
                    'during' => new external_value(PARAM_BOOL, 'During', VALUE_OPTIONAL),
                    'immediately' => new external_value(PARAM_BOOL, 'Immediately', VALUE_OPTIONAL),
                    'open' => new external_value(PARAM_BOOL, 'Open', VALUE_OPTIONAL),
                    'closed' => new external_value(PARAM_BOOL, 'Closed', VALUE_OPTIONAL),
                ], 'Attempt', VALUE_DEFAULT, []),
                'correctness' => new external_single_structure([
                    'during' => new external_value(PARAM_BOOL, 'During', VALUE_OPTIONAL),
                    'immediately' => new external_value(PARAM_BOOL, 'Immediately', VALUE_OPTIONAL),
                    'open' => new external_value(PARAM_BOOL, 'Open', VALUE_OPTIONAL),
                    'closed' => new external_value(PARAM_BOOL, 'Closed', VALUE_OPTIONAL),
                ], 'Correctness', VALUE_DEFAULT, []),
                'maxmarks' => new external_single_structure([
                    'during' => new external_value(PARAM_BOOL, 'During', VALUE_OPTIONAL),
                    'immediately' => new external_value(PARAM_BOOL, 'Immediately', VALUE_OPTIONAL),
                    'open' => new external_value(PARAM_BOOL, 'Open', VALUE_OPTIONAL),
                    'closed' => new external_value(PARAM_BOOL, 'Closed', VALUE_OPTIONAL),
                ], 'Maximum marks', VALUE_DEFAULT, []),
                'marks' => new external_single_structure([
                    'during' => new external_value(PARAM_BOOL, 'During', VALUE_OPTIONAL),
                    'immediately' => new external_value(PARAM_BOOL, 'Immediately', VALUE_OPTIONAL),
                    'open' => new external_value(PARAM_BOOL, 'Open', VALUE_OPTIONAL),
                    'closed' => new external_value(PARAM_BOOL, 'Closed', VALUE_OPTIONAL),
                ], 'Marks', VALUE_DEFAULT, []),
                'specificfeedback' => new external_single_structure([
                    'during' => new external_value(PARAM_BOOL, 'During', VALUE_OPTIONAL),
                    'immediately' => new external_value(PARAM_BOOL, 'Immediately', VALUE_OPTIONAL),
                    'open' => new external_value(PARAM_BOOL, 'Open', VALUE_OPTIONAL),
                    'closed' => new external_value(PARAM_BOOL, 'Closed', VALUE_OPTIONAL),
                ], 'Specificfeedback', VALUE_DEFAULT, []),
                'generalfeedback' => new external_single_structure([
                    'during' => new external_value(PARAM_BOOL, 'During', VALUE_OPTIONAL),
                    'immediately' => new external_value(PARAM_BOOL, 'Immediately', VALUE_OPTIONAL),
                    'open' => new external_value(PARAM_BOOL, 'Open', VALUE_OPTIONAL),
                    'closed' => new external_value(PARAM_BOOL, 'Closed', VALUE_OPTIONAL),
                ], 'Generalfeedback', VALUE_DEFAULT, []),
                'rightanswer' => new external_single_structure([
                    'during' => new external_value(PARAM_BOOL, 'During', VALUE_OPTIONAL),
                    'immediately' => new external_value(PARAM_BOOL, 'Immediately', VALUE_OPTIONAL),
                    'open' => new external_value(PARAM_BOOL, 'Open', VALUE_OPTIONAL),
                    'closed' => new external_value(PARAM_BOOL, 'Closed', VALUE_OPTIONAL),
                ], 'Rightanswer', VALUE_DEFAULT, []),
                'overallfeedback' => new external_single_structure([
                    'during' => new external_value(PARAM_BOOL, 'During', VALUE_OPTIONAL),
                    'immediately' => new external_value(PARAM_BOOL, 'Immediately', VALUE_OPTIONAL),
                    'open' => new external_value(PARAM_BOOL, 'Open', VALUE_OPTIONAL),
                    'closed' => new external_value(PARAM_BOOL, 'Closed', VALUE_OPTIONAL),
                ], 'Overallfeedback', VALUE_DEFAULT, []),
            ]
        );
    }

    /**
     * Update quiz review settings
     *
     * @param int $quizid
     * @param array $attempt
     * @param array $correctness
     * @param array $maxmarks
     * @param array $marks
     * @param array $specificfeedback
     * @param array $generalfeedback
     * @param array $rightanswer
     * @param array $overallfeedback
     * @return array returns true in case of review settings were updated successfully.
     */
    public static function execute(int $quizid, array $attempt, array $correctness, array $maxmarks, array $marks,
            array $specificfeedback, array $generalfeedback, array $rightanswer, array $overallfeedback): array {
        global $DB;
        $params = self::validate_parameters(self::execute_parameters(), [
            'quizid' => $quizid,
            'attempt' => $attempt,
            'correctness' => $correctness,
            'maxmarks' => $maxmarks,
            'marks' => $marks,
            'specificfeedback' => $specificfeedback,
            'generalfeedback' => $generalfeedback,
            'rightanswer' => $rightanswer,
            'overallfeedback' => $overallfeedback,
        ]);

        // Request and permission validation.
        $cm = get_coursemodule_from_instance('quiz', $params['quizid'], 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

        // We use get_moduleinfo_data, it also verifies form update capability.
        [$cm, $context, $module, $data, $cw] = get_moduleinfo_data($cm, $course);

        // Validate context.
        self::validate_context($context);

        // Form conditions logic check.
        $whens = ['during', 'immediately', 'open', 'closed'];
        foreach ($whens as $when) {
            if ($when === 'during') {
                continue;
            }
            if (isset($params['correctness'][$when]) && $params['correctness'][$when] === true
                    && (!isset($params['attempt'][$when]) || $params['attempt'][$when] === false)) {
                throw new \invalid_parameter_exception("Can't set 'correctness' '$when' without 'attempt' '$when'");
            }
            if (isset($params['specificfeedback'][$when]) && $params['specificfeedback'][$when] === true
                    && (!isset($params['attempt'][$when]) || $params['attempt'][$when] === false)) {
                throw new \invalid_parameter_exception("Can't set 'specificfeedback' '$when' without 'attempt' '$when'");
            }
            if (isset($params['generalfeedback'][$when]) && $params['generalfeedback'][$when] === true
                    && (!isset($params['attempt'][$when]) || $params['attempt'][$when] === false)) {
                throw new \invalid_parameter_exception("Can't set 'generalfeedback' '$when' without 'attempt' '$when'");
            }
            if (isset($params['rightanswer'][$when]) && $params['rightanswer'][$when] === true
                    && (!isset($params['attempt'][$when]) || $params['attempt'][$when] === false)) {
                throw new \invalid_parameter_exception("Can't set 'rightanswer' '$when' without 'attempt' '$when'");
            }
            if (isset($params['marks'][$when]) && $params['marks'][$when] === true
                    && (!isset($params['maxmarks'][$when]) || $params['maxmarks'][$when] === false)) {
               throw new \invalid_parameter_exception("Can't set 'marks' '$when' without 'maxmarks' '$when'");
            }
        }

        // Customise data. We only update values that were specified.
        $options = [
            'attempt',
            'correctness',
            'maxmarks',
            'marks',
            'specificfeedback',
            'generalfeedback',
            'rightanswer',
            'overallfeedback',
        ];
        $changes = [];
        foreach ($options as $option) {
            $fields = [];
            foreach ($whens as $when) {
                $fields[$option . $when] = isset($params[$option][$when]) ? $params[$option][$when] : false;
            }
            $review = quiz_review_option_form_to_db((object) $fields, $option);
            $fieldname = 'review' . $option;
            if ($data->$fieldname != $review) {
                $changes[$option] = "{$data->$fieldname} => " . $review;
                $data->$fieldname = $review;
            }
        }

        if (!count($changes)) {
            // Nothing to change. New values are matching current ones.
            return ['status' => false];
        }

        // Use form to preprocess and format data.
        $mform = new \local_ehl\form\mod_quiz_mod_form($data, $cw->section, $cm, $course);
        $mform->set_data($data);
        $fromform = $mform->get_submitted_data();
        update_moduleinfo($cm, $fromform, $course);
        return ['status' => true, 'changes' => json_encode($changes)];
    }

    /**
     * Update quiz review settings return structure
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