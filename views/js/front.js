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
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/
function SubmitFeedback()
{
    var feedbackBox = document.getElementById('feedbackBox');
    if(feedbackBox && sms_customer_id && customer_email)
    {
        var feedbackStr = feedbackBox.value;
        if(feedbackStr.length > 0)
        {
            $.ajax({
                type: 'POST',
                cache: false,
                dataType: 'json',
                url: adminajax_link, 
                data: {
                    ajax: true,
                    action: 'submitFeedback',//lowercase with action name
                    smsID: sms_customer_id,
                    email: customer_email,
                    feedbackComment: feedbackStr
                },
                success : function (data) {
                    if(data && data.success)
                    {
                        console.log('Feedback Sumbitted');
                        var feedbackRecievedBox = document.getElementById('feedback-received');
                        var inputedFunctions = document.getElementById('submit-inputs');
                        if(feedbackRecievedBox && inputedFunctions)
                        {
                            feedbackRecievedBox.style.display = 'block';
                            inputedFunctions.style.display = 'none';
                        }
                    }
                },
                error : function (data){
                    console.log('FAILED');
                    console.log(data);
                }
            });
        }
    }
}