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

class filter_intralibrary extends moodle_text_filter {

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param string $text
     * @param array $options
     */
    public function filter($text, array $options = array()) {

        $prefix = str_replace('/', '\/', repository_intralibrary\kaltura::PREFIX);
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

            return filter_intralibrary\utils::get_cached_embed_code($learningObjectId, $title);
        }

        return $matches[0];
    }

}
