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
 * Upgrade routines
 *
 * @package   local_ehl
 * @copyright 2022 Ecole hôtelière de Lausanne {@link https://www.ehl.edu/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade task
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool always true
 */
function xmldb_local_ehl_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2022011203) {

        // Define table local_ehl_restore to be created.
        $table = new xmldb_table('local_ehl_restore');

        // Adding fields to table local_ehl_restore.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('backupdir', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('callbackurl', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeexecuted', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('failurereason', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table local_ehl_restore.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_ehl_restore.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2022011203, 'local', 'ehl');
    }

    if ($oldversion < 2022012400) {

        // Define table local_ehl_restore to be created.
        $table = new xmldb_table('local_ehl_restore');

        $field = new xmldb_field('callbackpayload', XMLDB_TYPE_TEXT, null, null, null, null, null, 'callbackurl');

        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2022012400, 'local', 'ehl');
    }

    if ($oldversion < 2023032602) {

        // Define table local_ehl_restore to be created.
        $table = new xmldb_table('local_ehl_restore');

        $field = new xmldb_field('restoreid', XMLDB_TYPE_TEXT, null, null, null, null, null, 'restoreid');

        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('failed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2023032602, 'local', 'ehl');
    }

    return true;
}
