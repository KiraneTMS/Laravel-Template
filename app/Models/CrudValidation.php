<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrudValidation extends Model
{
    protected $fillable = ['crud_field_id', 'rule'];

    public function field()
    {
        return $this->belongsTo(CrudField::class, 'crud_field_id');
    }
}
