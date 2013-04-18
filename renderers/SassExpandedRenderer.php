<?php
/* SVN FILE: $Id$ */
/**
 * SassExpandedRenderer class file.
 * @author      Chris Yates <chris.l.yates@gmail.com>
 * @copyright   Copyright (c) 2010 PBM Web Development
 * @license      http://phamlp.googlecode.com/files/license.txt
 * @package      PHamlP
 * @subpackage  Sass.renderers
 */

require_once('SassCompactRenderer.php');

/**
 * SassExpandedRenderer class.
 * Expanded is the typical human-made CSS style, with each property and rule
 * taking up one line. Properties are indented within the rules, but the rules
 * are not indented in any special way.
 * @package      PHamlP
 * @subpackage  Sass.renderers
 */
class SassExpandedRenderer extends SassRenderer {
  const MAXCHARS = 80;

  protected function stripExtraSpaces($str) {
    return str_replace("\n" . self::INDENT . "\n", "\n\n", $str);
  }

  /**
   * Renders the brace between the selectors and the properties
   * @return string the brace between the selectors and the properties
   */
  protected function between() {
    return " {\n";
  }

  /**
   * Renders the brace at the end of the rule
   * @return string the brace between the rule and its properties
   */
  protected function end() {
    return "\n}\n\n";
  }

  /**
   * Returns the indent string for the node
   * @param SassNode the node to return the indent string for
   * @return string the indent string for this SassNode
   */
  protected function getIndent($node) {
    return str_repeat(self::INDENT, $node->level);
  }

  /**
   * Renders a comment.
   * @param SassNode the node being rendered
   * @return string the rendered commnt
   */
  public function renderComment($node) {
    $indent = $this->getIndent($node);
    $lines = explode("\n", $node->value);
    foreach ($lines as &$line) {
      $line = trim($line);
    }
    return "/*\n * ".join("\n * ", $lines)."\n */\n";
  }

  /**
   * Renders a directive.
   * @param SassNode the node being rendered
   * @param array properties of the directive
   * @return string the rendered directive
   */
  public function renderDirective($node, $properties) {
    $properties = self::INDENT . str_replace("\n", "\n".self::INDENT,
      $this->renderProperties($node, $properties));
    return $this->stripExtraSpaces($node->directive . $this->between() . $properties . $this->end());
  }

  /**
   * Renders properties.
   * @param SassNode the node being rendered
   * @param array properties to render
   * @return string the rendered properties
   */
  public function renderProperties($node, $properties) {
    return join("\n", $properties);
  }

  /**
   * Renders a property.
   * @param SassNode the node being rendered
   * @return string the rendered property
   */
  public function renderProperty($node) {
    $node->important = $node->important ? ' !important' : '';
    return "{$node->name}: {$node->value}{$node->important};";
  }

  /**
   * Renders a rule.
   * @param SassNode the node being rendered
   * @param array rule properties
   * @param string rendered rules
   * @return string the rendered directive
   */
  public function renderRule($node, $properties, $rules) {
    $selectors = $this->renderSelectors($node);
    $rule = "";
    if ($selectors && !empty($properties)) {
      $properties = self::INDENT . str_replace("\n", "\n".self::INDENT,
        $this->renderProperties($node, $properties));
      $rule = $this->stripExtraSpaces($selectors . $this->between() . $properties . $this->end());
    }
    return $rule . $rules;
  }

  /**
   * Renders the rule's selectors
   * @param SassNode the node being rendered
   * @return string the rendered selectors
   */
  protected function renderSelectors($node) {
    $selectors = array(array());
    $strsep = ", ";
    $linesep = ",\n";
    foreach ($node->selectors as $selector) {
      $s = &$selectors[sizeof($selectors) - 1];
      if (!empty($selector) && !$node->isPlaceholder($selector)) {
        if (empty($s) ||
            strlen(join($strsep, $s) . $strsep . $selector) < self::MAXCHARS) {
          $s[] = $selector;
        } else {
          $selectors[] = array($selector);
        }
      }
    }

    return join($linesep, array_map(function($s) use ($strsep) { return join($strsep, $s); }, $selectors));
  }
}