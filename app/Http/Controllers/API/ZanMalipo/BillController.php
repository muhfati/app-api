<?php

namespace App\Http\Controllers\API\ZanMalipo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\API\ZanMalipo\ZanMalipoController;
use App\Jobs\SendBillToRabbitMQ;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use DB;

class BillController extends Controller
{
    public static function create($billDetails)
    {
        $SpCode = "SP20002";
        $SubSpCode = "1001";
        $SpSysId = "TZHPR001";

        // Log::info('Bill Details For Request Ctrl No == '.$billDetails);

        if (empty($billDetails) || !isset($billDetails[0])) {
            Log::info('Invalid bill details provided == ');
            throw new Exception("Invalid bill details provided");
        }

        $payerName = $billDetails[0]->first_name . " " . $billDetails[0]->last_name;

        $payerName = htmlspecialchars($payerName, ENT_QUOTES | ENT_XML1, 'UTF-8');

        $billId = $billDetails[0]->uuid;
        $phone_number = $billDetails[0]->phone_number;
        $service_name = $billDetails[0]->service_name;
        $email = $billDetails[0]->email;
        $cost_unit = $billDetails[0]->cost_unit;
        $gfs_code = $billDetails[0]->gfs_code;
        $bill_amount = $billDetails[0]->bill_amount;
        
        date_default_timezone_set('Africa/Dar_es_Salaam');
        $billGenDt = date('Y-m-d') . 'T' . date('H:i:s');
        $billExprDt = date('Y-m-d', strtotime('+30 day')) . 'T' . date('H:i:s');

        $billItemAmount = doubleval($bill_amount ?? 0);
        $billAmount = floatval($bill_amount ?? 0); // Ensure $bill_amount is not null

        $billConversionRates = DB::table('bill_conversion_rates')
                                ->select('convert_rate')
                                ->whereNull('deleted_at')
                                ->first();

        $convertRate = $billConversionRates ? $billConversionRates->convert_rate : 2616.43; 

        $currency = $cost_unit ?? 'TZS';
        $billEqvAmt = ($currency === "USD") ? round($billAmount * $convertRate, 2) : round($billAmount, 2);

        Log::info('Bill Equivalent amount == '.$billEqvAmt);

        $data_string = <<<XML
        <gepgBillSubReq><BillHdr><SpCode>{$SpCode}</SpCode><RtrRespFlg>true</RtrRespFlg></BillHdr><BillTrxInf><BillId>{$billId}</BillId><SubSpCode>{$SubSpCode}</SubSpCode><SpSysId>{$SpSysId}</SpSysId><BillAmt>{$billAmount}</BillAmt><MiscAmt>0</MiscAmt><BillExprDt>{$billExprDt}</BillExprDt><PyrId>{$billId}</PyrId><PyrName>{$payerName}</PyrName><BillDesc>ZANMALIPO</BillDesc><BillGenDt>{$billGenDt}</BillGenDt><BillGenBy>SystemGenerated</BillGenBy><BillApprBy>SystemGenerated</BillApprBy><PyrCellNum>{$phone_number}</PyrCellNum><PyrEmail>{$email}</PyrEmail><Ccy>{$cost_unit}</Ccy><BillEqvAmt>{$billEqvAmt}</BillEqvAmt><RemFlag>true</RemFlag><BillPayOpt>1</BillPayOpt><BillItems><BillItem><BillItemRef>{$service_name}</BillItemRef><UseItemRefOnPay>N</UseItemRefOnPay><BillItemAmt>{$billItemAmount}</BillItemAmt><BillItemEqvAmt>{$billEqvAmt}</BillItemEqvAmt><BillItemMiscAmt>0</BillItemMiscAmt><GfsCode>{$gfs_code}</GfsCode></BillItem></BillItems></BillTrxInf></gepgBillSubReq>
        XML;

        $privateKeyFilePath = __DIR__ . '/Certificates/wubuprivate.pfx';
        $privateKeyPass = 'Wubu@2022';
        $privateKeyAlias = 'gepgclient';

        $signedString = ZanMalipoController::createSignature($data_string, $privateKeyPass, $privateKeyAlias, $privateKeyFilePath);

        $xmlPayload = "<Gepg>{$data_string}<gepgSignature>{$signedString}</gepgSignature></Gepg>";

        Log::info('Submit Bill Payload == '.$xmlPayload);
        $url = "https://uat1.gepg.go.tz/api/bill/sigqrequest";
       
        $ackString = ZanMalipoController::sendRequest($xmlPayload,$url);
        Log::info('Create Bill Acknowledgment == '.$ackString);
        
        return $ackString;
    }

    public static function reuse($billDetails)
    {
        $SpCode = "SP20002";
        $SubSpCode = "1001";
        $SpSysId = "TZHPR001";

        if (!isset($billDetails[0])) {
            throw new Exception("Invalid bill details provided.");
        }

        $billDetail = $billDetails[0];

        $payerName = ($billDetail->first_name ?? '') . " " . ($billDetail->last_name ?? '');

        $payerName = htmlspecialchars($payerName, ENT_QUOTES | ENT_XML1, 'UTF-8');

        date_default_timezone_set('Africa/Dar_es_Salaam');
        $billGenDt = date('Y-m-d') . 'T' . date('H:i:s');
        $billExprDt = date('Y-m-d', strtotime('+30 day')) . 'T' . date('H:i:s');

        $billAmount = doubleval($billDetail->bill_amount ?? 0);

        $billConversionRates = DB::table('bill_conversion_rates')
                            ->select('convert_rate')
                            ->whereNull('deleted_at')
                            ->first();

        $convertRate = $billConversionRates ? $billConversionRates->convert_rate : 2616.43; 

        $currency = $billDetail->cost_unit ?? 'TZS';
        $equivAmount = ($currency === "USD") ? $billAmount * $convertRate: $billAmount;

        $data_string = <<<XML
        <gepgBillSubReq><BillHdr><SpCode>{$SpCode}</SpCode><RtrRespFlg>true</RtrRespFlg></BillHdr><BillTrxInf><BillId>{$billDetail->uuid}</BillId><SubSpCode>{$SubSpCode}</SubSpCode><SpSysId>{$SpSysId}</SpSysId><BillAmt>{$billAmount}</BillAmt><MiscAmt>0</MiscAmt><BillExprDt>{$billExprDt}</BillExprDt><PyrId>{$billDetail->uuid}</PyrId><PyrName>{$payerName}</PyrName><BillDesc>ZANMALIPO</BillDesc><BillGenDt>{$billGenDt}</BillGenDt><BillGenBy>SystemGenerated</BillGenBy><BillApprBy>SystemGenerated</BillApprBy><PyrCellNum>{$billDetail->phone_number}</PyrCellNum><PyrEmail>{$billDetail->email}</PyrEmail><Ccy>{$currency}</Ccy><BillEqvAmt>{$equivAmount}</BillEqvAmt><RemFlag>true</RemFlag><BillPayOpt>1</BillPayOpt><PayCntrNum>{$billDetail->control_number}</PayCntrNum><BillItems><BillItem><BillItemRef>{$billDetail->service_name}</BillItemRef><UseItemRefOnPay>N</UseItemRefOnPay><BillItemAmt>{$equivAmount}</BillItemAmt><BillItemEqvAmt>{$equivAmount}</BillItemEqvAmt><BillItemMiscAmt>0</BillItemMiscAmt><GfsCode>{$billDetail->gfs_code}</GfsCode></BillItem></BillItems></BillTrxInf></gepgBillSubReq>
        XML;

        $privateKeyFilePath = __DIR__ . '/Certificates/wubuprivate.pfx';
        $privateKeyPass = 'Wubu@2022';
        $privateKeyAlias = 'gepgclient';

        $signedString = ZanMalipoController::createSignature(
            $data_string,
            $privateKeyPass,
            $privateKeyAlias,
            $privateKeyFilePath
        );

        $xmlPayload = "<Gepg>{$data_string}<gepgSignature>{$signedString}</gepgSignature></Gepg>";

        Log::info('Reuse Bill Payload == '.$xmlPayload);

        $url = "https://uat1.gepg.go.tz/api/bill/sigqrequest_reuse";
        $ackString = ZanMalipoController::reuseBillSendRequest($xmlPayload, $url);
        Log::info('Reuse Bill Acknowledgment == '.$ackString);
        return $ackString;
    }

    public static function update($billDetails)
    {
        $SpCode = "SP20002";
        $SubSpCode = "1001";
        $SpSysId = "TZHPR001";

        if (!isset($billDetails[0])) {
            throw new Exception("Invalid bill details provided.");
        }

        $billDetail = $billDetails[0];

        $payerName = ($billDetail->first_name ?? '') . " " . ($billDetail->last_name ?? '');

        $payerName = htmlspecialchars($payerName, ENT_QUOTES | ENT_XML1, 'UTF-8');

        date_default_timezone_set('Africa/Dar_es_Salaam');
        $billGenDt = date('Y-m-d') . 'T' . date('H:i:s');
        $billExprDt = date('Y-m-d', strtotime('+180 day')) . 'T' . date('H:i:s');

        $data_string = <<<XML
        <gepgBillSubReq><BillHdr><SpCode>{$SpCode}</SpCode><RtrRespFlg>true</RtrRespFlg></BillHdr><BillTrxInf><BillId>{$billDetail->uuid}</BillId><SpSysId>{$SpSysId}</SpSysId><BillExprDt>{$billExprDt}</BillExprDt><BillRsv1></BillRsv1><BillRsv2></BillRsv2><BillRsv3></BillRsv3></BillTrxInf></gepgBillSubReq>
        XML;

        $privateKeyFilePath = __DIR__ . '/Certificates/wubuprivate.pfx';
        $privateKeyPass = 'Wubu@2022';
        $privateKeyAlias = 'gepgclient';

        $signedString = ZanMalipoController::createSignature(
            $data_string,
            $privateKeyPass,
            $privateKeyAlias,
            $privateKeyFilePath
        );

        $xmlPayload = "<Gepg>{$data_string}<gepgSignature>{$signedString}</gepgSignature></Gepg>";

        Log::info('Update Bill Payload == '.$xmlPayload);

        $url = "https://uat1.gepg.go.tz/api/bill/sigqrequest_change";
        $ackString = ZanMalipoController::changeBillSendRequest($xmlPayload, $url);

        Log::info('Update Bill Acknowledgment == '.$ackString);
        return $ackString;
    }

    public static function cancel($billDetails, $reason)
    {
        $SpCode = "SP20002";
        $SubSpCode = "1001";
        $SpSysId = "TZHPR001";

        if (!isset($billDetails[0])) {
            throw new Exception("Invalid bill details provided.");
        }

        $billDetail = $billDetails[0];

        $payerName = ($billDetail->first_name ?? '') . " " . ($billDetail->last_name ?? '');

        $payerName = htmlspecialchars($payerName, ENT_QUOTES | ENT_XML1, 'UTF-8');

        date_default_timezone_set('Africa/Dar_es_Salaam');

        $data_string = <<<XML
        <gepgBillCanclReq><SpCode>{$SpCode}</SpCode><SpSysId>{$SpSysId}</SpSysId><CanclReasn>{$reason}</CanclReasn><BillId>{$billDetail->uuid}</BillId></gepgBillCanclReq>
        XML;

        $privateKeyFilePath = __DIR__ . '/Certificates/wubuprivate.pfx';
        $privateKeyPass = 'Wubu@2022';
        $privateKeyAlias = 'gepgclient';

        $signedString = ZanMalipoController::createSignature(
            $data_string,
            $privateKeyPass,
            $privateKeyAlias,
            $privateKeyFilePath
        );

        $xmlPayload = "<Gepg>{$data_string}<gepgSignature>{$signedString}</gepgSignature></Gepg>";

        Log::info('Cancel Bill Payload == '.$xmlPayload);

        $url = "https://uat1.gepg.go.tz/api/bill/sigcancel_request";
        $ackString = ZanMalipoController::sendRequest($xmlPayload, $url);

        Log::info('Cancel Bill Acknowledgment == '.$ackString);
        return $ackString;
    }

    public static function reconsile($reconsDetails)
    {
        $SpCode = "SP20002";
        $SubSpCode = "1001";
        $SpSysId = "TZHPR001";

        if (!isset($reconsDetails->uuid) || !isset($reconsDetails->recon_date)) {
            throw new Exception("Invalid reconciliation details provided.");
        }
        $reconDate = Carbon::parse($reconsDetails->recon_date)->format('Y-m-d');

        $data_string = <<<XML
        <gepgSpReconcReq><SpReconcReqId>{$reconsDetails->recons_id}</SpReconcReqId><SpCode>{$SpCode}</SpCode><SpSysId>{$SpSysId}</SpSysId><TnxDt>{$reconDate}</TnxDt><ReconcOpt>1</ReconcOpt></gepgSpReconcReq>
        XML;

        $privateKeyFilePath = __DIR__ . '/Certificates/wubuprivate.pfx';
        $privateKeyPass = 'Wubu@2022';
        $privateKeyAlias = 'gepgclient';

        $signedString = ZanMalipoController::createSignature(
            $data_string,
            $privateKeyPass,
            $privateKeyAlias,
            $privateKeyFilePath
        );

        $xmlPayload = "<Gepg>{$data_string}<gepgSignature>{$signedString}</gepgSignature></Gepg>";

        Log::info('Reconciliations Bill Payload == '.$xmlPayload);
        
        $url = "https://uat1.gepg.go.tz/api/reconciliations/sig_sp_qrequest";
        $ackString = ZanMalipoController::sendRequest($xmlPayload, $url);
        
        Log::info('Reconsile Bill Acknowledgment == '.$ackString);
        return $ackString;
    }

}
