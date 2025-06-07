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

/**
 * local_ehl external class mod_quiz_delete_group_override
 *
 * @package   local_ehl
 * @copyright 2021 Ecole hôtelière de Lausanne {@link https://www.ehl.edu/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_delete_group_override extends external_api {

    /**
     * Delete quiz group override parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'overrideid' => new external_value(PARAM_INT, 'quiz group override id'),
            ]
        );
    }

    /**
     * Delete quiz group override
     *
     * @param int $overrideid
     * @return array returns status true in case of quiz group override was created.
     */
    public static function execute(int $overrideid): array {
        global $DB;
        $params = self::validate_parameters(self::execute_parameters(), [
            'overrideid' => $overrideid,
        ]);

        // Request and permission validation.
        $override = $DB->get_record('quiz_overrides', array('id' => $params['overrideid']));
        $cm = get_coursemodule_from_instance('quiz', $override->quiz, 0, false, MUST_EXIST);
        $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

        $context = \context_module::instance($cm->id);

        // Add or edit an override.
        require_capability('mod/quiz:manageoverrides', $context);

        // Validate context.
        self::validate_context($context);

        // Check group is accessible.
        if (!groups_group_visible($override->groupid, $course, $cm)) {
            throw new \invalid_parameter_exception(get_string('invalidoverrideid', 'quiz'));
        }

        $quizsettings = \mod_quiz\quiz_settings::create($quiz->id);
        $quizsettings->get_override_manager()->delete_overrides_by_id(
            ids: [$override->id]
        );

        return ['status' => true];
    }

    /**
     * Delete quiz group override return structure
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Success status'),
        ]);
    }
}