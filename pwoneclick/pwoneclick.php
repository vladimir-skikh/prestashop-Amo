<?php

if (!defined('_PS_VERSION_'))
    exit;

class PwOneClick extends PaymentModule
{
    /**
     * @var
     */
    public $html;

    public $active = true;

    /**
     * @var array|mixed
     */
    public $config = array();

    /**
     * @var array
     */
    public $errors = array();

    /**
     * PwOneClick constructor.
     */
    public function __construct()
    {
        $this->name = strtolower(get_class());
        $this->tab = 'other';
        $this->version = '0.6.0';
        $this->author = 'Prestaweb.ru';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l("Купить в 1 клик");
        $this->description = $this->l("Оформление быстрого заказа");
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        $config = Configuration::get('PW_ONE_CLICK');
        $this->config = unserialize($config);
    }

    /**
     * @return bool
     */
    public function install()
    {
        $config = array(
            'text' => $this->l('Ваш заказ оформлен. В течение рабочего дня наш менеджер свяжется с Вами. Спасибо!'),
            'complete' => true,
            'showemail' => true,
            'email' => true,
            'phone' => true,
            'comment' => true,
            'city' => true,
        );

        if ( !parent::install()
            || !$this->registerHook('displayHeader')
            || !$this->registerHook('displayFooter')
            || !$this->registerHook('displayProductButtons')
            || !$this->registerHook('displayProductListFunctionalButtons')
            || !$this->registerHook('displayProductListReviews')
            || !Configuration::updateValue('PW_ONE_CLICK', serialize($config))
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        if ( !parent::uninstall()
            || !Configuration::deleteByName('PW_ONE_CLICK')

        ) {
            return false;
        }

        return true;
    }

    /**
     * @param $params
     * @return mixed
     */
    public function hookDisplayProductButtons($params)
    {
        if ( !$this->active) {
            return;
        }
        $old_price = '';
        $product = $params['product'];

        //die(var_dump($product));

        if (is_array($params['product'])) {
            $product = new Product($params['product']['id_product'], true, $this->context->language->id);
        } else {
            $product = new Product($product->id, true, $this->context->language->id);
        }
        if(!isset($product) || !is_object($product)) {
            return false;
        }
        if (!$this->checkForMinimalPrice($product->price)) {
            return false;
        }

        $price_without_reduction = $product->getPriceWithoutReduct(false, NULL);
        if ($product->price > 0
            && isset($product->specific_prices)
            && $product->specific_prices
            && isset($product->specific_prices['reduction'])
            && $product->specific_prices['reduction'] > 0
            && $price_without_reduction > $product->price)
        {
            $old_price = Tools::displayPrice($product->price_without_reduction);
        }

        /*Проверка на возможность покупки */
        if((!$product->isAvailableWhenOutOfStock((int)$product->out_of_stock) && $product->quantity <= 0)
            || Configuration::get('PS_CATALOG_MODE')
            || !$product->available_for_order) {
            return false;
        }

        $id_cover = Image::getCover($product->id);
        $id_cover = $id_cover['id_image'];

        $id_image = Configuration::get('PS_LEGACY_IMAGES')==1 ? $product->id.'-'.$id_cover : $id_cover; //По разному надо отображать в зависиомсти от тпа хранения
        $product = array(
            'id'        => $product->id,
            'name'      => $product->name,
            'price'     => Tools::displayPrice($product->price),
            'old_price' => $old_price,
            'id_image'  => $id_image,
            'link_rewrite' => $product->link_rewrite,
            'image_url' => $this->context->link->getImageLink($product->link_rewrite, $id_image, 'large_default'),
        );
        $this->context->smarty->assign(array(
            'product' => $product,
            'page_name' => $this->context->controller->php_self,
            'city' => $this->context->cookie->pw_city_name,
        ));
        return $this->display(__FILE__, 'views/templates/hook/pwoneclick_button.tpl');
    }

    /**
     * @param $params
     * @return mixed
     */
    public function hookDisplayProductListFunctionalButtons($params)
    {

        if ( !$this->active) {
            return;
        }
        $product = $params['product'];
        if (is_array($params['product'])) {
            $product = new Product($params['product']['id_product'], true, $this->context->language->id);
        }
        if(!isset($product) || !is_object ($product))
            return false;

        if(!$this->checkForMinimalPrice($product->price)) return false;

        $old_price = '';
        if (isset($params['product']['price_without_reduction'])) {
            $product->price_without_reduction = $params['product']['price_without_reduction'];
        } else {
            $product->price_without_reduction = null;
        }


        if ($product->price_without_reduction > 0
            && isset($product->specific_prices)
            && $product->specific_prices
            && isset($product->specific_prices['reduction'])
            && $product->specific_prices['reduction'] > 0) {
            $old_price = Tools::displayPrice($product->price_without_reduction);
        }
        $image = Product::getCover($product->id);
        $product = array(
            'id'        => $product->id,
            'name'      => $product->name,
            'price'     => Tools::displayPrice($product->price),
            'old_price' => $old_price,
            'image'     => $this->context->link->getImageLink($product->id, $image['id_image'], 'home_default'),
            'image_url' => $this->context->link->getImageLink($product->link_rewrite, $image['id_image'], 'large_default'),
        );

        $this->context->smarty->assign(array(
            'product' => $product,
            'page_name' => $this->context->controller->php_self,
        ));

        return $this->display(__FILE__, 'views/templates/hook/pwoneclick_button.tpl');
    }

    /**
     * @param $params
     * @return mixed
     */
    public function hookdisplayProductListReviews($params)
    {
        if ( !$this->active) {
            return;
        }
        $product = $params['product'];
        if (is_array($params['product'])) {
            $product = new Product($params['product']['id_product'], true, $this->context->language->id);
        }
        if(!isset($product) || !is_object ($product))
            return false;

        if(!$this->checkForMinimalPrice($product->price)) return false;
        if(isset($_COOKIE['pastilas']))
            die('test');
        $old_price = '';
        if (isset($params['product']['price_without_reduction'])) {
            $product->price_without_reduction = $params['product']['price_without_reduction'];
        } else {
            $product->price_without_reduction = null;
        }

        if ($product->price_without_reduction > 0
            && isset($product->specific_prices)
            && $product->specific_prices
            && isset($product->specific_prices['reduction'])
            && $product->specific_prices['reduction'] > 0) {
            $old_price = Tools::displayPrice($product->price_without_reduction);
        }
        $image = Product::getCover($product->id);
        $product = array(
            'id'        => $product->id,
            'name'      => $product->name,
            'price'     => Tools::displayPrice($product->price),
            'old_price' => $old_price,
            'image'     => $this->context->link->getImageLink($product->id, $image['id_image'], 'home_default'),
            'image_url' => $this->context->link->getImageLink($product->link_rewrite, $image['id_image'], 'large_default'),
        );

        $this->context->smarty->assign(array(
            'product' => $product,
            'page_name' => $this->context->controller->php_self,
        ));

        return $this->display(__FILE__, 'views/templates/hook/pwoneclick_button.tpl');
    }

    public function checkForMinimalPrice($price)
    {
        if ($price < Configuration::get('PS_PURCHASE_MINIMUM')) {
            return false;
        }
        return true;
    }

    /**
     * @return mixed
     */
    public function renderForm()
    {
        $default_lang = $this->context->language->id;
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->title = $this->displayName;
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
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Text after the order'),
                    'name' => 'success_message',
                    'class' => 'textarea-autosize',
                    'required' => true
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Redirect after order complete'),
                    'name' => 'redirect',
                    'required' => false,
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'redirect_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'redirect_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Показывать поле ввода E-mail'),
                    'name' => 'showemail',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'showemail_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'showemail_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Показывать поле ввода города'),
                    'name' => 'city',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'city_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'city_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Требовать ввод E-mail'),
                    'name' => 'email',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'email_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'email_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Требовать ввод телефона'),
                    'name' => 'phone',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'phone_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'phone_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Показывать поле для комментария'),
                    'name' => 'comment',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'comment_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'comment_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'button'
            )
        );
        $helper->fields_value = array(
            'success_message' => $this->config['text'],
            'redirect' => (bool)!empty($this->config['redirect']),
            'email' => (bool)!empty($this->config['email']),
            'showemail' => (bool)!empty($this->config['showemail']),
            'phone' => (bool)$this->config['phone'],
            'comment' => (bool)$this->config['comment'],
            'city' => (bool)$this->config['city'],
        );
        return $helper->generateForm($fields_form);
    }

    /**
     * @return string
     */
    public function getContent()
    {
        if (Tools::isSubmit('submitpwoneclick'))
        {
            $this->config['text'] = Tools::getValue('success_message');
            $this->config['redirect'] = Tools::getValue('redirect');
            $this->config['email'] = Tools::getValue('email');
            $this->config['showemail'] = Tools::getValue('showemail');
            $this->config['phone'] = Tools::getValue('phone');
            $this->config['comment'] = Tools::getValue('comment');
            $this->config['city'] = Tools::getValue('city');

            // если не показываем поле e-mail, делаем его необязательным
            if(!$this->config['showemail']){ $this->config['email']=$this->config['showemail'];}

            if ( !Configuration::updateValue('PW_ONE_CLICK', serialize($this->config))) {
                $this->errors[] = $this->l('Произошла ошибка');
            }
            if (!empty($this->errors)) {
                $this->context->controller->errors = array_merge($this->context->controller->errors, $this->errors);
            } else {
                $this->context->controller->confirmations[] = $this->l('Настройки обновлены');
            }
        }
        return $this->renderForm();
    }

    /**
     * @param $params
     */
    public function hookDisplayHeader($params){
        if (!$this->active) {
            return;
        }
        $this->context->controller->addJqueryPlugin('fancybox');
        $this->context->controller->addCSS(($this->_path) . 'views/css/' . ($this->name) . '.css', 'all');
        $this->context->controller->addJS(($this->_path) . 'views/js/' . ($this->name) .'.js');
    }
}
