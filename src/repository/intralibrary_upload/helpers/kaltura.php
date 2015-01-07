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
 * Kaltura Helper
 *
 * @package    repository_intralibrary_upload
 * @category   repository
 * @copyright  2015 Intrallect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

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
