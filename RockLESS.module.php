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
  public $addTimestamp;

  public static function getModuleInfo() {
    return [
      'title' => 'RockLESS',
      'version' => '1.0.3',
      'summary' => 'Module to parse LESS files via PHP.',
      'autoload' => false,
      'icon' => 'css3',
    ];
  }

  public function init() {
    // load less.php if it is not already loaded
    // a simple require_once does not work properly
    if(!class_exists('Less_Parser')) require_once(__DIR__."/vendor/autoload.php");
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
    if($this->vars) $parser->ModifyVars($this->vars);
    $css = $parser->getCss();

    // now save the CSS file to the file system and return it
    file_put_contents($cssfile, $css);
    $obj->css = $css;
    return $obj;
  }

  /**
   * Parse less to css
   * @return string
   */
  public function parse($less, $options = null) {
    $parser = new \Less_Parser($options);
    $parser->parse($less);
    if($this->vars) $parser->ModifyVars($this->vars);
    $css = $parser->getCss();
    return $css;
  }

  /**
   * Parse a single LESS file
   * Alias of parse()
   */
  public function parseFile($file, $options = []) {
    return $this->parseFiles([$file], $options);
  }

  /**
   * Parse multiple LESS files
   */
  public function parseFiles($files, $options = []) {
    $parser = $this->getParser($options);
    foreach($files as $file) $parser->parseFile($file);
    if($this->vars) $parser->ModifyVars($this->vars);
    return $parser->getCss();
  }

  /**
   * Get an instance of the parser
   * @return \Less_Parser
   */
  public function getParser($options = null) {
    return new \Less_Parser($options);
  }

  /**
   * Parse less files and save to single CSS file
   * This will only recreate the file if the LESS file is newer than the
   * resulting CSS file. If no options are set it will not monitor any
   * other folders or subdirectories for changes.
   *
   * Usage:
   * $less->saveCSS(__DIR__."/FooFile.css", __DIR__."/FooFile.less");
   *
   * @param string $file
   * @param array $less
   * @param array $options
   * @return object
   */
  public function saveCSS($file, $less, $options = []) {
    $config = $this->config;

    // prepare result
    $result = $this->wire(new WireData()); /** @var WireData $result */
    $result->path = $file;
    $result->url = str_replace($config->paths->root, $config->urls->root, $file);
    $old = is_file($file) ? filemtime($file) : 0;
    $new = 0;
    $result->css = $old ? file_get_contents($file) : '';

    // check less files for updates
    if(is_string($less)) $less = [$less];
    foreach($less as $f) $new = $this->max($f, $new);

    // check monitorFiles for updates
    $monitorFiles = array_key_exists("monitorFiles", $options)
      ? $options['monitorFiles'] : [];
    foreach($monitorFiles as $f) $new = $this->max($f, $new);

    // check monitorDirs for updates
    $monitorDirs = array_key_exists("monitorDirs", $options)
      ? $options['monitorDirs'] : [];
    $monitorDirDepth = array_key_exists("monitorDirDepth", $options)
      ? $options['monitorDirDepth'] : 0;
    foreach($monitorDirs as $dir) {
      $opt = ['recursive' => $monitorDirDepth, 'extensions' => ['less']];
      foreach($this->files->find($dir, $opt) as $f) $new = $this->max($f, $new);
    }

    // no change, return!
    if($new <= $old) return $result;

    // parse all less files
    $parserOptions = array_key_exists("parserOptions", $options)
      ? $options['parserOptions'] : [];
    $css = $this->parsefiles($less, $parserOptions);
    $this->files->filePutContents($file, $css);
    $result->css = $css;

    return $result;
  }

  /**
   * Return max timestamp
   */
  public function max($file, $time) {
    if(!is_file($file)) return $time;
    return max(filemtime($file), $time);
  }

  /**
   * Parse given less file and add to pw config styles array
   * @return void
   */
  public function addToConfig($file, $prepend = false) {
    $url = str_replace($this->config->paths->root, $this->config->urls->root, $file);
    $path = $this->config->paths->root.ltrim($url, "/\\");
    if(!is_file($path)) return;
    $obj = $this->getCSS($path);
    $m = "?m=".filemtime($path);
    if($prepend) $this->config->styles->prepend($obj->cssUrl.$m);
    else $this->config->styles->append($obj->cssUrl.$m);
  }

  /**
   * Get relative url from given path
   *
   * @param string $path
   * @return string
   */
  public function getUrl($path) {
    $path = Paths::normalizeSeparators($path);
    $url = str_replace(
      $this->config->paths->root,
      $this->config->urls->root,
      $path
    );
    if($this->addTimestamp) "?t=" . filemtime($path);
    return $url;
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
