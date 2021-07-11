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
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
 * local_ehl external class mod_quiz_update_group_override
 *
 * @package   local_ehl
 * @copyright 2021 Ecole hôtelière de Lausanne {@link https://www.ehl.edu/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_update_group_override extends external_api {

    /**
     * Update quiz group override parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'overrideid' => new external_value(PARAM_INT, 'quiz group override id'),
                'timeopen' => new external_value(PARAM_INT, 'Open date in Unix time stamp', VALUE_DEFAULT, -1),
                'timeclose' => new external_value(PARAM_INT, 'Close date in Unix time stamp', VALUE_DEFAULT, -1),
                'timelimit' => new external_value(PARAM_INT, 'Specify timelimit in seconds, setting to 0 disables limit', VALUE_DEFAULT, -1),
                'attempts' => new external_value(PARAM_INT, 'Number of attempts allowed, 0 for unlimited',
                    VALUE_DEFAULT, -1),
            ]
        );
    }

    /**
     * Update quiz group override
     *
     * @param int $overrideid
     * @param int $timeopen
     * @param int $timeclose
     * @param int $timelimit
     * @param int $attempts
     * @return array returns status true in case of quiz group override was created.
     */
    public static function execute(int $overrideid, int $timeopen, int $timeclose, int $timelimit, int $attempts): array {
        global $DB;
        $params = self::validate_parameters(self::execute_parameters(), [
            'overrideid' => $overrideid,
            'timeopen' => $timeopen,
            'timeclose' => $timeclose,
            'timelimit' => $timelimit,
            'attempts' => $attempts,
        ]);

        // Request and permission validation.
        $override = $DB->get_record('quiz_overrides', array('id' => $params['overrideid']));
        $cm = get_coursemodule_from_instance('quiz', $override->quiz, 0, false, MUST_EXIST);
        $quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);

        $context = \context_module::instance($cm->id);

        // Add or edit an override.
        require_capability('mod/quiz:manageoverrides', $context);

        // Validate context.
        self::validate_context($context);

        // Check group is accessible.
        if (!groups_group_visible($override->groupid, $course, $cm)) {
            throw new \invalid_parameter_exception(print_error('invalidoverrideid', 'quiz'));
        }

        // Check time.
        if ($params['timeopen'] !== -1 && $params['timeclose'] !== -1 && $params['timeclose'] < $params['timeopen'] ) {
            throw new \invalid_parameter_exception(get_string('closebeforeopen', 'quiz'));
        }

        // Check attempts.
        if ($params['attempts'] > QUIZ_MAX_ATTEMPT_OPTION) {
            throw new \invalid_parameter_exception('Invalid attempts value, it should not exceed ' . QUIZ_MAX_ATTEMPT_OPTION);
        }

        // Customise data. We only update values that were specified.
        $changes = [];
        if ($params['timeopen'] !== -1 && $params['timeopen'] != $override->timeopen) {
            $changes['timeopen'] = "{$override->timeopen} => " . $params['timeopen'];
            $override->timeopen = $params['timeopen'];
        }
        if ($params['timeclose'] !== -1 && $params['timeclose'] != $override->timeclose) {
            $changes['timeclose'] = "{$override->timeclose} => " . $params['timeclose'];
            $override->timeclose = $params['timeclose'];
        }
        if ($params['timelimit'] !== -1 && $params['timelimit'] != $override->timelimit) {
            $changes['timelimit'] = "{$override->timelimit} => " . $params['timelimit'];
            $override->timelimit = $params['timelimit'];
        }
        if ($params['attempts'] !== -1 && $params['attempts'] != $override->attempts) {
            $changes['attempts'] = "{$override->attempts} => " . $params['attempts'];
            $override->attempts = $params['attempts'];
        }

        if (!count($changes)) {
            // Nothing to change. New values are matching current ones.
            return ['status' => false];
        }

        // Update override.
        $DB->update_record('quiz_overrides', $override);

        // Trigger event.
        $eventparams = [
            'objectid' => $override->id,
            'context' => $context,
            'other' => [
                'quizid' => $quiz->id,
                'groupid' => $override->groupid,
            ]
        ];
        $event = \mod_quiz\event\group_override_updated::create($eventparams);
        $event->trigger();

        // Priorities may have shifted, so we need to update attempts and all of
        // the calendar events for group overrides.
        quiz_update_open_attempts(['quizid'=>$quiz->id]);
        quiz_update_events($quiz);

        return ['status' => true, 'changes' => json_encode($changes)];
    }

    /**
     * Update quiz group override return structure
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