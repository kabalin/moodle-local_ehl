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

namespace local_ehl\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for local_ehl.
 *
 * @package   local_ehl
 * @category  event
 * @copyright 2022 Ecole hôtelière de Lausanne {@link https://www.ehl.edu/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class observer {

    /**
     * Course restored event callback.
     *
     * @param  \core\event\course_restored $event
     * @return void
     */
    public static function course_restored(\core\event\course_restored $event) {
        global $DB, $CFG;
        if ($restore = $DB->get_record('local_ehl_restore', ['course' => $event->courseid])) {
            // Clean up files.
            $path = $CFG->tempdir . DIRECTORY_SEPARATOR . "backup" . DIRECTORY_SEPARATOR . $restore->backupdir;
            fulldelete($path);

            if ($restore->callbackurl) {
                // Execute callback.
                $curl = new \curl();

                // Set timeout.
                if ($timeout = get_config('local_ehl', 'callbacktimeout')) {
                    $curl->setopt(array('CURLOPT_TIMEOUT' => $timeout, 'CURLOPT_CONNECTTIMEOUT' => $timeout));
                }

                // Add auth header.
                $header = get_config('local_ehl', 'callbackapiheader');
                $key = get_config('local_ehl', 'callbackapikey');
                if ($header && $key) {
                    $curl->setHeader("{$header}: {$key}");
                }

                // Set content type if we have JSON encoded payload.
                if ($restore->callbackpayload) {
                    $curl->setHeader(['Cache-Control: no-cache', 'Content-Type: application/json']);
                }

                // Execute POST request.
                $restore->timeexecuted = time();
                $response = $curl->post($restore->callbackurl, $restore->callbackpayload);

                // Log errors.
                $info = $curl->get_info();
                if ($curlerrno = $curl->get_errno()) {
                    $restore->failurereason = "Unexpected response, CURL error number: $curlerrno Error: {$curl->error}";
                } else if ((int)$info['http_code'] >= 400) {
                    $restore->failurereason = "Unexpected response, HTTP code: " . $info['http_code'] . " Response: $response";
                }
                if ($restore->failurereason) {
                    debugging($restore->failurereason);
                    $DB->update_record('local_ehl_restore', $restore);
                    return;
                }
            }
            // Clean up record.
            $DB->delete_records('local_ehl_restore', ['id' => $restore->id]);
        }
    }
}
