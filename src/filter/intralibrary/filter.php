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
 * The core of the IntraLibrary Filter plugin
 *
 * This filter plugin renders Kaltura  videos sourced
 * from the IntraLibrary repository. The filter must
 * be turned on in order to display Kaltura videos.
 *
 * @package    block_intralibrary
 * @category   block
 * @copyright  2015 Intrallect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use IntraLibrary\Cache;

class filter_intralibrary extends moodle_text_filter {
    private static $_initialised = FALSE;
    private static $_user_id;

    private static function _initialise() {

        if (!self::$_initialised) {
            global $CFG;
            require_once($CFG->dirroot . '/repository/intralibrary/init.php');

            self::$_initialised = TRUE;
            self::$_user_id = get_config('intralibrary', 'admin_user_id');
        }
    }
    private $hostname;

    /**
     *
     * @param unknown_type $context
     * @param array $localconfig
     */
    public function __construct($context, array $localconfig) {

        parent::__construct($context, $localconfig);

        self::_initialise();
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param string $text
     * @param array $options
     */
    public function filter($text, array $options = array()) {

        $prefix = str_replace('/', '\/', KALTURA_VIDEO_PREFIX);
        $pattern = '/<a href="' . $prefix . '(\d+)\/(.*)">.*<\/a>/iU';
        $replaced = preg_replace_callback($pattern, array(
                $this,
                '_kaltura_replace'
        ), $text);

        // Support for legacy embeds.
        $pattern = '/<a href="kaltura-video:(\d*):(.*)">.*<\/a>/iU';
        $replaced = preg_replace_callback($pattern, array(
                $this,
                '_kaltura_replace'
        ), $replaced);

        return $replaced;
    }

    /**
     * preg replace callback for kaltura embed codes
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     *
     * @param array $matches
     */
    private function _kaltura_replace($matches) {

        if (isset($matches[1])) {

            $learningObjectId   = $matches[1];
            $title              = isset($matches[2]) ? urldecode($matches[2]) : NULL;

            return $this->_get_cached_embed_code($learningObjectId, $title);
        }

        return $matches[0];
    }

    private function _get_cached_embed_code($learningObjectId, $title) {

        $cacheKey   = 'kaltura-embed-code:' . $learningObjectId;
        $embedCode  = Cache::load($cacheKey);

        if ($embedCode) {
            return $embedCode;
        }

        try {
            $embedCode = $this->_get_embed_code($learningObjectId);
            Cache::save($cacheKey, $embedCode);
        } catch (Exception $ex) {
            $message    = $title ? "&quot;$title&quot; ($learningObjectId)" : $learningObjectId;
            $embedCode  = "<p class='error'>" . $this->_get_string('unable_to_display') . " {$message} {$ex->getMessage()}</p>";
        }

        return $embedCode;
    }

    private function _get_embed_code($learningObjectId) {

        if (empty(self::$_user_id)) {
            throw new Exception($this->_get_string('check_settings'));
        }

        $req = new \IntraLibrary\Service\RESTRequest();
        $resp = $req->get('LearningObject/embedCode', array(
                'learning_object_id' => $learningObjectId,
                'user_id' => self::$_user_id
        ));

        $data   = $resp->getData();
        $error  = isset($data['error']) ? $data['error'] : $resp->getError();

        if ($error) {
            throw new Exception($error);
        } else if (empty($data['LearningObject'])) {
            throw new Exception($this->_get_string('missing_embed_code'));
        } else {

            $embed = trim($data['LearningObject']);
            if (stristr($embed, $this->_get_kaltura_hostname())) {
                /* Only display the embed code if it contains the Kaltura hostname
                 * (embed codes without signs of Kaltura might not be ready for use) */
                return $embed;
            }

            throw new Exception($this->_get_string('video_being_proccessed'));
        }
    }

    /**
     * Helper function for language strings
     *
     * @param unknown $stringId
     */
    private function _get_string($stringId) {
        return get_string($stringId, 'filter_intralibrary');
    }

    /**
     *
     * @return string get the Kaltura server's hostname
     */
    private function _get_kaltura_hostname() {

        if (empty($this->hostname)) {
            $url = get_config('intralibrary_upload', 'kaltura_url');
            $this->hostname = parse_url($url, PHP_URL_HOST);
        }

        return $this->hostname;
    }
}
