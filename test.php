<?php include "loader.php"; /* loads all php-includes */?>
<html>
<head>
  <title>'modulekit' framework test</title>
  <?php print modulekit_include_js(); /* prints all js-includes */ ?>
  <?php print modulekit_include_css(); /* prints all css-includes */ ?>
</head>
<body>
<p>
This page demonstrates the use of the 'modulekit' framework. First initialize your project by running the following commands:
<pre>
git clone https://github.com/plepe/modulekit.git
mkdir modules/
</pre>

This clones the framework into the modulekit/-directory and the initializes the modules/-directory, where the submodules will be placed. This is the basic structure for your project:
<ul>
  <li>/<ul>
    <li>modulekit/ - the inc-library</li>
    <li>modules/ - submodules this project uses<ul>
      <li>form/ ... - as an example the form/-library</li>
      <li>lang/ ... - as an example the lang/-library</li>
    </ul>
    <li>inc/ - Sourcecode for this project</li>
    <li>modulekit.php - configuration of this module</li>
  </ul>
</ul>
<p>
Commands to use, to include submodules:<br>
<pre>
git submodule add git://github.com/plepe/Form.git modules/form
git add .gitmodules modules/form
git commit
</pre>

<p>
The modules should have the following structure:
<ul>
  <li>/ - All files in the root-directory are ignored, it may contain examples, licence information, ...
  <li>inc/ - The inc-directory contains all included source code of the submodule. They will be included when the according function in the framework is called. See example below.
  <li>modules/ - Submodules this module uses if it used standalone. You should provide these submodules in your project. See the submodule's READMEs for more information. Will not be included by the framework.
  <li>modulekit.php - configuration of this project/module</li>
</ul>
Therefore your project can be used as submodule for another project based on modulekit.

<p>
The modulekit.php:
<pre>
&lt;?php
$name="Name of this project/module";

// these modules should be loaded first
$depend=array("form", "lang");

// these files will be included in this order:
$include_php=array(
  "inc/file1.php",
  "inc/file2.php",
);
$include_js=array(
  "inc/a_js_file.js",
);
$include_css=array(
  "inc/a_css_file.css",
);
</pre>

<p>
Example source code:
<pre>
&lt;?php include "modulekit/loader.php"; /* loads all php-includes */?&gt;
&lt;html&gt;
  &lt;head&gt;
    &lt;title&gt;Framework Example&lt;/title&gt;
    &lt;?php modulekit_include_js(); /* prints all js-includes */ ?&gt;
    &lt;?php modulekit_include_css(); /* prints all css-includes */ ?&gt;
  &lt;/head&gt;
  &lt;body&gt;
  &lt;/body&gt;
&lt;/html&gt;
</pre>
</body>
</html>
