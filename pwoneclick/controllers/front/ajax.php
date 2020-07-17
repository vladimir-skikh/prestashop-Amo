<?php

class PwOneClickAjaxModuleFrontController extends ModuleFrontController
{
    const INPUT_PHONE   = 'phone';
    const INPUT_EMAIL   = 'email';
    const INPUT_NAME    = 'firstname';
    const INPUT_COMMENT    = 'comment';
    const INPUT_CITY    = 'city';

    /**
     * @var array
     */
    public $errors = Array();

    /**
     * @var Address
     */
    public $address;

    /**
     * @var Customer
     */
    public $customer;

    /**
     * Current language id
     *
     * @var int
     */
    public $language = 0;

    /**
     * @var array
     */
    public $defaultFields = Array();

    /**
     * @var array
     */
    public $address_fields = Array(
        'lastname',
        'firstname',
        'address1',
        'address2',
        'city',
        'other',
        'phone',
        'phone_mobile'
    );

    public function __construct()
    {
        parent::__construct();

        $this->defaultFields = Array(
            'zip'       => '000000',
            'city'      => $this->module->l('Город не указан', 'ajax'),
            'alias'     => $this->module->l('Мой адрес', 'ajax'),
            'other'     => '',
            'state'     => '743', // Москва
            'lastname'  => ' ',
            'address1'  => ' ',
            'company'   => '',
            'country'   => Configuration::get('PS_COUNTRY_DEFAULT'),
            'password'  => substr(uniqid(rand() . true), 0, 6),
            'email'     => 'inbox@supersatin.ru',
        );

        $this->customer     = $this->context->customer;
        $this->language     = $this->context->language->id;
    }



    /**
     * @return bool
     */
    public function initContent()
    {
        parent::initContent();

        if (Tools::getValue('getCustomer')) {
            $response = Tools::jsonEncode($this->getCustomerInfo());
            return die($response);
        } elseif (Tools::getValue('getForm')) {
            die($this->getForm());
        }

        $name           = trim(Tools::getValue(self::INPUT_NAME));
        $phone          = trim(Tools::getValue(self::INPUT_PHONE));
        $email          = trim(Tools::getValue(self::INPUT_EMAIL));
        $comment        = Tools::getValue(self::INPUT_COMMENT);
        $city        = Tools::getValue(self::INPUT_CITY);
        $id_product     = Tools::getValue('id_product');
        $quantity       = Tools::getValue('quantity');
        $combination    = (($combination = Tools::getValue('combination')) !== false) ? $combination : null;
        if (Tools::isSubmit('group') && method_exists('Product', 'getIdProductAttributesByIdAttributes')) {
            $combination = (int)Product::getIdProductAttributesByIdAttributes($id_product, Tools::getValue('group'), true);
        }

        if (empty($quantity) || $quantity == "undefined") {
            $quantity = 1;
        }
		
		//Если email обязателен и не заполнен
		if($this->module->config['email']&&empty($email))
		{
			$this->addError(Array(self::INPUT_EMAIL => $this->module->l('Укажите вашу почту', 'ajax')));
		}
		//Если телефон обязателен и не заполнен
		if($this->module->config['phone']&&empty($phone))
		{
			$this->addError(Array(self::INPUT_PHONE => $this->module->l('Укажите ваш телефон', 'ajax')));
        }
        //Если город обязателен и не заполнен
		if($this->module->config['city']&&empty($city))
		{
			$this->addError(Array(self::INPUT_CITY => $this->module->l('Укажите ваш город', 'ajax')));
		}

		//Если телефон не валиден
		if (!empty($phone) && !Validate::isPhoneNumber($phone)) {
            $this->addError(Array(self::INPUT_PHONE => $this->module->l('Некорректный телефон', 'ajax')));
        }
		
		//Если email не валиден
        if (!empty($email)&&!Validate::isEmail($email))
		{
           $this->addError(Array(self::INPUT_EMAIL => $this->module->l('Некорректная почта', 'ajax')));
        }
        
        if (empty($name) || !Validate::isName($name)) {
            $this->addError(Array(self::INPUT_NAME => $this->module->l('Некорректное имя', 'ajax')));
        }

        if ( $this->issetErrors()) {
            $response = Array(
                'status' => 'error',
                'errors' => $this->getErrors()
            );
            die(Tools::jsonEncode($response));
        }
        
        if (empty($email)) {
            $email = $this->getDefaultValue('email');
            $_POST['email'] = $this->getDefaultValue('email'); //иначе validateController() начнет громко возмущаться
        }

        if ($this->customer->isLogged() === false || $this->customer->email != $email) {
            $result = Db::getInstance()->getRow('
				SELECT *
				FROM `' . _DB_PREFIX_ . 'customer`
				WHERE `active` = 1
				AND `email` = \'' . $email . '\'
				AND `deleted` = 0
				AND `is_guest` = 0');

            if ($result['id_customer'] && $email) {
                $this->customer = $this->context->customer = new Customer($result['id_customer']);
                $this->setCustomerCookie();
                $this->address = $this->getAddress();
                if ($this->validateAddress()) {
                    $this->address->save();
                }
            } else {
                $this->customer = $this->context->customer = $this->createCustomer();;
                if ($this->validateCustomer() && $this->customer->save()) {
                    $this->setCustomerCookie();
                    if ($this->module->config['email']) {
                        $this->sendNewCustomerMail();
                    }

                    $this->address = $this->getAddress();
                    if ($this->validateAddress()) {
                        $this->address->save();
                    }
                }
            }
        } else{
            $addresses = $this->customer->getAddresses($this->language);
            $this->address = $this->getAddress($addresses[0]['id_address']);
        }
		
		if($comment)
		{
			$this->customer->note=$comment;
			$this->customer->save();
		}

        if ( !$this->issetErrors()) {

            if (isset($this->context->cookie->id_customer) && $this->context->cookie->id_customer != $this->customer->id) {
                $this->logoutANDResetCart();
            }
            $this->createCart();

            // add product cart and create order
            if ($id_product && $this->isExistProduct($id_product)) {
                if(!$this->addProduct($id_product, $combination, $quantity) || count($this->context->cart->getProducts(true))<1){
                    $this->addError(Array('system' => $this->module->l('Не удалось добавить товар в корзину', 'ajax')));
                }
                $this->updateCart();
                $this->isValidCart();
                if (!$this->issetErrors()) {
                    $this->processOrder();
                }
            }
        }

        if ($this->issetErrors()) {
            $response = Array(
                'status' => 'error',
                'errors' => $this->getErrors()
            );

        } else {
            $config = Configuration::get('PW_ONE_CLICK');
            $config = unserialize($config);
            $response = Array(
                'status'    => 'success',
                'message'   => $config['text']
            );
            if (!empty($config['redirect'])) {
                $response['redirect_after'] = $this->context->link->getPageLink(
                    'order-confirmation',
                    null,
                    null,
                    'id_order='.Order::getOrderByCartId($this->context->cart->id).'&id_module='.$this->module->id.'&key='.$this->context->customer->secure_key.'&id_cart='.$this->context->cart->id
                );
            }
        }

        die(Tools::jsonEncode($response));
    }
    
    protected function getForm()
    {
        $order = array(
            'link'  => $this->context->link->getModuleLink('pwoneclick', 'ajax')
        );
		
		$config = Configuration::get('PW_ONE_CLICK');
        $config = unserialize($config);
		
        $this->context->smarty->assign(array(
            'order' => $order,
            'config' => $config,
            'PW_COOCKIE_CITY' => $this->context->cookie->pw_city_name,
        ));

        return $this->module->display('pwoneclick', 'pwoneclick_form.tpl');
    }

    /**
     * Check exists product
     *
     * @param $id
     * @return bool
     */
    private function isExistProduct($id)
    {
        $product = new Product($id, true, $this->language);

        if (!$product->checkQty(1)) {
            $this->addError(Array('system' => $this->module->l('Данный товар не доступен для заказа', 'ajax')));
            return false;
        }
        return true;
    }

    /**
     * @return array|bool|null|object
     */
    public function getDefaultCurrency()
    {
        return Currency::getCurrency(Configuration::get('PS_CURRENCY_DEFAULT'));
    }

    /**
     * Validate cart
     *
     * @return bool
     */
    private function isValidCart()
    {
        $valid = true;

        $order_total = $this->context->cart->getOrderTotal();

        $order_total_default = Tools::convertPrice($this->context->cart->getOrderTotal(true, 1), $this->getDefaultCurrency());

        $minimal_purchase = Configuration::get('PS_PURCHASE_MINIMUM');

        if ($order_total_default < $minimal_purchase) {
            $this->addError(Array('system' => sprintf($this->module->l('Общая сумма заказа должна быть не менее %s', 'ajax'),
                Tools::displayPrice($minimal_purchase,
                    Currency::getCurrency($this->context->cart->id_currency))
            )));
            $valid = false;
        }

        if (count($this->context->cart->getProducts(true)) == 0) {
            $this->addError(Array('system' => $this->module->l('Ваша корзина пуста', 'ajax')));
            $valid = false;
        }
        return $valid;
    }

    /**
     * Validate fields Customer
     *
     * @return bool
     */
    private function validateCustomer()
    {
        $controller_errors = $this->customer->validateController();
        $fields_errors = $this->customer->validateFieldsRequiredDatabase();

        if (!empty($controller_errors) || !empty($fields_errors)) {
            $this->addError($fields_errors);
            $this->addError($controller_errors);
            return false;
        }
        return true;
    }

    /**
     * Validate Address fields
     *
     * @return bool
     */
    private function validateAddress()
    {
        $errors = $this->address->validateController();
        if (!empty($errors)) {
            $this->addError($errors);
            return false;
        }
        return true;
    }

    /**
     * Create cart
     *
     * @return bool
     */
    private function createCart()
    {
        unset($this->context->cookie->id_cart);
        $this->context->cookie->update();
        $cart = new Cart();
        $cart->id_lang = (int)$this->context->cookie->id_lang;
        $cart->id_currency = (int)$this->context->cookie->id_currency;
        $cart->id_guest = (int)$this->context->cookie->id_guest;
        $cart->id_shop_group = (int)$this->context->shop->id_shop_group;
        $cart->id_shop = $this->context->shop->id;
        if ($this->context->cookie->id_customer) {
            $cart->id_customer = (int)$this->context->cookie->id_customer;
            $cart->id_address_delivery = (int)Address::getFirstCustomerAddressId($cart->id_customer);
            $cart->id_address_invoice = (int)$cart->id_address_delivery;
        } else {
            $cart->id_address_delivery = 0;
            $cart->id_address_invoice = 0;
        }

        // Needed if the merchant want to give a free product to every visitors
        $this->context->cart = $cart;
        $this->context->cart->save();

        if ($this->context->cart->id) {
            $this->context->cookie->id_cart = $this->context->cart->id;
        }
        return $this->context->cart->id;
    }


    /**
     * Add product to cart
     *
     * @param $id
     * @param int $quantity
     * @return bool
     */
    private function addProduct($id, $id_product_attribute, $quantity = 1)
    {
        return $this->context->cart->updateQty($quantity, $id, $id_product_attribute);
    }

    /**
     * Create new Customer
     *
     * @return Customer
     */
    private function createCustomer()
    {
        $customer = new Customer();
        $customer->email = Tools::getValue(self::INPUT_EMAIL);
        $customer->birthday = date("Y-m-d", strtotime("-18 years"));
        $customer->passwd   = Tools::encrypt($this->getDefaultValue('password'));
        $customer->firstname = Tools::getValue(self::INPUT_NAME);
        $customer->lastname  = $this->getDefaultValue('lastname');
        
        return $customer;
    }

    /**
     *
     */
    private function sendNewCustomerMail()
    {
        Mail::Send(
            $this->context->cookie->id_lang,
            'account',
            Mail::l('Welcome!'),
            array(
                '{firstname}' => $this->customer->firstname,
                '{lastname}'  => $this->customer->lastname,
                '{email}'     => $this->customer->email,
                '{passwd}'    =>$this->getDefaultValue('password')
            ),
            $this->customer->email,
            $this->customer->firstname . ' ' . $this->customer->lastname
        );
    }

    /**
     *
     */
    private function setCustomerCookie()
    {
        $this->context->cookie->id_customer        = $this->customer->id;
        $this->context->cookie->customer_lastname  = $this->customer->lastname;
        $this->context->cookie->customer_firstname = $this->customer->firstname;
        $this->context->cookie->passwd             = $this->customer->passwd;
        $this->context->cookie->logged             = 1;
        $this->context->cookie->email              = $this->customer->email;
    }

    /**
     * Get customer address
     *
     * @param int $id
     * @return Address
     */
    private function getAddress($id = 0)
    {
        if ($id === 0) {
            $address = new Address();
            $address->alias         = $this->getDefaultValue('alias');
            $address->phone         = Tools::getValue(self::INPUT_PHONE);
            $address->city          = $this->getDefaultValue('city');
            $address->other         = $this->getDefaultValue('other');
            $address->company       = $this->getDefaultValue('company');
            $address->id_state      = $this->getDefaultValue('state');
            $address->lastname      = $this->getDefaultValue('lastname');
            $address->postcode      = $this->getDefaultValue('zip');
            $address->address1      = $this->getDefaultValue('address1');
            $address->firstname     = $this->customer->firstname;
            $address->id_country    = $this->getDefaultValue('country');
            $address->id_customer   = $this->customer->id;
        } else {
            $address = new Address($id);
        }

        $this->context->country = new Country($address->id_country);

        return $address;
    }

    /**
     * Get logged customer address
     *
     * @param $id_customer
     * @return array|bool|null|object
     */
    public function getLastAddress($id_customer)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
		SELECT *
		FROM `' . _DB_PREFIX_ . 'address`
		WHERE `id_customer` = ' . (int)($id_customer) . '
		AND `deleted` = 0 ORDER BY `id_address` DESC');
    }

    /**
     *
     */
    private function logoutANDResetCart()
    {
        $this->context->cookie->mylogout();
        $this->context->cookie->id_cart = $this->context->cart->id;
    }

    /**
     * 
     */
    private function updateCart()
    {
        $this->context->cart->delivery_option     = "";
        $this->context->cart->id_customer         = $this->customer->id;
        $this->context->cart->id_address_delivery = $this->address->id;
        $this->context->cart->id_address_invoice  = $this->address->id;
        $this->context->cart->secure_key          = $this->customer->secure_key;
        $this->context->cart->update();

        //Выставляем корректный адрес у товаров, иначе будет выставляться не тот адрес
        $this->context->cart->updateAddressId(
            key($this->context->cart->getPackageList(true)),
            $this->context->cart->id_address_delivery
        );
    }

    /**
     * Validate order data and create
     *
     * @return bool
     */
    private function processOrder()
    {
        $total = $this->context->cart->getOrderTotal(true, 3);
        $orderValidate = $this->module->validateOrder(
            $this->context->cart->id,
            Configuration::get('PS_OS_PREPARATION'),
            $total,
            $this->module->displayName,
            null,
            array(),
            null,
            false,
            $this->customer->secure_key
        );

        if ($orderValidate !== true) {
            $this->addError(Array('system' => $this->module->l('Произошла ошибка', ajax)));
            return false;
        }
        return true;
    }

    /**
     * Check exists address where phone
     *
     * @param Address $address
     * @return false|null|string
     */
    public function getSimilarAddress(Address $address){

        $sql = sprintf('SELECT `id_address` FROM `%saddress` WHERE 1 AND `phone` LIKE "%s" AND `phone_mobile` LIKE "%2$s" AND `deleted` = 0 ORDER BY id_address DESC',
                _DB_PREFIX_, $address->phone);
        $result = Db::getInstance()->getValue($sql);
        return $result;
    }

    /**
     * @return array
     */
    public function getCustomerInfo()
    {
        $customerData = Array();

        if ($this->customer->isLogged()) {
            $phone = '';
            $address = $this->customer->getAddresses($this->context->language->id);

            if (!empty($address[0])) {
                $address = array_shift($address);
                $phone = !empty($address['phone']) ? $address['phone'] : $address['phone_mobile'];
            }

            $customerData = Array(
                'name' => $this->customer->firstname,
                'email' => $this->customer->email,
                'phone' => $phone
            );
        }

        return $customerData;
    }

    /**
     * @param $error
     */
    public function addError($error)
    {
        foreach ($error as $key => $value) {
            if(is_integer($key)) $key= "system"; //Подставляем в нужный массив
            $error[$key] = htmlspecialchars_decode($value);
        }
        $this->errors = array_merge($this->errors, $error);
    }

    /**
     * @return bool
     */
    public function issetErrors()
    {
        return !empty($this->errors) ? true : false;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function getDefaultValue($key)
    {
        return isset($this->defaultFields[$key]) ? $this->defaultFields[$key] : null;
    }
}
