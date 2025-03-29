<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrudColumn extends Model
{
    protected $fillable = ['crud_entity_id', 'field_name'];

    public function entity()
    {
        return $this->belongsTo(CrudEntity::class, 'crud_entity_id');
    }
}
