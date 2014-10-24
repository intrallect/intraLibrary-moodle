<?php

namespace repository_intralibrary\event;

defined('MOODLE_INTERNAL') || die();

/**
 *
 * Logs available at System Administratoin -> Reports -> Logs
 *
 * This event logs the IntraLibrary deposits from Moodle.
 *
 * Created as a requirement for moving to Moodle 2.6
 * References:
 * http://docs.moodle.org/dev/Event_2
 * http://docs.moodle.org/dev/Migrating_logging_calls_in_plugins
 * @author bence
 *
 */
class resource_uploaded extends \core\event\base {

    /**
     * These array elements are mandatory, must be the same for all instances of the class
     */
    protected function init() {
        $this->data['crud'] = 'c'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Provide the localised name for the admin log screen, should be
     * the same for all instances
     *
     * @return string
     */
    public static function get_name() {
        return get_string('upload_event_name', 'repository_intralibrary');
    }

    /**
     * The description dynamically generated for each event instance based on the "other" data
     * field, it is shown on the admin log view.
     * In this case, it reads the title of the resource from the "other" data variable's title field.
     *
     * @return string
     */
    public function get_description() {
        return '"'. $this->other['title'] .'" has been uploaded to IntraLibrary successfully.';
    }

    /**
     * This function provides the intralibrary url of the resource.
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url($this->other['url']);
    }
}