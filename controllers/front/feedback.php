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


/**
 * Class itself
 */
class shoplync_customer_surveyfeedbackModuleFrontController extends ModuleFrontController
{   
    /**
     * Save form data.
     */
    public function postProcess()
    {
        return parent::postProcess(); 
    }
 


/**
* ====================================
* Filter/Search Page
* ====================================
*/ 
    /**
     * This function sets the appropritate error headers and returns the default 'Failed' error response
     * 
     * $errorMessage string - The error message to return
     * $extra_details array() - array of key:value pairs to be added to the error json response
     * 
    */
    public function setErrorHeaders($errorMessage = 'Failed', $extra_details = [])
    {
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: application/json; charset=UTF-8');
        
        $error_array = ['errorResponse' => $errorMessage];
        
        if(!empty($extra_details) && is_array($extra_details))
            $error_array = $error_array + $extra_details;
        
        $this->ajaxDie(json_encode($error_array));
    }
    
    /**
    * Triggered via an AJAX call, unsets all the values stored in the cookie
    */
    public function displayAjaxSubmitFeedback()
    {
        error_log('Fetching Vehicle details');
        if (Tools::isSubmit('smsID') && Tools::isSubmit('email') && Tools::isSubmit('feedbackComment'))
        {
            $sms_id = (int)Tools::getValue('smsID', 0);
            $email = Tools::getValue('email', '');
            $customer_comment = Tools::getValue('feedbackComment', '');
            
            if($sms_id > 0 && strlen($email) > 0 && strlen($customer_comment)
                && Shoplync_customer_survey::isValidCustomer($sms_id, $email))
            {
                $result = Shoplync_customer_survey::SetUserFeedback($sms_id, $email, $customer_comment);
                if($result === FALSE)
                    $this->setErrorHeaders('Failed To Save Feedback.');
                else
                {
                    $this->ajaxDie(json_encode([
                        'success' => true,
                    ]));
                }
            }
        }
        else
            $this->setErrorHeaders('Failed To Save Feedback.');
    }    
}