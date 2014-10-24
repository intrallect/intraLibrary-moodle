<?php
use \IntraLibrary\Configuration;

// load helpers
require_once __DIR__ . '/helpers/view.php';

// load parent class 'repository_intralibrary_upload'
require_once __DIR__ . '/abstract_repository_intralibrary.php';

/**
 * Moodle 2 Plugin Repository Plugin for IntraLibrary
 */
class repository_intralibrary extends abstract_repository_intralibrary {

    const NUM_RESULTS = 30;

    private static $SETTINGS;

    protected static function settings() {
        if (!isset(self::$SETTINGS)) {
            require_once __DIR__ . '/helpers/repository_intralibrary_settings.php';
            self::$SETTINGS = new repository_intralibrary_settings(self::data_service());
        }
        return self::$SETTINGS;
    }

    /**
     *
     * @param MoodleQuickForm $mform
     * @param array $data
     * @param array $errors
     */
    public static function type_form_validation($mform, $data, $errors) {
        return self::settings()->type_form_validation($mform, $data, $errors);
    }

    /**
     * (non-PHPdoc)
     *
     * @see repository::type_config_form()
     *
     * @param MoodleQuickForm $mform
     */
    public static function type_config_form($mform, $classname = 'repository') {
        parent::type_config_form($mform, $classname = 'repository');
        return self::settings()->type_config_form($mform);

    }

    /**
     * Options for all instances
     *
     * @return array
     */
    public static function get_type_option_names() {
        return self::settings()->get_type_option_names();
    }

    /**
     *
     * @var repository_intralibrary_view
     */
    private $view_helper;

    /**
     * Constructor
     *
     * @param int $repositoryid
     * @param stdClass $context
     * @param array $options
     * @param int $readonly
     */
    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array(), $readonly = 0) {
        parent::__construct($repositoryid, $context, $options, $readonly);

        // non staff members adding submissions
        $isAddingAssignment = $_SERVER['PHP_SELF'] == '/mod/assign/view.php' && $_GET['action'] = 'editsubmission';
        if ($isAddingAssignment && !self::auth()->is_staff()) {
            $this->disabled = TRUE;
        }

        // create a view helper
        $this->view_helper = new repository_intralibrary_view(self::data_service());
    }

    /**
     * (non-PHPdoc)
     *
     * @see abstract_repository_intralibrary::_prepare_for_repository()
     */
    protected function _prepare_for_repository(moodle_page $page, $repositoryid) {
        parent::_prepare_for_repository($page, $repositoryid);

        $addFile = basename($_SERVER['SCRIPT_NAME']) == 'modedit.php' && (isset($_GET['add']) && $_GET['add'] == 'resource');
        $page->requires->js_init_call('M.repository_intralibrary.set_reference_only', array(
                $addFile
        ));
        $page->requires->string_for_js('search_kaltura_error', 'repository_intralibrary');

    }

    /**
     * Get file listing
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param string $path
     * @param string $page
     */
    public function get_listing($path = '', $page = '') {
        throw new Exception('Direct listing is unavailable, use search instead');
    }

    /**
     * Return false to trigger login (search) form
     */
    public function check_login() {
        return FALSE;
    }

    /**
     * Login form used as a search form
     */
    public function print_login() {

        $authentication     = get_config('intralibrary', 'authentication');
        $collections        = self::data_service()->get_available_collections();
        $filetypes          = $this->_get_accepted_types();

        $form = array(
            'login' => $this->view_helper->get_search_inputs($filetypes, $authentication , $collections),
            'login_btn_label' => get_string('search'),
            'login_btn_action' => 'search'
        );

        if ($authentication == INTRALIBRARY_AUTH_SHARED) {
            $form['intralibrary_url'] = get_config('intralibrary', 'hostname');
        }

        return $form;
    }

    /**
     * Search in external repository
     *
     * @param string $text
     */
    public function search($search_text, $page = 0) {
        global $SESSION;

        if (!$page || $page == 1) {
            // only capture request parameters if no page / 1st page is requested
            // as they need to remain intact for pagination
            $SESSION->intralibrary_search_parameters = array(
                    'searchterm' => optional_param('searchterm', $search_text, PARAM_RAW),
                    'myresources' => optional_param('myresources', '', PARAM_RAW),
                    'collection' => optional_param('collection', NULL, PARAM_RAW),
                    'filetype' => optional_param('filetype', NULL, PARAM_RAW),
                    'starrating' => optional_param('starrating', NULL, PARAM_RAW),
                    'category' => optional_param('category', NULL, PARAM_RAW)
            );
        }

        try {
            $options = $SESSION->intralibrary_search_parameters;
            return $this->_get_listings($options, $page);
        } catch (Exception $ex) {
            $form = $this->print_login();
            $form['error_message'] = $ex->getMessage();
            return $form;
        }
    }

    private function _get_listings($options, $page) {
        require_once __DIR__ . '/helpers/intralibrary_list_item.php';

        if (empty($options['searchterm'])) {
            throw new Exception('Missing Search Term');
        }

        // include request-based query options
        $options['accepted_types'] = $this->_get_accepted_types();
        $options['env'] = $this->_get_env();

        $limit = self::NUM_RESULTS;
        $hostname = get_config('intralibrary', 'hostname');

        $page = $page ?: 1; // always start on page 1 (why does Moodle default to page 0?)
        $start = (($page - 1) * $limit) + 1;

        // build an intralibrary search service object
        $service = self::factory()->build_intralibrary_service($options);

        // request all records and iterate through them to build list items
        $response = $service->get_records($options, $limit, $start);

        if ($error = $response->getError()) {
            throw new Exception($error);
        }

        $listing = array(
                'logouttext' => self::get_string('search_back'),
                'nosearch' => TRUE,
                'norefresh' => TRUE,
                'list' => array(),
                'page' => $page,
                'pages' => $limit ? ceil($response->getTotalRecords() / $limit) : 0,
                'parameters' => $options
        );
        if (get_config("intralibrary", "authentication") == INTRALIBRARY_AUTH_SHARED) {
            $listing["manage"] = rtrim($hostname, '/') . '/_search.jsp?search_phrase=' . $options['searchterm'];
        }

        foreach ($response->getRecords() as $object) {
            $listing['list'][] = new intralibrary_list_item($object);
        }

        return $listing;
    }

    /**
     * (non-PHPdoc)
     *
     * @see repository::get_link()
     */
    public function get_link($source) {
        $array = @unserialize($source);

        //Check accepted types in order to prevent false links for videos when inserting an html link
        $acceptedTypes = optional_param_array('accepted_types', "*", PARAM_RAW);
        if ($acceptedTypes == "*" && isset($array['send_url'])) {
            $link = $array['send_url'];
        } else if (!empty($array['url'])) {
            if (strpos($array['url'], KALTURA_VIDEO_PREFIX) === 0) {
                if ($this->_get_env() == "filemanager") {
                   throw new moodle_exception('search_kaltura_error', 'repository_intralibrary');
                }
                $link = $array['url'];
            } else if (optional_param('get_original_filename', FALSE, PARAM_RAW)) {
                $link = $this->_get_repository_filename($array['url']);
            } else {
                $link = $this->_get_redirected_url($array['url']);
            }
        }

        return isset($link) ? $link : $source;
    }

    /**
     * (non-PHPdoc)
     *
     * @see repository::get_file()
     */
    public function get_file($source, $filename = '') {
        return parent::get_file($this->get_link($source), $filename);
    }

    /**
     * (non-PHPdoc)
     *
     * @see repository::supported_returntypes()
     */
    public function supported_returntypes() {
        // FILE_INTERNAL is needed to allow searching for IMS / SCORM
        return FILE_EXTERNAL | FILE_REFERENCE | FILE_INTERNAL;
    }
}

