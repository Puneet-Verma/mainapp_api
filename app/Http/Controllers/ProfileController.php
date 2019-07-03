<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Model\AppUser;
use App\Helper\CommonFunction;

class ProfileController extends Controller{

    public function getProfile(Request $request)
    {
        $cf = new CommonFunction();
        if(!isset($request->mobile))
        {
            $result['status']="failed";
            $result["message"] = "Data missing: Mobile number is required";
            $statusCode=400;
            return response()->json([$result],$statusCode);
        }
        else if(!isset($request->token))
        {
        return $cf->parValidation($request->token,"token is required");
        }
        else if(!isset($request->fcmToken))
        {
        return $cf->parValidation($request->fcmToken,"FCM token isrequired");
        }


        /*elseif(!isset($request->token))
        {
            $result['status']="failed";
            $result["message"] = "Data missing: Token is required";
            $statusCode=400;
            return response()->json([$result],$statusCode);
        }
        */
        if(!isset($request->isdCode))
        {
            $result['status']="failed";
            $result["message"] = "Data missing: ISD Code is required";
            $statusCode=400;
            return response()->json([$result],$statusCode);
        }

        $mobile    = $request->mobile;
        //$token     = $request->token;
        $isdCode = $request->isdCode;
        $fcmToken = $request->fcmToken;
        $token = $request->token;


        // checking fcm token for force login
        $fcmCheck=$cf->fcmCheck($isdCode,$mobile,$fcmToken);
        if($fcmCheck)
        {
        return $fcmCheck;
        }


        $tokenCheck = $cf->tokenCheck($token,$isdCode,$mobile);
        if($tokenCheck)
        {
        return $tokenCheck;
        }



        $user=AppUser::where([['mobileNumber','=',$mobile],['isdCode','=',$isdCode]])->get();

        if(!$user)
        {
            $result['status']="success";
            $result["message"] = "No User Found";
            $statusCode=200;
        }
        else
        {

        //    $result["payload"]["aptId"]       = $aptInfo[0]->apyId;
        //    $result["payload"]["address"]     = $aptInfo[0]->aptNumber;
            $result["payload"]["apartment"]=array();

            foreach ($user as $key => $value) {

                $aptInfo  = $this->getApartmentInfo($value->aptId);
                //$result["payload"]["society"]     = $aptInfo[0]->societyName;



            $apartment=array();
            $apartment["society"]     = $aptInfo[0]->societyName;


            if($aptInfo[0]->block_app=='1')
            {
                $apartment["block_app"]     =true;

            }

            if($aptInfo[0]->block_app=='0')
            {
                $apartment["block_app"]=false;

            }

            $apartment["appTarget"]     = $aptInfo[0]->appTarget;

             if(!isset($aptInfo[0]->appTarget) || $aptInfo[0]->appTarget==0)
             {
                 $apartment["targetSet"]     = false;
             }
             else
             {
                 $apartment["targetSet"]     = true;
             }




            $slab=$this->getSlab($aptInfo[0]->societyId);





             $apartment["aptId"]   = $aptInfo[0]->apyId;
             $apartment["address"] = $aptInfo[0]->aptNumber;


	         $commodity["unit_text"]       = "Litres";
             $commodity["unit_abbrev"]     =  "lit";
             $commodity["unit_symbol"]     =  "";

             $commodity["currency_symbol"] = $aptInfo[0]->currencyLocaleSymbol;
             $commodity["currency_text"]   = $aptInfo[0]->currencyLocaleLongString;
             $commodity["currency_abbrev"] = $aptInfo[0]->currencyLocaleString;


             $apartment["commodity"]=$commodity;





            $meteringPt=DB::table('tbl_metering_point as tmp')->where('tmp.aptId',$aptInfo[0]->apyId)
            ->join('tbl_meter as tm', 'tm.meteringPointId', '=', 'tmp.meteringPointId')
            ->select('tm.meterSerialNo as id','location as location_default','locationUser as location_user','tm.valveExist as has_valve', 'valveStatus as valve_current_status')
            ->get();

            //$result["payload"]["commodity"]=$commodity;
            //$result["payload"] ['meters']=$meteringPt;

            //$result['meters']=$meteringPt;

            $apartment["meters"]=$meteringPt;

            $apartment["slab"]=$slab;



            array_push($result["payload"]["apartment"],$apartment);

            }


            $result["status"]  = "success";
            $result["message"] = "Account Found";
            $statusCode=200;
        }

        return response()->json($result,$statusCode);

    }

    public function getApartmentInfo($aptId)
    {
        $aptInfo = DB::table('tbl_apartments as apt')
        ->select('appTarget','societyName','sm.societyId as societyId','sm.block_app as block_app','apyId','aptNumber','address','currencyLocaleSymbol','currencyLocaleLongString','currencyLocaleString')
        ->join('tbl_society_master as sm', 'apt.societyId', '=', 'sm.societyId')
        ->where('apt.apyId',$aptId)
        //->select('users.*', 'contacts.phone', 'orders.price')
        ->get();
        return $aptInfo;
    }


    public function getSlab($societyId)
    {
        $slab=DB::table('tbl_society_billing_slabs')
                  ->where('societyId',$societyId)
                  ->get();

        return $slab;
    }





}
