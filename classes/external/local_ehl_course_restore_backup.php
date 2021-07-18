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

require_once($CFG->dirroot . "/backup/util/includes/restore_includes.php");

/**
 * local_ehl external class local_ehl_course_restore_backup
 *
 * @package   local_ehl
 * @copyright 2021 Ecole hôtelière de Lausanne {@link https://www.ehl.edu/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_ehl_course_restore_backup extends external_api {

    /**
     * Course restore parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'fileitemid' => new external_value(PARAM_INT, 'File item id', VALUE_REQUIRED),
                'categoryid' => new external_value(PARAM_INT, 'Category id to restore course into', VALUE_DEFAULT, 0),
                'courseid' => new external_value(PARAM_INT, 'Course id to restore into with content overwriting', VALUE_DEFAULT, 0),
                'courseidnumber' => new external_value(PARAM_RAW, 'Course idnumber to restore into with content overwriting', VALUE_DEFAULT, ''),
                'courseshortname' => new external_value(PARAM_RAW, 'Course shortname to restore into with content overwriting', VALUE_DEFAULT, ''),
            ]
        );
    }

    /**
     * Course restore
     *
     * @param int $courseid
     * @param string $courseidnumber
     * @param string $courseshortname
     * @param bool $nousers
     * @return array
     */
    public static function execute(int $fileitemid, int $categoryid, int $courseid, string $courseidnumber, string $courseshortname): array {
        global $DB, $USER, $CFG;
        $params = self::validate_parameters(self::execute_parameters(), [
            'fileitemid' => $fileitemid,
            'categoryid' => $categoryid,
            'courseid' => $courseid,
            'courseidnumber' => $courseidnumber,
            'courseshortname' => $courseshortname,
        ]);

        // Check course or category exists.
        $target = \backup::TARGET_EXISTING_DELETING;
        if ($params['courseid']) {
            $courseid = $DB->get_field('course', 'id', ['id' => $params['courseid']]);
        } else if ($params['courseshortname']) {
            $courseid = $DB->get_field('course', 'id', ['shortname' => $params['courseshortname']]);
        } else if ($params['courseidnumber']) {
            $courseid = $DB->get_field('course', 'id', ['idnumber' => $params['courseidnumber']]);
        } else if ($params['categoryid']) {
            if (!$categoryid = $DB->get_field('course_categories', 'id', ['id' => $params['categoryid']])) {
                throw new \invalid_parameter_exception(print_error('invalidcategoryid'));
            }
            // Validate category context.
            $context = \context_coursecat::instance($categoryid);
            self::validate_context($context);
            require_capability('moodle/restore:restorecourse', $context);

            [$fullname, $shortname] = \restore_dbops::calculate_course_names(0, get_string('restoringcourse', 'backup'),
                get_string('restoringcourseshortname', 'backup'));
            $courseid = \restore_dbops::create_new_course($fullname, $shortname, $categoryid);
            $target = \backup::TARGET_NEW_COURSE;
        }

        if ($courseid) {
            // Validate course context.
            $context = \context_course::instance($courseid);
            self::validate_context($context);
            require_capability('moodle/restore:restorecourse', $context);
        } else {
            throw new \invalid_parameter_exception(print_error('invalidcourse'));
        }

        $usercontext = \context_user::instance($USER->id);
        $draftfiles = get_file_storage()->get_area_files($usercontext->id, 'user', 'draft', $params['fileitemid'], 'id');
        if (empty($draftfiles)) {
            throw new \invalid_parameter_exception('File is not found.');
        }

        // Unpack file.
        $backupdir = "restore_" . uniqid();
        $path = $CFG->tempdir . DIRECTORY_SEPARATOR . "backup" . DIRECTORY_SEPARATOR . $backupdir;
        $fp = get_file_packer('application/vnd.moodle.backup');
        $fp->extract_to_pathname(reset($draftfiles), $path);

        // Restore.
        try {
            $rc = new \restore_controller($backupdir, $courseid, \backup::INTERACTIVE_NO,
                \backup::MODE_GENERAL, $USER->id, $target);
            $rc->execute_precheck();
            $rc->execute_plan();
            $rc->destroy();
            fulldelete($path);
        } catch (\Exception $e) {
            fulldelete($path);
            throw new \moodle_exception('generalexceptionmessage', 'error', '', $e->getMessage());
        }

        return ['status' => true];
    }

    /**
     * Return for restoring backup file.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Success status'),
        ]);
    }
}