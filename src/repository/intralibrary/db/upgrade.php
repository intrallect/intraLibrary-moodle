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
 * Upgrade Script.
 *
 * @package    repository_intralibrary
 * @category   repository
 * @copyright  2015 Intrallect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


function xmldb_repository_intralibrary_upgrade($oldversion) {

    // Kaltura url config moved to main plugin
    if ($oldversion < 2015031102) {
        $kalturaUrl = get_config('intralibrary_upload', 'kaltura_url');
        if ($kalturaUrl) {
            set_config('kaltura_url', $kalturaUrl, 'intralibrary');
        }
    }

    // Optional search fields, enable all by default
    if ($oldversion < 2015033002) {
        $values = array('my_resources', 'collection', 'file_type', 'star_rating', 'category');
        foreach ($values as $value) {
            $settingsKey = 'optional_field_' . $value;
            if (!get_config('intralibrary', $settingsKey)) {
                set_config($settingsKey, 1, 'intralibrary');
            }
        }
    }

    return TRUE;
}
