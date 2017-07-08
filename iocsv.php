<?php
if (!defined('_PS_VERSION_'))
{
  exit;
}

class Iocsv extends Module
{

  private $collection;

  public function __construct()
  {
    $this->name = 'iocsv';
    $this->tab = 'front_office_features';
    $this->version = '1.0.0';
    $this->author = 'Faissal Lachheb';
    $this->need_instance = 0;
    $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    $this->bootstrap = true;

    parent::__construct();

    $this->displayName = $this->l('IO csv');
    $this->description = $this->l('Import Export csv');

    $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

    if (!Configuration::get('IO_CSV'))
      $this->warning = $this->l('No name provided');
  }

  public function install()
  {
    if (Shop::isFeatureActive())
      Shop::setContext(Shop::CONTEXT_ALL);

    if (!parent::install() ||
      !$this->registerHook('leftColumn') ||
      !$this->registerHook('header') ||
      !Configuration::updateValue('IO_CSV', 'my friend')
    )
      return false;

    return true;
  }

    public function uninstall()
    {
      if (!parent::uninstall() ||
        !Configuration::deleteByName('IO_CSV')
      )
        return false;

      return true;
    }

    public function getContent()
    {
        $output = null;



        if (Tools::isSubmit('submit'.$this->name))
        {
            $modele = strval(Tools::getValue('modele'));
            $mode = strval(Tools::getValue('mode'));
            if (!$modele || empty($modele) || !Validate::isGenericName($modele))
                $output .= $this->displayError($this->l('Invalid Configuration value'));
            else{
                Configuration::updateValue('modele', $modele);
                Configuration::updateValue('mode', $mode);
                if (Configuration::get('mode') == 'import')
                    $this->import(Configuration::get('modele'));
                if (Configuration::get('mode') == 'export')
                    $this->export(Configuration::get('modele'));
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }

        }
        return $output.$this->displayForm();
    }

    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Import Export .CSV'),
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('mode'),
                    'name' => 'mode',
                    'multiple' => false,
                    'options' => array(
                        'query' => array(
                            array('key' => 'import', 'name' => 'Import'),
                            array('key' => 'export', 'name' => 'Export'),
                        ),
                        'id' => 'key',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('modele'),
                    'name' => 'modele',
                    'multiple' => false,
                    'options' => array(
                        'query' => array(
                            array('key' => 'product', 'name' => 'Produits'),
                            array('key' => 'category', 'name' => 'Catégories'),
                            array('key' => 'commande', 'name' => 'Cammandes'),
                        ),
                        'id' => 'key',
                        'name' => 'name'
                    )
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        // Load current value
        $helper->fields_value['modele'] = Configuration::get('modele');
        $helper->fields_value['mode'] = Configuration::get('mode');

        return $helper->generateForm($fields_form);
    }

    public function export($modele){
      switch ($modele) {
        case 'product':
          $sql = new DbQuery();
          $sql->select('*');
          $sql->from('product');
          $this->collection = Db::getInstance()->executeS($sql);
          $this->putfile(__DIR__.'/files/product.csv');
          break;

        case 'category':
          $sql = new DbQuery();
          $sql->select('*');
          $sql->from('category');
          $this->collection = Db::getInstance()->executeS($sql);
          $this->putfile(__DIR__.'/files/category.csv');
          break;

        default:
          # code...
          break;
      }
    }

    public function import($modele){
      switch ($modele) {
        case 'product':


          $data = array_map('str_getcsv', file(__DIR__.'/files/from_prestashop/product.csv'));
          foreach ($data as $row) {
            foreach ($row as $value) {
              $value = explode(";", $value);

              $sql = new DbQuery();
              $sql->select('id_product');
              $sql->from('product');
              $sql->where('reference = '.$value[1]);
              $id_product = Db::getInstance()->executeS($sql);
              $product = new Product($id_product);
              $product->name = $value[0];
              $product->save();
            }
          }
          break;

        case 'category':
          $sql = new DbQuery();
          $sql->select('*');
          $sql->from('category');
          $this->collection = Db::getInstance()->executeS($sql);
          $this->putfile(__DIR__.'/files/category.csv');
          break;

        default:
          # code...
          break;
      }
    }

    public function putfile($url_file){
      $file = fopen($url_file, 'w');
      foreach ($this->collection as $row) {
        foreach ($row as $value) {
          fwrite($file, strval($value).";");
        }
        fwrite($file, "\n");
      }
      fclose($file);
    }
}
