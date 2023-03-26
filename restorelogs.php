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
 * Restore logs listing page
 *
 * @package   local_ehl
 * @copyright 2022 Ecole hôtelière de Lausanne {@link https://www.ehl.edu/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('restorecallbacklogs');
$PAGE->set_context(context_system::instance());

$action = optional_param('action', '', PARAM_ALPHA);
if ($action == 'clear') {
    require_sesskey();
    $DB->delete_records_select('local_ehl_restore', "failed = 1");
    redirect($PAGE->url);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('restorecallbacklogs', 'local_ehl'));
$table = new \local_ehl\output\restore_table();
$table->define_baseurl('restorelogs.php');
$table->out(25, false);

$clearurl = new \moodle_url($PAGE->url, ['action' => 'clear']);
$clearbutton = new single_button($clearurl, get_string('clearlogs', 'local_ehl'));
$clearbutton->add_confirm_action(get_string('clearlogsconfirm', 'local_ehl'));
echo $OUTPUT->render($clearbutton);

echo $OUTPUT->footer();
