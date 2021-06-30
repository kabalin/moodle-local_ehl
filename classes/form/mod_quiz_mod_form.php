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

namespace local_ehl\form;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->dirroot/mod/quiz/mod_form.php");

/**
 * mod_quiz_mod_form local proxy class to emulate submission.
 *
 * We could avoid having this class if it was possible to pass $ajaxformdata
 * to the moodleform constructor, unfortunately this is not implemented in
 * moodleform_mod child class that is used for mod forms.
 *
 * @package   local_ehl
 * @copyright 2021 Ecole hôtelière de Lausanne {@link https://www.ehl.edu/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_mod_form extends \mod_quiz_mod_form {

    public function __construct($current, $section, $cm, $course) {
        $this->_modname = 'quiz';
        parent::__construct($current, $section, $cm, $course);
    }

    /**
     * Setting data for form.
     *
     * @param mixed $default_values object or array of default values
     */
    function set_data($default_values) {
        if (is_object($default_values)) {
            $default_values = (array)$default_values;
        }
        // Set element defaults as normal.
        parent::set_data($default_values);

        // Emulate form submission.
        $this->data_preprocessing($default_values);
        $this->_form->updateSubmission($default_values, []);
    }

    /**
     * Exposes $this->_form object (usually called $mform)
     *
     * @return \MoodleQuickForm
     */
    public function get_quick_form(): \MoodleQuickForm {
        return $this->_form;
    }
}
