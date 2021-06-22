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

/**
 * Copy of admin/cli/backup.php adjusted for EHL requirements.
 *
 * @package    core
 * @subpackage cli
 * @copyright  2013 Lancaster University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', 1);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

// Now get cli options.
[$options, $unrecognized] = cli_get_params([
    'courseid' => false,
    'courseshortname' => '',
    'courseidnumber' => '',
    'destination' => '',
    'nousers' => false,
    'help' => false,
    ], ['h' => 'help', 'nu' => 'nousers']);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || !($options['courseid'] || $options['courseshortname'] || $options['courseidnumber'])) {
    $help = <<<EOL
Perform backup of the given course.

Options:
--courseid=INTEGER          Course ID for backup.
--courseshortname=STRING    Course shortname for backup.
--courseidnumber=STRING     Course idnumber for backup.
--destination=STRING        Path where to store backup file. If not set the backup
                            will be stored within the course backup file area.
-nu, --nousers              Do not include user enrolments.
-h, --help                  Print out this help.

Example:
\$sudo -u www-data /usr/bin/php local/ehl/cli/backup.php --courseid=2 --destination=/moodle/backup/\n
EOL;

    echo $help;
    exit(0);
}

if (!$admin = get_admin()) {
    cli_error(get_string('noadmins', 'error'));
}

// Do we need to store backup somewhere else?
$dir = rtrim($options['destination'], '/');
if (!empty($dir)) {
    if (!file_exists($dir) || !is_dir($dir) || !is_writable($dir)) {
        mtrace("Destination directory does not exists or not writable.");
        die;
    }
}

// Check that the course exists.
if ($options['courseid']) {
    $course = $DB->get_record('course', ['id' => $options['courseid']], '*');
} else if ($options['courseshortname']) {
    $course = $DB->get_record('course', ['shortname' => $options['courseshortname']], '*');
} else if ($options['courseidnumber']) {
    $course = $DB->get_record('course', ['idnumber' => $options['courseidnumber']], '*');
}

if (!$course) {
    cli_error(get_string('invalidcourse', 'error'));
}

cli_heading(get_string('backupcourse', 'backup', $course->shortname));
$bc = new backup_controller(backup::TYPE_1COURSE, $course->id, backup::FORMAT_MOODLE,
                            backup::INTERACTIVE_YES, backup::MODE_GENERAL, $admin->id);

// Set users including preference.
if ($options['nousers']) {
    cli_writeln("Skipping user enrolments");
    $bc->get_plan()->get_setting('users')->set_value(0);
}

// Set the default filename.
$format = $bc->get_format();
$type = $bc->get_type();
$id = $bc->get_id();
$users = $bc->get_plan()->get_setting('users')->get_value();
$anonymised = $bc->get_plan()->get_setting('anonymize')->get_value();
$filename = backup_plan_dbops::get_default_backup_filename($format, $type, $id, $users, $anonymised);
$bc->get_plan()->get_setting('filename')->set_value($filename);

// Execution.
$bc->finish_ui();
$bc->execute_plan();
$results = $bc->get_results();
$file = $results['backup_destination']; // May be empty if file already moved to target location.

// Do we need to store backup somewhere else?
if (!empty($dir)) {
    if ($file) {
        cli_writeln("Writing " . $dir.'/'.$filename);
        if ($file->copy_content_to($dir.'/'.$filename)) {
            $file->delete();
            cli_writeln("Backup completed.");
        } else {
            cli_writeln("Destination directory does not exist or is not writable. Leaving the backup in the course backup file area.");
        }
    }
} else {
    cli_writeln("Backup completed, the new file is listed in the backup area of the given course");
}
$bc->destroy();
exit(0);