<?php

// load parent class 'abstract_repository_intralibrary'
require_once __DIR__ . '/../intralibrary/abstract_repository_intralibrary.php';

/**
 * Moodle 2 Plugin Repository Plugin for IntraLibrary
 */
class repository_intralibrary_upload extends abstract_repository_intralibrary {

    const POST_UPLOAD_STANDARD = 0; // return a URL to the newly created IntraLibrary resource
    const POST_UPLOAD_KALTURA = 1; // create a new Kaltura resource, referencing the newly created IntraLibrary resource
    const FILE_INPUT = 'repo_upload_file';
    const UPLOAD_FORM_ID_PREFIX = 'intralibrary_upload-form';

    /**
     * @var repositroy_intralibrary_upload_settings
     */
    private static $SETTINGS;

    /**
     * @return repositroy_intralibrary_upload_settings
     */
    protected static function settings() {
        if (!isset(self::$SETTINGS)) {
            require_once __DIR__ . '/helpers/repository_intralibrary_upload_settings.php';
            self::$SETTINGS = new repositroy_intralibrary_upload_settings(self::data_service());
        }
        return self::$SETTINGS;
    }
    /**
     *
     * @return array
     */
    public static function get_type_option_names() {
        return self::settings()->get_type_option_names();
    }

    /**
     * Create the settings form
     *
     * @param MoodleQuickForm $mform
     * @param string $classname
     */
    public static function type_config_form($mform, $classname = 'repository') {
        parent::type_config_form($mform, $classname);
        return self::settings()->type_config_form($mform);
    }

    /**
     *
     * @param moodleform $mform
     * @param array $data
     * @param array $errors
     * @return array an array of errors
     */
    public static function type_form_validation($mform, $data, $errors) {
        return self::settings()->type_form_validation($mform, $data, $errors);
    }

    /**
     * Governs what to do after a successful IntraLibrary SWORD deposit
     *
     * @param string
     */
    private $postUpload;

    /**
     * Constructor
     *
     * @param int $repositoryid
     * @param stdClass $context
     * @param array $options
     * @param int $readonly
     */
    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array(), $readonly = 0) {
        global $PAGE;
        parent::__construct($repositoryid, $context, $options, $readonly);

        $isSharedAuthStaff = $this->auth()->is(INTRALIBRARY_AUTH_SHARED) && $this->auth()->is_staff();
        $hasCapability = has_capability('mod/resource:addinstance', $PAGE->context);

        if (!$isSharedAuthStaff && !$hasCapability) {
            $this->disabled = TRUE;
        }
    }

    /**
     * Will get called once per repositoryid whenver a full page
     * is being requested.
     *
     * @param integer $repositoryid
     */
    protected function _prepare_for_repository(moodle_page $page, $repositoryid) {
        parent::_prepare_for_repository($page, $repositoryid);

        // subclasses shouldn't inherit this
        if (get_called_class() == 'repository_intralibrary_upload') {
            global $course;

            // disable in content package context
            if (intralibrary_is_ims_request()) {
                $page->requires->js_init_call('M.repository_intralibrary_filepicker.disable_in_env_from_server',
                    array('filemanager', $repositoryid), FALSE, self::$FILEPICKER_MODULE
                );
            }

            $page->requires->js_init_call('M.repository_intralibrary_upload.set_kaltura_extensions',
                    array(
                            explode(',', KALTURA_FILE_EXTENSIONS)
                    ), FALSE, self::$FILEPICKER_MODULE);
            $page->requires->js_init_call('M.repository_intralibrary_upload.set_categories',
                    array(
                            self::data_service()->get_categories()
                    ));
            $page->requires->js_init_call('M.repository_intralibrary_upload.set_sub_categories',
                    array(
                            self::data_service()->get_sub_categories()
                    ));

            // pass language strings to javascript
            include __DIR__ . '/lang/en/repository_intralibrary_upload.php';
            if (!isset($string) || !is_array($string)) {
                throw new Exception("Unable to load translation strings");
            }
            $keys = array_keys($string);
            $page->requires->strings_for_js($keys, 'repository_intralibrary_upload');

            // Pass settings to JS
            $value = (array) get_config("intralibrary_upload");
            unset($value["kaltura_admin_secret"]);
            unset($value["kaltura_enabled"]);
            unset($value["kaltura_partner_id"]);
            unset($value["kaltura_url"]);

            $page->requires->js_init_call('M.repository_intralibrary_upload.set_settings_variables', array(
                     $value ));
            // This implies that we're on a course page
            if (isset($course->format) && $course->format != 'site') {
                $page->requires->js_init_call('M.repository_intralibrary_upload.set_course',
                        array(
                                $course->shortname,
                                $course->fullname
                        ));
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * (non-PHPdoc)
     * @see repository::get_listing()
     */
    public function get_listing($path = '', $page = '') {
        $ret = array();

        $ret['nologin'] = TRUE;
        $ret['nosearch'] = TRUE;
        $ret['norefresh'] = TRUE;
        $ret['list'] = array();
        $ret['uploadType'] = $this->_determinate_upload_type();
        $ret['dynload'] = FALSE;
        $ret['ext'] = $this->_accepted_mimetype();

        // label will be re-written by JavaScript
        $ret['upload'] = array(
                'label' => '',
                'id' => self::UPLOAD_FORM_ID_PREFIX
        );

        return $ret;
    }

    /**
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param unknown $saveas_name
     * @param unknown $maxbytes
     * @return multitype:Ambigous <string, string>
     */
    public function upload($saveas_name, $maxbytes) {

        // create a manifest based on post data
        $manifest = $this->_create_manifest();
        $package = new \IntraLibrary\IMS\Package($manifest);

        if ($this->_determinate_upload_type() == 'url') {
            // ensure that a valid URL was supplied
            // and set it on the manifest
            $this->_attach_url($package);
        } else {
            // validate that a file was uploaded properly
            // and attach it to the manifest
            $this->_attach_file($package);
        }

        // create a package
        $packagePath = $package->create();

        // deposit the package
        return $this->deposit_package($packagePath, $manifest);
    }

    protected function deposit_package($packagePath, $manifest = NULL, $cleanup = TRUE) {
        // get the appropriate deposit url
        $sword = self::factory()->build_sword_service();

        $depositUrl = $this->_get_deposit_url();

        $response = $sword->deposit($depositUrl, $packagePath);

        // cleanup
        if ($cleanup) {
            unlink($packagePath);
        }

        // return failure if there's no content source
        if (empty($response->sac_content_src)) {
            intralibrary_log_with_exception("Failed to Upload File - Please Try Again ($response->sac_summary)", "add");
        }

        // process the file
        if ($this->postUpload == self::POST_UPLOAD_KALTURA) {
            $this->_kaltura_upload($manifest, $response);
        }

        if (!preg_match('/^.*:(\d*)$/', (string) $response->sac_id, $matches)) {
            intralibrary_log_with_exception("Unable to determine IntraLibrary ID from SWORD response", "add");
        }
        $internalId = $matches[1];

        $acceptedTypes = optional_param_array('accepted_types', "*", PARAM_RAW);
        $env = $this->_get_env();
        if ($env == 'editor' && $this->postUpload == self::POST_UPLOAD_KALTURA && $acceptedTypes != "*") {
            // if this is a kaltura resource being uploaded from the editor,
            // we need to set the we need to prepare the URL for the text filter
            $url = repository_intralibrary_generate_kaltura_uri($internalId, (string) $response->sac_title);
        } else {
            // standard behaviour is to return a URL to the package
            $url = urldecode((string) $response->sac_content_src);
            $url = $this->_get_redirected_url($url);
        }

        // if the upload request is coming from the file manager,
        // create a file from reference to use in the current session
        if ($this->_determinate_upload_type() == "singleFile") {
            require_once __DIR__ . '/../intralibrary/helpers/intralibrary_list_item.php';
            $reference = intralibrary_list_item::create_source($internalId, (string) $response->sac_title, $url);

            $fileData = $this->_validate_uploaded_file();
            $name = $fileData['name'];
            if ($this->postUpload == self::POST_UPLOAD_KALTURA) {
                // strip the file extension so that moodle doesn't try to embed it
                $name = pathinfo($name, PATHINFO_FILENAME);
            }

            $record = $this->_create_upload_record($name);
            $record->source = repository::build_source_field($reference);

            /*
             * get_reference_file_lifetime() has been deprecated,
             * use default value instead.
             */
            $record->referencelifetime = 86400;

            get_file_storage()->create_file_from_reference($record, $this->id, $reference);
        }
        intralibrary_add_upload_log($_POST['title'], $url);
        header('Content-type: text/html');
        return array(
                'url' => $url
        );
    }

    /**
     * (non-PHPdoc)
     *
     * @see repository::supported_returntypes()
     */
    public function supported_returntypes() {
        return FILE_EXTERNAL | FILE_REFERENCE;
    }

    /**
     * This function calculates the upload action's target format based on a set of environmental variables.
     *
     * The determinated format is sent to the client on "list" action. On upload, some of these required
     * environmental variables are not accessable, so the client posts back the previously determinated
     * target format to help post upload processing.
     *
     * @return string
     */
    private function _determinate_upload_type() {
        // look for existing posted target format before trying to determinate it again
        if (array_key_exists('upload_type', $_POST) && !empty($_POST['upload_type'])) {
            return $_POST["upload_type"];
        }

        // get the environment information
        $env = optional_param('env', 'filepicker', PARAM_ALPHA);
        $action = optional_param('action', '', PARAM_ALPHA);
        $accepted_types = optional_param_array('accepted_types', '*', PARAM_RAW);

        /*
         * Basic rules for content packages (as of Moodle 2.6 08/07/2014):
         *            |     SCORM       |      IMS     |
         * ---------------------------------------------
         *     ENV    |  'filemanager'  | 'filepicker' |
         * ---------------------------------------------
         *   ACTION   |     'list'      |    'list'    |
         * ---------------------------------------------
         * ACC. TYPES |    .zip, .xml   |       *      |
         */

        // use the condition combination from above to determinate the proper type
        if (intralibrary_is_ims_request() ||
            ($action == 'list' && $env == 'filemanager' && count($accepted_types) == 2 && in_array('.zip', $accepted_types)
                     && in_array('.xml', $accepted_types)) ||
            ($action == 'list' && $env == 'filepicker')) {
            return "contentPackage";
        } else {
            return "singleFile";
        }
    }

    private function _kaltura_upload(\IntraLibrary\IMS\Manifest $manifest, SWORDAPPEntry $response) {
        require_once __DIR__ . '/helpers/kaltura.php';

        // create a session with the Kaltura server
        $kHelper = new intralibrary_kaltura_helper();
        $client = $kHelper->getClient();
        $client->setKs($kHelper->startSession());

        // validate the uploaded file, and upload it to Kaltura
        $fileData = $this->_validate_uploaded_file();
        $token = $client->upload->upload($fileData['tmp_name']);

        // add a media entry from the uploaded file
        $entry = $kHelper->createMediaEntry($manifest, (string) $response->sac_id);
        $client->media->addFromUploadedFile($entry, $token);
    }

    protected function _required_param_value($name, $type = PARAM_RAW) {
        $value = required_param($name, $type);
        if (!$value) {
            intralibrary_log_with_exception('Missing parameter: ' . ucwords(str_replace('_', ' ', $name)), "add");
        }

        return $value;
    }

    private function _attach_url(\IntraLibrary\IMS\Package $package) {
        $url = optional_param(self::FILE_INPUT, NULL, PARAM_RAW);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            intralibrary_log_with_exception("Invalid upload URL", "add");
        }

        // check URL & get content type
        $curlH = curl_init($url);
        curl_setopt($curlH, CURLOPT_RETURNTRANSFER, TRUE);
        curl_exec($curlH);
        $httpStatus = curl_getinfo($curlH, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($curlH, CURLINFO_CONTENT_TYPE);
        curl_close($curlH);

        if ($httpStatus < 200 || $httpStatus >= 400) {
            intralibrary_log_with_exception("$url came back with HTTP status code $httpStatus", "add");
        }

        $contentType = explode(';', $contentType);

        $manifest = $package->getManifest();
        $manifest->setTechnicalFormat($contentType[0]);
        $manifest->setTechnicalLocation($url);

        $this->postUpload = self::POST_UPLOAD_STANDARD;
    }

    private function _attach_file(\IntraLibrary\IMS\Package $package) {
        $fileData = $this->_validate_uploaded_file();

        $manifest = $package->getManifest();
        $manifest->setTechnicalFormat($fileData['type']);
        $manifest->setTechnicalSize($fileData['size']);

        $kalEnabled = get_config("intralibrary_upload", "kaltura_enabled") == TRUE;
        $fileName   = $fileData['name'];
        $ext        = pathinfo($fileName, PATHINFO_EXTENSION);
        $kalExts    = explode(',', KALTURA_FILE_EXTENSIONS);

        if ($kalEnabled && in_array($ext, $kalExts)) {
            // files with kaltura file extensions will be treated separately
            $this->postUpload = self::POST_UPLOAD_KALTURA;
        } else {
            // attach the uploaded file to the package
            $manifest->setFileName($fileName);
            $package->setFile($fileData['tmp_name']);
            $this->postUpload = self::POST_UPLOAD_STANDARD;
        }
    }

    private function _create_manifest() {
        // create an imsmanifest
        $imsmanifest = new \IntraLibrary\IMS\Manifest();
        $imsmanifest->setCopyright('Uploaded from Moodle');

        // set title & description
        $imsmanifest->setTitle($this->_required_param_value('title'));
        $imsmanifest->addDescription($this->_required_param_value('description'));

        // set the date
        $imsmanifest->setDateTime(date('c'));

        // set classification data
        $categoryRefId = $this->_required_param_value('category_value');
        $categoryName = $this->_required_param_value('category_name');

        if ($subcategories = required_param('subcategory_value', PARAM_RAW)) {
            // add each sub category
            $subcategories = explode(',', $subcategories);
            $subcategoryNames = explode(',', $this->_required_param_value('subcategory_name'));
            foreach ($subcategories as $i => $refId) {
                $imsmanifest->addClassification(self::_get_category_source(),
                        array(
                                array(
                                        'refId' => $categoryRefId,
                                        'name' => $categoryName
                                ),
                                array(
                                        'refId' => $refId,
                                        'name' => $subcategoryNames[$i]
                                )
                        ));
            }
        } else {
            // or just add the parent category on its own
            $imsmanifest->addClassification(self::_get_category_source(),
                    array(
                            array(
                                    'refId' => $categoryRefId,
                                    'name' => $categoryName
                            )
                    ));
        }

        // set keywords
        $keywords = optional_param('keywords', NULL, PARAM_RAW);
        if ($autoKeywords = optional_param('auto_keywords', NULL, PARAM_RAW)) {
            $keywords = $keywords ? "$keywords, $autoKeywords" : $autoKeywords;
        }
        $keywords = explode(',', $keywords);
        if ($keywords && $keywords[0] != '') {
            $imsmanifest->setKeywords($keywords);
        }

        // set approval reason (if requires approval)
        for ($i = 1; $i <= 2; $i++) {
            if ($this->_upload_optional($i) && get_config("intralibrary_upload", "optional_".$i."_extra_info")) {
                $imsmanifest->addDescription($this->_required_param_value('optional_'.$i.'_reason'));
                break;
            }
        }
        $contributorData = self::factory()->build_contributor_data();

        $imsmanifest->setFullName($contributorData->FullName);
        $imsmanifest->setEmail($contributorData->Email);
        $imsmanifest->setOrganisation($contributorData->Organisation);

        return $imsmanifest;
    }

    /**
     * Does the current upload request require approval?
     */
    protected function _upload_optional($num) {
        $field = 'optional_'.$num;
        $input = strtolower(optional_param($field, NULL, PARAM_RAW));
        $returnval = $input == 'on';
        return $returnval;
    }

    /**
     * Get the deposit URL based on the request
     *
     * @param string $depositURL
     */
     private function _get_deposit_url() {
         if ($this->_upload_optional(1)) {
             $url = get_config("intralibrary_upload", "optional_1_alter_collection");
             if (empty($url)) {
                 intralibrary_log_with_exception(array('settings_optional_deposit_missing',
                 'repository_intralibrary_upload', 1), "add", "moodle");
             }
         } else if ($this->_upload_optional(2)) {
             $url = get_config("intralibrary_upload", "optional_2_alter_collection");
             if (empty($url)) {
                 intralibrary_log_with_exception(array('settings_optional_deposit_missing',
                 'repository_intralibrary_upload', 2), "add", "moodle");
             }
         } else {
             $url = get_config("intralibrary_upload", "default_deposit_point");
             if (empty($url)) {
                 intralibrary_log_with_exception(array('settings_default_deposit_missing',
                 'repository_intralibrary_upload'), "add", "moodle");
             }
         }

        if ($url == repository_intralibrary_auth::DEPOSIT_POINT_FROM_SSO) {
             $url = self::auth()->get_sso_user()->get_deposit_url();
        }
        return $url;
     }

    /**
     *
     * @throws moodle_exception
     */
    private function _validate_uploaded_file() {
        $name = self::FILE_INPUT;

        $filename = $_FILES[$name];
        if (!isset($filename)) {
            intralibrary_log_with_exception(array("nofile", "error"), "add", "moodle");
        }

        if (!empty($filename['error'])) {
            intralibrary_log_with_exception(array(file_get_upload_error($filename['error']), NULL), "add", "moodle");
        }

        // check if kaltura content
//         $kaltura_enabled = get_config("intralibrary_upload", "kaltura_enabled");
//         $type = $filename['type'];
//         if ($this->_get_env() == "filemanager" && strpos($type, 'video/') === 0 && $kaltura_enabled) {
//             throw new moodle_exception('search_kaltura_error', 'repository_intralibrary');
//         }

        // check file extension
        $ext = strtolower(".".pathinfo($_FILES["repo_upload_file"]["name"], PATHINFO_EXTENSION));
        $available_types = (array) $this->_get_accepted_types();

        if (in_array('*', $available_types) === FALSE && in_array($ext, $available_types) === FALSE) {
            throw new moodle_exception("upload_invalid_ext", "repository_intralibrary_upload");
        }

        return $filename;
    }

    /**
     *
     * @param string $filename
     * @return stdClass
     */
    private function _create_upload_record($filename) {
        global $saveas_path, $itemid, $license, $author, $USER;

        // Prepare file record.
        $record = new stdClass();
        $record->filepath = $saveas_path;
        $record->filename = $filename;
        $record->component = 'user';
        $record->filearea = 'draft';
        $record->itemid = $itemid;
        $record->license = $license;
        $record->author = $author;

        if ($record->filepath !== '/') {
            $record->filepath = trim($record->filepath, '/');
            $record->filepath = '/' . $record->filepath . '/';
        }

        /*
         * get_context_instance has been deprecated, that's the new way of
         * obtaining a context
         */
        $usercontext = context_user::instance($USER->id);
        $now = time();
        $record->contextid = $usercontext->id;
        $record->timecreated = $now;
        $record->timemodified = $now;
        $record->userid = $USER->id;
        $record->sortorder = 0;

        return $record;
    }

    private function _accepted_mimetype() {
        $types = (array) $this->_get_accepted_types();
        if (in_array(".png", $types)) {
            return "image/*";
        } else if (in_array(".avi", $types)) {
            return "video/*";
        } else
            return "*";
    }
}
