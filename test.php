<?php include "inc/loader.php"; /* loads all php-includes */?>
<html>
<head>
  <title>Framework Test</title>
  <?php framework_include_js(); /* prints all js-includes */ ?>
  <?php framework_include_css(); /* prints all css-includes */ ?>
</head>
<body>
<p>
This page demonstrates the use of the framework. Clone the submodules into the inc/-directory, e.g.:
<ul>
  <li>/<ul>
    <li>inc/<ul>
      <li>form/ ...</li>
      <li>lang/ ...</li>
    </ul>
  </ul>
</ul>
<p>
Commands to use to clone submodules:<br>
<pre>
git submodule add git://github.com/plepe/Form.git inc/form
git add .gitmodules inc/form
git commit
</pre>

<p>
The modules should have the following structure:
<ul>
  <li>/ - All files in the root-directory are ignored, it may contain examples, licence information, ...
  <li>inc/ - The inc-directory contains all source code of the submodule. They will be included when the according function in the framework is called. See example below.
  <li>lib/ - Functions which are necessary to use the submodule stand-alone, but might be included in several modules. See the submodule's READMEs for more information.
</ul>

<p>
Example source code:
<pre>
&lt;?php include "inc/loader.php"; /* loads all php-includes */?&gt;
&lt;html&gt;
  &lt;head&gt;
    &lt;title&gt;Framework Example&lt;/title&gt;
    &lt;?php framework_include_js(); /* prints all js-includes */ ?&gt;
    &lt;?php framework_include_css(); /* prints all css-includes */ ?&gt;
  &lt;/head&gt;
  &lt;body&gt;
  &lt;/body&gt;
&lt;/html&gt;
</body>
</html>
