<?php

namespace App\Http\Controllers;

use App\User;
use App\Model\appUser;
use GuzzleHttp\Client;
use App\Helper\ApiFunctions;
use Illuminate\Http\Request;
use DB;
//use Auth;
//use JWTAuth;
//use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function authenticate(Request $request)
    {
        try{

            $fn = new ApiFunctions();
            $mobileNo = $request->mobile;
            $mobileNumber = $fn->getRegExprValidateMoNumber($mobileNo,"");
            $isd= $mobileNumber->getCountryCode();
            $mobile = $mobileNumber->getNationalMobileNumber();
            $otp = mt_rand(100000, 999999);
            $mb=$isd.$mobile;
            $user =  DB::table('tbl_appUsers')->where([
                ['isdCode','=',$isd],
                ['mobileNumber','=', $mobile],
            ])->get();

            //update or Insert in tbl_app_user_register
            $pwd = $otp;
            DB::table('tbl_app_users_register')->updateOrInsert(
                 ['isdCode' => $isd, 'mobileNumber' => $mobile,'mobileNo'=>$mb],['otpCode'=>$otp,'password'=>bcrypt($pwd)]
            );
            //check if user exist
            if(count($user)==0){
                return response()->json(['status' => 'fail','status_code' => 401,'error' => 'unauthorized', 'message' => 'User is not registerd.'], 401);
            }else{
                DB::table('tbl_lite_register')->updateOrInsert(
                    ['isdCode' => $isd,'mobileNumber' => $mobile],['isdCode' => $isd,'mobileNumber' => $mobile]
                );
                // $mobile = 7802032060;
                // $url = $_ENV['NOTIFICATION_ENGINE_URL_PREFIX'];
                $url = 'http://ne.wateron.in';
                $client = new Client([
                // 'headers' => ['X-Auth-Token'=>$_ENV['NOTIFICATION_ENGINE_AUTH']],
                'headers' => ['X-Auth-Token'=>'a8b5cb95d8244a4b8b9fd1808de5e895'],
                ]);
                $response = $client->request('POST',$url.'/v1/sms/plivo/send',[
                    'json' => [
                    'to' => $isd.$mobile,
                    'msg' => '<#> '.$otp.': is your OTP for WaterOn sign up. 4RuqauXTu8E'
                    ]
                ]);
                $data = $response->getBody();
                // return response()->json(['success' => 'Valid User'], 200);
                return response()->json(['status' => 'success','status_code' => 200,'error' => '', 'message' => 'Valid User'], 200);
            }
        }catch(JWTException $e){
            return response()->json(['status' => 'fail','status_code' => 500,'error' => 'could_not_create_token', 'message' => 'Something went wrong. Please try again'], 500);
        }
    }

    public function varifyOtp(Request $request){
        try{
            $otp = $request->otp;
            $fn = new ApiFunctions();
            $mobileNo = $request->mobile;
            $mobileNumber = $fn->getRegExprValidateMoNumber($mobileNo,"");
            $isd= $mobileNumber->getCountryCode();
            $mobile = $mobileNumber->getNationalMobileNumber();

            $otpQry = DB::table('tbl_app_users_register')->where([
                ['isdCode','=',$isd],
                ['mobileNumber','=',$mobile],
                ['otpCode','=',$otp],
            ])->get();
            if(count($otpQry)==0){
                return response()->json(['status' => 'fail','status_code' => 401,'error' => 'wrong_otp', 'message' => 'Otp does not match'], 401);
            }
            elseif(count($otpQry)==1){
                $pwd = $otp;
                $credentials = (['isdCode'=>$isd,'mobileNumber'=>$mobile,'password'=>$pwd]);
                $token = JWTAuth::attempt($credentials);
                info($token);
                DB::table('tbl_app_users_register')
            ->where([
                ['isdCode','=',$isd],
                ['mobileNumber','=', $mobile],
                ['otpCode','=', $otp],
            ])
            ->update(['authToken' => $token]);

                return response()->json(['status' => 'success','status_code' => 200,'error' => '', 'message' => 'Otp match','token'=>$token], 200);
            }
            else{
                return response()->json(['status' => 'fail','status_code' => 500,'error' => 'could_not_create_token', 'message' => 'Something went wrong. Please try again'], 500);
            }
        }catch(JWTException $e){
            return response()->json(['status' => 'fail','status_code' => 100,'error' => 'could_not_create_token', 'message' => 'Something went wrong. Please try again'], 500);
        }
    }

    public function logout(Request $request){
        try {
            if (! $user = JWTAuth::parseToken($request->token)->authenticate()) {
        return response()->json(array('success' => false,'token_validation'=>0,'status_code' => 100,'message' => "User not found"));
            }
        }
        catch (TokenExpiredException $e) {
          return response()->json(array('success' => false,'token_validation'=>0,'status_code' => 100,'message' => "Token Expired"));
        }
        catch (TokenInvalidException $e) {
          return response()->json(array('success' => false,'token_validation'=>0,'status_code' => 100,'message' => "Invalid Token"));
        }
        catch (JWTException $e) {
          return response()->json(array('success' => false,'token_validation'=>0,'status_code' => 100,'message' => "Token Absent"));
        }
        catch (Exception $e) {
          return response()->json(array('success' => false,'token_validation'=>0,'status_code' => 100,'message' => "Server Error"));
        }

        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(array('success' => true,'token_validation'=>1,'status_code' => 200,'message' => "Logout Successfully "));
    }
}
