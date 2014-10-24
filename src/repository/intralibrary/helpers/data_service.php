<?php

use IntraLibrary\LibraryObject\TaxonomyData;
use IntraLibrary\Configuration;

class repository_intralibrary_data_service {

    /**
     * @var TaxonomyData
     */
    private $tProvider;

    private $catTaxonomySource;

    /**
     * @var repository_intralibrary_logger
     */
    private $logger;

    private $categories;
    private $subcategories;

    public function __construct(TaxonomyData $tProvider, $source, $logger) {
        $this->tProvider = $tProvider;
        $this->catTaxonomySource = $source;
        $this->logger = $logger;

        if (!$source) {
            $this->logger->log("Category Taxonomy Source isn't configured.");
        }
    }

    /**
     * Get a list of categories from intralibrary
     *
     * @return array taxon refId/name pairs
     */
    public function get_categories() {

        if (!isset($this->categories)) {

            if (!$this->catTaxonomySource) {
                return array();
            }

            $taxonomy = $this->tProvider->retrieveBySource($this->catTaxonomySource);
            if (!$taxonomy) {
                $this->logger->log("Configured Category Taxonomy Source ($this->catTaxonomySource) isn't available.");
                return array();
            }

            // the taxonomy will have one child (being the root taxon)
            $taxonIds = $taxonomy->getChildIds();
            $this->categories = $this->_get_category_children($taxonIds[0]);
        }

        return $this->categories;
    }

    /**
     * Get subcategory data
     *
     * @return array
     */
    public function get_sub_categories() {

        if (!isset($this->subcategories)) {
            $this->subcategories = array();

            // loop through all categories, and get subcategories for each one
            foreach ($this->get_categories() as $category) {
                $id = $category['id'];
                $refId = $category['refId'];
                $this->subcategories[$refId] = $this->_get_category_children($id);
            }
        }

        return $this->subcategories;
    }

    /**
     * Get all direct children of a taxonomy (category)
     *
     * @param string $objectId
     * @return array
     */
    private function _get_category_children($objectId) {
        $categories = array();

        $categoryTaxon = $this->tProvider->retrieveById($objectId);
        foreach ($categoryTaxon->getChildIds() as $childId) {
            $taxon = $this->tProvider->retrieveById($childId);
            $categories[] = array(
                    'id' => $taxon->getId(),
                    'refId' => $taxon->getRefId(),
                    'name' => $taxon->getName()
            );
        }

        usort($categories, function ($catA, $catB) {
            return strcmp($catA['name'], $catB['name']);
        });

        return $categories;
    }

    /**
     * Get all taxonomy sources
     *
     * @return array
     */
    public function get_category_sources() {
        $categories = array();
        try {

            $useCached = TRUE;
            if ($this->_is_updating_settings()) {
                Configuration::set($_POST);
                $useCached = FALSE;
            }

            $tProvider = new \IntraLibrary\LibraryObject\TaxonomyData();
            foreach ($tProvider->getAvailableTaxonomies(TRUE, $useCached) as $taxonomyId) {
                if ($taxonomy = $tProvider->retrieveById($taxonomyId, 'taxonomy')) {
                    $categories[$taxonomy->getSource()] = $taxonomy->getName();
                }
            }
        } catch (Exception $ex) {
            intralibrary_add_moodle_log("update", $ex->getMessage());
            $this->logger->log($ex->getMessage());
        }

        return $categories;
    }

    public function get_all_collections() {
        $collections = array();
        try {

            // if we're currently updating plugin data, use the POSTed settings
            $useCached = TRUE;
            if ($this->_is_updating_settings()) {
                Configuration::set($_POST);
                $useCached = FALSE;
            }

            $collectionProvider = new \IntraLibrary\LibraryObject\CollectionData();
            $collections = $collectionProvider->getAvailableCollections(TRUE, $useCached);

        } catch (Exception $ex) {
            intralibrary_add_moodle_log("update", $ex->getMessage());
            $this->logger->log($ex->getMessage());
        }

        return $collections;
    }

    /**
     * Get all enabled collections
     *
     * @return array
     */
    public function get_available_collections() {
        $collections = get_config('intralibrary', 'enabled_collections');
        if (!$collections) {
            return array();
        }
        $collections = explode(',', $collections);

        $all_collections = $this->get_all_collections();
        $enabled_collections = array();
        foreach ($collections as $collection_id) {
            if (isset($all_collections[$collection_id])) {
                $enabled_collections[$collection_id] = $all_collections[$collection_id];
            }
        }

        return $enabled_collections;
    }

    private function _is_updating_settings() {
        return isset($_POST['action'], $_POST['hostname'], $_POST['admin_username'], $_POST['admin_password'])
                    && in_array($_POST['action'], array('newon', 'edit'));
    }

    /**
     * get_availabe_locations()
     * @return array
     */
    public function get_availabe_locations() {

        $depositItems = array('' => '-- Please Select One --');

        if (get_config('intralibrary', 'authentication') == INTRALIBRARY_AUTH_SHARED) {
            $key = repository_intralibrary_auth::DEPOSIT_POINT_FROM_SSO;
            $depositItems[$key] = '* Determined by SSO User';
        }
        require_once __DIR__.'/../lib.php';
        $swordService = repository_intralibrary::factory()->build_sword_service(TRUE);
        $depositURLs = $swordService->getDepositDetails();
        foreach ($depositURLs as $workflowNames => $workflowItems) {
            foreach ($workflowItems as $key => $depositURL) {
                $depositItems[$depositURL] = $workflowNames.", ".$key;
            }
        }

        return $depositItems;
    }
}
