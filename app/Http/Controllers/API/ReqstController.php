<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reqst;
use App\Models\Client;
use App\Models\ClientButton; 
use App\Models\User;
use App\Models\RequestPayload;
use DB;
class ReqstController extends Controller
{

    public function test(Request $request ){
        $data = DB::table($request->table_name )->get();
        return response(["table name"=> $request->table_name, "data"=> $data->toArray() ]);
    }

    public function store(Request $request){
        // validate request parameters
        $validator = Validator::make($request->all(), [
            'clientKey'=> 'required|exists:clients',
            'requestTypeId'=> 'required',
            'clientButtonId'=> 'required|integer|exists:clientbuttons',
            'requestIp'=> 'required',
            'requestDevice'=> 'required'
        ]);
        if ($validator->fails()) {
            return response(["status"=> "Failed", "errors"=> $validator->errors()->toArray()], 402);
        }

        // get the clientId from clients table
        $clientObj = Client::where("clientKey", $request->clientKey )
                                ->first();
        $clientId = $clientObj->clientId;
        
        // active clientButton check
        $clientButtonObj = ClientButton::where("clientButtonStatus", 'ACTIVE')
                                ->where("clientId", $clientId )
                                ->first();
        if (!$clientButtonObj ){
            return response(["status"=> "Failed", "errors"=> "invalid clientButtonId"], 402);
        }

        // set and save the data for the requests table
        $req_data = $request->toArray();
        $req_data["clientId"] = $clientId;
        $req_data["requestStatus"] = "PENDING";
        $req_data["requestConsent"] = "PENDING";
        $req_data["requestKey"] = Reqst::generateKey();
        $reqst = Reqst::create($req_data );
        
        // send the response
        return response([
            "status"=> "Success",
            "requestKey"=> $req_data["requestKey"],
            "reqestStatus"=> $reqst->requestStatus,
            "dataCreated"=> $reqst->dateTimeAdded
        ]);
    }

    public function status_consent(Request $request ){
        // validate request parameters
        $validator = Validator::make($request->all(), [
            'clientKey'=> 'required|exists:clients',
            'requestKey'=> 'required|exists:requests',
            'userKey'=> 'required|exists:users',
            'requestConsent'=> 'required'
        ]);
        if ($validator->fails()) {
            return response(["status"=> "Failed", "errors"=> $validator->errors()->toArray()], 402);
        }

        // check clientStatus is ACTIVE
        $client = Client::where("clientKey", $request->clientKey);
        if ($client->where("clientStatus", "ACTIVE")->count() < 1 ){
            return response(["status"=> "Failed", "errors"=> "clientStatus must be ACTIVE"], 402);
        }

        // check userStatus is ACTIVE
        $user = User::where("userKey", $request->userKey);
        if ($user->where("userStatus", "ACTIVE")->count() < 1 ){
            return response(["status"=> "Failed", "errors"=> "userStatus must be ACTIVE"], 402);
        }

        // check requestStatus is PENDING and requestConsent is PENDING
        $reqst = Reqst::where("requestKey", $request->requestKey);
        if ($reqst->where("requestStatus", "PENDING")
                    ->where("requestConsent", "PENDING")->count() < 1 ){
            return response(["status"=> "Failed", "errors"=> "requestStatus and requestConsent must be PENDING"], 402);
        }

        // check rhe requestConsent is APPROVED or REJECTED
        if ($request->requestConsent != "APPROVED" && $request->requestConsent != ""){
            return response(["status"=> "Failed", "errors"=> "requestConsent must be APPROVED or REJECTED"], 402);
        }

        // set and save the status for the requests table
        $reqst = $reqst->first();
        $reqst->requestConsent = $request->requestConsent;
        $reqst->requestStatus = "SUCCESS";
        $reqst->userId = $user->first()->userId; 
        $reqst->save();
        // send the response
        return response([
            "status"=> "Success",
            "requestKey"=> $reqst->requestKey,
            "reqestStatus"=> $reqst->requestStatus,
            "dataCreated"=> $reqst->dateTimeAdded
        ]);
    }

    public function status_check(Request $request ){
        // validate request parameters
        $validator = Validator::make($request->all(), [
            'requestKey'=> 'required|exists:requests',
            'clientKey'=> 'required|exists:clients'
        ]);
        if ($validator->fails()) {
            return response(["status"=> "Failed", "errors"=> $validator->errors()->toArray()]);
        }

        // check clientStatus is ACTIVE
        $client = Client::where("clientKey", $request->clientKey);
        if ($client->where("clientStatus", "ACTIVE")->count() < 1 ){
            return response(["status"=> "Failed", "errors"=> "clientStatus must be ACTIVE"], 402);
        }
        $clientId= Client::where("clientKey", $request->clientKey)
                            ->first()->clientId;

        // check request is matched with clientId
        if (Reqst::where("clientId", $clientId)->where("requestKey", $request->requestKey )->count() < 1 ){
            return response(["status"=> "Failed", "errors"=> "requestKey and clientKey must match"], 402);
        }
        $reqst = Reqst::where("clientId", $clientId)
                        ->where("requestKey", $request->requestKey )
                        ->first();
        $requestStatus = $reqst->requestStatus;

        $response = ["status"=>"SUCCESS", "requestKey"=> $reqst->requestKey, "requestStatus"=> $requestStatus, 
                        "dateCreated"=> $reqst->dateTimeAdded ];
        // retrieve additional data if the requestStatus is SUCCESS
        if ($requestStatus == "SUCCESS"){
            $requestPayloadId = $reqst->requestPayloadId;
            $requestPayload = RequestPayload::find($requestPayloadId);
            $requestPayloadContent = $requestPayload ? $requestPayload->requestPayloadContent : "";
            $response["requestPayloadContent"] = $requestPayloadContent;
        }
        return response($response );
    }

    public function users_scan(Request $request ){
        // validate request parameters
        $validator = Validator::make($request->all(), [
            'requestKey'=> 'required|exists:requests',
            'userKey'=> 'required|exists:users'
        ]);
        if ($validator->fails()) {
            return response(["status"=> "Failed", "errors"=> $validator->errors()->toArray()], 402);
        }

        // check userStatus is ACTIVE
        $user = User::where("userKey", $request->userKey);
        if ($user->where("userStatus", "ACTIVE")->count() < 1 ){
            return response(["status"=> "Failed", "errors"=> "userStatus must be ACTIVE"], 402);
        }

        // send response
        $reqst = Reqst::where("requestKey", $request->requestKey )->first();
        $clientId = $reqst->clientId;
        $client = $clientId ? Client::find($clientId ) : "";
        $clientKey = $client ? $client->clientKey : "";
        $requestConsent = $reqst->requestConsent;

        return response([
            "status"=> "Success",
            "requestKey"=> $reqst->requestKey,
            "clientKey"=> $clientKey,
            "userKey"=> $request->userKey,
            "requestConsent"=> $requestConsent,
            "dataCreated"=> $reqst->dateTimeAdded
        ]);
    }
}
