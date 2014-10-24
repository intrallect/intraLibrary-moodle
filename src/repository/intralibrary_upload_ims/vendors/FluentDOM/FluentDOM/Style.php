<?php
/**
* FluentDOMStyle extends the FluentDOM class with a function to edit
* the style attribute of html tags
*
* @version $Id$
* @license http://www.opensource.org/licenses/mit-license.php The MIT License
* @copyright Copyright (c) 2009 Bastian Feder, Thomas Weinert
*
* @package FluentDOM
*/

/**
* include the parent class (FluentDOM)
*/
require_once(dirname(__FILE__).'/../FluentDOM.php');
/**
* include the css property helper classes (FluentDOM)
*/
require_once(dirname(__FILE__).'/../FluentDOM/Css.php');
require_once(dirname(__FILE__).'/../FluentDOM/Css/Properties.php');

/**
* Function to create a new FluentDOMStyleinstance and loads data into it if
* a valid $source is provided.
*
* @param mixed $source
* @param string $contentType optional, default value 'text/xml'
* @return object FluentDOMStyle
*/
function FluentDOMStyle($source = NULL, $contentType = 'text/xml') {
  $result = new FluentDOMStyle();
  if (isset($source)) {
    return $result->load($source, $contentType);
  } else {
    return $result;
  }
}

/**
* FluentDOMStyle extends the FluentDOM class with a function to edit
* the style attribute of html tags
*
* @property FluentDOMCss $css Access the style attribute
*
* @package FluentDOM
*/
class FluentDOMStyle extends FluentDOM {

  /**
  * Allow read access to "css" as a dynamic property.
  * Call inherited method for other properties.
  *
  * This allows to get/set css style properties using array syntax.
  *
  * @see FluentDOM::__get()
  * @param string $name
  * @param mixed $value
  */
  public function __get($name) {
    switch ($name) {
    case 'css' :
      return new FluentDOMCss($this, $this->attr('style'));
    }
    return parent::__get($name);
  }

  /**
  * Allow write access to "css" as a dynamic property.
  * Call inherited method for other properties.
  *
  * This allows to set css style properties using an array.
  *
  * @see FluentDOM::__get()
  * @param string $name
  * @param mixed $value
  */
  public function __set($name, $value) {
    switch ($name) {
    case 'css' :
      $this->css($value);
      return;
    }
    parent::__set($name, $value);
  }

  /**
  * get or set CSS values in style attributes
  *
  * @param string|array $property
  * @param NULL|string|object Closure $value
  * @return string|object FluentDOMStyle
  */
  public function css($property, $value = NULL) {
    if (is_string($property) && is_null($value)) {
      try {
        $firstNode = $this->_getContentElement($this->_array);
        $properties = new FluentDOMCssProperties($firstNode->getAttribute('style'));
        if (isset($properties[$property])) {
          return $properties[$property];
        }
      } catch (UnexpectedValueException $e) {
      }
      return NULL;
    } elseif (is_string($property)) {
      $propertyList = array($property => $value);
    } elseif (is_array($property) ||
              $property instanceOf Traversable) {
      $propertyList = $property;
    } else {
      throw new InvalidArgumentException('Invalid css property name argument type.');
    }
    //set list of properties to all elements
    foreach ($this->_array as $index => $node) {
      if ($node instanceof DOMElement) {
        $properties = new FluentDOMCssProperties($node->getAttribute('style'));
        foreach ($propertyList as $name => $value) {
          $properties[$name] = $properties->compileValue(
            $value, $node, $index, isset($properties[$name]) ? $properties[$name] : NULL
          );
        }
        if (count($properties) > 0) {
          $node->setAttribute('style', (string)$properties);
        } elseif ($node->hasAttribute('style')) {
          $node->removeAttribute('style');
        }
      }
    }
  }
}