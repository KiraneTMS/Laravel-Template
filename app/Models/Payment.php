<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['purchase_id', 'payment_date', 'amount', 'payment_evidence'];

}