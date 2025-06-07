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
 * local_ehl external class mod_quiz_create_group_override
 *
 * @package   local_ehl
 * @copyright 2021 Ecole hôtelière de Lausanne {@link https://www.ehl.edu/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_create_group_override extends external_api {

    /**
     * Create quiz group override parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'quizid' => new external_value(PARAM_INT, 'quiz instance id'),
                'groupid' => new external_value(PARAM_INT, 'group instance id'),
                'timeopen' => new external_value(PARAM_INT, 'Open date in Unix time stamp', VALUE_DEFAULT, 0),
                'timeclose' => new external_value(PARAM_INT, 'Close date in Unix time stamp', VALUE_DEFAULT, 0),
                'timelimit' => new external_value(PARAM_INT, 'Specify timelimit in seconds, setting to 0 disables limit', VALUE_DEFAULT, 0),
                'attempts' => new external_value(PARAM_INT, 'Number of attempts allowed, 0 for unlimited',
                    VALUE_DEFAULT, 0),
            ]
        );
    }

    /**
     * Create quiz group override
     *
     * @param int $quizid
     * @param int $groupid
     * @param int $timeopen
     * @param int $timeclose
     * @param int $timelimit
     * @param int $attempts
     * @return array returns status true in case of quiz group override was created.
     */
    public static function execute(int $quizid, int $groupid, int $timeopen, int $timeclose, int $timelimit, int $attempts): array {
        global $DB;
        $params = self::validate_parameters(self::execute_parameters(), [
            'quizid' => $quizid,
            'groupid' => $groupid,
            'timeopen' => $timeopen,
            'timeclose' => $timeclose,
            'timelimit' => $timelimit,
            'attempts' => $attempts,
        ]);

        // Request and permission validation.
        $cm = get_coursemodule_from_instance('quiz', $params['quizid'], 0, false, MUST_EXIST);
        $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);

        $context = \context_module::instance($cm->id);

        // Add or edit an override.
        require_capability('mod/quiz:manageoverrides', $context);

        // Validate context.
        self::validate_context($context);

        // Check group.
        $groups = groups_get_activity_allowed_groups($cm);
        if (!in_array($params['groupid'], array_keys($groups))) {
            throw new \invalid_parameter_exception('Group does not exist or not acessible');
        }

        // Check existing override.
        $conditions = [
            'quiz' => $quiz->id,
            'groupid' => $params['groupid'],
        ];
        if ($existingoverride = $DB->get_record('quiz_overrides', $conditions)) {
            throw new \invalid_parameter_exception('Group override for this quiz and group exists, its id: ' . $existingoverride->id);
        }

        // Check time.
        if ($params['timeopen'] !== 0 && $params['timeclose'] !== 0 && $params['timeclose'] < $params['timeopen'] ) {
            throw new \invalid_parameter_exception(get_string('closebeforeopen', 'quiz'));
        }
        if ($params['timeopen'] === 0) {
            $params['timeopen'] = null;
        }
        if ($params['timeclose'] === 0) {
            $params['timeclose'] = null;
        }

        // Check attempts.
        if ($params['attempts'] > QUIZ_MAX_ATTEMPT_OPTION) {
            throw new \invalid_parameter_exception('Invalid attempts value, it should not exceed ' . QUIZ_MAX_ATTEMPT_OPTION);
        }
        if ($params['attempts'] === 0) {
            $params['attempts'] = null;
        }

        // Check timelimit.
        if ($params['timelimit'] === 0) {
            $params['timelimit'] = null;
        }

        // Check if we change anything.
        if (is_null($params['timeopen']) && is_null($params['timeclose']) &&
                is_null($params['attempts']) && is_null($params['timelimit'])) {
            throw new \invalid_parameter_exception(get_string('nooverridedata', 'quiz'));
        }

        // Create override.
        $params['quiz'] = $params['quizid'];
        $overrideid = $DB->insert_record('quiz_overrides', (object) $params);

        // Trigger event.
        $eventparams = [
            'objectid' => $overrideid,
            'context' => $context,
            'other' => [
                'quizid' => $quiz->id,
                'groupid' => $params['groupid'],
            ]
        ];
        $event = \mod_quiz\event\group_override_created::create($eventparams);
        $event->trigger();

        // Priorities may have shifted, so we need to update attempts and all of
        // the calendar events for group overrides.
        quiz_update_open_attempts(['quizid'=>$quiz->id]);
        quiz_update_events($quiz);

        return ['status' => true, 'overrideid' => $overrideid];
    }

    /**
     * Create quiz group override return structure
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Success status'),
            'overrideid' => new external_value(PARAM_INT, 'Created quiz group override id'),
        ]);
    }
}