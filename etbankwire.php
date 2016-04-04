<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_'))
	exit;

class EtBankWire extends PaymentModule
{
	protected $html = '';
	protected $postErrors = array();

	public $details;
	public $owner;
	public $address;
	public $extra_mail_vars;
	public function __construct()
	{
		$this->name = 'etbankwire';
		$this->tab = 'payments_gateways';
		$this->version = '1.0.1';
		$this->author = 'Victor Castro';
		$this->controllers = array('payment', 'validation');
		$this->is_eu_compatible = 1;

		$this->currencies = true;
		$this->currencies_mode = 'checkbox';

		$this->bootstrap = true;
		parent::__construct();

		$this->displayName = $this->l('Bank wire Advanced');
		$this->description = $this->l('Accept payments for your products via bank wire transfer.');
		$this->confirmUninstall = $this->l('Are you sure about removing these details?');
		if (!isset($this->owner) || !isset($this->details) || !isset($this->address))
			$this->warning = $this->l('Account owner and account details must be configured before using this module.');
		if (!count(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency has been set for this module.');
/*
		$this->extra_mail_vars = array(
										'{bankwire_owner}' => Configuration::get('BANK_WIRE_OWNER'),
										'{bankwire_details}' => nl2br(Configuration::get('BANK_WIRE_DETAILS')),
										'{bankwire_address}' => nl2br(Configuration::get('BANK_WIRE_ADDRESS'))
										);
*/	}

	public function install()
	{
		if (!parent::install() || !$this->registerHook('payment') || ! $this->registerHook('displayPaymentEU') || !$this->registerHook('paymentReturn'))
			return false;
		return true;
	}

	public function uninstall()
	{
		if (!Configuration::deleteByName('ETBW_BANK_NAME')
				|| !Configuration::deleteByName('ETBW_BANK_PHONE')
				|| !Configuration::deleteByName('ETBW_BANK_SWIFT')
				|| !parent::uninstall())
			return false;
		return true;
	}

	public function hookPayment($params)
	{
		if (!$this->active)
			return;
		if (!$this->checkCurrency($params['cart']))
			return;

		$this->smarty->assign(array(
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/',
            'etbw_txt_payment' => Configuration::get('ETBW_TXT_PAYMENT')
		));
		return $this->display(__FILE__, 'payment.tpl');
	}

	public function hookDisplayPaymentEU($params)
	{
		if (!$this->active)
			return;

		if (!$this->checkCurrency($params['cart']))
			return;

		$payment_options = array(
			'cta_text' => $this->l('Pay by Bank Wire'),
			'logo' => Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/bankwire.jpg'),
			'action' => $this->context->link->getModuleLink($this->name, 'validation', array(), true)
		);

		return $payment_options;
	}

	public function hookPaymentReturn($params)
	{
		if (!$this->active)
			return;

		$state = $params['objOrder']->getCurrentState();
		if (in_array($state, array(Configuration::get('PS_OS_BANKWIRE'), Configuration::get('PS_OS_OUTOFSTOCK'), Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'))))
		{
			$this->smarty->assign(array(
				'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
				'status' => 'ok',
				'id_order' => $params['objOrder']->id,
                'etbw_txt_details' => Configuration::get('ETBW_TXT_DETAILS'),
                'etbw_own_name' => Configuration::get('ETBW_OWN_NAME'),
                'etbw_own_acc_pen' => Configuration::get('ETBW_OWN_ACC_PEN'),
                'etbw_own_acc_usd' => Configuration::get('ETBW_OWN_ACC_USD'),
                'etbw_own_int_pen' => Configuration::get('ETBW_OWN_INT_PEN'),
                'etbw_own_int_usd' => Configuration::get('ETBW_OWN_INT_USD'),
                'etbw_bank_swift' => Configuration::get('ETBW_BANK_SWIFT'),
                'etbw_bank_name' => Configuration::get('ETBW_BANK_NAME'),
                'etbw_bank_phone' => Configuration::get('ETBW_BANK_PHONE'),
                'etbw_bank_address' => Configuration::get('ETBW_BANK_ADDRESS'),
                'etbw_bank_city' => Configuration::get('ETBW_BANK_CITY'),
                'etbw_bank_country' => Configuration::get('ETBW_BANK_COUNTRY'),
			));
			if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference))
				$this->smarty->assign('reference', $params['objOrder']->reference);
		}
		else
			$this->smarty->assign('status', 'failed');
		return $this->display(__FILE__, 'payment_return.tpl');
	}

	public function checkCurrency($cart)
	{
		$currency_order = new Currency($cart->id_currency);
		$currencies_module = $this->getCurrency($cart->id_currency);

		if (is_array($currencies_module))
			foreach ($currencies_module as $currency_module)
				if ($currency_order->id == $currency_module['id_currency'])
					return true;
		return false;
	}

	public function renderForm()
	{
		$fields_form = array();

        $fields_form[0]['form'] = array(
            'legend' => array(
            'title' => $this->l('Details of Account'),
            'icon' => 'icon-cogs',
            ),
            'input' => array(
                array(
                    'col' => 3,
                    'type' => 'text',
                    'name' => 'ETBW_OWN_NAME',
                    'label' => $this->l('Name of Owner'),
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'name' => 'ETBW_OWN_ACC_PEN',
                    'label' => $this->l('Account Soles'),
                    'class' => 'fixed-width-lg',
                    'suffix' => $this->l("PEN S/")
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'name' => 'ETBW_OWN_ACC_USD',
                    'label' => $this->l('Account Dollars'),
                    'class' => 'fixed-width-lg',
                    'suffix' => $this->l("USD $")
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'name' => 'ETBW_OWN_INT_PEN',
                    'label' => $this->l('Account Interbank Soles'),
                    'class' => 'fixed-width-xl',
                    'suffix' => $this->l("PEN S/")
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'name' => 'ETBW_OWN_INT_USD',
                    'label' => $this->l('Account Interbank Dollars'),
                    'class' => 'fixed-width-xl',
                    'suffix' => $this->l("USD $")
                ),
                 array(
                    'col' => 3,
                    'type' => 'text',
                    'name' => 'ETBW_BANK_SWIFT',
                    'label' => $this->l('Code swift'),
                    'class' => 'fixed-width-md',
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        );


        $fields_form[1]['form'] = array(
            'legend' => array(
            'title' => $this->l('Details of Bank'),
            'icon' => 'icon-cogs',
            ),
            'input' => array(
                array(
                    'col' => 3,
                    'type' => 'text',
                    'name' => 'ETBW_BANK_NAME',
                    'label' => $this->l('Name of bank'),
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'name' => 'ETBW_BANK_ADDRESS',
                    'label' => $this->l('Address'),
                    
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'name' => 'ETBW_BANK_PHONE',
                    'label' => $this->l('Phone'),
                    'class' => 'fixed-width-md',
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'name' => 'ETBW_BANK_CITY',
                    'label' => $this->l('City'),
                    'class' => 'fixed-width-md',
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'name' => 'ETBW_BANK_COUNTRY',
                    'label' => $this->l('Country'),
                    'class' => 'fixed-width-md',
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        );

         $fields_form[2]['form'] = array(
            'legend' => array(
            'title' => $this->l('Details of Bank'),
            'icon' => 'icon-cogs',
            ),
            'input' => array(
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Text of Payment'),
                    'name' => 'ETBW_TXT_PAYMENT',
                    'cols' => 110,
                    'rows' => 7,
                    'desc' => $this->l('Deje en blanco para mostrar el texto por defecto.'),
                ),
                array(
                    'type' => 'textarea',
                    'label' => $this->l('Text of show details account'),
                    'name' => 'ETBW_TXT_DETAILS',
                    'cols' => 110,
                    'rows' => 7,
                    'desc' => $this->l('Deje en blanco para mostrar el texto por defecto.'),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        );

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();
		$helper->id = (int)Tools::getValue('id_carrier');
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'btnSubmit';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFormValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id
		);

		return $helper->generateForm($fields_form);
	}

	protected function getConfigFormValues()
    {
        return array(
            'ETBW_BANK_NAME' => Configuration::get('ETBW_BANK_NAME'),
            'ETBW_BANK_SWIFT' => Configuration::get('ETBW_BANK_SWIFT'),
            'ETBW_BANK_PHONE' => Configuration::get('ETBW_BANK_PHONE'),
            'ETBW_BANK_ADDRESS' => Configuration::get('ETBW_BANK_ADDRESS'),
            'ETBW_BANK_CITY' => Configuration::get('ETBW_BANK_CITY'),
            'ETBW_BANK_COUNTRY' => Configuration::get('ETBW_BANK_COUNTRY'),
            'ETBW_OWN_NAME' => Configuration::get('ETBW_OWN_NAME'),
            'ETBW_OWN_ACC_PEN' => Configuration::get('ETBW_OWN_ACC_PEN'),
            'ETBW_OWN_ACC_USD' => Configuration::get('ETBW_OWN_ACC_USD'),
            'ETBW_OWN_INT_PEN' => Configuration::get('ETBW_OWN_INT_PEN'),
            'ETBW_OWN_INT_USD' => Configuration::get('ETBW_OWN_INT_USD'),
            'ETBW_TXT_PAYMENT' => Configuration::get('ETBW_TXT_PAYMENT'),
            'ETBW_TXT_DETAILS' => Configuration::get('ETBW_TXT_DETAILS'),
        );
    }

    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    public function getContent()
    {
        if (((bool)Tools::isSubmit('btnSubmit')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }
}
