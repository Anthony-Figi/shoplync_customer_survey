<?php
/**
* 2007-2022 PrestaShop
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

//Include the class of the new model 
include_once dirname (__FILE__).'/classes/SurveyCustomer.php';
include_once dirname (__FILE__).'/classes/WebserviceSpecificManagementCustomerEmailUpload.php';

class Shoplync_customer_survey extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'shoplync_customer_survey';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.0';
        $this->author = 'Shoplync';
        $this->need_instance = 0;

        $this->controllers = array('review', 'feedback');
        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Customer Survey');
        $this->description = $this->l('Allows administrators to send out survey\'s to their customers and determine the businesses rating. Allows to redirect users to Google reviews/FB if they enter above the admin specified threshold');

        $this->confirmUninstall = $this->l('');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }


    protected static function EnableWebServicePermissions()
    {
        $sms_api_key = Configuration::get('SHOPLYNC_SMS_PRO_API_KEY', null);
        if(isset($sms_api_key))
        {
            $sqlQuery = 'SELECT * FROM `' . _DB_PREFIX_ . 'webservice_account` AS ws '
            .'WHERE ws.key LIKE "'.$sms_api_key.'"';
            
            $result = Db::getInstance()->executeS($sqlQuery);
            if(empty($result) || !is_array($result))
            {
                error_log('Failed to find API key');
                return;
            }
            
            if(array_key_exists('id_webservice_account', $result[0]))
            {
                //Delete All Previous permisions
                $sqlDelete = 'DELETE FROM `' . _DB_PREFIX_ . 'webservice_permission` WHERE (resource LIKE "CustomerEmailUpload" OR resource LIKE "survey_customers")'.
                ' AND id_webservice_account = '.$result[0]['id_webservice_account'];
                Db::getInstance()->execute($sqlDelete);
                
                $available_methods = array('GET', 'POST', 'HEAD');
                foreach($available_methods as $method)
                {//Insert all permision
                    $sqlInsert = 'INSERT INTO ps_ca_webservice_permission(resource, method, id_webservice_account) '.
                    'VALUES ("CustomerEmailUpload", "'.$method.'", '.$result[0]['id_webservice_account'].'), '.
                    '("survey_customers", "'.$method.'", '.$result[0]['id_webservice_account'].')';
                    
                    if(Db::getInstance()->execute($sqlInsert) == FALSE)
                    {
                        error_log('SQL Insert Query Failed: '.$sqlInsert);
                    }
                }
            }
        }
    }
    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('SHOPLYNC_CUSTOMER_SURVEY_LIVE_MODE', TRUE);
        Configuration::updateValue('SHOPLYNC_CUSTOMER_SURVEY_START_ORDER_STATUS', 4);
        Configuration::updateValue('SHOPLYNC_CUSTOMER_SURVEY_TARGET_ORDER_STATUS', 5);
        Configuration::updateValue('SHOPLYNC_CUSTOMER_THRESHOLD', 2);
        Configuration::updateValue('SHOPLYNC_CUSTOMER_SURVEY_G_REVIEW', '');
        Configuration::updateValue('SHOPLYNC_CUSTOMER_SURVEY_FB_REVIEW', '');
        Configuration::updateValue('SHOPLYNC_CUSTOMER_SURVEY_SUBTITLE_1', 'We\'d love it if you left us a positive review.');
        Configuration::updateValue('SHOPLYNC_CUSTOMER_SURVEY_SUBTITLE_2', 'We appreciate your feedback, please let us know how we can improve.');
        Configuration::updateValue('SHOPLYNC_CUSTOMER_SURVEY_SUBTITLE_3', 'Please contact us if you have additional feedback or comments to share with us.');

        include(dirname(__FILE__).'/sql/install.php');

        self::EnableWebServicePermissions();

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('actionOrderDetail') &&
            $this->registerHook('actionOrderStatusPostUpdate') &&
            $this->registerHook('actionOrderStatusUpdate') &&
            $this->registerHook('addWebserviceResources') &&
            $this->registerHook('displayCustomerAccount') &&
            $this->registerHook('moduleRoutes');
    }

    public function uninstall()
    {
        Configuration::deleteByName('SHOPLYNC_CUSTOMER_SURVEY_LIVE_MODE');
        Configuration::deleteByName('SHOPLYNC_CUSTOMER_SURVEY_START_ORDER_STATUS');
        Configuration::deleteByName('SHOPLYNC_CUSTOMER_SURVEY_TARGET_ORDER_STATUS');
        Configuration::deleteByName('SHOPLYNC_CUSTOMER_THRESHOLD');
        Configuration::deleteByName('SHOPLYNC_CUSTOMER_SURVEY_G_REVIEW');
        Configuration::deleteByName('SHOPLYNC_CUSTOMER_SURVEY_FB_REVIEW');
        Configuration::deleteByName('SHOPLYNC_CUSTOMER_SURVEY_SUBTITLE_1');
        Configuration::deleteByName('SHOPLYNC_CUSTOMER_SURVEY_SUBTITLE_2');
        Configuration::deleteByName('SHOPLYNC_CUSTOMER_SURVEY_SUBTITLE_3');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitShoplync_customer_surveyModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign('module_settings', $this->renderForm());

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitShoplync_customer_surveyModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }
    
    public static function isValidCustomer($sms_cust_id = '', $email_addr = '', $limitByRating = false)
    {
        if(strlen($sms_cust_id) > 0 && strlen($email_addr) > 0)
        {
            $duplicateSqlQuery = 'SELECT * FROM `' . _DB_PREFIX_ . 'shoplync_customer_survey` WHERE (sms_customer_id = '.$sms_cust_id.' OR email_address LIKE "'.$email_addr.'")'; 
            if($limitByRating)
                $duplicateSqlQuery .= ' AND rating IS NOT NULL';
            
            //error_log($duplicateSqlQuery);
            $duplicateEntries = Db::getInstance()->executeS($duplicateSqlQuery);

            return !empty($duplicateEntries); 
        }
        return false;
    }
    
    public static function AlreadyHasRating($sms_cust_id = '', $email_addr = '')
    {
        return !self::isValidCustomer($sms_cust_id, $email_addr, true);
    }
    
    public static function SetUserRating($sms_cust_id = '', $email_addr = '', $user_rating = 0)
    {
        if(strlen($sms_cust_id) > 0 && strlen($email_addr) > 0 && $user_rating > 0 && $user_rating <= 5)
        {
            $sqlQuery = 'SELECT * FROM `' . _DB_PREFIX_ . 'shoplync_customer_survey` WHERE (sms_customer_id = '.$sms_cust_id.' OR email_address LIKE "'.$email_addr.'") AND rating IS NULL';
            //error_log($sqlQuery);
            $customers = Db::getInstance()->executeS($sqlQuery);
            
            if(!empty($customers) && is_array($customers))
            {
                //error_log('return: '.print_r($customers, true));
                $sqlSetRatingQuery = 'UPDATE `' . _DB_PREFIX_.'shoplync_customer_survey` SET rating = '.$user_rating
                    .', rating_recieved = CURRENT_TIMESTAMP'
                    .' WHERE customer_survey_id = '.$customers[0]['customer_survey_id'];
                    
                Db::getInstance()->execute($sqlSetRatingQuery);
            }
        }
    }
    
    public static function SetUserFeedback($sms_cust_id = '', $email_addr = '', $user_feedback = '')
    {
        if(strlen($sms_cust_id) > 0 && strlen($email_addr) > 0 && strlen($user_feedback) > 0)
        {
            $sqlQuery = 'SELECT * FROM `' . _DB_PREFIX_ . 'shoplync_customer_survey` WHERE (sms_customer_id = '.$sms_cust_id.' OR email_address LIKE "'.$email_addr.'") AND feedback IS NULL';
            //error_log($sqlQuery);
            $customers = Db::getInstance()->executeS($sqlQuery);
            
            if(!empty($customers) && is_array($customers))
            {
                //error_log('return: '.print_r($customers, true));
                $sqlSetRatingQuery = 'UPDATE `' . _DB_PREFIX_.'shoplync_customer_survey` SET feedback = "'.pSQL(htmlentities($user_feedback)).'" '
                    .' WHERE customer_survey_id = '.$customers[0]['customer_survey_id'];
                    
                return Db::getInstance()->execute($sqlSetRatingQuery);
            }
        }
    }
    
    /*
     * Queries the database for all the vehicle makes
     *
     * $alias_id string - Ability to set the alias name for the make_id column
     * $visibleOnly boolean - Whether to filter out the 'hidden' types
     *
     * return array - The database result
    */
    protected static function GetOrderStates()
    {
        $lang = Configuration::get('PS_LANG_DEFAULT');
        if(is_null($lang) || $lang == 0)
        {
            $lang = 1;
        }
        
        $sql = 'SELECT os.id_order_state, osl.name FROM `' . _DB_PREFIX_ 
        . 'order_state` AS os LEFT JOIN `' . _DB_PREFIX_ 
        . 'order_state_lang` AS osl ON os.id_order_state = osl.id_order_state where osl.id_lang = '
        .$lang.' ORDER BY os.id_order_state';
        
        $result = Db::getInstance()->executeS($sql);
        
        if(empty($result))
            return [];
        
        return $result;
    }
    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        //get default languages
        //load order status with default language name
        //use the same for both shipped starting status
        //the delivered target status
        $order_states = self::GetOrderStates();
        
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'SHOPLYNC_CUSTOMER_SURVEY_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => 'Select Starting Order Status',
                        'name' => 'SHOPLYNC_CUSTOMER_SURVEY_START_ORDER_STATUS',
                        'class' => 'chosen',
                        'options' => array(
                            'optiongroup'=>array(
                                'label'=>'label',
                                'query'=>array(
                                    array(
                                        'label'=>'Select A Status',
                                        'options'=> $order_states,
                                    )
                                ),
                            ),
                            'options'=>array(
                                 'query'=>'options',
                                 'id'=>'id_order_state',
                                 'name'=>'name'
                            )
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'label' => 'Select Target Order Status',
                        'name' => 'SHOPLYNC_CUSTOMER_SURVEY_TARGET_ORDER_STATUS',
                        'class' => 'chosen',
                        'options' => array(
                            'optiongroup'=>array(
                                'label'=>'label',
                                'query'=>array(
                                    array(
                                        'label'=>'Select A Status',
                                        'options'=> $order_states,
                                    )
                                ),
                            ),
                            'options'=>array(
                                 'query'=>'options',
                                 'id'=>'id_order_state',
                                 'name'=>'name'
                            )
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'col' => 3,
                        'prefix' => '<i class="icon icon-clock-o"></i>',
                        'desc' => 'Enter the number of shipping days that will trigger email',
                        'name' => 'SHOPLYNC_CUSTOMER_THRESHOLD',
                        'label' => 'Delivered in x Days Threshold',
                    ),
                    array(
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-google"></i>',
                        'desc' => 'Enter Your Google Review Link',
                        'name' => 'SHOPLYNC_CUSTOMER_SURVEY_G_REVIEW',
                        'label' => 'Google Review Link',
                    ),
                    array(
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-facebook"></i>',
                        'desc' => 'Enter Your Facebook Review Link',
                        'name' => 'SHOPLYNC_CUSTOMER_SURVEY_FB_REVIEW',
                        'label' => 'Facebook Review Link',
                    ),
                    array(
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-comment"></i>',
                        'desc' => 'The prompt message displayed to the user when a user gives a high rating',
                        'name' => 'SHOPLYNC_CUSTOMER_SURVEY_SUBTITLE_1',
                        'label' => 'High User Rating Message',
                    ),
                    array(
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-comment"></i>',
                        'desc' => 'The prompt message displayed to the user when a user gives a low rating',
                        'name' => 'SHOPLYNC_CUSTOMER_SURVEY_SUBTITLE_2',
                        'label' => 'Low User Rating Message',
                    ),
                    array(
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-copy"></i>',
                        'desc' => 'The prompt message displayed to the user when trying to submit a second rating',
                        'name' => 'SHOPLYNC_CUSTOMER_SURVEY_SUBTITLE_3',
                        'label' => 'Already Recieved Rating Message',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'SHOPLYNC_CUSTOMER_SURVEY_LIVE_MODE' => Configuration::get('SHOPLYNC_CUSTOMER_SURVEY_LIVE_MODE', true),
            'SHOPLYNC_CUSTOMER_SURVEY_START_ORDER_STATUS' => Configuration::get('SHOPLYNC_CUSTOMER_SURVEY_START_ORDER_STATUS', 4),
            'SHOPLYNC_CUSTOMER_SURVEY_TARGET_ORDER_STATUS' => Configuration::get('SHOPLYNC_CUSTOMER_SURVEY_TARGET_ORDER_STATUS', 5),
            'SHOPLYNC_CUSTOMER_THRESHOLD' => Configuration::get('SHOPLYNC_CUSTOMER_THRESHOLD', 2),
            'SHOPLYNC_CUSTOMER_SURVEY_G_REVIEW' => Configuration::get('SHOPLYNC_CUSTOMER_SURVEY_G_REVIEW', ''),
            'SHOPLYNC_CUSTOMER_SURVEY_FB_REVIEW' => Configuration::get('SHOPLYNC_CUSTOMER_SURVEY_FB_REVIEW', ''),
            'SHOPLYNC_CUSTOMER_SURVEY_SUBTITLE_1' => Configuration::get('SHOPLYNC_CUSTOMER_SURVEY_SUBTITLE_1', 'We\'d love it if you left us a positive review.'),
            'SHOPLYNC_CUSTOMER_SURVEY_SUBTITLE_2' => Configuration::get('SHOPLYNC_CUSTOMER_SURVEY_SUBTITLE_2', 'We appreciate your feedback, please let us know how we can improve.'),
            'SHOPLYNC_CUSTOMER_SURVEY_SUBTITLE_3' => Configuration::get('SHOPLYNC_CUSTOMER_SURVEY_SUBTITLE_3', 'Please contact us if you have additional feedback or comments to share with us.'),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        
        if(is_array($form_values))
        {
            foreach (array_keys($form_values) as $key) {
                Configuration::updateValue($key, Tools::getValue($key));
            }
        }
        //process other  values
        //process tempelate file, if new one uploaded use that instead
        //process sms pro file?
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookActionOrderDetail()
    {
        /* Place your code here. */
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        /* Place your code here. */
        //error_log(print_r($params, true));
        //Configuration::get('SHOPLYNC_CUSTOMER_SURVEY_START_ORDER_STATUS', 4)
        if($params['newOrderStatus']->id == Configuration::get('SHOPLYNC_CUSTOMER_SURVEY_TARGET_ORDER_STATUS', 5))
        {
            //send out survey email

        }

        return;
    }

    public function hookActionOrderStatusUpdate()
    {
        /* Place your code here. */
        
    }

    public function hookDisplayCustomerAccount()
    {
        /* Place your code here. */
    }
    
    public static function SendSurveyMail($toCustomerName, $toCustomerEmail, $additional_vars = [], $subject = 'Thank You For Your Purchase!', $survey_template = 'rate_us')
    {
        //_PS_MAIL_DIR_./en/rate_us.html
        $template_path = file_exists(_PS_MAIL_DIR_.'en/rate_us.html') ? _PS_MAIL_DIR_ : _PS_MODULE_DIR_ . 'shoplync_customer_survey/mails';
        
        $default_vars = array(
            '{email}' => Configuration::get('PS_SHOP_EMAIL'), // sender email address
            '{message}' => 'Hello world' // email content
        );
        
        $tpl_vars = empty($additional_vars) ? $default_vars : array_merge($default_vars, $additional_vars);
        Mail::Send(
            (int)(Configuration::get('PS_LANG_DEFAULT')), // defaut language id
            $survey_template, // email template file to be use
            $subject, // email subject
            $tpl_vars,
            $toCustomerEmail, // receiver email address
            $toCustomerName, //receiver name
            NULL, //from email address
            NULL,  //from name
            NULL, //file attachment
            NULL, //mode smtp
            $template_path //custom template path
        );
    }
    
    public function hookAddWebserviceResources()
    {
        return array(
            'survey_customers' => array('description' => 'Manage Survey Customers', 'class' => 'SurveyCustomer'),
            'CustomerEmailUpload' => array('description' => 'Manage Vehicle Fitment', 'specific_management' => true),
        );
    }
    
    public function hookModuleRoutes($params)
    {
        $regex_pattern = '[_a-zA-Z0-9\pL\pS/.:+-]*';
        return array(
            //Will Allow Users to Specify Make-Model-Year (With/Without friendly name)
            // example.ca/2022-suzuki-gsxr1000/125-6810-68/
            'module-shoplync_customer_survey-review' => array(
                'controller' => 'review',
                'rule' => 'review/{rewrite}',
                'keywords' => array(
                    'rewrite' => array('regexp' => $regex_pattern),
                ),
                'params' => array(
                    'fc' => 'module',
                    'module' => 'shoplync_customer_survey',
                )
            ),
        );
    }
}
