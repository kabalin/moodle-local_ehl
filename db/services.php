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
 * local_ehl external functions and service definitions.
 *
 * @package   local_ehl
 * @copyright 2021 Ecole hôtelière de Lausanne {@link https://www.ehl.edu/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

$functions = [
    'local_ehl_mod_quiz_update_timing_settings' => [
        'classname' => \local_ehl\external\mod_quiz_update_timing_settings::class,
        'methodname' => 'execute',
        'description' => 'Update quiz timing settings',
        'type' => 'write',
        'capabilities' => 'moodle/course:manageactivities',
    ],
    'local_ehl_mod_quiz_update_grading_settings' => [
        'classname' => \local_ehl\external\mod_quiz_update_grading_settings::class,
        'methodname' => 'execute',
        'description' => 'Update quiz grading settings',
        'type' => 'write',
        'capabilities' => 'moodle/course:manageactivities',
    ],
    'local_ehl_mod_quiz_update_review_settings' => [
        'classname' => \local_ehl\external\mod_quiz_update_review_settings::class,
        'methodname' => 'execute',
        'description' => 'Update quiz review settings',
        'type' => 'write',
        'capabilities' => 'moodle/course:manageactivities',
    ],
    'local_ehl_mod_quiz_create_group_override' => [
        'classname' => \local_ehl\external\mod_quiz_create_group_override::class,
        'methodname' => 'execute',
        'description' => 'Create group override',
        'type' => 'write',
        'capabilities' => 'mod/quiz:manageoverrides',
    ],
    'local_ehl_mod_quiz_update_group_override' => [
        'classname' => \local_ehl\external\mod_quiz_update_group_override::class,
        'methodname' => 'execute',
        'description' => 'Update group override',
        'type' => 'write',
        'capabilities' => 'mod/quiz:manageoverrides',
    ]
];
