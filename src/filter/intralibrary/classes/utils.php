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
 * @package    filter_intralibrary
 * @category   filter
 * @copyright  2015 Intrallect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_intralibrary;

use IntraLibrary\Cache;

class utils {

    public static function get_cached_embed_code($learningObjectId, $title) {

        $cacheKey   = 'kaltura-embed-code:' . $learningObjectId;
        $embedCode  = Cache::load($cacheKey);

        if ($embedCode) {
            return $embedCode;
        }

        try {
            $embedCode = self::get_embed_code($learningObjectId);
            Cache::save($cacheKey, $embedCode);
        } catch (Exception $ex) {
            $message    = get_string('unable_to_display', 'filter_intralibrary');
            $message   .= $title ? "&quot;$title&quot; ($learningObjectId)" : $learningObjectId;
            $embedCode  = "<p class='error'>{$message} {$ex->getMessage()}</p>";
        }

        return $embedCode;
    }

    public static function get_embed_code($learningObjectId) {

        $userid = self::_get_admin_user_id();
        if (empty($userid)) {
            throw new fiexception('check_settings');
        }

        $req = new \IntraLibrary\Service\RESTRequest();
        $resp = $req->get('LearningObject/embedCode', array(
            'learning_object_id' => $learningObjectId,
            'user_id' => $userid
        ));

        $data   = $resp->getData();
        $error  = isset($data['error']) ? $data['error'] : $resp->getError();

        if ($error) {
            throw new \Exception($error);
        } else if (empty($data['LearningObject'])) {
            throw new fiexception('missing_embed_code');
        } else {

            $embed = trim($data['LearningObject']);
            if (self::embed_code_is_ready($embed)) {
                return $embed;
            }

            throw new fiexception('video_being_proccessed');
        }
    }

    /**
     *
     * @return string get the Kaltura server's hostname
     */
    public static function embed_code_is_ready($embed) {

        static $detect;

        if (empty($detect)) {
            $detect = get_config('filter_intralibrary', 'detect_embed_code');
        }

        if ($detect === '') {
            return true;
        }

        return stristr($embed, $detect);
    }

    private static function _get_admin_user_id() {

        static $userid;

        if (empty($userid)) {
            global $CFG;
            require_once($CFG->dirroot . '/repository/intralibrary/init.php');

            $userid = get_config('intralibrary', 'admin_user_id');
        }

        return $userid;

    }
}
