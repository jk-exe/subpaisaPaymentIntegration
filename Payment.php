<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
clas Payment extends MY_Controller{

    private  $cypher_name = "aes-128-cbc";
    private  $cyper_key_len = 16; 
    /**
     * Home::__construct()
     * 
     * @return
     */
    public function __construct()
    {
        parent::__construct();
       $this->load->model('home/Payment_model','payment');
        $head = array();
        $data = array();
        
        $this->load->helper('math_helper');
        $this->load->helper('string_helper');
        $this->load->model('home/Aboutus_model','about');
        $this->load->library('pagination');
        $this->load->helper('pagination_helper');
        $this->load->module('template');
    }
    private  function fixKey($key) {
        
        if (strlen($key) < $this->cyper_key_len) {
           
            return str_pad("$key", $this->cyper_key_len, "0"); 
        }
        
        if (strlen($key) > $this->cyper_key_len) {
           
            return substr($key, 0, $this->cyper_key_len); 
        }
        return $key;
    }
    
    public function encrypt($key, $iv, $data) {
        echo 'Data value is :' .$data;
        $encodedEncryptedData = base64_encode(openssl_encrypt($data, $this->cypher_name, $this->fixKey($key), OPENSSL_RAW_DATA, $iv));
        $encodedIV = base64_encode($iv);
        $encryptedPayload = $encodedEncryptedData.":".$encodedIV;
        echo '$encryptedPayload value is :' .$encryptedPayload;
        return $encryptedPayload;
    }
    
    public function decrypt($key, $data) {
        $parts = explode(':', $data); //Separate Encrypted data from iv.
        $encrypted = $parts[0];
        $iv = $parts[1];
        $decryptedData = openssl_decrypt(base64_decode($encrypted), $this->cypher_name, $this->fixKey($key), OPENSSL_RAW_DATA, base64_decode($iv));
        return $decryptedData;
    }
  public function success()
  {
    $query=$_GET['query'];
    $payment_type = false;
    $payment_type = $_SESSION['payment_type'];
    switch( $payment_type )
    {
        case  "domestic":
        {
             $authKey = "your auth key ";                                         //Authentication Key Provided By Sabpaisa  (Mandatory only if authentication is enabled) 
             $authIV = "your auth iv"; 
             break;
        }
        case "international":
        {
            $authKey = "your auth key";                                         //Authentication Key Provided By Sabpaisa  (Mandatory only if authentication is enabled) 
            $authIV = "your auth iv"; 		                                  //Authentication IV Provided by Sabpaisa(Mandatory only if authentication is enabled)
             break; 
        }
        default :
        {
            $this->flashAndRedirect(0,"","No pyment type defined");
            redirect(base_url('payment'));
            break;
        }
    }
    $decText = null;
    $decText = $this->decrypt($authKey,$query);
    $responseArray = explode('&',$decText);
 
    $details= array();
    $details['first_name']=str_replace("firstName=","",$responseArray[7] );
    $details['last_name']=str_replace("lastName=","",$responseArray[8]);
    $details['email']=str_replace("email=","",$responseArray[10]);
    $details['mobile']=str_replace("mobileNo=","",$responseArray[11]);
    $details['amount']=str_replace("amount=","",$responseArray[5]);
    $details['payment_array']=serialize($responseArray);
    $details['status']=str_replace("spRespStatus=","",$responseArray[18]);
    $details['payment_date']=date('Y-m-d H:i:s');
    $details['pay_mode'] = str_replace("payMode=","",$responseArray[9]);
    $details['PGTxnNo']=str_replace("PGTxnNo=","",$responseArray[1]);
    $details['SabPaisaTxId']=str_replace("SabPaisaTxId=","",$responseArray[2]);
    $details['issuerRefNo']=str_replace("issuerRefNo=","",$responseArray[3]);
    $insert = $this->payment->save_payment($details);
    $this->flashAndRedirect($insert,"Payment is Done Successfully","Some Error Occourred");
    redirect(base_url('payment'));
  }
  public function failure()
  {
    $query=$_GET['query'];
    $payment_type = false;
    $payment_type = $_SESSION['payment_type'];
    switch( $payment_type )
    {
        case  "domestic":
        {
             $authKey = "your auth key";                                         //Authentication Key Provided By Sabpaisa  (Mandatory only if authentication is enabled) 
             $authIV = "your auth iv";
             break; 
        }
        case "international":
        {
            $authKey = "your_auth_key";                                         //Authentication Key Provided By Sabpaisa  (Mandatory only if authentication is enabled) 
            $authIV = "your_auth_iv";
            break; 		                                  //Authentication IV Provided by Sabpaisa(Mandatory only if authentication is enabled)
                    
        }
        default :
        {
            $this->flashAndRedirect(0,"","No pyment type defined");
            redirect(base_url('payment'));//your default payment url where payment get initiated//
            break;
        }
    }
    $decText = null;   
    $details = array();
    $decText = $this->decrypt($authKey,$query);
    $responseArray = explode('&',$decText);
    $details['pay_mode'] = str_replace("payMode=","",$responseArray[9]);
    $details['first_name']=str_replace("firstName=","",$responseArray[7] );
    $details['last_name']=str_replace("lastName=","",$responseArray[8]);
    $details['email']=str_replace("email=","",$responseArray[10]);
    $details['mobile']=str_replace("mobileNo=","",$responseArray[11]);
    $details['amount']=str_replace("amount=","",$responseArray[5]);
    $details['payment_array']=serialize($responseArray);
    $details['status']=str_replace("spRespStatus=","",$responseArray[18]);
    $details['payment_date']=date('Y-m-d H:i:s');
   
    $details['PGTxnNo']=str_replace("PGTxnNo=","",$responseArray[1]);
    $details['SabPaisaTxId']=str_replace("SabPaisaTxId=","",$responseArray[2]);
    $details['issuerRefNo']=str_replace("issuerRefNo=","",$responseArray[3]);
    $insert = $this->payment->save_payment($details);
    $this->flashAndRedirect($insert,"Payment  Failed Try Again","Some Error Occourred");
    redirect(base_url('payment'));//your default payment url
  }
    
    public function index()
    {
        $data['title']="Payment";
        $data['discription']='';
        $data['banner']=false;
        $data['tagline']=false;
        $data['view_content']='home/payment/payment-form';
        
        if($this->input->post('submit_payment'))
        {
            $post_data = $this->input->post();
            $payment_type = $post_data['payment_type'];
            $this->session->set_userdata('payment_type',$payment_type);
            switch($payment_type)
            {
                case  "domestic":
                {
                    $spURL = null;
                    $spDomain = "https://securepay.sabpaisa.in/SabPaisa/sabPaisaInit";    //URL provided by SabPaisa(Mandatory) 
                    
                    $username = "user_name";                                            //Username provided by Sabpaisa (Mandatory) 
                    $password = "password";                          				  //Password provided by Sabpaisa (Mandatory) 
                    $programID=rand(1000225,9999999);                                       //Transaction ID (Mandatory) 
                    $clientCode = "clientcode";                                       //Client Code Provided by Sabpaisa (Mandatory) 
                    $authKey = "authkey";                                         //Authentication Key Provided By Sabpaisa  (Mandatory only if authentication is enabled) 
                    $authIV = "authiv"; 		                                  //Authentication IV Provided by Sabpaisa(Mandatory only if authentication is enabled)
                    $txnId=rand(1000225,9999999); 
                    break;
                }
                case "international":
                {
                    $spURL = null;
                    $spDomain = "https://securepay.sabpaisa.in/SabPaisa/sabPaisaInit";    //URL provided by SabPaisa(Mandatory) 
                    //$spDomain = "http://192.168.1.142:8080/SabPaisa/sabPaisaInit";
                    $username = "user_name";                                            //Username provided by Sabpaisa (Mandatory) 
                    $password = "password";                          				  //Password provided by Sabpaisa (Mandatory) 
                    $programID=rand(1000225,9999999);                                       //Transaction ID (Mandatory) 
                    $clientCode = "client_code";                                       //Client Code Provided by Sabpaisa (Mandatory) 
                    $authKey = "auth_key";                                         //Authentication Key Provided By Sabpaisa  (Mandatory only if authentication is enabled) 
                    $authIV = "auth_iv"; 		                                  //Authentication IV Provided by Sabpaisa(Mandatory only if authentication is enabled)
                    $txnId=rand(1000225,9999999); 
                    break;
                } 
                default:
                {
                    $this->flashAndRedirect(0,"","payment type not defined");
                    redirect(base_url('payment'));
                }
            }
            
                                         // Unique For Every Transaction
            $txnAmt = $_POST['amount'];                          //Transaction Amount (Mandatory)
            $URLsuccess = base_url('payment-success');                                   //Return URL upon successful transaction (Optional)
            $URLfailure = base_url('payment-failure');                                  //Return URL upon failed Transaction (Optional)
            $payerFirstName = $_POST['fname'];        //Payer's First Name (Optional)
            $payerLastName = $_POST['lname'];         //Payer's Last Name (Optional)
            $payerContact = $_POST['mobile'];         //Payer's Contact Number (Optional)
            $payerEmail = $_POST['email'];           //Payer's Email ID


            $spURL ="?clientName=".$clientCode."&usern=".$username."&pass=".$password."&amt=".$txnAmt."&txnId=".$txnId."&firstName=".$payerFirstName."&lstName=".$payerLastName."&contactNo=".$payerContact."&Email=".$payerEmail."&ru=".$URLsuccess."&failureURL=".$URLfailure;
            
            $spURL = $this->encrypt($authKey,$authIV,$spURL); 
            $spURL = str_replace("+", "%2B",$spURL); 
            $spURL="?query=".$spURL."&clientName=".$clientCode;  
            $spURL = $spDomain.$spURL; 
            redirect($spURL);

        }
        
        
        $this->template->base_template($data);
    }

   
}
?>