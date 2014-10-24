<?php
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
