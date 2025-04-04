<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Purchase;

class Supplier extends Model
{
    protected $fillable = ['name', 'contact_email', 'phone', 'is_active'];

public function purchases()
    {
        return $this->hasMany(Purchase::class, 'supplier_id');
    }
}