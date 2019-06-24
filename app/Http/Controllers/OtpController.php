<?php

namespace App\Http\Controllers;
use App\User;
use App\Model\appUser;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use DB;
//use Auth;
//use JWTAuth;
//use Tymon\JWTAuth\Exceptions\JWTException;

class OtpController extends Controller
{
    public function generateOtp(Request $request)
    {

        if(!isset($request->mobile))
        {
            $result['status']="failed";
            $result["message"] = "Data missing: Mobile number is required";
            $statusCode=400;
            return response()->json([$result],$statusCode);
        }
        elseif(!isset($request->isd))
        {
            $result['status']="failed";
            $result["message"] = "Data missing: ISD code is required";
            $statusCode=400;
            return response()->json([$result],$statusCode);
        }
        $mobile = $request->mobile;
        $isd    = $request->isd;

        $user =  DB::table('tbl_appUsers')
        ->where([['isdCode','=',$isd],['mobileNumber','=', $mobile]])
        ->get();


        if(count($user)==0){
            return response()->json(['status' => 'fail','status_code' => 401,'error' => 'unauthorized', 'message' => 'User is not registerd.'], 401);
        }else{

        $otp = mt_rand(100000, 999999);
        $pwd = $otp;
        $mb=$isd.$mobile;
        DB::table('tbl_app_users_register')->updateOrInsert(
             ['isdCode' => $isd, 'mobileNumber' => $mobile,'mobileNo'=>$mb],['otpCode'=>$otp,'password'=>bcrypt($pwd)]
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

    }

    public function verifyOtp(Request $request)
    {
        if(!isset($request->mobile))
        {
            $result['status']="failed";
            $result["message"] = "Data missing: Mobile number is required";
            $statusCode=400;
            return response()->json([$result],$statusCode);
        }
        elseif(!isset($request->isd))
        {
            $result['status']="failed";
            $result["message"] = "Data missing: ISD code is required";
            $statusCode=400;
            return response()->json([$result],$statusCode);
        }
        elseif(!isset($request->otp))
        {
            $result['status']="failed";
            $result["message"] = "Data missing: ISD code is required";
            $statusCode=400;
            return response()->json([$result],$statusCode);
        }

        $otp = $request->otp;
        $mobile = $request->mobile;
        $isd    = $request->isd;

        $otpQry = DB::table('tbl_app_users_register')->where([
            ['isdCode','=',$isd],
            ['mobileNumber','=',$mobile],
            ['otpCode','=',$otp],
        ])->get();
        if(count($otpQry)==0)
        {
            return response()->json(['status' => 'fail','status_code' => 401,'error' => 'wrong_otp', 'message' => 'Otp does not match'], 401);
        }
        else
        {
            $token = sha1(mt_rand(1, 90000) . $mobile);

            DB::table('tbl_app_users_register')
            ->where([
                ['isdCode','=',$isd],
                ['mobileNumber','=', $mobile],
                ['otpCode','=', $otp],
            ])
            ->update(['authToken' => $token]);
            return response()->json(['status' => 'success','status_code' => 200,'error' => '', 'message' => 'Otp match','token'=>$token], 200);
        }


    }



}



// TODO:
// OTP verification
// logOUT
