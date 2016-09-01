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
 * IMS Helper to process package content
 *
 * @package    repository_intralibrary_upload_ims
 * @category   repository
 * @copyright  2015 Intrallect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class IMSContentPackageTool {

    /**
     * Get the work directory for this user (session based)
     *
     * @return string
     */
    public static function getWorkDirectory() {
        $workDirectory = sys_get_temp_dir() . '/il-deposit-' . session_id() . '/';
        if (!is_dir($workDirectory)) {
            mkdir($workDirectory);
        }
        return $workDirectory;
    }

    /**
     * Create a IMSContentPackageTool from an uploaded file
     *
     * @param string $filesName the name of the $_FILES parameter
     * @throws Exception
     * @return IMSContentPackageTool
     */
    public static function createFromUploadedPackage($filesName) {
        $uploaded = isset($_FILES[$filesName]) ? $_FILES[$filesName] : NULL;
        if (!$uploaded) {
            throw new Exception("No file uploaded");
        }
        if (!empty($uploaded['error'])) {
            global $CFG;
            require_once $CFG->dirroot . '/lib/filelib.php';
            throw new Exception(file_get_upload_error($uploaded['error']));
        }

        if (pathinfo($uploaded['name'], PATHINFO_EXTENSION) != 'zip') {
            throw new Exception('Only .zip files accepted');
        }

        $packagePath = self::getWorkDirectory() . $uploaded['name'];
        move_uploaded_file($uploaded['tmp_name'], $packagePath);

        return new IMSContentPackageTool($packagePath);
    }

    private $packagePath;

    private $metadataXml;

    /**
     *
     * @param string $packagePath
     */
    public function __construct($packagePath) {
        if (!is_readable($packagePath)) {
            throw new Exception('Package not readable -- please try again');
        }
        $this->packagePath = $packagePath;
    }

    public function getPackagePath() {
        return $this->packagePath;
    }

    public function getMetadataXML() {
        return $this->metadataXml;
    }

    public function getMetadataXMLPath() {
        return $this->packagePath . '-metadata.xml';
    }

    public function getExtractedPath() {
        return $this->packagePath . '-extracted';
    }

    /**
     * Unzip the package and return the target directory
     *
     * @throws Exception
     * @return string
     */
    public function unzipPackage() {
        $packagePath = $this->getPackagePath();
        $zip = new ZipArchive();
        if ($zip->open($packagePath) !== TRUE) {
            throw new Exception('Failed to extract package -- is it definitely a .zip file?');
        }

        // set up a directory to extract the package to
        $extractedDir = $this->getExtractedPath();
        if (is_dir($extractedDir)) {
            fulldelete($extractedDir);
        }
        mkdir($extractedDir);

        $zip->extractTo($extractedDir);
        $zip->close();

        $manifestPath = $extractedDir . '/imsmanifest.xml';
        if (!is_readable($manifestPath)) {
            fulldelete($extractedDir);
            throw new Exception('Package did not contain imsmanifest.xml');
        }

        return $extractedDir;
    }

    /**
     * Save the captured metadata to a file
     *
     * @return string the name (not full path) of the saved file
     */
    public function saveMetadataXMLToFile() {
        $xmlMetadata = $this->getMetadataXML();
        if (trim((string) $xmlMetadata) == '') {
            throw new Exception('Metadata is not ready');
        }
        $path = $this->getMetadataXMLPath();
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<root>
$xmlMetadata
</root>
XML;
        file_put_contents($path, $xml);
    }

    /**
     * Deposit, export (and save) and delete a package in order to normalise its metadata
     *
     * @return $savedDirectory
     */
    public function getMetadata() {
        require_once __DIR__ . '/../../intralibrary_upload/lib.php';
        require_once __DIR__ . '/../../intralibrary/helpers/factory.php';

        $factory = new repository_intralibrary_factory();
        $sword = $factory->build_sword_service();

        // ensure we have access to the default deposit collection/workflow
        $depositUrl = get_config('intralibrary_upload', 'default_deposit_point');
        if (empty($depositUrl)) {
            throw new Exception('Default Deposit Point is not configured');
        }

        $response = $sword->deposit($depositUrl, $this->packagePath);

        // return failure if there's no content source
        if (empty($response->sac_content_src)) {
            throw new Exception("Failed to Upload File - Please Try Again ($response->sac_summary)");
        }

        // find the new ID
        $loID = $sword->get_lo_id($response);
        if (!$loID) {
            \IntraLibrary\Debug::log("IMS Package upload failed to process: $response->sac_id");
            throw new Exception("Unable to retrieve package identifier");
        }

        // export (and save) the newly deposited Learning Object from IntraLibrary
        $req = new \IntraLibrary\Service\Request('IntraLibrary-REST/');
        $xml = $req->get('LearningObject/metadata/' . $loID, array(
                'format' => 'LOM'
        ));

        require_once __DIR__ . '/../vendors/FluentDOM/FluentDOM.php';
        require_once __DIR__ . '/LOMXMLHelper.php';
        $fDOM = FluentDOM($xml);
        $fDOM->namespaces(LOMXMLHelper::$_namespaces);

        $REST = new \IntraLibrary\Service\RESTRequest();
        $REST->get('LearningObject/delete/' . $loID);

        $responseNode = $fDOM->find('/intralibrary-ws/response');
        if (!isset($responseNode->length) || $responseNode->length != 1) {
            throw new Exception('Unable to retrieve metadata for this package');
        }

        $this->metadataXml = $responseNode->xml();

        $tNodeList = $responseNode->find('lom:lom/lom:general/lom:title/lom:string');
        $dNodeList = $responseNode->find('lom:lom/lom:general/lom:description/lom:string');
        $keywords = array();
        $responseNode->find('lom:lom/lom:general/lom:keyword/lom:string')->each(
                function (DOMElement $element) use(&$keywords) {
                    $keywords[] = $element->textContent;
                });

        return array(
                'title' => $tNodeList->length > 0 ? $tNodeList->item(0)->textContent : '',
                'description' => $dNodeList->length > 0 ? $dNodeList->item(0)->textContent : '',
                'keywords' => $keywords
        );
    }
}
