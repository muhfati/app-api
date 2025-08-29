<?php

namespace App\Http\Controllers\API\ZanMalipo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\API\ZanMalipo\ZanMalipoController;
use App\Http\Controllers\API\Setup\SendMsgController;
use App\Models\Bills;
use App\Models\ApplicantRegistrationTypes;
use App\Enums\Status;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Log;

class BillCallBackController extends Controller
{
    private  $request;
    private $signatureObject;
    private $token;
    
    public function __construct()
    {
        date_default_timezone_set('Africa/Dar_es_Salaam');
        // $this->request =file_get_contents('php://input');
           
    }

    public function receive_controll_no(Request $request)
    {
        $xmlContent = $request->getContent();
        Log::info('Control Number Response Payload == '.$xmlContent);
        $time = date('Y-m-d H:i:s');
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        $privateKeyPath = __DIR__ . '/Certificates/wubuprivate.pfx';
        $privateKeyPass = 'Wubu@2022';
        $privateKeyAlias = 'gepgclient';

        // error_log("\n".'['.$time.']'." request from Gepg ".$this->request, 3, storage_path('/logs/bill.log'));
            $values = $this->getXMLData($xmlContent);
          
            $statuscodes = $values['TrxStsCode'];
            $TrxSts = $values['TrxSts'];
            $billid = $values['billid'];
            $codes = explode(';',$statuscodes);
            $control_no = $values['PayCntrNum'];
            $signature=$values['gepgSignature'];
            $content='<gepgBillSubResp>'.$values['gepgBillSubResp'].'</gepgBillSubResp>';
            $bill_status = Status::awaiting_fees->value;
            $currentDate = Carbon::now()->toDateString();

            if($TrxSts == "GS" || $statuscodes == "7226;7201" || $statuscodes == "7101")
            {
                $update_bills = DB::table('bills')
                                ->where('uuid', $billid)
                                ->update([
                                    'control_number' => $control_no,
                                    'bill_status' => $bill_status,
                                    'bill_response_code' => $statuscodes,
                                    'receive_ctrl_no_response_payload' => $values,
                                ]);

                $status_change = $this->change_app_status($billid,$bill_status);
                Log::info('Change app staus function for recived Ctrl No run successfully');
            }
            else
            {
                $bill_status = Status::fail_receive_controll_no->value;

                $update_bills = DB::table('bills')
                                ->where('uuid', $billid)
                                ->update([
                                    'control_number' => $control_no,
                                    'bill_status' => $bill_status,
                                    'bill_response_code' => $statuscodes
                                ]);

                $status_change = $this->change_app_status($billid,$bill_status); 
                Log::info('Change app staus function for fail run successfully');
            }
           
            $content ='<gepgBillSubRespAck>
                        <TrxStsCode>7101</TrxStsCode>      
                       </gepgBillSubRespAck>';  
                        $generatedSignature = ZanMalipoController::createSignature($content, $privateKeyPass, $privateKeyAlias, $privateKeyPath);
                        $response = "<Gepg>".$content."<gepgSignature>".$generatedSignature."</gepgSignature></Gepg>";

            Log::info('Receive Ctrl No Response Payload == '.$response);

            return response($response, 200)->header('Content-type', 'application/xml');
    }

    public function receive_payment(Request $request)
    {
        $xmlContent = $request->getContent();
        Log::info('Receive Payment Response Payload == '.$xmlContent);

        $time = date('Y-m-d H:i:s');
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        
        $privateKeyPath = __DIR__ . '/Certificates/wubuprivate.pfx';
        $privateKeyPass = 'Wubu@2022';
        $privateKeyAlias = 'gepgclient';

        $values = $this->getPaymentXMLData($xmlContent);

        $receipt = $values['PspReceiptNumber'];
        $paymentDate = $values['TrxDtTm'];
        $controlno = $values['PayCtrNum'];
        $billid = $values['BillId'];
        $payRef = $values['PayRefId'];
        $PspName = $values['PspName'];
        $signature = $values['gepgSignature'];
        $content = '<gepgPmtSpInfo>'.$values['gepgPmtSpInfo'].'</gepgPmtSpInfo>';

        $bill_status = Status::paid->value;
        
        $update_bills = DB::table('bills')
                            ->where('uuid', $billid)
                            ->update([
                                'paid_date' => $paymentDate,
                                'bill_status' => $bill_status,
                                'receipt_number' => $receipt,
                                'reference_number' => $payRef,
                                'psp_name' => $PspName,
                                'receive_payment_response_payload'=> $values,
                            ]);

        $status_change = $this->change_app_status($billid,$bill_status);

        Log::info('Change app staus function for recived payment run successfully');

        $content ='<gepgPmtSpInfoAck>
                    <TrxStsCode>7101</TrxStsCode>
                    </gepgPmtSpInfoAck>';  
                    $generatedSignature = ZanMalipoController::createSignature($content, $privateKeyPass , $privateKeyAlias, $privateKeyPath);
                    $response = "<Gepg>".$content."<gepgSignature>".$generatedSignature."</gepgSignature></Gepg>";

                    Log::info('Receive payment Response Payload == '.$response);
                    
        return response($response, 200)->header('Content-type', 'application/xml');
    }

    public function receive_reconciliation(Request $request)
    {
        $xmlContent = $request->getContent();

        Log::info('Receive Reconciliation Response Payload == '.$xmlContent);
        
        $time = date('Y-m-d H:i:s');
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        $privateKeyPath = __DIR__ . '/Certificates/wubuprivate.pfx';
        $privateKeyPass = 'Wubu@2022';
        $privateKeyAlias = 'gepgclient';

        $xmlObject = simplexml_load_string($xmlContent);
        $jsonData = json_encode($xmlObject, JSON_PRETTY_PRINT);
        $ArrayData = json_decode($jsonData, true);

        $ReconcTrans = $ArrayData["gepgSpReconcResp"]["ReconcTrans"] ?? [];
        $SpReconcReqId = $ArrayData["gepgSpReconcResp"]['ReconcBatchInfo']['SpReconcReqId'];

        // Update the bills table
        $reconsiliations = DB::table('reconsiliations')
                            ->where('recons_id', $SpReconcReqId)
                            ->update([
                                'reconsiliation_response_payload' => $ArrayData,
                            ]);

        // Check if ReconcTrans contains ReconcTrxInf
        if (isset($ReconcTrans['ReconcTrxInf'])) {
            $reconcTrxInfList = is_array($ReconcTrans['ReconcTrxInf']) && array_keys($ReconcTrans['ReconcTrxInf']) === range(0, count($ReconcTrans['ReconcTrxInf']) - 1)
                ? $ReconcTrans['ReconcTrxInf'] // Already a list
                : [$ReconcTrans['ReconcTrxInf']]; // Single item, wrap in array

            foreach ($reconcTrxInfList as $data) {
                if (is_array($data) && isset($data['SpBillId'])) 
                {
                    $billid = $data['SpBillId'];

                    // Update the bills table
                    $update_bills = DB::table('bills')
                        ->where('uuid', $billid)
                        ->update([
                            'paid_date' => $data['TrxDtTm'],
                            'bill_status' => Status::paid->value,
                            'receipt_number' => $data['pspTrxId'],
                            'reference_number' => $data['PayRefId'],
                            'psp_name' => $data['PspName'],
                        ]);

                    // Fetch bill details
                    $billDetails = DB::table('bills')
                        ->leftJoin('applicant_registration_types', 'applicant_registration_types.bill_id', '=', 'bills.bill_id')
                        ->select(
                            'bills.bill_status',
                            'bills.bill_id',
                            'bills.control_number',
                            'applicant_registration_types.registration_types_id',
                            'applicant_registration_types.app_status',
                            'applicant_registration_types.app_registration_type_id'
                        )
                        ->where('bills.uuid', $billid)
                        ->whereNull('applicant_registration_types.deleted_at')
                        ->get();

                    $bill_status = $billDetails[0]->bill_status ?? null;

                    if ($bill_status == Status::paid->value) {
                        $status_change = $this->change_app_status($billid, $bill_status);
                    }

                    Log::info('Bill details from Query  ==='.$billDetails);
                    if (!empty($billDetails[0]->app_status)) 
                    {
                        $app_registration_type_id = $billDetails[0]->app_registration_type_id ?? null;
                        $app_status = $billDetails[0]->app_status ?? null;

                        if ($app_status == Status::awaiting_exam_fees->value) 
                        {
                            Log::info('updtae Applicant Registration Types by app_registration_type_id for examination ==='.$app_registration_type_id);

                            $applicantRegistrationTypes = ApplicantRegistrationTypes::find($app_registration_type_id);
                            if ($applicantRegistrationTypes) {
                                $applicantRegistrationTypes->app_status = Status::exam_completed->value;
                                $applicantRegistrationTypes->update();
                            }
                        } elseif ($app_status == Status::awaiting_app_fees->value) 
                        {
                            Log::info('updtae Applicant Registration Types by app_registration_type_id aplication fee ==='.$app_registration_type_id);

                            $applicantRegistrationTypes = ApplicantRegistrationTypes::find($app_registration_type_id);
                            if ($applicantRegistrationTypes) {
                                $applicantRegistrationTypes->app_status = Status::app_completed->value;
                                $applicantRegistrationTypes->update();
                            }
                        }
                    }
                } else {
                    Log::error('Unexpected data format in ReconcTrxInf', ['data' => $data]);
                }
            }
        } else {
            Log::info('No ReconcTrxInf found in ReconcTrans');
        }

               
        $content = '<gepgSpReconcRespAck>
        <ReconcStsCode>7101</ReconcStsCode>
            </gepgSpReconcRespAck>';  
                $generatedSignature = ZanMalipoController::createSignature($content, $privateKeyPass , $privateKeyAlias, $privateKeyPath);
            $response ="<Gepg>".$content."<gepgSignature>".$generatedSignature."</gepgSignature></Gepg>";

            return response($response, 200)->header('Content-type', 'application/xml'); 
    }

    function getXMLData($request)
    {
        $values = array();
        $values['billid'] = $this->get_string_between($request, '<BillId>', '</BillId>');
        $values['PayCntrNum'] = $this->get_string_between($request, '<PayCntrNum>', '</PayCntrNum>');
        $values['TrxSts'] = $this->get_string_between($request, '<TrxSts>', '</TrxSts>');
        $values['TrxStsCode'] =$this->get_string_between($request, '<TrxStsCode>', '</TrxStsCode>');
        $values['gepgSignature']=$this->get_string_between($request, '<gepgSignature>', '</gepgSignature>');
        $values['gepgBillSubResp']=$this->get_string_between($request, '<gepgBillSubResp>', '</gepgBillSubResp>');
        return $values;
    }

    function getPaymentXMLData($request)
    {
        $values = array();
        $values['BillId'] = $this->get_string_between($request, '<BillId>', '</BillId>');
        $values['PayCtrNum'] = $this->get_string_between($request, '<PayCtrNum>', '</PayCtrNum>');
        $values['TrxId'] = $this->get_string_between($request, '<TrxId>', '</TrxId>');
        $values['PspReceiptNumber'] =$this->get_string_between($request, '<PspReceiptNumber>', '</PspReceiptNumber>');
        $values['PayRefId']=$this->get_string_between($request, '<PayRefId>', '</PayRefId>');
        $values['PspName']=$this->get_string_between($request, '<PspName>', '</PspName>');
        $values['gepgSignature']=$this->get_string_between($request, '<gepgSignature>', '</gepgSignature>');
        $values['gepgPmtSpInfo']=$this->get_string_between($request, '<gepgPmtSpInfo>', '</gepgPmtSpInfo>');
        $values['TrxDtTm'] = $this->get_string_between($request, '<TrxDtTm>', '</TrxDtTm>');
        return $values;
    }

    // get data string from xml
    public function get_string_between($string, $start, $end)
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }

    public function change_app_status($billid,$bill_status)
    {
        if($bill_status == "AWAITING_FEES")
        {
            Log::info('in the first block in change_app_status function');
            $bill_services = DB::table('bills')
                            ->join('bill_services', 'bill_services.bill_id', '=', 'bills.bill_id')
                            ->select('bill_services.service_id','bill_services.bill_id')
                            ->where('bills.uuid',$billid)
                            ->get();

            if(sizeof($bill_services) == 0){
                Log::info('No data found for bill services with bill uuid ==='.$billid);
            }
            else
            {
                $service_id = $bill_services[0]->service_id;
                $bill_id = $bill_services[0]->bill_id;
                
                if($service_id == '10000001')
                {
                    $billDetails = DB::table('bills')
                                ->join('pre_registrations', 'pre_registrations.bill_id', '=', 'bills.bill_id')
                                ->select('bills.bill_id','bills.first_name','bills.last_name','bills.phone_number','bills.bill_amount','bills.cost_unit','bills.control_number','pre_registrations.council_sender_id')
                                ->where('bills.uuid',$billid)
                                ->get();

                    if ($billDetails->isEmpty() || sizeof($billDetails) == 0) {
                        Log::info('Service id is 10000003, No data found for billDetails with uuid === '.$billid);
                    }
                    else
                    {
                        $pre_registrations = DB::table('pre_registrations')
                                            ->where('bill_id', $bill_id)
                                            ->update([
                                                'pre_status' => $bill_status,
                                            ]);

                        $number = '+'.$billDetails[0]->phone_number;
                        $first_name = $billDetails[0]->first_name;
                        $last_name = $billDetails[0]->last_name;
                        $senderId = $billDetails[0]->council_sender_id;
                        $bill_amount = $billDetails[0]->bill_amount;
                        $control_number = $billDetails[0]->control_number;
                        $cost_unit = $billDetails[0]->cost_unit;
            
                        //======= send control number SMS to client for about registration =======
                        $message = "Dear ".$first_name." ". $last_name ." You need to pay ". number_format($bill_amount) ." ".$cost_unit." for registration form through control number ".$control_number." Thanks.";
            
                        $respone_otp = new SendMsgController();
                        $respone_otp->sendOTP($number,$message,$senderId);

                        Log::info('SMS with Ctrl Number for registration form sent successfully');
                    }
                }
                else if($service_id == '10000002')
                {
                    $billDetails = DB::table('bills')
                                ->join('applicant_bills', 'applicant_bills.bill_id', '=', 'bills.bill_id')
                                ->select('bills.bill_id','bills.first_name','bills.last_name','bills.phone_number','bills.bill_amount','bills.cost_unit','bills.control_number','applicant_bills.council_sender_id','applicant_bills.app_registration_type_id')
                                ->where('bills.uuid',$billid)
                                ->get();

                    if ($billDetails->isEmpty() || sizeof($billDetails) == 0) {
                        Log::info('Service id is 10000002, No data found for billDetails with uuid === '.$billid);
                    }
                    else
                    {
                        $applicant_bills = DB::table('applicant_bills')
                                            ->where('bill_id', $bill_id)
                                            ->update([
                                                'app_bill_status' => Status::awaiting_app_fees->value,
                                            ]);

                        $app_registration_type_id = $billDetails[0]->app_registration_type_id;

                        $app_registration_types = DB::table('applicant_registration_types')
                                                ->where('app_registration_type_id', $app_registration_type_id)
                                                ->update([
                                                    'app_status' => Status::awaiting_app_fees->value,
                                                ]);

                        $update_bills = DB::table('bills')
                                        ->where('uuid', $billid)
                                        ->update([
                                            'bill_status' => Status::awaiting_app_fees->value,
                                        ]);

                        $number = '+'.$billDetails[0]->phone_number;
                        $first_name = $billDetails[0]->first_name;
                        $last_name = $billDetails[0]->last_name;
                        $senderId = $billDetails[0]->council_sender_id;
                        $bill_amount = $billDetails[0]->bill_amount;
                        $control_number = $billDetails[0]->control_number;
                        $cost_unit = $billDetails[0]->cost_unit;

                        //======= send control number SMS to client for about registration =======
                        $message = "Dear ".$first_name." ". $last_name ." You need to pay ". number_format($bill_amount) ." ".$cost_unit." for application through control number ".$control_number." Thanks.";

                        $respone_otp = new SendMsgController();
                        $respone_otp->sendOTP($number,$message,$senderId);
                        Log::info('SMS with Ctrl Number for application sent successfully');  
                    }
                }
                else if($service_id == '10000003')
                {
                    $billDetails = DB::table('bills')
                                ->join('applicant_exams', 'applicant_exams.bill_id', '=', 'bills.bill_id')
                                ->select('bills.bill_id','bills.first_name','bills.last_name','bills.phone_number','bills.bill_amount','bills.cost_unit','bills.control_number','applicant_exams.council_sender_id','applicant_exams.app_registration_type_id')
                                ->where('bills.uuid',$billid)
                                ->get();

                    if ($billDetails->isEmpty() || sizeof($billDetails) == 0) {
                        Log::info('Service id is 10000003, No data found for billDetails with uuid === '.$billid);
                    }
                    else
                    {
                        $applicant_exams = DB::table('applicant_exams')
                                            ->where('bill_id', $bill_id)
                                            ->update([
                                                'exam_status' => Status::awaiting_exam_fees->value,
                                            ]);

                        $app_registration_type_id = $billDetails[0]->app_registration_type_id;

                        $app_registration_types = DB::table('applicant_registration_types')
                                                ->where('app_registration_type_id', $app_registration_type_id)
                                                ->update([
                                                    'app_status' => Status::awaiting_exam_fees->value,
                                                ]);

                        $update_bills = DB::table('bills')
                                        ->where('uuid', $billid)
                                        ->update([
                                            'bill_status' => Status::awaiting_exam_fees->value,
                                        ]);

                        $number = '+'.$billDetails[0]->phone_number;
                        $first_name = $billDetails[0]->first_name;
                        $last_name = $billDetails[0]->last_name;
                        $senderId = $billDetails[0]->council_sender_id;
                        $bill_amount = $billDetails[0]->bill_amount;
                        $control_number = $billDetails[0]->control_number;
                        $cost_unit = $billDetails[0]->cost_unit;
            
                        //======= send control number SMS to client for about registration =======
            
                        $message = "Dear ".$first_name." ". $last_name ." You need to pay ". number_format($bill_amount) ." ".$cost_unit." for exam through control number ".$control_number." Thanks.";
            
                        $respone_otp = new SendMsgController();
                        $respone_otp->sendOTP($number,$message,$senderId);
                        Log::info('SMS with Ctrl Number for exam sent successfully');
                    }
                }
            }
        }
        else
        {
            Log::info('in the else block in change_app_status function');

            $bill_services = DB::table('bills')
                                ->join('bill_services', 'bill_services.bill_id', '=', 'bills.bill_id')
                                ->select('bill_services.service_id','bill_services.bill_id','bills.first_name','bills.last_name','bills.phone_number')
                                ->where('bills.uuid',$billid)
                                ->get();

            if(sizeof($bill_services) == 0)
            {
                Log::info('No data found for bill services with bill uuid ==='.$billid);
            }
            else
            {
                $service_id = $bill_services[0]->service_id;
            
                if($service_id == '10000001')
                {
                    $billDetails = DB::table('bills')
                                    ->join('pre_registrations', 'pre_registrations.bill_id', '=', 'bills.bill_id')
                                    ->select('bills.bill_id','bills.first_name','bills.last_name','bills.phone_number','bills.control_number','pre_registrations.council_sender_id','pre_registrations.reference_number')
                                    ->where('bills.uuid',$billid)
                                    ->get();

                    if(sizeof($billDetails) == 0){
                        Log::info('No data foundfor service id === '.$billid);
                    }
                    else
                    {
                        $bill_id = $billDetails[0]->bill_id;

                        $pre_registrations = DB::table('pre_registrations')
                                                ->where('bill_id', $bill_id)
                                                ->update([
                                                    'pre_status' => $bill_status,
                                                ]);

                        $reference_number = $billDetails[0]->reference_number;
                        $first_name = $billDetails[0]->first_name;
                        $last_name = $billDetails[0]->last_name;
                        $senderId  = $billDetails[0]->council_sender_id;
                        $number = '+'.$billDetails[0]->phone_number;

                        if($bill_status == Status::fail_receive_controll_no->value){

                        }
                        else
                        {
                            //======= send control number SMS to client for about registration =======
                            $message = "Dear ".$first_name." ". $last_name ." Your reference number is ".$reference_number.", Please use this number to continue  with registration. Thanks.";

                            $respone_otp = new SendMsgController();
                            $respone_otp->sendOTP($number,$message,$senderId);

                            Log::info('SMS with reference number sent successfully');
                        } 
                    }                      
                }
                else if($service_id == '10000002')
                {
                    $billDetails = DB::table('bills')
                                    ->join('applicant_bills', 'applicant_bills.bill_id', '=', 'bills.bill_id')
                                    ->select('bills.bill_id','bills.first_name','bills.last_name','bills.phone_number','bills.control_number','applicant_bills.app_registration_type_id')
                                    ->where('bills.uuid',$billid)
                                    ->get();

                    if(sizeof($billDetails) == 0){
                        Log::info('Service id is 10000002, No data foundfor service id === '.$billid);
                    }
                    else
                    {
                        $app_registration_type_id = $billDetails[0]->app_registration_type_id;
                        $bill_id = $billDetails[0]->bill_id;

                        $applicantRegistrationTypes = ApplicantRegistrationTypes::find($app_registration_type_id);
                        $applicantRegistrationTypes->app_status  = Status::app_completed->value;
                        $applicantRegistrationTypes->update();

                        $applicant_bills = DB::table('applicant_bills')
                                            ->where('bill_id', $bill_id)
                                            ->update([
                                                'app_bill_status' => Status::app_completed->value,
                                            ]);

                        Log::info('app_bill_status updated  with value === '.Status::app_completed->value);
                    } 
                }
                else if($service_id == '10000003')
                {
                    $billDetails = DB::table('bills')
                                    ->join('applicant_exams', 'applicant_exams.bill_id', '=', 'bills.bill_id')
                                    ->select('bills.bill_id','bills.first_name','bills.last_name','bills.phone_number','bills.control_number','applicant_exams.app_registration_type_id')
                                    ->where('bills.uuid',$billid)
                                    ->get();

                    if(sizeof($billDetails) == 0){

                        Log::info('Service id is 10000003, No data foundfor service id === '.$billid);
                    }
                    else
                    {
                        $app_registration_type_id = $billDetails[0]->app_registration_type_id;
                        $bill_id = $billDetails[0]->bill_id;
    
                        $applicantRegistrationTypes = ApplicantRegistrationTypes::find($app_registration_type_id);
                        $applicantRegistrationTypes->app_status  = Status::exam_completed->value;;
                        $applicantRegistrationTypes->update();
    
                        $applicant_exams = DB::table('applicant_exams')
                                            ->where('bill_id', $bill_id)
                                            ->update([
                                                'exam_status' => Status::exam_completed->value,
                                            ]); 

                        Log::info('exam status updated  with value === '.Status::exam_completed->value);
                    }
                }
                else{

                }

            }
            
        }
        return true;
    }

}
