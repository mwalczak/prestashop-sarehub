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
}