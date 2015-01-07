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

define('AJAX_SCRIPT', TRUE);

require_once __DIR__ . '/../intralibrary/helpers/utils.php';
require_once intralibrary_get_moodle_config_path();

require_login();

// package data (send back what was initially posted...)
$packageData = $_POST;

// and augment with package data
try {
    require_once __DIR__ . '/helpers/IMSContentPackageTool.php';
    $packageTool = IMSContentPackageTool::createFromUploadedPackage('package');
    // extract the relevant metadata
    $packageData['metadata'] = $packageTool->getMetadata();
    $packageData['filename'] = basename($packageTool->getPackagePath());
    $packageData['form_prefix'] = repository_intralibrary_upload::UPLOAD_FORM_ID_PREFIX;

    // cache the metadata for the official deposit
    $packageTool->saveMetadataXMLToFile();
} catch (Exception $ex) {
    $packageData['error'] = $ex->getMessage();
}

// prepare & print JS output
$packageData = json_encode($packageData);
echo <<<RESPONSE
<!DOCTYPE html>
<html>
<head></head>
<body>
<script type="text/javascript">
if (window.top.M) {
	window.top.M.repository_intralibrary_upload_ims.load_upload_fields($packageData);
}
</script>
</body>
</html>
RESPONSE;
