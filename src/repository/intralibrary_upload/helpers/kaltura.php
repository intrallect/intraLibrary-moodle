<?php
class intralibrary_kaltura_helper {

    const PLUGIN_NAME = 'intralibrary_upload';

    /**
     *
     * @var \Kaltura\Client\Client
     */
    private $client;

    public function __construct($serviceUrl = NULL, $partnerId = NULL) {
        $partnerId = $partnerId ?: get_config(self::PLUGIN_NAME, 'kaltura_partner_id');
        $serviceUrl = $serviceUrl ?: get_config(self::PLUGIN_NAME, 'kaltura_url');

        $kalConf = new \Kaltura\Client\Configuration($partnerId);
        $kalConf->setServiceUrl($serviceUrl);
        $this->client = new \Kaltura\Client\Client($kalConf);
    }

    /**
     *
     * @return KalturaClient
     */
    public function getClient() {
        return $this->client;
    }

    public function startSession($adminSecret = NULL) {
        $adminSecret = $adminSecret ?: get_config(self::PLUGIN_NAME, 'kaltura_admin_secret');
        $userId = ''; // no need for a user id
        $type = \Kaltura\Client\Enum\SessionType::ADMIN;
        $partnerId = $this->client->getConfig()->getPartnerId();

        return $this->client->session->start($adminSecret, $userId, $type, $partnerId);
    }

    /**
     *
     * @param IntraLibraryIMSManifest $manifest
     * @param string $partnerData
     * @return KalturaMediaEntry
     */
    public function createMediaEntry(\IntraLibrary\IMS\Manifest $manifest, $partnerData) {
        $entry = new \Kaltura\Client\Type\MediaEntry();
        $entry->partnerData = $partnerData;
        $entry->name = $manifest->getTitle();
        $desc = $manifest->getDescriptions();
        $entry->description = $desc[0];
        $entry->mediaType = \Kaltura\Client\Enum\MediaType::VIDEO;

        return $entry;
    }
}
