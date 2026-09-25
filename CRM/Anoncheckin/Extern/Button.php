<?php

/**
 * Button (initial use case is for 'extra buttons' on Fatal)
 */
class CRM_Anoncheckin_Extern_Button {

  /**
   * @var string User-facing label.
   */
  var $label;

  /**
   * @var string Absolute url for destination.
   */
  var $url;

  /**
   * @var string Class name(s) to be applied to the button.
   */
  var $class;
  
  /**
   * @var Int Weight for sorting.
   */
  var $weight;

  public function __construct(string $label, string $url, string $class, int $weight = 1) {
    $this->label = $label;
    $this->url = $url;
    $this->class = $class;
    $this->weight = $weight;
  }
  
  /**
   * Return all properties as an associative array.
   * @return array
   */
  public function toArray() : array {
    $ret = [];
    $props = ['label', 'url', 'class', 'weight'];
    foreach ($props as $prop) {
      $ret[$prop] = $this->$prop;
    }
    return $ret;
  }
}
