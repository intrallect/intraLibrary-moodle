<?php
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
