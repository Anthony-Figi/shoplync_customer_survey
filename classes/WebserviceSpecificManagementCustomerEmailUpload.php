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
use PrestaShop\PrestaShop\Core\File\Exception\FileUploadException;
use PrestaShop\PrestaShop\Core\File\Exception\MaximumSizeExceededException;
use PrestaShop\PrestaShop\Core\File\FileUploader;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Response;

/**
 * This class is responsible for managing VehicleFitments through webservice
 */
class WebserviceSpecificManagementCustomerEmailUpload implements WebserviceSpecificManagementInterface {
    
    /**
     * @var WebserviceRequest
     */
    protected $wsObject;

    /**
     * @var string
     */
    protected $output;
    
    /**
     * @var WebserviceOutputBuilder
     */
    protected $objOutput;
    
    /**
     * @var array|null
     */
    protected $displayFile;
    
    /**
     * @var array The list of supported mime types
     */
    protected $acceptedMimeTypes = [
        'text/csv', 'text/plain', 
        'application/csv', 'application/x-csv', 
        'text/x-csv', 'text/comma-separated-values', 
        'text/x-comma-separated-values', 'text/tab-separated-values',
        'application/vnd.ms-excel'
    ];
    
    /**
     * @var int The maximum size supported when uploading images, in bytes
     */
    protected $maximumSize = 3000000;


    /**
     * @param WebserviceOutputBuilder $obj
     *
     * @return WebserviceSpecificManagementInterface
     */
    public function setObjectOutput(WebserviceOutputBuilderCore $obj)
    {
        $this->objOutput = $obj;

        return $this;
    }
    
    /**
     * Get Object Output
     */
    public function getObjectOutput()
    {
        return $this->objOutput;
    }

    public function setWsObject(WebserviceRequestCore $obj)
    {
        $this->wsObject = $obj;
        
        return $this;
    }

    public function getWsObject()
    {
        return $this->wsObject;
    }

    protected function defaultResponse()
    {
        $more_attr = [
            'get' => 'true', 
            'put' => 'false', 
            'post' => 'true', 
            'delete' => 'false', 
            'head' => 'true',
            'upload_allowed_mimetypes' => implode(', ', $this->acceptedMimeTypes),
        ];
        
        $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader('file_types', []);
        $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader('csv', [], $more_attr, false);
        $this->output .= $this->objOutput->getObjectRender()->renderNodeFooter('file_types', []);
    }
    
    public function manage()
    {
        $method = $this->wsObject->method;
        
        if(isset($method) && $method == 'POST')
        {//see if it goe sot switch 
            switch($this->getWsObject()->urlSegment[1])
            {
                case 'customers':
                    $this->processPostedFile();
                    break;
                default:
                    $this->defaultResponse();
                    return true;
            }
        }
        else
        {
            $this->defaultResponse();
            return true;
        }
        
        return $this->getWsObject()->getOutputEnabled();
    }

    /**
     * Gets the mime file type for the given file
     *
     * @param $_FILES array $arry
     *
     * @return string
     */
    protected function GetMimeType($file = null)
    {
        if (!isset($file['tmp_name']))
        {
            $file = $_FILES['file'];
        }
     
        // Get mime content type
        $mime_type = false;
        if (Tools::isCallable('finfo_open')) {
            $const = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
            $finfo = finfo_open($const);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        } elseif (Tools::isCallable('mime_content_type')) {
            $mime_type = mime_content_type($file['tmp_name']);
        } elseif (Tools::isCallable('exec')) {
            $mime_type = trim(exec('file -b --mime-type ' . escapeshellarg($file['tmp_name'])));
        }
        if (empty($mime_type) || $mime_type == 'regular file') {
            $mime_type = $file['type'];
        }
        if (($pos = strpos($mime_type, ';')) !== false) {
            $mime_type = substr($mime_type, 0, $pos);
        }
        
        
        return $mime_type;
    }
    /**
    * Check the given mime type to see if it is part of the acceptedMimeTypes
    *
    * $mime_type string - the mime type to be checked
    *
    * return boolean - Whether the given mim type is value true/false
    */
    protected function isValidMimeType($mime_type = null)
    {
        if (!isset($mime_type))
        {
            return false;
        }
        
        error_log('type: '.$mime_type.'file: '.print_r($_FILES['file'], true));
        
        if (!$mime_type || !in_array($mime_type, $this->acceptedMimeTypes)) {
            throw new WebserviceException('This type of image format is not recognized, allowed formats are: ' . implode('", "', $this->acceptedMimeTypes), [73, 400]);
        } elseif ($_FILES['file']['error']) {
            // Check error while uploading
            throw new WebserviceException('Error while uploading image. Please change your server\'s settings', [74, 400]);
        }
        
        return true;
    }

    
    
    /**
    * This helper function is used to determine which row processor to pass the parsed CSV line to
    *
    * $type int - The specific file type being processed
    */
    public function processPostedFile()
    {
        if (isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name']) {
            $file = $_FILES['file'];
            if ($file['size'] > $this->maximumSize) {
                throw new WebserviceException(sprintf('The image size is too large (maximum allowed is %d KB)', ($this->maximumSize / 1000)), [72, 400]);
            }
            
            // Get mime content type
            $mime_type = $this->GetMimeType($file);
            
            //process csv file
            if (($handle = fopen($file['tmp_name'], "r")) !== FALSE && $this->isValidMimeType($mime_type)) {
                while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if(!is_numeric($row[0]))
                        continue;//skip possible header
                    $this->processPostedFitmentFile($row);
                }
                fclose($handle);
            }
            else 
                $this->defaultResponse();
        }
    }
    
    /**
    * Checks to see whether there row meets a valid column/parameter count
    *
    * $row array - The parse row stored as an array
    * $count int - The required parameters/column the row must have
    *
    * return Whether the count was met or not
    */
    protected static function isValidRowCount($row = [], $count = 2)
    {
        if(count($row) !== $count)
        {
             error_log(sprintf('Error: Row count %d, expected %d', count($row), $count));
             return false;
        }
        return true;
    }
    
    protected static function AlreadyExists($sms_cust_id = '', $ps_cust_id = '', $email_addr = '')
    {
        $smsCustID = strlen($sms_cust_id) > 0 ? $sms_cust_id : '0';
        $psCustID = strlen($ps_cust_id) > 0 ? $ps_cust_id : '0';
        $emailAddr = strlen($email_addr) > 0 ? $email_addr : '';
        
        $duplicateSqlQuery = 'SELECT * FROM ' . _DB_PREFIX_ . 'shoplync_customer_survey WHERE sms_customer_id = '.$smsCustID.' OR ps_customer_id = '.$psCustID.' OR email_address LIKE "'.$emailAddr.'"';
        $duplicateEntries = Db::getInstance()->executeS($duplicateSqlQuery);
        
        return $duplicateEntries;
    }
    protected static function MakeObjArray($row = null) 
    {
        if(is_array($row) && self::isValidRowCount($row, 10))
        {
            //Determine website link
            $link = strlen($row[9]) > 0 ? $row[9] : $row[2];
            
            return array(
                'sms_customer_id' => $row[0],
                'ps_customer_id' => $row[1],
                'website_link' => $link,
                'customer_name' => $row[3],
                'customer_email' => $row[4],
            );
        }
    }
    /**
    * This function processes row by row vehicle fitment and saves to database
    * 
    * $row array - The current row to process
    */
    protected function processPostedFitmentFile($row = null)
    {
        //Column count
        if(is_array($row) && $this->isValidRowCount($row, 10))
        {
            $rowObj = self::MakeObjArray($row);
            //check if exists sms id / ps id or email
            //shoplync_customer_survey
            $duplicateEntries = self::AlreadyExists($rowObj['sms_customer_id'], $rowObj['ps_customer_id'], $rowObj['customer_email']);
            if(empty($duplicateEntries))
            {
                $psCustID = isset($rowObj['ps_customer_id']) && strlen($rowObj['ps_customer_id']) > 0 ? $rowObj['ps_customer_id'] : 'NULL';
                //insert into  db if no exists
                $sqlInsert = 'INSERT INTO ' . _DB_PREFIX_ . 'shoplync_customer_survey(sms_customer_id, ps_customer_id, customer_name, email_address, website_link, email_sent) '
                .'VALUES('.$rowObj['sms_customer_id'].', '.$psCustID.', "'.$rowObj['customer_name'].'", "'.$rowObj['customer_email'].'", "'.$rowObj['website_link'].'", CURRENT_TIMESTAMP'.')';
                
                if(Db::getInstance()->execute($sqlInsert) == FALSE)
                {
                    error_log('SQL Query Failed: '.$sqlInsert);
                    return;
                }   
                //if new send email with template use name/id/site ...etc
                error_log('email_sent');
                Shoplync_customer_survey::SendSurveyMail($rowObj['customer_name'], $rowObj['customer_email'], array(
                    '{firstname}' => $rowObj['customer_name'],
                    '{lastname}' => '',
                    '{rating_url}' => $rowObj['website_link'].'/review/?sms-id='.$rowObj['sms_customer_id'].'&email='.$rowObj['customer_email'],
                    '{customer_shop_url}' => $rowObj['website_link'],
                ));
            }
        }
    }

    /**
     * This must be return a string with specific values as WebserviceRequest expects.
     *
     * @return string
     */
    public function getContent()
    {
        if ($this->output != '') {
            return $this->objOutput->getObjectRender()->overrideContent($this->output);
        }

        return '';
    }

}