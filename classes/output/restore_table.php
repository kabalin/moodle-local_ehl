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

namespace local_ehl\output;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

/**
 * Pending restores table class.
 *
 * @package   local_ehl
 * @copyright 2022 Ecole hôtelière de Lausanne {@link https://www.ehl.edu/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_table extends \table_sql {

    /**
     * Sets up the table.
     */
    public function __construct() {
        parent::__construct('local_ehl_restore');

        $this->set_sql('r.*, c.shortname', '{local_ehl_restore} r LEFT JOIN {course} c ON (r.course = c.id)', "1=1", []);
        $this->define_columns([
            'course',
            'timecreated',
            'restorestatus',
            'timeexecuted',
            'failurereason'
        ]);
        $this->define_headers([
            get_string('course'),
            get_string('timecreated', 'local_ehl'),
            get_string('restorestatus', 'local_ehl'),
            get_string('timeexecuted', 'local_ehl'),
            get_string('failurereason', 'local_ehl'),
        ]);
        $this->collapsible(true);
        $this->sortable(true, 'timecreated', SORT_DESC);
        $this->no_sorting('failurereason');
    }

    /**
     * Generate the course column.
     *
     * @param \stdClass $row.
     * @return string
     */
    public function col_course($row) {
        if ($row->shortname) {
            $courseurl = new \moodle_url('/course/view.php', ['id' => $row->course]);
            return \html_writer::link($courseurl, format_string($row->shortname,
                    true, \context_course::instance($row->course)));
        } else {
            return get_string('coursedoesnotexist', 'local_ehl', $row->course);
        }
    }

    /**
     * Generate the time restore task created column.
     *
     * @param \stdClass $row.
     * @return string
     */
    public function col_timecreated($row) {
        return userdate($row->timecreated, get_string('strftimedatetimeshort'));
    }

    /**
     * Generate the restore status column.
     *
     * @param \stdClass $row.
     * @return string
     */
    public function col_restorestatus($row) {
        global $DB;
        try {
            $results = \backup_controller_dbops::get_progress($row->restoreid);
        } catch (\Exception $e) {
            // Controller is missing.
            return get_string('restorestatusunknown', 'local_ehl');
        }

        $restorestatus = (int) $results['status'];
        if ($restorestatus === \backup::STATUS_AWAITING) {
            return get_string('restorestatusawaiting', 'local_ehl');
        } else if ($restorestatus === \backup::STATUS_EXECUTING) {
            return get_string('restorestatusexecuting', 'local_ehl', round((float) $results['progress']));
        } else if ($restorestatus === \backup::STATUS_FINISHED_OK) {
            return get_string('restorestatuscompleted', 'local_ehl');
        } else if ($restorestatus === \backup::STATUS_FINISHED_ERR) {
            // If we encounter restore error, course_restored event will never be triggered.
            // We have to mark failed earlier.
            $row->failed = 1;
            $DB->update_record('local_ehl_restore', $row);
            return get_string('restorestatuserror', 'local_ehl');;
        }
    }

    /**
     * Generate the time callback executed column.
     *
     * @param \stdClass $row.
     * @return string
     */
    public function col_timeexecuted($row) {
        if ($row->timeexecuted) {
            return userdate($row->timeexecuted, get_string('strftimedatetimeshort'));
        }
    }

    /**
     * Generate the failure column.
     *
     * @param \stdClass $row.
     * @return string
     */
    public function col_failurereason($row) {
        return \html_writer::tag('pre', $row->failurereason);
    }
}
