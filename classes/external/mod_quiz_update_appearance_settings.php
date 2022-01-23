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
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
 * local_ehl external class mod_quiz_update_appearance_settings
 *
 * @package   local_ehl
 * @copyright 2022 Ecole hôtelière de Lausanne {@link https://www.ehl.edu/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_update_appearance_settings extends external_api {

    /**
     * Update quiz appearance settings parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'quizid' => new external_value(PARAM_INT, 'quiz instance id'),
                'showuserpicture' => new external_value(PARAM_INT, 'Show user\'s image, 0 - none, 1 - small, 2 - large', VALUE_DEFAULT, -1),
                'decimalpoints' => new external_value(PARAM_INT, 'Decimal places in grades', VALUE_DEFAULT, -1),
                'questiondecimalpoints' => new external_value(PARAM_INT, 'Decimal places in question grades (set -1 for "same as in overall grades")', VALUE_DEFAULT, -2),
                'showblocks' => new external_value(PARAM_INT, 'Show blocks during quiz attempts, 0 - don\'t show, 1 - show', VALUE_DEFAULT, -1),
            ]
        );
    }

    /**
     * Update quiz appearance settings
     *
     * @param int $quizid
     * @param int $showuserpicture
     * @param int $decimalpoints
     * @param int $questiondecimalpoints
     * @param int $showblocks
     * @return array returns true in case of appearance settings were updated successfully.
     */
    public static function execute(int $quizid, int $showuserpicture, int $decimalpoints, int $questiondecimalpoints, int $showblocks): array {
        global $DB;
        $params = self::validate_parameters(self::execute_parameters(), [
            'quizid' => $quizid,
            'showuserpicture' => $showuserpicture,
            'decimalpoints' => $decimalpoints,
            'questiondecimalpoints' => $questiondecimalpoints,
            'showblocks' => $showblocks,
        ]);

        // Request and permission validation.
        $cm = get_coursemodule_from_instance('quiz', $params['quizid'], 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

        // We use get_moduleinfo_data, it also verifies form update capability.
        [$cm, $context, $module, $data, $cw] = get_moduleinfo_data($cm, $course);

        // Validate context.
        self::validate_context($context);

        if ($params['showuserpicture'] !== -1 && !in_array($params['showuserpicture'], array_keys(quiz_get_user_image_options()))) {
            throw new \invalid_parameter_exception('Invalid showuserpicture value');
        }

        if ($params['decimalpoints'] !== -1 && $params['decimalpoints'] > QUIZ_MAX_DECIMAL_OPTION) {
            throw new \invalid_parameter_exception('Invalid decimalpoints value, it should not exceed ' . QUIZ_MAX_DECIMAL_OPTION);
        }

        if ($params['questiondecimalpoints'] !== -2 && $params['questiondecimalpoints'] > QUIZ_MAX_Q_DECIMAL_OPTION) {
            throw new \invalid_parameter_exception('Invalid questiondecimalpoints value, it should not exceed ' . QUIZ_MAX_Q_DECIMAL_OPTION);
        }

        // Customise data. We only update values that were specified.
        $changes = [];
        if ($params['showuserpicture'] !== -1 && $params['showuserpicture'] != $data->showuserpicture) {
            $changes['showuserpicture'] = "{$data->showuserpicture} => " . $params['showuserpicture'];
            $data->showuserpicture = $params['showuserpicture'];
        }
        if ($params['decimalpoints'] !== -1 && $params['decimalpoints'] != $data->decimalpoints) {
            $changes['decimalpoints'] = "{$data->decimalpoints} => " . $params['decimalpoints'];
            $data->decimalpoints = $params['decimalpoints'];
        }
        if ($params['questiondecimalpoints'] !== -2 && $params['questiondecimalpoints'] != $data->questiondecimalpoints) {
            $changes['questiondecimalpoints'] = "{$data->questiondecimalpoints} => " . $params['questiondecimalpoints'];
            $data->questiondecimalpoints = $params['questiondecimalpoints'];
        }
        if ($params['showblocks'] !== -1 && $params['showblocks'] != $data->showblocks) {
            $changes['showblocks'] = "{$data->showblocks} => " . $params['showblocks'];
            $data->showblocks = $params['showblocks'];
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
     * Update quiz appearance settings return structure
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