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

$link = _PS_BASE_URL_;
$link = str_replace('http://', '', $link);
$link = str_replace('https://', '', $link);
$link = rtrim($link, '/');

$sql = array();

/*$sql[0] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'shoplync_customer_survey` (
    `id_shoplync_customer_survey` int(11) NOT NULL AUTO_INCREMENT,
    PRIMARY KEY  (`id_shoplync_customer_survey`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';*/

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'shoplync_customer_survey` (
    `customer_survey_id` int(11) NOT NULL AUTO_INCREMENT,
    `sms_customer_id` int(11) NOT NULL,
    `ps_customer_id` int(10) unsigned,
    `customer_name` varchar(255) NOT NULL,
    `email_address` varchar(255) NOT NULL,
    `website_link` varchar(255) NOT NULL DEFAULT "'. $link .'",
    `email_sent` datetime DEFAULT NULL,
    `email_opened` datetime DEFAULT NULL,    
    `rating_recieved` datetime DEFAULT NULL,
    `rating` int(11) DEFAULT NULL,
    `feedback` varchar(255),
    `review_link_clicked` datetime DEFAULT NULL,
    `reminder_sent` bool DEFAULT FALSE NOT NULL,
    PRIMARY KEY  (`customer_survey_id`),
    UNIQUE KEY `unique_sms_customer_id` (sms_customer_id),
    UNIQUE KEY `unique_email_address` (email_address),
    FOREIGN KEY (`ps_customer_id`) REFERENCES ' . _DB_PREFIX_ . 'customer(`id_customer`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        error_log('SQL Query Failed: '.$query);
        return false;
    }
}
