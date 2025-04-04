<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Supplier;
use App\Models\Payment;

class Purchase extends Model
{
    protected $fillable = ['supplier_id', 'purchase_date', 'total_amount', 'description', 'payment_status', 'payment_method', 'payment_deadline', 'payment_date', 'amount', 'payment_evidence'];

    public function suppliers()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
public function payments()
    {
        return $this->hasMany(Payment::class, 'purchase_id');
    }
}