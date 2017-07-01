<?php

require_once 'simple_html_dom.php'; //import parcer XML

class Xml{

  private $xml;

  function __construct($file,$structure){
    $this->structure = $structure;
    $this->file = $file;
    $this->init();
  }

  private function init(){
    $this->xml = new simple_html_dom();
    $this->xml->load_file($this->file);
  }

  public function import(){
    foreach($this->xml->find('products') as $row){
      $product = new Product();
      $product->title = $row->title;
      $product->description = $row->description;
      $product->img = $row->img;
      $product->save();
    }
  }

  public function export(){
    foreach($this->xml->find('h1') as $element)
           echo $element->plaintext . '<br>';
  }

}
 ?>
