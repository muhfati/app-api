<?php

namespace App\Http\Controllers\API\ZanMalipo;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\ZanMalipo\ZanMalipoController;
use Illuminate\Http\Request;

class ZanMalipoController extends Controller
{
    //read private key
    public static function getPrivateKey($keyPass, $keyAlias, $keyFilePath){
        $privateKey ="";
        
        if (!$cert_store = file_get_contents($keyFilePath,'r+')) {
            echo "Error: Unable to read the cert file\n";
            
            exit;
        }
        else
        {
            if(!empty($keyAlias))
            {
                if (openssl_pkcs12_read($cert_store, $cert_info, $keyPass))
                {
                    $privateKey = $cert_info['pkey'];
                }
                else {
                    echo "Error: Unable to parse the PKCS#12 certificate.\n";
                    echo "OpenSSL error: " . openssl_error_string() . "\n";
                    exit;
                }
            }
        }       
        return $privateKey;
    }

    //create signature 
    public static function createSignature($content,$privateKeyPass,$privateKeyAlias, $privateKeyFilePath){
        $signature = "";
        $privateKey = self::getPrivateKey($privateKeyPass,$privateKeyAlias,$privateKeyFilePath);
        if(!empty($privateKey) && !empty($content))
        {
            
            openssl_sign($content, $signature, $privateKey, "sha1WithRSAEncryption");
            $signature = base64_encode($signature);
        }
        return $signature;
    }

    //read public key given by ZanMalipo
    public static function getPublicKey($keyPass, $keyAlias, $keyFilePath) {

        $publicKey ="";
        if (!$pcert_store = file_get_contents($keyFilePath,'r+')) {
            echo "Error: Unable to read the cert file\n";
            exit;
        }
        else
        {
            if(!empty($keyPass))
            {
                if (openssl_pkcs12_read($pcert_store,$pcert_info,$keyPass)) {
                    $publicKey = $pcert_info['extracerts']['0'];
                }
            }
        }
        return $publicKey;

    }

    //verify signature from ZanMalipo
    public static function verifySignature($signature, $content, $publicKeyPass, $publicKeyAlias,$publicKeyFilePath)
    {
        $t = FALSE;
        $publicKey = self::getPublicKey($publicKeyPass, $publicKeyAlias, $publicKeyFilePath);
        if(!empty($publicKey) && !empty($content))
        {
            $rawsignature = base64_decode($signature);
            $status = openssl_verify($content, $rawsignature, $publicKey);
            if ($status == 1) {
                $t = true;
            } else if ($status == 0) {
                echo "\n\nSignature Status:".$status; //echo "BAD";
            }
        }
        return $t;
    }

    //sent payload to ZanMalipo
    public static function sendRequest($requestString,$url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                            'Content-Type:application/xml',
                            'Gepg-Com:default.sp.in', //testing SP Code
                            'Gepg-Code:SP20002' //testing SP Code
                            )
        );
    
      curl_setopt($ch, CURLOPT_TIMEOUT, 50);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
    
      $ackxml = curl_exec($ch);
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      return $ackxml;
    }

    public static function reuseBillSendRequest($requestString,$url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                            'Content-Type:application/xml',
                            'Gepg-Com:reusebill.sp.in', //testing SP Code
                            'Gepg-Code:SP20002' //testing SP Code
                            )
        );
    
      curl_setopt($ch, CURLOPT_TIMEOUT, 50);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
    
      $ackxml = curl_exec($ch);
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      return $ackxml;
    }

    public static function changeBillSendRequest($requestString,$url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestString);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                            'Content-Type:application/xml',
                            'Gepg-Com:changebill.sp.in',
                            'Gepg-Com:default.sp.in', //testing SP Code
                            'Gepg-Code:SP20002' //testing SP Code
                            )
        );
    
      curl_setopt($ch, CURLOPT_TIMEOUT, 50);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
    
      $ackxml = curl_exec($ch);
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);
      return $ackxml;
    }
    
}
