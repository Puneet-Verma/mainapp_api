<?php

namespace App\Http\Controllers;
use App\User;
use App\Model\appUser;
use Illuminate\Http\Request;
use DB;
use Exception;
use App\Helper\CommonFunction;

class ConsumptionController extends Controller
{

    public function getHourlyReading(Request $request)
    {
        $cf = new CommonFunction();

        if(!isset($request->meterId))
        {
        return $cf->parValidation($request->meterId,"Meter Id");
        }
        else if(!isset($request->tdate))
        {
        return $cf->parValidation($request->tdate,"Date");
        }
        else if(!isset($request->fdate))
        {
        return $cf->parValidation($request->fdate,"Date");
        }
        else if(!isset($request->token))
        {
        return $cf->parValidation($request->token,"token");
        }
        else if(!isset($request->mobile))
        {
        return $cf->parValidation($request->mobile,"Mobile number");
        }
        else if(!isset($request->isd))
        {
        return $cf->parValidation($request->isd,"ISD code");
        }
        else if(!isset($request->fcmToken))
        {
        return $cf->parValidation($request->fcmToken,"FCM token required");
        }

        $meterId = $request->meterId;
        $token   = $request->token;
        $tdate   = $request->tdate;
        $fdate   = $request->fdate;
        $mobile  = $request->mobile;
        $isd     = $request->isd;
        $fcmToken = $request->fcmToken;

        $tokenCheck = $cf->tokenCheck($token,$isd,$mobile);
        if($tokenCheck)
        {
        return $tokenCheck;
        }

        // checking fcm token for force login
        $fcmCheck=$cf->fcmCheck($isd,$mobile,$fcmToken);
        if($fcmCheck)
        {
        return $fcmCheck;
        }

        $meterId=explode(",",$meterId);
        $payload=array();

       foreach ($meterId as $key => $value)
        {
            $dt=$fdate;
            while(strtotime($dt)<=strtotime($tdate))
            {
                $reading=DB::select("SELECT meterId,flowDate, HOUR(flowTime) AS hour , SUM(flowQuantity) AS totalFlow FROM tbl_data_raw_consum_last2days WHERE flowDate ='$dt' AND flowTime BETWEEN  '00:00:00' AND  '23:59:59' AND meterId =  '$value' GROUP BY hour");
                //$reading=DB::select("SELECT meterId,flowDate, HOUR(flowTime) AS hour , SUM(flowQuantity) AS totalFlow FROM tbl_data_raw_consum_last2days WHERE flowDate ='$dt' AND flowTime BETWEEN  '00:00:00' AND  '23:59:59' AND ($c) GROUP BY hour");


                $dt = date ("Y-m-d", strtotime("+1 day", strtotime($dt)));
                if($reading)
                {
                    array_push($payload,$reading);
                }
            }

        }

        $result['payload']=$payload;
        $result['status']="success";
        $result["message"] = "Success";

        $statusCode=200;
        return response()->json($result,$statusCode);

    }

    public function getMonthlyReading(Request $request)
    {

        $cf = new CommonFunction();

        if(!isset($request->aptnum))
        {
        return $cf->parValidation($request->aptnum,"Apartment number");
        }
        else if(!isset($request->noofdays))
        {
        return $cf->parValidation($request->noofdays,"Number of days");
        }
        else if(!isset($request->token))
        {
        return $cf->parValidation($request->token,"token");
        }
        else if(!isset($request->tilldate))
        {
        return $cf->parValidation($request->tilldate,"Till Date ");
        }
        else if(!isset($request->mobile))
        {
        return $cf->parValidation($request->mobile,"Mobile number");
        }
        else if(!isset($request->isd))
        {
        return $cf->parValidation($request->isd,"ISD Code");
        }

        else if(!isset($request->fcmToken))
        {
        return $cf->parValidation($request->fcmToken,"FCM token required");
        }

        $apt_num    = $request->aptnum;
        $token      = $request->token;
        $tilldate   = $request->tilldate;
        $mobile     = $request->mobile;
        $isd        = $request->isd;
        $no_of_days = intval($request->noofdays);
        $fcmToken = $request->fcmToken;

        $tokenCheck = $cf->tokenCheck($token,$isd,$mobile);
        if($tokenCheck)
        {
        return $tokenCheck;
        }

        $fcmCheck=$cf->fcmCheck($isd,$mobile,$fcmToken);
        if($fcmCheck)
        {
        return $fcmCheck;
        }

        $from_date  = $this->start_date($no_of_days,$tilldate);

        try
        {
            /* Get All metering information */
            $meterinfo         = DB::table('tbl_metering_point as mp')
                                ->select('mp.meteringPointId','mp.locationUser')
                                ->where('mp.aptId',$apt_num)
                                ->orderBy('mp.meteringPointId','asc')
                                ->get();
            /* Get total consumption of all the metering point */
            $total_consumption = DB::table('tbl_consum_apartment_daily as adc')
                                ->select('adc.consum','adc.flow_date')
                                ->where('adc.apt_id',$apt_num)
                                ->whereBetween("adc.flow_date",[$from_date,$tilldate]);

            /* Get total consumption of individual metering point and union with total consumption */
            $all_meter_consumption = DB::table('tbl_consum_meter_daily as mdc')
                                    ->where('mdc.apt_id',$apt_num)
                                    ->joinSub($total_consumption, 'total', function ($join) {
                                        $join->on('mdc.flow_date', '=', 'total.flow_date');})
                                    ->orderBy('total.flow_date','desc')
                                    ->orderBy('mdc.mtr_point_id','asc')
                                    ->select('mdc.flow_date as flowDate','total.consum as daily_consume','mdc.mtr_point_id as meteringPointId','mdc.consum as meter_consume')
                                    ->get();
        }
        catch (Exception $e)
        {
            $error = $this->error_db_message('Error occurs while getting consumption detail',$e->getMessage(),$e->getCode());
            return response()->json(['success' => false,'status_code' => 500,'error' =>$error, 'message' => 'Database error'], 500);
        }

        $response = array();
        $jsonarr  = array();
        $count    = -1;
        $key      = 0;

        while (strtotime($tilldate) >= strtotime($from_date))
        {
            if(!isset($jsonarr[$count]['date']) || $jsonarr[$count]['date'] != $tilldate)
            {

                //Put first element of json arr as flowdate
                $count++;
                $jsonarr[$count]['date'] = $tilldate;

                $obj = new \stdClass();
                $obj->meteringPointId = 0;
                $obj->flowDate = '1970-01-01';
                //take first value of consumption (from query --> desc dates)
                $consum = isset($all_meter_consumption[$key]) ? $all_meter_consumption[$key] : $obj;

                //if date match --> fill the data from query row
                if($consum->flowDate == $tilldate)
                {
                    $jsonarr[$count]['total_consume'] = $consum->daily_consume;
                    $jsonarr[$count]['meter_consume'] = array();

                    foreach($meterinfo as $m => $mtr)
                    {
                        $latestrow =  isset($all_meter_consumption[$key]) ? $all_meter_consumption[$key] : $obj;
                        if($latestrow->meteringPointId == $mtr->meteringPointId && $latestrow->flowDate == $tilldate)
                        {
                            $individual_consume = array(
                            'consume'        => $latestrow->meter_consume,
                            'locationUser'    => $mtr->locationUser,
                            'meteringPointId' => $mtr->meteringPointId
                            );
                            array_push($jsonarr[$count]['meter_consume'],$individual_consume);
                            $key++;
                        }
                        else
                        {
                            $individual_consume = array(
                                //'meterId'         => $consum->meterId,
                                'consume'        => 0,
                                'locationUser'    => $mtr->locationUser,
                                'meteringPointId' => $mtr->meteringPointId
                            );
                            array_push($jsonarr[$count]['meter_consume'],$individual_consume);
                        }
                    }
                }
                else
                {
                    //Make both total_consume & meter_consume zero with meteringPointId & location user
                    $jsonarr[$count]['total_consume'] = 0;
                    $jsonarr[$count]['meter_consume'] = array();
                    foreach($meterinfo as $m => $mtr){
                        $individual_consume = array(
                            'consume'         => 0,
                            'locationUser'    => $mtr->locationUser,
                            'meteringPointId' => $mtr->meteringPointId
                        );
                        array_push($jsonarr[$count]['meter_consume'],$individual_consume);
                    }
                }
                $tilldate = date ("Y-m-d", strtotime("-1 day", strtotime($tilldate)));
            }
        }

        $response = $jsonarr;

        return response()->json(['success' => true,'payload'=>$response], 200);

    }

    /**
     * Calculate starting date from which we require data
     * @param  int  $no_of_days
     * @param  string $tilldate
     * @return string $start_date
     */
    private function start_date($no_of_days,$tilldate){
      try{
        $no_of_days  = $no_of_days - 1;
        $date        = new \DateTime($tilldate);
        $date->sub(new \DateInterval('P'.$no_of_days.'D'));
        $start_date  = $date->format('Y-m-d');
        return $start_date;
      }
      catch (Exception $e)
      {
        $error  = $this->error_exception_message('Incorrect Format Of Date',$e->getMessage());
        return response()->json(['success' => false,'status_code' => 400,'error' =>$error, 'message' => 'Bad Request'], 400);
      }
    }

    /**
    * Error handling in case of bad request
    */
    private function error_exception_message($reason,$message){
      $error            = array();
      $error['code']    = 400;
      $error['message'] = "Bad Request";
      $error['reason']  = $reason;
      $error['title']   = $message;
      return $error;
    }

    /**
    * Error handling in case of database error
    */
    private function error_db_message($reason,$message,$code){

      $error            = array();
      $error['code']    = $code;
      $error['message'] = "Database Error";
      $error['reason']  = $reason;
      $error['title']   = $message;
      //Log::error($error);
      /*Do not send message to client side, table structure etc.*/
      //$error['title']     = '';
      $error['code']      = '500';
      return $error;
    }



}



// TODO:
