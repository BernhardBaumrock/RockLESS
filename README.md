# RockLESS

This is a helper module for parsing LESS and returning the resulting CSS file or content. It checks the LESS file for changes and recreates the CSS file only when needed. See the comments in the module file for options.

This is an exemplary return of the getCSS() method:

![example object](https://i.imgur.com/KVzY0uC.png)

And this is how you can use a less file as theme in your HTML markup:

```php
<link rel="stylesheet" type="text/css" href="<?= $modules->get('RockLESS')->getCSS($config->paths->templates . 'less/theme.less')->cssUrl; ?>">
```

Send php variables to LESS:

```php
$less = $modules->get('RockLESS');
$less->vars = [
  'foo' => 'bar',
];
$css = $less->getCSS($config->paths->templates . 'less/theme.less')->cssUrl;
echo "<link rel='stylesheet' type='text/css' href='$css'>";
```
