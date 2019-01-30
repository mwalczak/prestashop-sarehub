<?php
/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 * @author    Mateusz Walczak
 * @copyright 2018-2019 SARE SA
 * @license   GNU General Public License version 2
 */

require_once "classes/SarehubEvent.php";

if (!defined('_PS_VERSION_')) {
    exit;
}

class Sarehub extends Module
{
    public function __construct()
    {
        $this->name = 'sarehub';
        $this->tab = 'front_office_features';
        $this->author = 'm.walczak@sare.pl';
        $this->version = '1.0.0';
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
        ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if (!$this->unregisterHook('header') ||
            !$this->unregisterHook('orderConfirmation')
        ) {
            return false;
        }

        Configuration::deleteByName('SAREHUB_DOMAIN');
        Configuration::deleteByName('SAREHUB_PUSH');
        Configuration::deleteByName('SAREHUB_TIME');
        Configuration::deleteByName('SAREHUB_LOG');
        return parent::uninstall();
    }

    public function getContent()
    {
        $output = '';

        // If form has been sent
        if (Tools::isSubmit('submit' . $this->name)) {
            Configuration::updateValue('SAREHUB_DOMAIN', Tools::getValue('SAREHUB_DOMAIN'));
            Configuration::updateValue('SAREHUB_PUSH', Tools::getValue('SAREHUB_PUSH'));
            Configuration::updateValue('SAREHUB_TIME', Tools::getValue('SAREHUB_TIME'));
            Configuration::updateValue('SAREHUB_LOG', Tools::getValue('SAREHUB_LOG'));
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
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Sarehub push'),
                        'name' => 'SAREHUB_PUSH',
                        'options' => array(
                            'query' => array(
                                array(
                                    'value' => '',       // The value of the 'value' attribute of the <option> tag.
                                    'name' => 'Disable'    // The value of the text content of the  <option> tag.
                                ),
                                array(
                                    'value' => 'popup',
                                    'name' => 'Popup'
                                ),
                                array(
                                    'value' => 'popover',
                                    'name' => 'Popover'
                                )
                            ),
                            'id' => 'value',
                            'name' => 'name'
                        ),
                        'required' => true,
                        'hint' => $this->l('Enable SAREhub native web push')
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Send time events'),
                        'name' => 'SAREHUB_TIME',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'time_on',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'time_off',
                                'value' => 0,
                                'label' => $this->l('No')
                            )
                        ),
                        'desc' => $this->l('Check to send time events')
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Log events to console'),
                        'name' => 'SAREHUB_LOG',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'log_on',
                                'value' => 1,
                                'label' => $this->l('Yes')
                            ),
                            array(
                                'id' => 'log_off',
                                'value' => 0,
                                'label' => $this->l('No')
                            )
                        ),
                        'desc' => $this->l('Check to log events to browser console')
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save')
                )
            )
        );

        // Load current value
        $helper->fields_value['SAREHUB_DOMAIN'] = Configuration::get('SAREHUB_DOMAIN');
        $helper->fields_value['SAREHUB_PUSH'] = Configuration::get('SAREHUB_PUSH');
        $helper->fields_value['SAREHUB_TIME'] = Configuration::get('SAREHUB_TIME');
        $helper->fields_value['SAREHUB_LOG'] = Configuration::get('SAREHUB_LOG');

        return $helper->generateForm(array($fields_forms));
    }

    private function createSarehubEvent()
    {
        $event = new SarehubEvent(Tools::safeOutput(Configuration::get('SAREHUB_DOMAIN')), $this->context->customer->id, $this->context->customer->email);
        $event->setPushNotifications(Tools::safeOutput(Configuration::get('SAREHUB_PUSH')));
        $event->setTimeEvents(Tools::safeOutput(Configuration::get('SAREHUB_TIME')));
        $event->setLogging(Tools::safeOutput(Configuration::get('SAREHUB_LOG')));
        return $event;
    }

    public function hookOrderConfirmation($params)
    {
        $order = $params['order'];
        if (!empty($order)) {
            $event = $this->createSarehubEvent();
            $event->setCartPurchased($order->id_cart);
            return $event->getJavaScript("OrderConfirmation");
        }
        return "";
    }

    public function hookHeader($params)
    {
        $event = $this->createSarehubEvent();
        switch ($this->getPage()) {
            case "ProductController":
                if ($id_product = (int)Tools::getValue('id_product')) {
                    $event
                        ->setType("tag")
                        ->setProduct(
                            $id_product,
                            (((isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])),
                            $this->context->country->iso_code,
                            $this->context->language->iso_code
                        )
                        ->setJSEvent("productCartAdd", $this->context->country->iso_code, $this->context->language->iso_code, $params['cart']->id);
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
                        )
                        ->setJSEvent("productCartAdd", $this->context->country->iso_code, $this->context->language->iso_code, $params['cart']->id);
                }
                break;
            case "CartController":
                if (!empty($params['cart']->id)) {
                    $event->setJSEvent("productCartDel", $this->context->country->iso_code, $this->context->language->iso_code, $params['cart']->id)
                        ->setJSEvent("productCartQuantity", $this->context->country->iso_code, $this->context->language->iso_code, $params['cart']->id);
                }
                break;
            case "OrderController":
                $event->setCartRegistration($params['cart']->id)
                    ->setJSEvent("order", $this->context->country->iso_code, $this->context->language->iso_code, $params['cart']->id);
                break;
            case "OrderConfirmationController":
                return "";  //hookOrderConfirmation
            case "PageNotFoundController":
                return "";
            default:
                $event->setJSEvent("productCartAdd", $this->context->country->iso_code, $this->context->language->iso_code, $params['cart']->id);
                break;
        }
        return $event->getJavaScript($this->getPage());
    }

    private function getPage()
    {
        return !empty($this->context->controller) ? get_class($this->context->controller) : "";
    }
}
