<?php
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
    public function get_search_inputs($types, $auth, $collections) {
        $inputs = array();

        $query = new stdClass();
        $query->label = repository_intralibrary::get_string('search_query');
        $query->type = 'text';
        $query->name = 'searchterm';
        $query->value = $this->_get_value('searchterm');
        $query->id = 'intralibrary_searchterm';

        $inputs[] = $query;

        if ($auth == INTRALIBRARY_AUTH_SHARED) {
            $inputs[] = $this->_create_select('myresources', repository_intralibrary::get_string('search_myresources'),
                    array(
                            'no',
                            'yes'
                    ), array(), FALSE);
        }

        $inputs[] = $this->_create_select('collection', repository_intralibrary::get_string('search_collection'),
                array_keys($collections), $collections);

        if ($types == "*") {
            $inputs[] = $this->_create_select('filetype', repository_intralibrary::get_string('search_filetype'),
                    array(
                            'word',
                            'pdf',
                            'image'
                    ));
        }

        $inputs[] = $this->_create_select('starrating', repository_intralibrary::get_string('search_starrating'),
                array(
                        'star4',
                        'star3',
                        'star2',
                        'star1'
                ));

        $categories = array();
        foreach ($this->dataService->get_categories() as $element) {
            $categories[$element['refId']] = $element['name'];
        }

        $inputs[] = $this->_create_select('category', repository_intralibrary::get_string('search_category'),
                array_keys($categories), $categories);

        return $inputs;
    }
}
