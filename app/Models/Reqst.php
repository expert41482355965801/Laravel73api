<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reqst extends Model
{
    use HasFactory;
    
    protected $table = 'requests';
    protected $primaryKey = 'requestId';
    protected $fillable = [
            "requestKey", 
            "userId", 
            "clientId", 
            "vendorId", 
            "requestTypeId", 
            "clientButtonId", 
            "requestIp", 
            "requestDevice", 
            "requestStatus", 
            "requestConsent",
            "vendorAuthId", 
            "requestVendorSuccess", 
            "requestVendorFailed", 
            "requestPayloadId"];

    const CREATED_AT = 'dateTimeAdded';
    const UPDATED_AT = 'dateTimeModified'; 

    static public function generateKey(){
        $length = 9;
        $characters ='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $result = "";
        $charactersLength = strlen($characters);
        for ($i = 0; $i < $length; $i++ ) {
            $result .= substr($characters, rand() % $charactersLength, 1 );
        }
        
        return $result;
    }
}
