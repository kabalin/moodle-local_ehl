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

require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

/**
 * local_ehl external class local_ehl_course_backup
 *
 * @package   local_ehl
 * @copyright 2021 Ecole hôtelière de Lausanne {@link https://www.ehl.edu/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ehl_course_backup extends external_api {

    /**
     * Course backup parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'courseid' => new external_value(PARAM_INT, 'Course id', VALUE_DEFAULT, 0),
                'courseidnumber' => new external_value(PARAM_RAW, 'Course idnumber', VALUE_DEFAULT, ''),
                'courseshortname' => new external_value(PARAM_RAW, 'Course shortname', VALUE_DEFAULT, ''),
                'nousers' => new external_value(PARAM_BOOL, 'Don\'t include user enrolments', VALUE_DEFAULT, false),
            ]
        );
    }

    /**
     * Course backup
     *
     * @param int $courseid
     * @param string $courseidnumber
     * @param string $courseshortname
     * @param bool $nousers
     * @return array
     */
    public static function execute(int $courseid, string $courseidnumber, string $courseshortname, bool $nousers): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'courseidnumber' => $courseidnumber,
            'courseshortname' => $courseshortname,
            'nousers' => $nousers,
        ]);

        // Check that the course exists.
        if ($params['courseid']) {
            $course = $DB->get_record('course', ['id' => $params['courseid']], '*');
        } else if ($params['courseshortname']) {
            $course = $DB->get_record('course', ['shortname' => $params['courseshortname']], '*');
        } else if ($params['courseidnumber']) {
            $course = $DB->get_record('course', ['idnumber' => $params['courseidnumber']], '*');
        }

        if (!$course) {
            throw new \invalid_parameter_exception(print_error('invalidcourse'));
        }

        // Validate context.
        $context = \context_course::instance($course->id);
        self::validate_context($context);

        // Permission validation.
        require_capability('moodle/backup:backupcourse', $context);

        $bc = new \backup_controller(\backup::TYPE_1COURSE, $course->id, \backup::FORMAT_MOODLE,
                            \backup::INTERACTIVE_YES, \backup::MODE_GENERAL, $USER->id);

        // Set users including preference.
        if ($params['nousers']) {
            $bc->get_plan()->get_setting('users')->set_value(0);
        }

        // Set the default filename.
        $format = $bc->get_format();
        $type = $bc->get_type();
        $id = $bc->get_id();
        $users = $bc->get_plan()->get_setting('users')->get_value();
        $anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();
        $filename = \backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised);
        $bc->get_plan()->get_setting('filename')->set_value($filename);

        // Execution.
        $bc->finish_ui();
        $bc->execute_plan();
        $result = $bc->get_results();
        $file = $result['backup_destination'];
        $filesize = display_size($file->get_filesize());
        $fileurl = \moodle_url::make_webservice_pluginfile_url($file->get_contextid(), $file->get_component(),
            $file->get_filearea(), null, $file->get_filepath(), $file->get_filename());
        $bc->destroy();

        return ['filesize' => $filesize, 'fileurl' => $fileurl->out(false)];
    }

    /**
     * Return for getting backup file details.
     *
     * @return external_files
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
               'filesize'   => new external_value(PARAM_TEXT, 'Backup file size'),
               'fileurl' => new external_value(PARAM_URL, 'Backup file URL'),
        ], 'Backup file data.');
    }
}