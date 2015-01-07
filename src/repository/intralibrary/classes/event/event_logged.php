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
 *
 * General log entry type of the IntraLibrary plugin family
 *
 * This event is used to log any special event, including exceptions,
 * errors, configuration changes.
 *
 * @package    repository_intralibrary
 * @category   repository
 * @copyright  2015 Intrallect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
namespace repository_intralibrary\event;

defined('MOODLE_INTERNAL') || die();

class event_logged extends \core\event\base {

    /**
     * These array elements are mandatory, must be the same for all instances of the class
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Provide the localised name for the admin log screen, should be
     * the same for all instances
     *
     * @return string
     */
    public static function get_name() {
        return get_string('general_event_name', 'repository_intralibrary');
    }

    /**
     * The description dynamically generated for each event instance based on the "other" data
     * field, it is also shown on the admin log view
     *
     * @return string
     */
    public function get_description() {
        return $this->other['action'] . ": " . $this->other['info'];
    }
}