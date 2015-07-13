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
 * This file controls rendering in the file picker
 *
 * @package    repository_intralibrary
 * @category   repository
 * @copyright  2015 Intrallect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

class repository_intralibrary_view {

    private $dataService;

    public function __construct(repository_intralibrary_data_service $dataService) {
        $this->dataService = $dataService;
    }

    /**
     * Create a select element for filepicker rendering
     *
     * @param string $name
     * @param string $label
     * @param array $options
     */
    private function _create_select($name, $label, $options, $optionLabels = array(), $addEmpty = TRUE) {
        $select = new stdClass();
        $select->label = $label;
        $select->name = $name;
        $select->type = 'select';
        $select->value = $this->_get_value($name);
        $select->id = 'intralibrary_' . $name;

        $select->options = array();

        if ($addEmpty) {
            $select->options[] = array(
                    'value' => '',
                    'label' => repository_intralibrary::get_string('search_selectone')
            );
        }

        foreach ($options as $value) {

            $label = isset($optionLabels[$value]) ? $optionLabels[$value] : repository_intralibrary::get_string('search_' . $value);

            $select->options[] = array(
                    'value' => $value,
                    'label' => $label
            );
        }

        return $select;
    }

    private function _get_value($param) {
        global $SESSION;

        return isset($SESSION->intralibrary_search_parameters[$param]) ? $SESSION->intralibrary_search_parameters[$param] : '';
    }

    /**
     * Get an array of search input objects
     */
    public function get_search_inputs($types, $collections) {
        $inputs = array();

        $query = new stdClass();
        $query->label = repository_intralibrary::get_string('search_query');
        $query->type = 'text';
        $query->name = 'searchterm';
        $query->value = $this->_get_value('searchterm');
        $query->id = 'intralibrary_searchterm';

        $inputs[] = $query;

        if (repository_intralibrary::is_shared_auth() && $this->_field_enabled('my_resources')) {
            $inputs[] = $this->_create_select('myresources', repository_intralibrary::get_string('search_myresources'),
                    array(
                            'no',
                            'yes'
                    ), array(), FALSE);
        }

        if ($this->_field_enabled('collection')) {
            $inputs[] = $this->_create_select('collection', repository_intralibrary::get_string('search_collection'),
                    array_keys($collections), $collections);
        }

        if ($types == "*" && ($this->_field_enabled('file_type'))) {
            $inputs[] = $this->_create_select('filetype', repository_intralibrary::get_string('search_filetype'),
                    array(
                            'word',
                            'pdf',
                            'image'
                    ));
        }

        if ($this->_field_enabled('star_rating')) {
            $inputs[] = $this->_create_select('starrating', repository_intralibrary::get_string('search_starrating'),
                    array(
                            'star4',
                            'star3',
                            'star2',
                            'star1'
                    ));
        }

        if ($this->_field_enabled('resource_type')) {
            $resource_types = $this->dataService->get_resource_types();
            $inputs[] = $this->_create_select('resourcetype', repository_intralibrary::get_string('search_resourcetype'),
                    $resource_types, array_combine($resource_types, $resource_types));
        }

        if ($this->_field_enabled('category')) {
            $categories = array();
            foreach ($this->dataService->get_categories() as $element) {
                $categories[$element['refId']] = $element['name'];
            }

            $inputs[] = $this->_create_select('category', repository_intralibrary::get_string('search_category'),
                    array_keys($categories), $categories);
        }
        return $inputs;
    }

    private function _field_enabled($name) {
        return get_config('intralibrary', "optional_field_$name");
    }
}
