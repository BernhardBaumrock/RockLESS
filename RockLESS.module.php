<?php namespace ProcessWire;
/**
 * RockLESS Module
 *
 * @author Bernhard Baumrock, 09.01.2019
 * @license Licensed under MIT
 * @link https://www.baumrock.com
 */
class RockLESS extends WireData implements Module {

  public $vars;

  public static function getModuleInfo() {
    return [
      'title' => 'RockLESS',
      'version' => '0.0.1',
      'summary' => 'Module to parse LESS files via PHP.',
      'autoload' => false,
      'icon' => 'css3',
    ];
  }

  public function init() {
    // load less.php if it is not already loaded
    // a simple require_once does not work properly
    if(!class_exists('Less_Parser')) require_once("vendor/autoload.php");
  }

  /**
   * Get CSS from a LESS file.
   *
   * @param string $lessfile LESS file to parse.
   * @param string $cssfile CSS file to generate.
   * @param string $url Url option of less.php
   * @param array $options Options for less.php
   * @param array $files Array of files to monitor for changes.
   * @return object
   */
  public function getCSS($lessfile, $cssfile = null, $url = null, $options = null, $files = null) {
    if(!is_file($lessfile)) throw new WireException("LESS file not found!");

    // if no cssfile is specified we take the lessfile and append .css to it
    if(!$cssfile) $cssfile = "$lessfile.css";

    // prepare the return object
    $obj = (object)[];
    $obj->lessPath = $lessfile;
    $obj->cssPath = $cssfile;
    $obj->lessUrl = $this->getUrl($lessfile);
    $obj->cssUrl = $this->getUrl($cssfile);
    $obj->css = '';

    // if the less file is already a css file return it directly
    $info = pathinfo($lessfile);
    if($info['extension'] == 'css') return $obj;
    
    $modified_css = 0;
    $modified_less = filemtime($lessfile);

    // get modified date of css file
    if(is_file($cssfile)) {
      // there is already a css file available
      $modified_css = filemtime($cssfile);
    }

    // if files are set to monitor for changes we get the last modified date
    // this is only done for superusers for performance reasons
    if($this->user->isSuperuser()) {
      if(!$files) $files = $this->files->find(dirname($lessfile), ['extensions'=>['less']]);
      foreach($files as $file) {
        $mod = filemtime($file);
        if($mod > $modified_less) $modified_less = $mod;
      }
    }
    
    // if the css file is newer, we return it directly
    if($modified_css >= $modified_less) {
      $obj->css = file_get_contents($cssfile);
      return $obj;
    }

    // otherwise we need to parse the LESS
    $parser = new \Less_Parser($options);
    $parser->parseFile($lessfile, $url);
    if($this->vars) {
      // send custom php variables to less file
      $parser->ModifyVars($this->vars);
    }
    $css = $parser->getCss();

    // now save the CSS file to the file system and return it
    file_put_contents($cssfile, $css);
    $obj->css = $css;
    return $obj;
  }

  /**
   * Get relative url from given path
   *
   * @param string $path
   * @return string
   */
  public function getUrl($path) {
    return str_replace($this->config->paths->root, '/', $path);
  }

  /**
   * Debug Info
   * @return array
   */
  public function __debugInfo() {
    return [
      'vars' => $this->vars,
    ];
  }
}
