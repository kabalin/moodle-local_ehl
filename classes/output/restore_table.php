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

        $this->set_sql('r.*, c.shortname', '{local_ehl_restore} r JOIN {course} c ON (r.course = c.id)', "r.callbackurl != ''", []);
        $this->define_columns([
            'course',
            'timecreated',
            'timeexecuted',
            'failurereason'
        ]);
        $this->define_headers([
            get_string('course'),
            get_string('timecreated', 'local_ehl'),
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
        $courseurl = new \moodle_url('/course/view.php', ['id' => $row->course]);
        return \html_writer::link($courseurl, format_string($row->shortname,
                true, \context_course::instance($row->course)));
    }

    /**
     * Generate the time created column.
     *
     * @param \stdClass $row.
     * @return string
     */
    public function col_timecreated($row) {
        return userdate($row->timecreated, get_string('strftimedatetimeshort'));
    }

    /**
     * Generate the time executed column.
     *
     * @param \stdClass $row.
     * @return string
     */
    public function col_timeexecuted($row) {
        if ($row->timeexecuted) {
            return userdate($row->timeexecuted, get_string('strftimedatetimeshort'));
        }
        return get_string('restoreinprogress', 'local_ehl');
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
