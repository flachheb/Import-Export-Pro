<?php

$mode = 'import';
$format = 'xml';
$structure = 'product';
$file = 'http://ergonov.com';


switch ($format) {
  case 'xml':
    require_once 'Xml.php';
    $xml = new Xml($file, $structure);
    if ($mode=='import')
      $xml->import();
    if ($mode=='export')
      $xml->export();
    break;

  case 'json':
    echo "mode non pas encore traité";
    break;

  case 'csv':
    echo "mode non pas encore traité";
    break;

  case 'xsl':
    echo "mode non pas encore traité";
    break;

  case 'xsls':
    echo "mode non pas encore traité";
    break;

  default:
    # code...
    break;
}




 ?>
