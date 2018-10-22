<?php

require_once "classes/SarehubEvent.php";

if (!defined('_PS_VERSION_'))
    exit;

class Sarehub extends Module
{
    public function __construct()
    {
        $this->name = 'sarehub';
        $this->tab = 'front_office_features';
        $this->author = 'SARE SA';
        $this->version = '1.0';
        $this->bootstrap = true;

        parent::__construct();
        $this->displayName = $this->l('SAREhub integration module');
        $this->description = $this->l('Adding SAREhub scripts');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        if (!parent::install() ||
            !$this->registerHook('header') ||
            !$this->registerHook('orderConfirmation')
        )
            return false;

        return true;
    }

    public function uninstall()
    {
        if (!$this->unregisterHook('header') ||
            !$this->unregisterHook('orderConfirmation')
        )
            return false;

        Configuration::deleteByName('SAREHUB_DOMAIN');
        return parent::uninstall();
    }

    public function getContent()
    {
        $output = '';

        // If form has been sent
        if (Tools::isSubmit('submit' . $this->name)) {
            Configuration::updateValue('SAREHUB_DOMAIN', Tools::getValue('SAREHUB_DOMAIN'));
            $output .= $this->displayConfirmation($this->l('Settings updated successfully'));
        }

        $output .= $this->renderForm();
        return $output;
    }

    public function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;

        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->submit_action = 'submit' . $this->name;

        $fields_forms = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('General settings'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Sarehub domain'),
                        'name' => 'SAREHUB_DOMAIN',
                        'size' => 20,
                        'required' => true,
                        'hint' => $this->l('Enter domain that was setup in SAREhub')
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save')
                )
            )
        );

        // Load current value
        $helper->fields_value['SAREHUB_DOMAIN'] = Configuration::get('SAREHUB_DOMAIN');

        return $helper->generateForm(array($fields_forms));
    }

    private function debug($object)
    {

        file_put_contents("/tmp/shop_" . date("Y-m-d") . ".log",
            PHP_EOL . date("Y-m-d H:i:s") . PHP_EOL .
            print_r($object, true),
            FILE_APPEND
        );

    }

    public function hookOrderConfirmation($params)
    {
        $this->debug(['hook' => 'hookOrderConfirmation']);
        $this->debug($this->CheckPage());
        $this->debug($params);

        $order = $params['order'];
        if (!empty($order)) {
            $event = new SarehubEvent($this->context->customer->id, $this->context->customer->email);
            $event->setCartPurchased($order->id_cart);
            return $this->genScript($event, "OrderConfirmation");
        }
        return "";
    }

    public function hookHeader($params)
    {
        $this->debug(['hook' => 'hookHeader']);
        $this->debug($this->CheckPage());
        $this->debug($params);
        $javascriptEvent = null;
        $event = new SarehubEvent($this->context->customer->id, $this->context->customer->email);

        switch ($this->CheckPage()) {
            case "ProductController":
                if ($id_product = (int)Tools::getValue('id_product')) {
                    $event
                        ->setType("tag")
                        ->setProduct(
                            $id_product,
                            (((isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])),
                            $this->context->country->iso_code,
                            $this->context->language->iso_code
                        );

                    $javascriptEvent =
                        "   document.addEventListener('click', function(e){" . PHP_EOL
                        . "     if(e.srcElement.classList.contains('add-to-cart')){" . PHP_EOL
                        . "         var product_input = document.getElementById('product_page_product_id');" . PHP_EOL
                        . "         var quantity_input = document.getElementById('quantity_wanted');" . PHP_EOL
                        . "         var execute_params = {'_userId': '" . $this->context->customer->id . "', '_email' : '" . $this->context->customer->email . "', '_cartadd' : {'country' : '" . $this->context->country->iso_code . "', 'language': '" . $this->context->language->iso_code . "', 'cart_id' : '" . $params['cart']->id . "', 'product_id' : product_input.value, 'quantity' : quantity_input.value}};" . PHP_EOL
                        . "         console.log(execute_params);" . PHP_EOL
                        . "         sareX_core.execute(10, execute_params);" . PHP_EOL
                        . "     }" . PHP_EOL
                        . "   });" . PHP_EOL;
                }
                break;
            case "CategoryController":
                if ($id_category = (int)Tools::getValue('id_category')) {
                    $event
                        ->setType("tag")
                        ->setCategory(
                            $id_category,
                            $this->context->country->iso_code,
                            $this->context->language->iso_code
                        );
                }
                break;
            case "CartController":
            case "OrderController":
                if (!empty($params['cart']->id)) {
                    $event->setCartRegistration($params['cart']->id);
                }
                break;
            case "OrderConfirmationController":
                return "";  //hookOrderConfirmation
            case "PageNotFoundController":
                return "";
            default:
                break;
        }
        return $this->genScript($event, $this->CheckPage(), $javascriptEvent);
    }

    private function CheckPage()
    {
        return !empty($this->context->controller) ? get_class($this->context->controller) : "";
    }

    private function genScript(SarehubEvent $event, $eventType = '', $javascriptEvent = null)
    {
        $sarehub_domain = Tools::safeOutput(Configuration::get('SAREHUB_DOMAIN'));
        if (!$sarehub_domain) {
            return;
        }
        $script = '<script type="text/javascript">' .
            PHP_EOL . '   (function (p){' .
            PHP_EOL . '   window[\'sareX_params\']=p;var s=document.createElement(\'script\');' .
            PHP_EOL . '   s.src=\'//x.sare25.com/libs/sarex4.min.js\';s.async=true;var t=document.getElementsByTagName(\'script\')[0];' .
            PHP_EOL . '   t.parentNode.insertBefore(s,t);' .
            PHP_EOL . '   })({' .
            PHP_EOL . '       domain : \'' . $sarehub_domain . '\'' .
            PHP_EOL . '   });';
        if ($params = $event->getEncodedParams()) {
            $script .=
                PHP_EOL . '   sareX_params.'.$event->getType().' = ' . $params . ';';
        }
        if (!empty($javascriptEvent)) {
            $script .= PHP_EOL . PHP_EOL. $javascriptEvent;
        }
        $script .=
            PHP_EOL . '   console.log(' . json_encode(['site' => $eventType, 'type'=>$event->getType(), 'data' => $event->getEncodedParams()]) . ');' .
            PHP_EOL . '  </script>';

        $this->debug($script);

        return $script;
    }

    private function genExecute($event)
    {
        $script = '';
        if (!empty($event)) {
            $script = '<script type="text/javascript">';
            $script .= PHP_EOL . '   var execute_params = ' . json_encode($event);
            $script .= PHP_EOL . '   console.log(execute_params, \'sareX_core.execute\');';
            $script .= PHP_EOL . '   sareX_core.execute(10, execute_params);';
            $script .= PHP_EOL . '  </script>';
        }

        $this->debug($script);

        return $script;
    }
}
