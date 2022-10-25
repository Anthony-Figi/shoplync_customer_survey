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
class shoplync_customer_surveyreviewModuleFrontController extends ModuleFrontController
{
    /**
     * For allow SSL URL
     */
    public $ssl = true;

    /**
     * String Internal controller name
     */
    public $php_self = 'review';

    public $productList = null;
    public $productDetailsList = array();
    public $productRejectList = array();

    /**
     * Sets default medias for this controller
     */
    public function setMedia()
    {
        /**
         * Set media
         */
        parent::setMedia();
        
        $this->addCSS($this->module->getLocalPath().'/views/css/front.css');
        $this->addJS($this->module->getLocalPath().'/views/js/front.js');
       
    }

    /**
     * Redirects to canonical or "Not Found" URL
     *
     * !!!! There was not parameter which generated a "strict standards" warning
     *
     * @param string $canonical_url
     */
    public function canonicalRedirection($canonical_url = '')
    {
        //parameter added to function
        $canonical_url=null;
        if (Tools::getValue('live_edit')) {
            return $canonical_url;
        }
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        return $breadcrumb;
    }

    /**
     * Initializes controller
     *
     * @see FrontController::init()
     * @throws PrestaShopException
     */
    public function init()
    {
        $this->page_name = 'review';

        $this->display_column_left = false;
        $this->display_column_right = false;

        parent::init();
    }

    /**
     * Initializes page content variables
     */
    public function initContent()
    {
        parent::initContent();

        if((bool)Tools::isSubmit('rating') && (bool)Tools::isSubmit('sms-id') && (bool)Tools::isSubmit('email'))
        {   
            $user_rating = (int)Tools::getValue('rating');
            $sms_id = (int)Tools::getValue('sms-id');
            $user_email = Tools::getValue('email');
            $title_str = 'Thank you for your feedback!';
            $subtitle_str =  '';
            $top_icon = '&#xE86c;';
            $google_review_link = '';
            $facebook_review_link = '';
            
            //if not a valid user redirect to 404.php
            if(Shoplync_customer_survey::isValidCustomer($sms_id, $user_email) && $user_rating > 0 && $user_rating <= 5)
            {
                //check if rating already recieved
                if(Shoplync_customer_survey::AlreadyHasRating($sms_id, $user_email))
                {
                    Shoplync_customer_survey::SetUserRating($sms_id, $user_email, $user_rating);
                    if($user_rating >= 4)
                    {
                        $subtitle_str = Configuration::get('SHOPLYNC_CUSTOMER_SURVEY_SUBTITLE_1', 
                        'We\'d love it if you took a minute to post an online review.');
                        
                        $google_review_link = Configuration::get('SHOPLYNC_CUSTOMER_SURVEY_G_REVIEW', '');
                        $facebook_review_link = Configuration::get('SHOPLYNC_CUSTOMER_SURVEY_FB_REVIEW', '');
                        if(strlen($google_review_link) > 0)
                        {
                            $this->context->smarty->assign('g_link', $google_review_link);
                        }
                        
                        if(strlen($facebook_review_link) > 0)
                        {
                            $this->context->smarty->assign('fb_link', $facebook_review_link);
                        }
                    }
                    else
                    {
                        $top_icon = '&#xE0b7;';
                        $subtitle_str = Configuration::get('SHOPLYNC_CUSTOMER_SURVEY_SUBTITLE_2', 
                        'We appreciate your feedback, please let us know how we can improve.');
                        $this->context->smarty->assign('review_box', true);
                    }
                }
                else
                {
                    $title_str = 'We\'ve already received your feedback - thank you!';
                    $subtitle_str = Configuration::get('SHOPLYNC_CUSTOMER_SURVEY_SUBTITLE_3', 
                    'Please contact us if you have additional feedback or comments to share with us.');
                }
                $this->context->smarty->assign('title_str', $title_str);
                $this->context->smarty->assign('subtitle_str', $subtitle_str);
                $this->context->smarty->assign('top_icon', $top_icon);
                $this->context->smarty->assign('user_rating', $user_rating);
                Media::addJsDef([
                    'adminajax_link' => $this->context->link->getModuleLink('shoplync_customer_survey', 'feedback', array(), true),
                    'sms_customer_id' => $sms_id,
                    'customer_email' => $user_email
                ]);
                
                
                $this->setTemplate('module:shoplync_customer_survey/views/templates/front/review.tpl');
            }
            else 
            {
                Tools::redirect('index.php');
            }
        }
        else
        {
            Tools::redirect('index.php');
        }            
    }

    /**
     * Save form data.
     */
    public function postProcess()
    {
        return parent::postProcess(); 
    }
}
