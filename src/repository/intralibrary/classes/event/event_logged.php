<?php

namespace repository_intralibrary\event;

defined('MOODLE_INTERNAL') || die();

/**
 *
 * Logs available at System Administratoin -> Reports -> Logs
 *
 * This event is used to log any special event, including exceptions,
 * errors, configuration changes.
 *
 * Created as a requirement for moving to Moodle 2.6
 * References:
 * http://docs.moodle.org/dev/Event_2
 * http://docs.moodle.org/dev/Migrating_logging_calls_in_plugins
 * @author bence
 *
 */
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