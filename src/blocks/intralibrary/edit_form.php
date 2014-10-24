<?php

class block_intralibrary_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $CFG;
        $path = $CFG->wwwroot;
        $link = $path.'/blocks/intralibrary/file_for_sharing.php';
        $name = trim(get_string('pluginname', 'repository_intralibrary'), "Plugin");
        if ($this->block->config->title == "") {
            $this->block->config->title = get_string('uploadto', 'block_intralibrary')." ".$name;
        }
        if ($this->block->config->blockbody == "") {
            $this->block->config->blockbody = 'To deposit resources, click <a href="'.$link.'">here</a>.';
        }

        // Section header title according to language file.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('static', 'description', "", "Please note, empty fields will result the use of the default values.");
         // A sample string variable with a default value.
        $mform->addElement('text', 'config_title', get_string('optional_title', 'block_intralibrary'), 'size="60"');
        $mform->setType('config_title', PARAM_TEXT);

        $mform->addElement('textarea', 'config_blockbody', get_string('optional_body', 'block_intralibrary'),
                'wrap="virtual" rows="10" cols="58"');
        $mform->setType('config_blockbody', PARAM_RAW);

        $mform->addElement('static', 'description', "",
                "Make sure you add a link pointing to the upload page (<i>" . $link . "</i>)");

    }
}