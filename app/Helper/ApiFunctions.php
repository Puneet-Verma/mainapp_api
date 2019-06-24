<?php

namespace App\Helper;

use App\Model\ActivityLog;
use Session;
use Auth;
use App\Useragent\UserAgent;
use App\Http\Controllers\API\MobileNumber;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class ApiFunctions{
    public function getRegExprValidateMoNumber($mobileNo,$endpoint){
        $regexMobileNumber = "/^([(])([+]*)([0-9]+)([)])[0-9]+$/";
        $mobileNumber = new MobileNumber();
        if (preg_match($regexMobileNumber,$mobileNo)){
             $mobileNumber= $this->getCountryCodeAndMoNumber($mobileNo);
        }
        else{
            return response()->json(array('success' => false,'error' => 'Bad Request', 'message' => "Invalid mobile number format"),400);
        }
        return $mobileNumber;
    }
    public function getCountryCodeAndMoNumber($mobileNoWithCountryCd) {
        $countryCode;
        $splitNum = explode(")",$mobileNoWithCountryCd);
        $countryCdSplit = explode("(",$splitNum[0]);
        $countryCdStr =explode("+",$countryCdSplit[1]);
        if (strlen($countryCdStr[0]) == 0) {
            $countryCode = $countryCdStr[1];
        } else {

            $countryCode = $countryCdStr[0];
        }

        $mobileNumber = $splitNum[1];

        $mobileManager = new MobileNumber();
        $mobileManager->countryCode = $countryCode;
        $mobileManager->nationalMobileNumber= $mobileNumber;
        return $mobileManager;
    }
    public static function isUserLoggedIn($token){
		try {

			if (! $user = JWTAuth::parseToken($token)->authenticate()) {
				return false;
			}
			else{
				return $user;
			}
		}
		catch (TokenExpiredException $e) {
			return false;
		}
		catch (TokenInvalidException $e) {
			return false;
		}
		catch (JWTException $e) {
			return false;
		}
		catch (Exception $e) {
			return false;
		}
	}
}
