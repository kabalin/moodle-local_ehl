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
use external_multiple_structure;

require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
 * local_ehl external class mod_quiz_list_group_overrides
 *
 * @package   local_ehl
 * @copyright 2021 Ecole hôtelière de Lausanne {@link https://www.ehl.edu/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_list_group_overrides extends external_api {

    /**
     * List quiz group overrides parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'quizid' => new external_value(PARAM_INT, 'quiz instance id'),
            ]
        );
    }

    /**
     * List quiz group overrides
     *
     * @param int $quizid
     * @return array returns list of overrides.
     */
    public static function execute(int $quizid): array {
        global $DB;
        $params = self::validate_parameters(self::execute_parameters(), [
            'quizid' => $quizid,
        ]);

        // Request and permission validation.
        $cm = get_coursemodule_from_instance('quiz', $params['quizid'], 0, false, MUST_EXIST);
        $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);

        $context = \context_module::instance($cm->id);

        // Add or edit an override.
        require_capability('mod/quiz:manageoverrides', $context);

        // Validate context.
        self::validate_context($context);

        // To filter the result by the list of groups that the current user has access to.
        $params = ['quizid' => $quiz->id];
        $groups = groups_get_activity_allowed_groups($cm);
        [$insql, $inparams] = $DB->get_in_or_equal(array_keys($groups), SQL_PARAMS_NAMED);
        $params += $inparams;

        $sql = "SELECT o.*, g.name
                  FROM {quiz_overrides} o
                  JOIN {groups} g ON o.groupid = g.id
                 WHERE o.quiz = :quizid AND g.id $insql
              ORDER BY g.name";

        $overrides = $DB->get_records_sql($sql, $params);

        return ['overrides' => $overrides];
    }

    /**
     * List quiz group overrides return structure
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'overrides' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Override id'),
                    'quiz' => new external_value(PARAM_INT, 'quiz instance id'),
                    'groupid' => new external_value(PARAM_INT, 'group instance id'),
                    'timeopen' => new external_value(PARAM_INT, 'Open date in Unix time stamp', VALUE_OPTIONAL),
                    'timeclose' => new external_value(PARAM_INT, 'Close date in Unix time stamp', VALUE_OPTIONAL),
                    'timelimit' => new external_value(PARAM_INT, 'Timelimit in seconds, 0 - disabled limit', VALUE_OPTIONAL),
                    'attempts' => new external_value(PARAM_INT, 'Number of attempts allowed, 0 for unlimited', VALUE_OPTIONAL),
                ])
            )
        ]);
    }
}