<?php

use IntraLibrary\LibraryObject\Record;
/**
 * List item used for Moodle's file repository responses
 */
class intralibrary_list_item extends ArrayObject {

    /**
     * Create generate the "source" for a moodle file based on an intralibrary object
     *
     * @param \IntraLibrary\LibraryObject\Record $record
     * @return string
     */
    public static function create_source_from_object(\IntraLibrary\LibraryObject\Record $record) {
        $send_url = NULL;
        switch ($record->get('intralibraryType')) {
            case 'kaltura' :
                $url = repository_intralibrary_generate_kaltura_uri($record->get('packageId'), $record->get('title'));
                $send_url = $record->get('preview');
                break;
            case 'imscp' :
            case 'scorm1.2' :
            case 'scorm2004' :
                $url = $record->get('download') . '&manifest_type=original';
                break;
            default :
                $url = $record->get('preview');
        }

        return self::create_source($record->getId(), $record->get('title'), $url, $send_url);
    }

    /**
     *
     * @SuppressWarnings(PHPMD.ShortVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     *
     * @param integer $id
     * @param string $title
     * @param string $url
     * @param string $send_url
     */
    public static function create_source($id, $title, $url, $send_url = NULL) {
        return serialize(compact("id", "title", "url", "send_url"));
    }

    public function __construct(\IntraLibrary\LibraryObject\Record $record) {
        global $OUTPUT;

        $type = $record->get('intralibraryType');

        $isIMS = in_array($type, array(
                'scorm1.2',
                'scorm2004',
                'imscp'
        ));

        if ($isIMS) {
            $format     = 'application/zip';
            $fileExt    = 'zip';
        } else {
            $format     = (array) $record->get('format');
            $format     = $format[0];
            $fileExt    = intralibrary_get_file_extension($format);
        }

        $data = array(
                'title' => $record->get('title'),
                'thumbnail' => $this->_get_thumbnail($record, $format),
                'description' => $record->get('description'),
                'icon' => $OUTPUT->pix_url(file_mimetype_icon($format, 24))->out(FALSE),
                'date' => strtotime($record->get('created')),
                'datemodified' => strtotime($record->get('lastModified')),
                'mimetype' => $format,
                'fileext' => $fileExt,
                'source' => self::create_source_from_object($record),
                'type' => $type,
                'url' => $record->get('preview'),
                'author' => $this->_getAuthors($record->get('author'))
        );

        if ($size = trim($record->get('size'))) {
            $data['size'] = $size;
        }

        return parent::__construct($data);
    }

    private function _get_thumbnail(Record $record, $format) {
        global $OUTPUT;

        $thumbnail = $record->get('thumbnail');

        // try to use a supplied thumbnail, otherwise use a generic moodle copy
        return $thumbnail ? $thumbnail . '&thumbnail_size=small' : $OUTPUT->pix_url(file_mimetype_icon($format, 32))->out();
    }

     /**
      * Prase VCard to name
      * @param string VCard string
      * @return string name
      */
    private function _getAuthors($vcard) {
        $allnames ="";
        if (is_array($vcard)) {
            foreach ($vcard as $vc) {
                try {
                    $allnames .= " ".($this->_parseVCard($vc)).",";
                } catch (Exception $ex) {
                    //invalid vcard format
                }
            }
            return trim($allnames, ",");
        } else {
            return $this->_parseVCard($vcard);
        }
    }

    private function _parseVCard($vc) {
        $namePos = strpos($vc, "FN:");
        $name = substr($vc, $namePos + 3);
        $name = substr($name, 0, strpos($name, ":"));
        $name = substr($name, 0, strpos($name, "\n"));
        return $name;
    }
}
