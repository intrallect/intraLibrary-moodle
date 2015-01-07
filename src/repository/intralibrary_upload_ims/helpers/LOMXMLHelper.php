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
 * Helper for reading manifest XML files
 *
 * @package    repository_intralibrary_upload_ims
 * @category   repository
 * @copyright  2015 Intrallect
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

class LOMXMLHelper {

    public static $_namespaces = array(
            'lom' => 'http://ltsc.ieee.org/xsd/LOM'
    );

    /**
     *
     * @var FluentDOM
     */
    private $fDOM;

    public function __construct($source) {
        $this->fDOM = FluentDOM($source);
        $this->fDOM->namespaces(self::$_namespaces);
    }

    /**
     *
     * @return FluentDOM
     */
    public function getFluentDOM() {
        return $this->fDOM;
    }

    /**
     * Ensure a node exists at a particular xpath location.
     * If more than
     * one node matches the xpath selector, returns the first one
     *
     * @param string $xpath
     * @param array $nodeAttributes
     * @return FluentDOM
     */
    public function ensureNode($xpath, $nodeAttributes = array()) {
        // iterate through the segment pieces building the xpath starting from the beginning
        $segments = explode('/', $xpath);
        $numSegments = count($segments);
        for ($i = 1; $i <= $numSegments; $i++) {
            // ensure that each segment exists
            $nodeName = $segments[$i - 1];
            $subPath = implode('/', array_slice($segments, 0, $i));
            if ($this->fDOM->find($subPath)->length == 0) {
                // if it doesn't, add it to it's parent
                $parentPath = implode('/', array_slice($segments, 0, $i - 1));
                $parentNode = $this->fDOM->find($parentPath)->first();
                $parentNode->append($this->createElement($nodeName));
            }
        }

        // then grab the first node
        $node = $this->fDOM->find($xpath)->first();

        if ($nodeAttributes && is_array($nodeAttributes)) {
            $node->attr($nodeAttributes);
        }

        return $node;
    }

    /**
     * Create DOMElement helper
     *
     * @param string $nodeName
     * @param array $attributes
     * @param array $children
     * @return DOMNode
     */
    public function createElement($nodeName, $children = array(), $attributes = array()) {
        $nsInfo = explode(':', $nodeName);
        if (count($nsInfo) > 1 && isset(self::$_namespaces[$nsInfo[0]])) {
            $elem = $this->fDOM->document->createElementNS(self::$_namespaces[$nsInfo[0]], $nodeName);
        } else {
            $elem = $this->fDOM->document->createElement($nodeName);
        }

        foreach ($attributes as $name => $value) {
            $elem->setAttribute($name, $value);
        }

        foreach (((array) $children) as $child) {
            if (is_string($child)) {
                $child = $this->fDOM->document->createTextNode($child);
            } else if (!($child instanceof DOMNode)) {
                throw new InvalidArgumentException('Expecting DOMNode or string for createElement children');
            }

            $elem->appendChild($child);
        }

        return $elem;
    }

    public function createTaxonNode($refId, $name, $nameAttrs = array()) {
        return $this->createElement('lom:taxon',
                array(
                        $this->createElement('lom:id', $refId),
                        $this->createElement('lom:entry',
                                array(
                                        $this->createElement('lom:string', $name, $nameAttrs)
                                ))
                ));
    }
}
