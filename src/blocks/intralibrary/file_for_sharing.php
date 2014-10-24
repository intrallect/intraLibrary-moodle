<?php
require_once __DIR__ . '/../../repository/intralibrary/helpers/utils.php';
require_once intralibrary_get_moodle_config_path();
require_once __DIR__ . '/../../repository/intralibrary/abstract_repository_intralibrary.php';
require_once $CFG->dirroot . '/repository/lib.php';

$url = '/blocks/intralibrary/file_for_sharing.php';
require_login(SITEID);
$PAGE->set_url(new moodle_url($url));
$PAGE->set_pagelayout('admin');
$PAGE->set_title("IntraLibrary File For Sharing");
$PAGE->set_heading($COURSE->fullname);

try {
    // initialise intralibrary upload repositories
    $repo = intralibrary_get_repository('intralibrary');
    $uploadRepo = intralibrary_get_repository('intralibrary_upload');
    $uploadIMSRepo = intralibrary_get_repository('intralibrary_upload_ims');
    $clientId = uniqid();
    $uploadFormId = repository_intralibrary_upload::UPLOAD_FORM_ID_PREFIX . '_' . $clientId;

    // bring in the "file for sharing" javascript & styles
    $js_module = array(
            'name' => 'block_intralibrary_file_for_sharing',
            'fullpath' => '/blocks/intralibrary/file_for_sharing.js',
            'requires' => array(
                    'repository_intralibrary_upload'
            )
    );
    $PAGE->requires->js_init_call('M.repository_intralibrary_upload.file_for_sharing',
            array(
                    $uploadFormId,
                    $clientId,
                    $uploadRepo->id,
                    $uploadIMSRepo->id
            ), FALSE, $js_module);
    $PAGE->requires->css('/../../../repository/intralibrary/filepicker.css');
} catch (Exception $ex) {
    // this will happen if the repositories are not accessible by the current user
    $repo = FALSE;
}

echo $OUTPUT->header();
echo html_writer::start_tag('div', array(
        'class' => 'heightcontainer'
));

?>

<h2 class="main">
	<img src="/repository/intralibrary_upload/pix/icon.png" alt="" />
	File for Sharing
</h2>
<?php if (!$repo) : ?>
<div>Contributing to IntraLibrary is only available to staff members</div>
<?php else: ?>
<div class="mform">
	<fieldset>
		<legend>Upload files directly to <?php echo $repo->name ?></legend>
		<div class="file-picker intralibrary-file-for-sharing"
			id="filepicker-<?php echo $clientId ?>">
			<div class="fp-upload-form mdl-align intralibrary-upload">
				<div class="intralibrary-upload-types">
					<label>Single file<input type="radio" name="upload_type"
						value="file" checked="checked"></label> <label>IMS/SCORM package<input
						type="radio" name="upload_type" value="ims"></label> <label>Web
						resource<input type="radio" name="upload_type" value="url">
					</label>
				</div>
				<form id="<?php echo $uploadFormId ?>" method="POST"
					enctype="multipart/form-data" class="form-horizontal">
					Loading upload form ... <img class="smallicon" alt="loading..."
						src="<?php echo $OUTPUT->pix_url('i/loading_small') ?>" />
					<noscript>
						<p>JavaScript is required to use this feature</p>
					</noscript>
				</form>
				<div class="buttonContainer">
					<button class="fp-upload-btn">Upload</button>
					<button class="fb-cancel-btn">Cancel</button>
				</div>
				<div class="fp-content-loading" style="display: none;">
					<div>
						<img class="smallicon" alt="loading..."
							src="<?php echo $OUTPUT->pix_url('i/loading_small') ?>" />
						<p>Uploading to IntraLibrary... please be patient with large
							files.</p>
					</div>
				</div>
			</div>
		</div>
	</fieldset>
</div>
<?php endif; ?>
<?php

echo html_writer::end_tag('div');
echo $OUTPUT->footer();
