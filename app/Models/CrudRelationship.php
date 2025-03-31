<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrudRelationship extends Model
{
    protected $table = 'crud_relationships';

    protected $fillable = [
        'crud_entity_id',
        'type',
        'related_table',
        'foreign_key',
        'local_key',
        'display_columns',
    ];

    protected $casts = [
        'display_columns' => 'array',
    ];

    public function crudEntity()
    {
        return $this->belongsTo(CrudEntity::class);
    }
}
