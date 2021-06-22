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
 * Copy of admin/cli/restore_backup.php adjusted for EHL requirements.
 *
 * @package    core
 * @subpackage cli
 * @copyright  2020 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', 1);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . "/backup/util/includes/restore_includes.php");

list($options, $unrecognized) = cli_get_params([
    'file' => '',
    'categoryid' => '',
    'courseid' => '',
    'courseshortname' => '',
    'courseidnumber' => '',
    'showdebugging' => false,
    'help' => false,
    'force' => false,
], [
    'f' => 'file',
    'c' => 'categoryid',
    's' => 'showdebugging',
    'h' => 'help',
]);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || !($options['file']) || !($options['categoryid'] ||
        $options['courseid'] || $options['courseshortname'] || $options['courseidnumber'])) {
    $help = <<<EOL
Restore backup into provided category.

Options:
-f, --file=STRING           Path to the backup file.
-c, --categoryid=INT        ID of the category to restore to.
--courseid=INTEGER          Course ID to restore into.
--courseshortname=STRING    Course shortname to restore into.
--courseidnumber=STRING     Course idnumber to restore into.
-s, --showdebugging         Show developer level debugging information
--force                     Don't prompt for overwriting confirmation
-h, --help                  Print out this help.

Example:
\$sudo -u www-data /usr/bin/php local/ehl/cli/restore_backup.php --file=/path/to/backup/file.mbz --categoryid=1\n
EOL;

    echo $help;
    exit(0);
}

if ($options['showdebugging']) {
    set_debugging(DEBUG_DEVELOPER, true);
}

if (!$admin = get_admin()) {
    cli_error(get_string('noadmins', 'error'));
}

if (!file_exists($options['file'])) {
    cli_error(get_string('filenotfound', 'error'));
}

// Check course or category exists.
$target = backup::TARGET_EXISTING_DELETING;
if ($options['courseid']) {
    $courseid = $DB->get_field('course', 'id', ['id' => $options['courseid']]);
} else if ($options['courseshortname']) {
    $courseid = $DB->get_field('course', 'id', ['shortname' => $options['courseshortname']]);
} else if ($options['courseidnumber']) {
    $courseid = $DB->get_field('course', 'id', ['idnumber' => $options['courseidnumber']]);
} else if ($options['categoryid']) {
    if (!$categoryid = $DB->get_field('course_categories', 'id', ['id' => $options['categoryid']])) {
        cli_error(get_string('invalidcategoryid', 'error'));
    }
    [$fullname, $shortname] = restore_dbops::calculate_course_names(0, get_string('restoringcourse', 'backup'),
        get_string('restoringcourseshortname', 'backup'));
    $courseid = restore_dbops::create_new_course($fullname, $shortname, $categoryid);
    $target = backup::TARGET_NEW_COURSE;
}

if (!$courseid) {
    cli_error(get_string('invalidcourse', 'error'));
}

if ($target === backup::TARGET_EXISTING_DELETING && !$options['force']) {
    $coursename = $DB->get_field('course', 'shortname', ['id' => $courseid]);
    $input = cli_input('This will overwrite content of "' . $coursename . '" course, proceed (y/n)?', 'n', ['y', 'n']);
    if ($input === 'n') {
        exit(0);
    }
}

$backupdir = "restore_" . uniqid();
$path = $CFG->tempdir . DIRECTORY_SEPARATOR . "backup" . DIRECTORY_SEPARATOR . $backupdir;

cli_heading(get_string('extractingbackupfileto', 'backup', $path));
$fp = get_file_packer('application/vnd.moodle.backup');
$fp->extract_to_pathname($options['file'], $path);

cli_writeln(get_string('preprocessingbackupfile'));
try {
    $rc = new restore_controller($backupdir, $courseid, backup::INTERACTIVE_NO,
        backup::MODE_GENERAL, $admin->id, $target);
    $rc->execute_precheck();
    $rc->execute_plan();
    $rc->destroy();
} catch (Exception $e) {
    cli_writeln(get_string('cleaningtempdata'));
    fulldelete($path);
    print_error('generalexceptionmessage', 'error', '', $e->getMessage());
}

cli_writeln(get_string('restoredcourseid', 'backup', $courseid));
exit(0);
