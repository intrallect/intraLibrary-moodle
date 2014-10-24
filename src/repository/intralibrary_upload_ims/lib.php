<?php

// Load parent class 'repository_intralibrary_upload'.
require_once (__DIR__ . '/../intralibrary_upload/lib.php');
class repository_intralibrary_upload_ims extends repository_intralibrary_upload {

    private static $_LANG_ATTR = array(
            'language' => 'en'
    );

    private static $_LOM_SOURCE = 'LOMv1.0';

    /**
     * Return names of the general options
     * By default: no general option name
     *
     * @return array
     */
    public static function get_type_option_names() {
        return array(
                'pluginname'
        );
    }

    /**
     * Don't want to automatically call the inherited function from repository_intralibrary_upload
     *
     * @param unknown_type $mform
     * @param unknown_type $classname
     */
    public static function type_config_form($mform, $classname = 'repository') {
        return abstract_repository_intralibrary::type_config_form($mform, $classname);
    }

    /**
     * Don't want to automatically call the inherited function from repository_intralibrary_upload
     *
     * @param unknown_type $mform
     * @param unknown_type $data
     * @param unknown_type $errors
     */
    public static function type_form_validation($mform, $data, $errors) {
        return $errors;
    }

    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array(), $readonly = 0) {
        parent::__construct($repositoryid, $context, $options, $readonly);

        // when it's not an admin request and not an IMS request, we disable this
        if (!intralibrary_is_admin_request() && !intralibrary_is_ims_request() &&
                 strpos($_SERVER['REQUEST_URI'], '/blocks/intralibrary/file_for_sharing.php') !== 0) {
            $this->disabled = TRUE;
        }
    }

    protected function _get_js_requires() {
        $req = parent::_get_js_requires();
        $req[] = 'repository_intralibrary_upload';
        return $req;
    }

    public function upload($saveas_name, $maxbytes) {
        require_once __DIR__ . '/helpers/IMSContentPackageTool.php';
        require_once __DIR__ . '/vendors/FluentDOM/FluentDOM.php';
        require_once __DIR__ . '/helpers/LOMXMLHelper.php';

        $packageName = required_param(self::FILE_INPUT, PARAM_RAW);
        $packagePath = IMSContentPackageTool::getWorkDirectory() . basename($packageName);

        $packageTool = new IMSContentPackageTool($packagePath);
        $metadataNode = $this->_prepare_metadata($packageTool);

        // unzip the package and inject the modified metadata
        $extractedDir = $packageTool->unzipPackage();
        $manifestPath = $extractedDir . '/imsmanifest.xml';
        $manifestDOM = FluentDOM($manifestPath);
        $this->_rewrite_metadata($manifestDOM, $metadataNode);

        // update the existing zip archive
        $zip = new ZipArchive();
        $zip->open($packagePath, ZipArchive::CREATE);
        $zip->addFromString('imsmanifest.xml', $manifestDOM->document->saveXML());
        $zip->close();

        $response = $this->deposit_package($packagePath, NULL, FALSE);

        // remove files
        fulldelete($packageTool->getMetadataXMLPath());
        fulldelete($packageTool->getExtractedPath());

        // note that this is TRUE by default:
        $uploadToMoodle = optional_param('upload_to_moodle', TRUE, PARAM_BOOL);
        if ($uploadToMoodle) {
            // hijacking the moodle upload repository's functionality
            // to copy this package into Moodle
            $_FILES[self::FILE_INPUT] = array(
                    'error' => 0,
                    'tmp_name' => $packagePath,
                    'name' => $packageName
            );

            global $CFG;
            require_once $CFG->dirroot . '/repository/upload/lib.php';
            $upload_repo = new repository_upload($this->id); // need a valid repo ID for this to function..
            $response = $upload_repo->upload($saveas_name, $maxbytes);
        }

        // need to clean up since this isn't a temp uploaded file
        unlink($packagePath);

        // if there aren't any files left in the user's temp directory, remove it as well
        $tempDir = dirname($packagePath);
        if (!glob($tempDir . '/*')) {
            fulldelete($tempDir);
        }

        return $response;
    }

    public function supported_returntypes() {
        return FILE_INTERNAL;
    }

    private function _rewrite_metadata(FluentDOM $fDOM, $metadataNode) {
        $metadata = $fDOM->find('*[local-name()="metadata"]');
        if ($metadata->length == 0) {
            throw new Exception("Unable to find metadata element in uploaded imsmanifest.xml");
        }

        $lom = $metadata->find('*[local-name()="lom"]');
        if ($lom->length == 0) {
            throw new Exception("Unable to find lom element in uploaded imsmanifest.xml");
        }

        $lom->replaceWith($metadataNode);
    }

    /**
     * Prepare the metadata for an ims content package
     *
     * @param IMSContentPackageTool $packageTool
     */
    private function _prepare_metadata(IMSContentPackageTool $packageTool) {
        $hlpr = new LOMXMLHelper($packageTool->getMetadataXMLPath());
        $fDOM = $hlpr->getFluentDOM();
        $lomNode = $fDOM->find('lom:lom');

        $title = $hlpr->ensureNode('lom:lom/lom:general/lom:title/lom:string', self::$_LANG_ATTR);
        $title->text($this->_required_param_value('title'));

        $desc = $hlpr->ensureNode('lom:lom/lom:general/lom:description/lom:string', self::$_LANG_ATTR);
        $desc->text($this->_required_param_value('description'));

        // remove all keywords before re-adding everything that was posted back
        $fDOM->find('lom:lom/lom:general/lom:keyword')->remove();

        $keywords = optional_param('keywords', '', PARAM_RAW);
        $lomGeneral = $fDOM->find('lom:lom/lom:general');
        foreach (explode(',', $keywords) as $keyword) {
            $lomGeneral->append(
                    $hlpr->createElement('lom:keyword',
                            array(
                                    $hlpr->createElement('lom:string', trim($keyword), self::$_LANG_ATTR)
                            )));
        }

        // set approval reason (if requires approval)
        for ($i = 1; $i <= 2; $i++) {
            if ($this->_upload_optional($i) && get_config("intralibrary_upload", "optional_".$i."_extra_info")) {
                $additionalDesc = $this->_required_param_value('optional_'.$i.'_reason');
                $lomGeneral->append(
                        $hlpr->createElement('lom:description',
                                array(
                                        $hlpr->createElement('lom:string', $additionalDesc, self::$_LANG_ATTR)
                                )));
                break;
            }
        }

        // add contribute data to lifeCycle & metaMetadata
        $userData = (array) self::factory()->build_contributor_data();

        // add a contribute element to the lifeCycle
        $lomNode->append(
                $hlpr->createElement('lom:lifeCycle',
                        array(
                                $this->_create_contribute($hlpr, 'content provider', $userData)
                        )));

        // add a contribute element to the metaMetadata
        $lomNode->append(
                $hlpr->createElement('lom:metaMetadata',
                        array(
                                $this->_create_contribute($hlpr, 'creator', $userData),
                                $hlpr->createElement('lom:metadataSchema', 'IEEE LOM 1.0'),
                                $hlpr->createElement('lom:language', 'en')
                        )));

        $lomNode->append($this->_create_classification($hlpr));

        return $lomNode;
    }

    /**
     * Create a lom:classification node based on request parameters
     *
     * @param LOMXMLHelper $hlpr
     */
    private function _create_classification(LOMXMLHelper $hlpr) {
        $clssfctnNode = $hlpr->createElement('lom:classification',
                array(
                        $hlpr->createElement('lom:purpose',
                                array(
                                        $hlpr->createElement('lom:source', self::$_LOM_SOURCE),
                                        $hlpr->createElement('lom:value', 'discipline')
                                ))
                ));

        // set classification data
        $categoryRefId = $this->_required_param_value('category_value');
        $categoryName = $this->_required_param_value('category_name');

        if ($subcategories = required_param('subcategory_value', PARAM_RAW)) {
            // add each sub category
            $subcategories = explode(',', $subcategories);
            $subcategoryNames = explode(',', $this->_required_param_value('subcategory_name'));
            foreach ($subcategories as $i => $refId) {
                // create a taxon path for each category + subcategory
                $taxonPathNode = $hlpr->createElement('lom:taxonPath',
                        array(
                                $hlpr->createElement('lom:source',
                                        array(
                                                $hlpr->createElement('lom:string', self::_get_category_source(), self::$_LANG_ATTR)
                                        ))
                        ));

                $taxonPathNode->appendChild($hlpr->createTaxonNode($categoryRefId, $categoryName));
                $taxonPathNode->appendChild($hlpr->createTaxonNode($refId, $subcategoryNames[$i]));

                $clssfctnNode->appendChild($taxonPathNode);
            }
        } else {
            // or just add the parent category on its own
            $taxonPathNode = $hlpr->createElement('lom:taxonPath',
                    array(
                            $hlpr->createElement('lom:source',
                                    array(
                                            $hlpr->createElement('lom:string', self::_get_category_source(), self::$_LANG_ATTR)
                                    ))
                    ));
            $taxonPathNode->appendChild($hlpr->createTaxonNode($categoryRefId, $categoryName));
            $clssfctnNode->appendChild($taxonPathNode);
        }

        return $clssfctnNode;
    }

    /**
     * Create a lom:contribute DOMNode
     *
     * @param LOMXMLHelper $hlpr
     * @param string $role
     * @param array $userData
     * @return DOMNode
     */
    private function _create_contribute(LOMXMLHelper $hlpr, $role, $userData) {
        $vcard = <<<VCARD
BEGIN:vcard
FN:{$userData['FullName']}
ORG:{$userData['Organisation']}
EMAIL:{$userData['Email']}
END:vcard
VCARD;

        return $hlpr->createElement('lom:contribute',
                array(
                        $hlpr->createElement('lom:role',
                                array(
                                        $hlpr->createElement('lom:source', self::$_LOM_SOURCE),
                                        $hlpr->createElement('lom:value', $role)
                                )),
                        $hlpr->createElement('lom:entity', $vcard),
                        $hlpr->createElement('lom:date',
                                array(
                                        $hlpr->createElement('lom:dateTime', date('c'))
                                ))
                ));
    }
}
