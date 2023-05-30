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
        $val = Tools::getValue('action');
        if($val == 'opened')
        {
            self::MailOpened();
            die(' ');
        }
        else
            return parent::postProcess(); 
    }


    public static function MailOpened() 
    {
        if((bool)Tools::isSubmit('sms-id') && (bool)Tools::isSubmit('email'))
        {
            $sms_id = (int)Tools::getValue('sms-id');
            $user_email = Tools::getValue('email');
            
            //if not a valid user continue
            if(Shoplync_customer_survey::isValidCustomer($sms_id, $user_email) && $sms_id > 0)
            {
                if(Shoplync_customer_survey::SetEmailOpened($sms_id, $user_email) === FALSE)
                {
                    error_log('Failed to set email opened');
                }
            }
        }

        header( 'Accept-Ranges: bytes');
        header( 'Cache-Control: private, no-cache, no-store, max-age=0, must-revalidate, no-cache="Set-Cookie"' );
        header( 'Connection: close' );
        header( 'Content-Length: 96' );
        header( 'Content-Type: image/png');
        header( 'P3P: CP="CAO DSP TAIa OUR NOR UNI"');
        header( 'Pragma: no-cache' );
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZgAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=');
    }

/**
* ====================================
* Save User Feedback
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
        error_log('Saving User Feedback');
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
    /**
    * Triggered via an AJAX call, unsets all the values stored in the cookie
    */
    public function displayAjaxLinkClicked()
    {
        error_log('Review Link Clicked');
        if (Tools::isSubmit('smsID') && Tools::isSubmit('email'))
        {
            $sms_id = (int)Tools::getValue('smsID', 0);
            $email = Tools::getValue('email', '');
            
            if($sms_id > 0 && strlen($email) > 0
                && Shoplync_customer_survey::isValidCustomer($sms_id, $email))
            {
                $result = Shoplync_customer_survey::ReviewLinkClicked($sms_id, $email);
                if($result === FALSE)
                    $this->setErrorHeaders('Failed To Save Link Clicked.');
                else
                {
                    $this->ajaxDie(json_encode([
                        'success' => true,
                    ]));
                }
            }
        }
        else
            $this->setErrorHeaders('Failed To Set link Clicked.');
    } 
}