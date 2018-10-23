<?php
/**
 * Created by PhpStorm.
 * User: mwalczak
 * Date: 20.10.2018
 * Time: 19:53
 */

class SarehubEvent
{
    private $userId;
    private $email;
    private $id;
    private $type = "event";
    private $params = [];
    private $JSEvents = [];

    public function __construct($userId = null, $email = null)
    {
        $this->userId = $userId;
        $this->email = $email;
    }

    public function getEncodedParams()
    {
        if (empty($this->params)) {
            return "";
        } elseif ($this->type == "event") {
            return json_encode([
                    'id' => $this->id,
                    'params' => array_merge(
                        [
                            '_userId' => $this->userId,
                            '_email' => $this->email
                        ],
                        $this->params
                    )
                ]
            );

        } else {
            return json_encode(array_merge([
                '_userId' => $this->userId,
                '_email' => $this->email
            ], $this->params));
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
        $this->params['_product'] = [
            'id' => $id,
            'url' => $url,
            'country' => $country,
            'language' => $language
        ];
        return $this;
    }

    public function setCategory($id = "", $country = "", $language = "")
    {
        $this->params['_category'] = [
            'id' => $id,
            'country' => $country,
            'language' => $language
        ];
        return $this;
    }

    public function setCartRegistration($cartId)
    {
        $this->id = 10;
        $this->params['_cartregistration'] = [
            'cart_id' => $cartId
        ];
        return $this;
    }

    public function setCartPurchased($cartId)
    {
        $this->id = 10;
        $this->params['_cartpurchased'] = [
            'cart_id' => $cartId
        ];
        return $this;
    }

    public function setCartQuantity($cartId, $productId, $quantity, $country, $language)
    {
        $this->params['_cartquantity'] = [
            'cart_id' => $cartId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'country' => $country,
            'language' => $language
        ];
        return $this;
    }

    public function setJSEvent($eventType, $country, $language, $cartId)
    {
        switch ($eventType){
            case "productCartAdd":
                $this->JSEvents[]=
                    "   document.addEventListener('click', function(e){" . PHP_EOL
                    . "     if(e.srcElement.classList.contains('add-to-cart')){" . PHP_EOL
                    . "         var product_input = document.getElementById('product_page_product_id');" . PHP_EOL
                    . "         var quantity_input = document.getElementById('quantity_wanted');" . PHP_EOL
                    . "         var execute_params = {'_userId': '" . $this->userId . "', '_email' : '" . $this->email . "', '_cartadd' : {'country' : '" . $country . "', 'language': '" . $language . "', 'cart_id' : '" . $cartId . "', 'product_id' : product_input.value, 'quantity' : quantity_input.value}};" . PHP_EOL
                    . "         console.log(execute_params);" . PHP_EOL
                    . "         sareX_core.execute(10, execute_params);" . PHP_EOL
                    . "     }" . PHP_EOL
                    . "   });" . PHP_EOL;
        }
    }

    public function getJSEvent()
    {
        return implode(PHP_EOL, $this->JSEvents);
    }
}