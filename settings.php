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
 * Plugin settings
 *
 * @package   local_ehl
 * @copyright 2022 Ecole hôtelière de Lausanne {@link https://www.ehl.edu/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// New category.
$ADMIN->add('localplugins', new admin_category('localehl',
    new lang_string('pluginname', 'local_ehl')));

// Add custom page for logs.
$logs = new admin_externalpage('restorecallbacklogs',
        new lang_string('restorecallbacklogs', 'local_ehl'),
        new moodle_url('/local/ehl/restorelogs.php'));
$ADMIN->add('localehl', $logs);

// Add general settings.
$settings = new admin_settingpage('local_ehl', new lang_string('settings'));
$ADMIN->add('localehl', $settings);

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('local_ehl/restorewebservice',
            new lang_string('restorewebservice', 'local_ehl'), ''));

    $settings->add(new admin_setting_configtext('local_ehl/callbackapiheader',
            new lang_string('callbackapiheader', 'local_ehl'),
            new lang_string('callbackapiheader_desc', 'local_ehl'), 'capikey', PARAM_TEXT, 255));

    $settings->add(new admin_setting_configtext('local_ehl/callbackapikey',
            new lang_string('callbackapikey', 'local_ehl'), '', '', PARAM_ALPHANUM));
}

// Tell core we already added the settings structure.
$settings = null;
