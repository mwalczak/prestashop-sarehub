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

 *  @author    Mateusz Walczak

 *  @copyright 2018-2019 SARE SA

 *  @license   GNU General Public License version 2

 */

class SarehubEvent
{
    private $domain;
    private $userId;
    private $email;
    private $id;
    private $type = "event";
    private $params = array();
    private $JSEvents = array();
    private $pushNotifications = '';
    private $timeEvents = false;
    private $logging = false;

    public function __construct($domain, $userId = null, $email = null)
    {
        $this->domain = $domain;
        $this->userId = $userId;
        $this->email = $email;
    }

    public function setPushNotifications($pushNotifications)
    {
        $this->pushNotifications = $pushNotifications;
    }
    public function setTimeEvents($timeEvents)
    {
        $this->timeEvents = (bool) $timeEvents;
    }
    public function setLogging($logging)
    {
        $this->logging = (bool) $logging;
    }

    public function getEncodedParams()
    {
        if (empty($this->params)) {
            return "";
        } elseif ($this->type == "event") {
            return json_encode(array(
                    'id' => $this->id,
                    'params' => array_merge(
                        array(
                            '_userId' => $this->userId,
                            '_email' => $this->email
                        ),
                        $this->params
                    )
                )
            );
        } else {
            return json_encode(array_merge(array(
                '_userId' => $this->userId,
                '_email' => $this->email
            ), $this->params));
        }
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }


    public function setUserId($userId = null)
    {
        $this->userId = $userId;
        return $this;
    }

    public function setEmail($email = null)
    {
        $this->email = $email;
        return $this;
    }

    public function setProduct($id = "", $url = "", $country = "", $language = "")
    {
        $this->params['_product'] = array(
            'id' => $id,
            'url' => $url,
            'country' => $country,
            'language' => $language
        );
        return $this;
    }

    public function setCategory($id = "", $country = "", $language = "")
    {
        $this->params['_category'] = array(
            'id' => $id,
            'country' => $country,
            'language' => $language
        );
        return $this;
    }

    public function setCartInitialized($cartId)
    {
        $this->id = 10;
        $this->params['_cartinitialized'] = array(
            'cart_id' => $cartId
        );
        return $this;
    }

    public function setCartRegistration($cartId)
    {
        $this->id = 10;
        $this->params['_cartregistration'] = array(
            'cart_id' => $cartId
        );
        return $this;
    }

    public function setCartPurchased($cartId)
    {
        $this->id = 10;
        $this->params['_cartpurchased'] = array(
            'cart_id' => $cartId
        );
        return $this;
    }

    public function setCartQuantity($cartId, $productId, $quantity, $country, $language)
    {
        $this->params['_cartquantity'] = array(
            'cart_id' => $cartId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'country' => $country,
            'language' => $language
        );
        return $this;
    }

    public function setJSEvent($eventType, $country, $language, $cartId)
    {
        switch ($eventType) {
            case "productCartAdd":
                $this->JSEvents[] =
                    "   document.addEventListener('click', function(e){" . PHP_EOL
                    . "     if(e.srcElement.classList.contains('add-to-cart')){" . PHP_EOL
                    . "         var product_input = document.getElementById('product_page_product_id');" . PHP_EOL
                    . "         var quantity_input = document.getElementById('quantity_wanted');" . PHP_EOL
                    . "         var execute_params = {'_userId': '" . $this->userId . "', '_email' : '" . $this->email . "', '_cartadd' : {'country' : '" . $country . "', 'language': '" . $language . "', 'cart_id' : '" . $cartId . "', 'product_id' : product_input.value, 'quantity' : quantity_input.value}};" . PHP_EOL
                    . ($this->logging ? "         console.log(execute_params);" . PHP_EOL : '')
                    . "         sareX_core.execute(10, execute_params);" . PHP_EOL
                    . "     }" . PHP_EOL
                    . "   });" . PHP_EOL;
                break;
            case "productCartDel":
                $this->JSEvents[] =
                    "   document.addEventListener('click', function(e){" . PHP_EOL
                    . "     if(e.srcElement.parentElement.classList.contains('remove-from-cart')){" . PHP_EOL
                    . "         var product_id = e.srcElement.parentElement.getAttribute('data-id-product');" . PHP_EOL
                    . "         var execute_params = {'_userId': '" . $this->userId . "', '_email' : '" . $this->email . "', '_cartdel' : {'country' : '" . $country . "', 'language': '" . $language . "', 'cart_id' : '" . $cartId . "', 'product_id' : product_id, 'quantity' : 0}};" . PHP_EOL
                    . ($this->logging ? "         console.log(execute_params);" . PHP_EOL : '')
                    . "         sareX_core.execute(10, execute_params);" . PHP_EOL
                    . "     }" . PHP_EOL
                    . "   });" . PHP_EOL;
                break;
            case "productCartQuantity":
                $this->JSEvents[] =
                    "   document.addEventListener('change', function(e){" . PHP_EOL
                    . "   if(e.srcElement.classList.contains('js-cart-line-product-quantity')){" . PHP_EOL
                    . "     var product_id = e.srcElement.getAttribute('data-product-id');" . PHP_EOL
                    . "     var product_quantity = e.srcElement.value;" . PHP_EOL
                    . "     var execute_params = {'_userId': '" . $this->userId . "', '_email' : '" . $this->email . "', '_cartquantity' : {'country' : '" . $country . "', 'language': '" . $language . "', 'cart_id' : '" . $cartId . "', 'product_id' : product_id, 'quantity' : product_quantity}};" . PHP_EOL
                    . ($this->logging ? "     console.log(execute_params);" . PHP_EOL : '')
                    . "     sareX_core.execute(10, execute_params);" . PHP_EOL
                    . "   }" . PHP_EOL
                    . " });" . PHP_EOL
                    . "   document.addEventListener('click', function(e){" . PHP_EOL
                    . "     if(e.srcElement.parentElement.classList.contains('js-increase-product-quantity') || e.srcElement.parentElement.classList.contains('js-decrease-product-quantity')){" . PHP_EOL
                    . "         var product_group_node = e.srcElement.parentElement.parentElement.parentElement;" . PHP_EOL
                    . "         for (var i = 0; i < product_group_node.childNodes.length; i++) {" . PHP_EOL
                    . "             if (product_group_node.childNodes[i].classList.contains('js-cart-line-product-quantity')) {" . PHP_EOL
                    . "                 var product_id = product_group_node.childNodes[i].getAttribute('data-product-id');" . PHP_EOL
                    . "                 var product_quantity = product_group_node.childNodes[i].value;" . PHP_EOL
                    . "                 var execute_params = {'_userId': '" . $this->userId . "', '_email' : '" . $this->email . "', '_cartquantity' : {'country' : '" . $country . "', 'language': '" . $language . "', 'cart_id' : '" . $cartId . "', 'product_id' : product_id, 'quantity' : product_quantity}};" . PHP_EOL
                    . ($this->logging ? "                 console.log(execute_params);" . PHP_EOL : '')
                    . "                 sareX_core.execute(10, execute_params);" . PHP_EOL
                    . "                 break;" . PHP_EOL
                    . "             }" . PHP_EOL
                    . "         }" . PHP_EOL
                    . "     }" . PHP_EOL
                    . "   });" . PHP_EOL;
                break;
            case "order":
                $this->JSEvents[] =
                    "   document.addEventListener('click', function(e){" . PHP_EOL
                    . "     if(e.srcElement.name=='confirm-addresses' || e.srcElement.name=='confirmDeliveryOption'){" . PHP_EOL
                    . "         switch (e.srcElement.name) {" . PHP_EOL
                    . "             case 'confirm-addresses':" . PHP_EOL
                    . "                 var execute_params = {'_userId': '" . $this->userId . "', '_email' : '" . $this->email . "', '_cartdelivery' : {'country' : '" . $country . "', 'language': '" . $language . "', 'cart_id' : '" . $cartId . "'}};" . PHP_EOL
                    . "                 break;" . PHP_EOL
                    . "             case 'confirmDeliveryOption':" . PHP_EOL
                    . "                 var execute_params = {'_userId': '" . $this->userId . "', '_email' : '" . $this->email . "', '_cartpayment' : {'country' : '" . $country . "', 'language': '" . $language . "', 'cart_id' : '" . $cartId . "'}};" . PHP_EOL
                    . "                 break;" . PHP_EOL
                    . "         }" . PHP_EOL
                    . ($this->logging ? "         console.log(execute_params);" . PHP_EOL : '')
                    . "         sareX_core.execute(10, execute_params);" . PHP_EOL
                    . "     }" . PHP_EOL
                    . "   });" . PHP_EOL;
                break;
        }
        return $this;
    }

    public function getJSEvent()
    {
        return implode(PHP_EOL, $this->JSEvents);
    }

    public function getJavaScript($pageType){
        $script = '<script type="text/javascript">';
        if(empty($this->domain)){
            $script .= 'console.log(\'SAREhub Error: Configure your domain first\');';
        } else {
            $script .=
                PHP_EOL . '   (function (p){' .
                PHP_EOL . '   window[\'sareX_params\']=p;var s=document.createElement(\'script\');' .
                PHP_EOL . '   s.src=\'//x.sare25.com/libs/sarex4.min.js\';s.async=true;var t=document.getElementsByTagName(\'script\')[0];' .
                PHP_EOL . '   t.parentNode.insertBefore(s,t);' .
                PHP_EOL . '   })({' .
                PHP_EOL . '       domain : \'' . $this->domain . '\','.
                PHP_EOL . '       inisTrack : {t:\'p\', c:\'moxie\',s:\'all\', uid:[2014]}';

            if(!empty($this->timeEvents)){
                $script .= ',';
                $script .= PHP_EOL . '       ping : {\'period0\' : 10, \'period1\' : 60}';
            }
            if(!empty($this->pushNotifications)){
                $script .= ',';
                $script .= PHP_EOL . '      webPush: {';
                $script .= PHP_EOL . '          mode: \''.$this->pushNotifications.'\'';
                $script .= PHP_EOL . '      }';
            }
            $script .= PHP_EOL . '   });';
            if ($params = $this->getEncodedParams()) {
                $script .=
                    PHP_EOL . '   sareX_params.'.$this->getType().' = ' . $params . ';';
            }
            if ($JSEvent = $this->getJSEvent()) {
                $script .= PHP_EOL . PHP_EOL. $JSEvent;
            }
            if(!empty($this->logging)) {
                $script .= PHP_EOL . '   console.log(' . json_encode(array('site' => $pageType, 'type' => $this->getType(), 'data' => $this->getEncodedParams())) . ');';
            }
        }
        $script .= PHP_EOL . '  </script>';

        return $script;
    }
}