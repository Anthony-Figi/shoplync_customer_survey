<?php
/**
* @author    Anthony Figueroa - Shoplync Inc <sales@shoplync.com>
* @copyright 2007-2022 Shoplync Inc
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
* @category  PrestaShop module
* @package   Bike Model Filter
*      International Registered Trademark & Property of Shopcreator
* @version   1.0.0
* @link      http://www.shoplync.com/
*/
class SurveyCustomer extends ObjectModel {
 
    /** @var int SMS Customer ID */ 
    public $sms_customer_id;
 
    /** @var int Prestashop Customer ID */ 
    public $ps_customer_id;
 
    /** @var string Customer Name */ 
    public $customer_name; 
    
    /** @var string Customer Email Address */ 
    public $email_address; 
    
    /** @var string Customer Website Link */ 
    public $website_link; 
    
    /** @var datetime E-email sent date */ 
    public $email_sent;  
    
    /** @var datetime E-email sent date */ 
    public $email_opened;  
    
    /** @var datetime Rating recieved date */ 
    public $rating_recieved; 
    
    /** @var int Customer Rating */ 
    public $rating; 
    
    /** @var string The Customers Written Feedback*/ 
    public $feedback;
 
    /** @var datetime E-email sent date */ 
    public $review_link_clicked;  
    /** @var datetime E-email sent date */ 
    public $reminder_sent;  
 
    /**
     * Definition of class parameters
     */ 
    public static $definition = array( 
        'table' => 'shoplync_customer_survey', 
        'primary' => 'customer_survey_id', 
        'multilang' => false, 
        'multilang_shop' => false, 
        'fields' => array( 
            'sms_customer_id' => array('type' => self::TYPE_INT), 
            'ps_customer_id' => array('type' => self::TYPE_INT), 
            
            'customer_name' => array('type' => self::TYPE_STRING), 
            'email_address' => array('type' => self::TYPE_STRING), 
            'website_link' => array('type' => self::TYPE_STRING), 
            
            'email_sent' => array('type' => self::TYPE_DATE), 
            'email_opened' => array('type' => self::TYPE_DATE), 
            'rating_recieved' => array('type' => self::TYPE_DATE),
            
            'rating' => array('type' => self::TYPE_INT), 
            'feedback' => array('type' => self::TYPE_STRING), 
            'review_link_clicked' => array('type' => self::TYPE_DATE),
            'reminder_sent' => array('type' => self::TYPE_BOOL),
        ), 
    );
 
    /**
     * Mapping of the class with the webservice
     *
     * @var type
     */ 
    protected  $webserviceParameters  =  [ 
        'objectsNodeName' => 'survey_customers',  //objectsNodeName must be the value declared in hookAddWebserviceResources(entity list) 
        'objectNodeName' => 'survey_customer',  // Detail of an entity 
        'fields' => [] 
    ]; 
}