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
 * IntraLibrary repository helper class
 *
 * @package    repository_intralibrary
 * @category   repository
 * @copyright  2015 Intrallect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

abstract class abstract_intralibrary_service {

    private $customCQL = NULL;

    /**
     * Get records based on a fix set of parameters
     * options includes:
     * - 'searchterm': (string) the search term
     * - 'myresource': (boolean) [optional] if true, only search in the current user's resources
     * - 'collection': (string) [optional] the collection name
     * - 'filetype': (string) [optional] the filetype: 'word', 'pdf' or 'image'
     * - 'starrating': (string) [optional] the average star rating
     * - 'category': (string) [optional] the category
     *
     * @param strign $searchterm
     * @param array $options
     * @param integer $limit [optional] defaults to 100
     * @throws Exception
     * @return \IntraLibrary\Service\SRWResponse
     */
    public function get_records($options, $limit = 100, $startRecord = 1, $order = null) {

        $srwResp = new \IntraLibrary\Service\SRWResponse('lom');
        $request = $this->create_request($srwResp);

        if (!($request instanceof \IntraLibrary\Service\AbstractSRURequest)) {
            throw new Exception("IntraLibrary Service created an unsuppored Request object");
        }

        return $request->query(array(
                'limit' => $limit,
                'startRecord' => $startRecord,
                'order' => $order,
                'query' => $this->build_query($options)
        ));
    }

    public function set_custom_cql($customCQL) {
        if (!$customCQL) {
            throw new moodle_exception('settings_customCQL_error', 'repository_intralibrary');
        }
        $this->customCQL = $customCQL;
    }

    protected function build_query($options) {

        $searchterm = $collection = $filetype = $starrating = $category = $resourcetype = $accepted_types = $env = '';
        extract($options);

        // XSearch query begins with a search term
        $query = $this->_process_search_term($searchterm);

        // and is followed by constraints
        $query .= $this->_build_collection_constraint($collection);
        $query .= $this->_build_star_rating_constraint($starrating);
        $query .= $this->_build_filetype_constraint($filetype);
        $query .= $this->_build_category_constraint($category);
        $query .= $this->_build_accepted_types_constraint($accepted_types);
        $query .= $this->_build_resource_type_constraint($resourcetype);
        $query .= $this->_build_env_constraint($env);

        if ($this->customCQL) {
            $query .= " ". $this->customCQL;
        }

        return ltrim($query, " AND");
    }

    /**
     * Create a request object
     *
     * @param SRWResponse $srwResp       The SRWResponse object that your request must use
     * @param array       $xSearchParams The search parameters that your request must use
     * @return \IntraLibrary\Service\AbstractSRURequest
     */
    protected abstract function create_request(\IntraLibrary\Service\SRWResponse $srwResp);

    /**
     * Escape all quotes, and then surround the phrase with quotes
     *
     * @param string $searchterm
     * @return string
     *
     */
    private function _process_search_term($searchterm) {
        if (empty($searchterm)) {
            return '';
        }

        // XXX [Janek 13/11/12]: This if statement should be removed
        // once intralibrary is updated to convert spaces to ANDs
        // (vs. ORs, as it currently does)
        if (strpos($searchterm, '"') === FALSE && strpos($searchterm, '\'') === FALSE && strpos($searchterm, '(') === FALSE &&
                 strpos($searchterm, ')') === FALSE && stripos($searchterm, ' NOT ') === FALSE &&
                 stripos($searchterm, ' AND ') === FALSE && stripos($searchterm, ' OR ') === FALSE) {
            // if there are no operators, quotes or parentheses:
            // replace all spaces with the AND operator
            $searchterm = preg_replace('/\s\s*/', ' AND ', trim($searchterm));
        }

        return '"' . addslashes($searchterm) . '"';
    }

    /**
     * Add a star rating constraint
     *
     * @param string $starrating
     * @return string
     */
    private function _build_star_rating_constraint($starrating) {
        $ratings = array();
        switch ($starrating) {
            case 'star1' :
                $ratings[] = 1;
            case 'star2' :
                $ratings[] = 2;
            case 'star3' :
                $ratings[] = 3;
            case 'star4' :
                $ratings[] = 4;
                break;
            default :
                return '';
        }

        $query = $this->_match_any('intrallect.annotationextension_averagerating', $ratings);
        return " AND ($query)";
    }

    /**
     * Add a filetype constraint
     *
     * @param string $filetype
     * @return string
     */
    private function _build_filetype_constraint($filetype) {
        switch ($filetype) {
            case 'image' :
                $mime_types = array(
                        'application/vnd.oasis.opendocument.image',
                        'image/gif',
                        'image/jpeg',
                        'image/png',
                        'image/svg+xml',
                        'image/tif',
                        'image/vnd.djvu',
                        'image/x-bmp'
                );
                break;
            case 'pdf' :
                $mime_types = array(
                        'application/pdf'
                );
                break;
            case 'word' :
                $mime_types = array(
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
                );
                break;
            default :
                return '';
        }

        $query = $this->_match_any('lom.technical_format', $mime_types);
        return " AND ($query)";
    }

    /**
     * Add a collection constraint
     *
     * @param string $collection
     * @return string
     */
    private function _build_collection_constraint($collection) {
        if ($collection != '') {
            return ' AND rec.collectionIdentifier="' . $collection . '"';
        } else {
            $collections = repository_intralibrary::data_service()->get_available_collections();
            if (empty($collections)) {
                throw new moodle_exception('repository_intralibrary', 'settings_user_collections_error');
            }
            return ' AND ('.self::_match_any("rec.collectionIdentifier", array_keys($collections)).')';
        }
    }
    /**
     * Add a category constraint
     *
     * @param string $categoryRefId
     * @return string
     */
    private function _build_category_constraint($categoryRefId) {
        if ($categoryRefId) {
            return " AND lom.classification_taxonpath_taxon_id=$categoryRefId";
        }

        return '';
    }

    /**
     * Build a constraint based on Mooddle's accepted_types parameter
     */
    private function _build_accepted_types_constraint($accepted_types) {
        $accepted_types = (array) $accepted_types;
        $types = array();
        $mimetypes = get_mimetypes_array();
        foreach ($accepted_types as $type) {
            $type = substr($type, 1);
            if (isset($mimetypes[$type]['type'])) {
                $types[] = $mimetypes[$type]['type'];
            }
        }

        // add missing audio types
        if (in_array('.mp3', $accepted_types) || in_array('.m4a', $accepted_types)) {
            if (!in_array('audio/mpeg', $types)) {
                $types[] = 'audio/mpeg';
            }
            if (!in_array('audio/x-mpeg', $types)) {
                $types[] = 'audio/x-mpeg';
            }
        }

        if (empty($types)) {
            return '';
        }

        $query = $this->_match_any('lom.technical_format', $types);
        return " AND ($query)";
    }

    /**
     * Build constraints based on Moodle's environment paraameter
     */
    private function _build_env_constraint($env) {
        switch ($env) {
            case 'url' :
                return ' AND intralibrary.type = "web"';
            case 'filepicker' :
                $query = $this->_match_any('intralibrary.type',
                        array(
                                'scorm1.2',
                                'scorm2004',
                                'imscp'
                        ));
                return " AND ($query)";
            default :
                return '';
        }
    }

    private function _build_resource_type_constraint($resourcetype) {
        if ($resourcetype) {
            return " AND lom.educational_learningResourceType=\"$resourcetype\"";
        }

        return '';
    }

    /**
     * Generate a 'match any' clause
     *
     * @param string $parameter the parameter to match on
     * @param array $values an array of acceptable values
     */
    private function _match_any($parameter, $values) {
        $conditions = array_map(function ($value) use($parameter) {
            return "$parameter=\"$value\"";
        }, $values);
        return implode(' OR ', $conditions);
    }
}
