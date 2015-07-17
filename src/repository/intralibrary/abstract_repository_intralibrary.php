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
 * Abstract repository intralibrary
 *
 * This class is used all across the subplugins,
 * because it contains a number of helper functions
 *
 * @package    repository_intralibrary
 * @category   repository
 * @copyright  2015 Intrallect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use IntraLibrary\Service\RESTRequest;

// initialise the intralibrary moodle plugin
require_once __DIR__ . '/init.php';

/**
 * Moodle 2 Plugin Repository Plugin for IntraLibrary
 *
 * NOTE: abstract_repository_intralibrary::initialise();
 * gets called at the bottom of this file
 */
abstract class abstract_repository_intralibrary extends repository {

    protected static $FILEPICKER_MODULE = array(
            'name' => 'repository_intralibrary_filepicker',
            'fullpath' => '/repository/intralibrary/filepicker.js',
            'requires' => array(
                    'core_filepicker'
            )
    );

    /**
     *
     * @var IntraLibraryTaxonomyData
     */
    protected static $_TPROVIDER;

    /**
     * Accessible via self::factory()
     *
     * @var repository_intralibrary\factory
     */
    private static $_FACTORY;

    /**
     * Accessible via self::data()
     *
     * @var repository_intralibrary_data_service
     */
    private static $_DATA_SERVICE;

    /**
     * @var repository_intralibrary\logger
     */
    protected static $_LOGGER;

    /**
     * Get the configured category source
     */
    protected static function _get_category_source() {
        static $source;
        if (!isset($source)) {
            $source = get_config('intralibrary', 'category');
        }
        return $source;
    }

    /**
     * Get the plugin name
     */
    protected static function _name() {
        return str_replace('repository_', '', get_called_class());
    }

    /**
     * Initialise the class
     */
    public static function initialise() {
        global $PAGE;

        self::$_TPROVIDER = new \IntraLibrary\LibraryObject\TaxonomyData();
        self::$_LOGGER = new repository_intralibrary\logger();

        if ($PAGE) {
            $PAGE->requires->js('/repository/intralibrary/vendors/underscore-min.js');
            $PAGE->requires->js_module(self::$FILEPICKER_MODULE);
            $PAGE->requires->js_init_call('M.repository_intralibrary_filepicker.hook_into_filepicker', array(), FALSE,
                    self::$FILEPICKER_MODULE);
        }
    }

    /**
     * @return repository_intralibrary\factory
     */
    public static function factory() {
        if (!isset(self::$_FACTORY)) {
            self::$_FACTORY = new repository_intralibrary\factory();
        }
        return self::$_FACTORY;
    }

    /**
     * @return repository_intralibrary\auth
     */
    public static function auth() {
        return self::factory()->get_auth();
    }

    /**
     * @return repository_intralibrary_data_service
     */
    public static function data_service() {
        if (!isset(self::$_DATA_SERVICE)) {
            require_once __DIR__ . '/../intralibrary/helpers/data_service.php';
            self::$_DATA_SERVICE = new repository_intralibrary_data_service(
                    self::$_TPROVIDER,
                    self::_get_category_source(),
                    self::$_LOGGER
            );
        }
        return self::$_DATA_SERVICE;
    }

    /**
     * @return true if plugin is configured with shared authentication
     */
    public static function is_shared_auth() {
        static $is_shared_auth;

        if (!isset($is_shared_auth)) {
            $auth = self::auth();
            $is_shared_auth = $auth->is(INTRALIBRARY_AUTH_SHARED);
        }

        return $is_shared_auth;
    }

    /**
     * 'get_string()' wrapper
     * using 'repository_intralibrary' as the component
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @param string $identifier
     * @param object $a
     */
    public static function get_string($identifier, $a = NULL) {
        return get_string($identifier, get_called_class(), $a);
    }

    /**
     * Constructor
     *
     * @param int $repositoryid
     * @param stdClass $context
     * @param array $options
     * @param int $readonly
     */
    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array(), $readonly = 0) {
        static $loaded = array();

        // parent constructor
        parent::__construct($repositoryid, $context, $options, $readonly);

        if (empty($loaded[$repositoryid])) {
            global $PAGE;

            // only process this on non-ajax scripts, and only once a hostname has been configured
            $config = repository_intralibrary_config();
            if ($PAGE && !empty($config->hostname) && !(defined('AJAX_SCRIPT') && AJAX_SCRIPT)) {
                $PAGE->requires->js_module(
                        array(
                                'name' => get_called_class(),
                                'fullpath' => '/repository/' . static::_name() . '/module.js',
                                'requires' => $this->_get_js_requires()
                        ));

                $this->_prepare_for_repository($PAGE, $repositoryid);

                $loaded[$repositoryid] = TRUE;
            }
        }
    }

    /**
     * Get all JS dependencies (module names)
     *
     * @return array
     */
    protected function _get_js_requires() {
        return array(
                self::$FILEPICKER_MODULE['name']
        );
    }

    /**
     * Will get called once per repositoryid whenver a full page
     * is being requested.
     *
     * @param integer $repositoryid
     */
    protected function _prepare_for_repository(moodle_page $page, $repositoryid) {
        $page->requires->js_init_call('M.' . get_called_class() . '.init',
                array(
                        $repositoryid,
                        $this->name
                ));
    }

    /**
     * Get response headers for a given URL request
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     *
     * @param string $url
     * @return string
     */
    protected function _get_response_headers($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_NOBODY, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $headers = curl_exec($ch);
        curl_close($ch);

        return $headers;
    }

    /**
     * Resolve a redirected URL
     *
     * @param string $url
     * @return string
     */
    protected function _get_redirected_url($url) {
        $headers = $this->_get_response_headers($url);

        if (preg_match('#Location: (.*)#', $headers, $match)) {
            return trim($match[1]);
        }

        return $url;
    }

    /**
     * Get the filename for a url
     *
     * @param string $url
     * @return string
     */
    protected function _get_repository_filename($url) {
        $headers = $this->_get_response_headers($url);

        // this is meant to be a "file download" request
        if (preg_match('#Content\-Disposition\: attachment; filename=(.*)#', $headers, $match)) {
            return trim($match[1]);
        }

        // if there was a redirect, use that
        if (preg_match('#Location: (.*)#', $headers, $match)) {
            $url = trim($match[1]);
        }

        return basename(parse_url($url, PHP_URL_PATH));
    }

    /**
     * Get the filename from a resource id
     * @param unknown $id
     */
    protected function _get_repository_filename_from_id($id) {
        $req = new RESTRequest();
        $data = $req->get("LearningObject/show/$id")->getData();

        return isset($data['learningObject']) && isset($data['learningObject']['fileName']) ?
            $data['learningObject']['fileName'] :
            null;
    }

    /**
     * Get the environment in which this repository is being invoked
     */
    protected function _get_env() {
        return optional_param('env', isset($this->env) ? $this->env : '', PARAM_RAW);
    }

    /**
     */
    protected function _get_accepted_types() {
        return optional_param_array('accepted_types', '*', PARAM_RAW);
    }

    /**
     * Redirect the browser to the download location
     *
     * (non-PHPdoc)
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.ExitExpression)
     *
     * @see repository::send_file()
     */
    public function send_file($storedfile, $lifetime = 86400, $filter = 0, $forcedownload = FALSE, array $options = NULL) {
        $array = @unserialize($storedfile->get_source());
        if (!empty($array['send_url'])) {
            header('Location: ' . $array['send_url']);
            exit();
        } else if (!empty($array['url'])) {
            if (!self::is_shared_auth()) {
                $array['url'] = $this->_get_redirected_url($array['url']);
            }
            header('Location: ' . $array['url']);
            exit();
        } else {
            throw new Exception('Unable to send this file -- missing url data');
        }
    }

    /**
     * Don't sync files as they will be retrieved every time anyways
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function sync_individual_file(stored_file $storedfile) {
        return FALSE;
    }
}

// Initialise the class
abstract_repository_intralibrary::initialise();
