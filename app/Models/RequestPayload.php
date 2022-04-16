<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestPayload extends Model
{
    use HasFactory;
    
    protected $table = 'requestpayload';
    protected $primaryKey = 'requestPayloadId';
    protected $fillable = ["requestPayloadContent"];

    const CREATED_AT = 'dateTimeAdded';
    const UPDATED_AT = 'dateTimeModified'; 
}
