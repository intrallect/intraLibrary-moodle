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
 * Get the intralibrary configuration
 *
 * @return stdClass configuration object
 */
function repository_intralibrary_config() {
    static $config;

    if (empty($config)) {
        $config = get_config('intralibrary');
        if (isset($config->admin_username)) {
            $config->username = $config->admin_username;
        }
        if (isset($config->admin_password)) {
            $config->password = $config->admin_password;
        }
    }

    return $config;
}

function repository_intralibrary_do_with_user(repository_intralibrary\sso_user $sso_user, $callable) {

    if (!is_callable($callable)) {
        throw new Exception("Must pass a callable");
    }

    $config = repository_intralibrary_config();
    $username = $config->username;
    $password = $config->password;

    IntraLibrary\Configuration::set('username', $sso_user->get_username());
    IntraLibrary\Configuration::set('password', $sso_user->get_password());

    $result = $callable();

    IntraLibrary\Configuration::set('username', $username);
    IntraLibrary\Configuration::set('password', $password);

    return $result;
}

/**
 * Get a memcache object, if it is available
 *
 * @param string $config a host[:port] string
 * @return Memcache
 */
function repository_intralibrary_get_memcache($hostport = NULL) {
    static $memcache = NULL;

    if (!isset($memcache) || $hostport !== NULL) {
        if (!class_exists('Memcache')) {
            $memcache = FALSE;
        }

        if (empty($hostport)) {
            $config = repository_intralibrary_config();
            $hostport = isset($config->memcache) ? $config->memcache : NULL;
        }

        if (!empty($hostport)) {

            $memcache = new Memcache();

            $hostport = explode(':', $hostport);
            if (count($hostport) > 1) {
                $connected = @$memcache->connect($hostport[0], $hostport[1]);
            } else {
                $connected = @$memcache->connect($hostport[0]);
            }

            if (!$connected) {
                $memcache = FALSE;
            }
        }
    }

    return $memcache;

}

/**
 * Logging function
 */
function repository_intralibrary_log() {

    $config = repository_intralibrary_config();

    // check if logging is enabled
    if (!empty($config->logenabled)) {

        // convert the arguments into a loggable format
        $argv = func_get_args();
        switch (func_num_args()) {
            case 0 :
                return;
            case 1 :
                $argv = $argv[0];
                break;
        }

        $data = date('c') . ': ' . print_r($argv, TRUE) . "\n";

        file_put_contents($config->logfile, $data, FILE_APPEND);
    }
}

/**
 * Determine if the filepath is writeable
 *
 * @param string $filepath
 */
function repository_intralibrary_can_write_to_file($filepath) {
    if (is_dir($filepath)) {
        $error = "$filepath exists but is a directory, please specify a file instead.";
    } else if (file_exists($filepath)) {
        $error = !is_writable($filepath) ? "Unable to write to $filepath." : FALSE;
    } else {
        $error = !is_writable(dirname($filepath) . '/') ? "Unable to write to $filepath." : FALSE;
    }

    return empty($error) ? TRUE : $error;
}

/**
 * Generate a Kaltura Video URI for a learning object id
 * to be used by the moodle intralibrary filter plugin
 *
 * @param integer $learningObjectId
 * @return string
 */
function repository_intralibrary_generate_kaltura_uri($learningObjectId, $title = 'KalturaVideo') {
    return KALTURA_VIDEO_PREFIX . ((int) $learningObjectId) . '/' . urlencode($title);
}

/**
 * Determine whether the current request is IMS related
 * (useful for showing/hiding/filtering repositories and listings)
 */
function intralibrary_is_ims_request() {
    switch (basename($_SERVER['SCRIPT_NAME'])) {
        case 'modedit.php' :
            $add = isset($_GET['add']) ? $_GET['add'] : NULL;
            if ($add == 'scorm' || $add == 'imscp') {
                return TRUE;
            }
    }

    return FALSE;
}

/**
 * Determine whether the current request is for an ADMIN page
 */
function intralibrary_is_admin_request() {
    return strpos($_SERVER['REQUEST_URI'], '/admin/') === 0;
}

/**
 * Get extension from mimetype
 *
 * @param string $mimetype
 */
function intralibrary_get_file_extension($mimetype) {
    $mimetypes = get_mimetypes_array();
    foreach ($mimetypes as $ext => $data) {
        if ($data['type'] == $mimetype) {
            return $ext;
        }
    }

    return NULL;
}

/**
 * Get the path to moodle's root config.php
 *
 * @SuppressWarnings(PHPMD.ExitExpression)
 */
function intralibrary_get_moodle_config_path() {
    static $config_path;
    if (!isset($config_path)) {
        // is a standard moodle install
        if (is_readable(__DIR__ . '/../../../config.php')) {
            $config_path = __DIR__ . '/../../../config.php';
        } else if (is_readable(__DIR__ . '/../../../../public_html/config.php')) {
            // is a development environment (git repo structure)
            $config_path = __DIR__ . '/../../../../public_html/config.php';
        } else {
            exit("Unable to find Moodle installation");
        }
    }

    return $config_path;
}

/**
 *
 * @param string $type
 * @throws Exception
 * @return repository
 */
function intralibrary_get_repository($type) {
    $repos = repository::get_instances(array(
            'type' => $type
    ));
    if (empty($repos)) {
        throw new Exception("Unable to find an $type repository; ensure the $type repository is enabled.");
    }
    return array_pop($repos);
}

/**
 * This function determines wheter the current user has 'resource:addinstance' capability
 * at any point in the moodle installation, result is stored in session cache in order to
 * prevent additional database queries.
 *
 * @return boolean
 */
function intralibrary_isEditor() {
    try {
        $factory = abstract_repository_intralibrary::factory();
        $ssoUser = $factory->get_auth()->get_sso_user();

        if ($ssoUser) {
            return $ssoUser->is_staff();
        }
    } catch (Exception $ex) {
        return FALSE;
    }

    return TRUE;
}

/**
 * Creates a prepared log data array with the basic attirubtes
 * for Moodle Event API ::create() function
 * @return array
 */
function intralibrary_get_basic_log_data() {
    global $PAGE;

    // determine context and course id
    $context = $PAGE->context;
    $courseId = ($context->get_course_context(FALSE)) ? $context->get_course_context()->instanceid : get_site()->id;

    // put the information above in an array using the expected format for event create() function
    $logData = array(
        'courseid' => $courseId,
        'context' => $context
    );
    return $logData;
}

function intralibrary_trigger_event($className, $logData) {
    // safety check for Moodle 2.6+
    if (class_exists($className)) {
        call_user_func(array($className, 'create'), $logData)->trigger();
    }
}

function intralibrary_add_moodle_log($action, $info) {
    $logData = intralibrary_get_basic_log_data();
    $logData['other'] = array(
        'action' => $action,
        'info' => $info
    );
    intralibrary_trigger_event('\repository_intralibrary\event\event_logged', $logData);
}

function intralibrary_log_with_exception($info, $action = 'View', $extype = '') {
    if ($extype == "moodle") {
        $logData = intralibrary_get_basic_log_data();
        $logTitle;
        if (is_array($info)) {
            $info[2] = isset($info[2]) ? $info[2] : NULL;
            $logTitle = get_string($info[0], $info[1], $info[2]);
        } else {
            $logTitle = get_string($info);
        }

        $logData['other'] = array(
            'info' => "Error: " . $logTitle,
        );
        intralibrary_trigger_event('\repository_intralibrary\event\event_logged', $logData);
        throw new moodle_exception($logTitle);
    } else {
        intralibrary_add_moodle_log($action, $info);
        throw new Exception($info);
    }
}

function intralibrary_add_upload_log($title, $url) {
    // get basic log data
    $logData = intralibrary_get_basic_log_data();

    /*
     * Add specific attributes to log data array
     * to create a useful log entry
     */
    $logData['other'] = array(
        'url' => $url, // url field must contain the resource's URL
        'title' => $title // title field must contain the resource's title
    );

    // create event object with log data and trigger event
    intralibrary_trigger_event('\repository_intralibrary\event\resource_uploaded', $logData);
}
