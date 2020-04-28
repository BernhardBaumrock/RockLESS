# RockLESS

Support forum thread: https://processwire.com/talk/topic/20658-rockless-php-less-parsing-helper-module/

This is a helper module for parsing LESS and returning the resulting CSS file or content. It uses the wikimedia less parser. See the docs here: https://github.com/wikimedia/less.php

Beside the methods provided by RockLESS you can call any of the underlying methods of the less parser. You can retrieve an instance of the parser easily:

```php
$less = $modules->get('RockLESS');
$options = []; // optional
$parser = $less->getParser($options);
$parser->parseFile( '/var/www/mysite/bootstrap.less', '/mysite/' );
$css = $parser->getCss();
```

## Parsing LESS files

Parsing a single file can be done via `$less->parse()` or its alias `$less->parseFile()`. But you can also parse multiple LESS files into one single CSS file:

```php
$less = $modules->get('RockLESS');
$dir = $config->paths->assets;
$options = []; // optional, see docs at saveCSS method
$less->parseFiles([
  $dir."foo.less",
  $dir."bar.less",
], $options);
```

This will always return the parsed CSS content ready for writing it to a file or to an inline `<style>` tag. This method will ALWAYS parse the files you throw into it, no matter if anything has changed or not. If you want to cache the results you can use the `saveCSS` method:

```php
$less = $modules->get('RockLESS');
$dir = $config->paths->assets;
$options = []; // optional
$css = $config->paths->templates . "main.css";
$result = $less->saveCSS($css, [
  $dir."foo.less",
  $dir."bar.less",
], $options);
```

The method will return a `WireData` object holding the path and the url of the generated CSS file and the parsed CSS content:

![img](https://i.imgur.com/7m1Qv2p.png)

The options array can hold the following settings:

* `parserOptions`: Options for the less parser.
* `monitorFiles`: Array of files to monitor for changes.
* `monitorDirs`: Array of directories to monitor for changes.
* `monitorDirDepth`: Depth of directories to monitor for changes.

Example:

```php
$less = $modules->get('RockLESS');
$dir = $config->paths->assets;
$css = $config->paths->templates . "main.css";
$result = $less->saveCSS($css, [
  $dir."foo.less",
  $dir."bar.less",
], [
  'parserOptions' => ['compress' => true],
  'monitorFiles' => [
    $config->paths->assets."included-by-foo.less",
    $config->paths->assets."included-by-bar.less",
  ],
  'monitorDirs' => $config->paths->templates,
  'monitorDirDepth' => 3, // default is 0
]);
```

## Use LESS files in your modules

Using RockLESS it is very easy to write your module's styles as LESS and not CSS. RockLESS has a `addToConfig()` method that will automatically parse a LESS file and add it to the `$config->styles` array. The css file will only be generated when it does not exist or the less file has changed:

```php
// in init() method
$url = $this->config->urls($this);
$file = $url.$this->className.".less";
$less = $this->modules->get('RockLESS'); /** @var RockLESS $less */
if($less) $less->addToConfig($file);
else $this->config->styles->add("$file.css");
```

## Sending PHP data/variables to LESS

Sometimes you might need to send PHP variables to your LESS files. For example you could want to create a stylesheet based on some user input.

```php
$less = $modules->get('RockLESS');
$less->vars = [
  'foo' => 'bar',
];
$css = $less->getCSS($config->paths->templates . 'less/theme.less')->cssUrl;
echo "<link rel='stylesheet' type='text/css' href='$css'>";
```

You could also define global variables in `config.php`:

```php
$config->lessVars = [
  'padding' => '10px';
  'margin' => '10px';
];
```
```php
$less = $modules->get('RockLESS');
$less->vars = $config->lessVars;
// $less->parse(...) or $less->parseFiles(...) etc
```
