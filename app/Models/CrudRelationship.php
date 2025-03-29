<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrudRelationship extends Model
{
    // Define the table name if it’s not the default 'crud_relationships'
    protected $table = 'crud_relationships';

    // Add fillable fields
    protected $fillable = [
        'crud_entity_id',
        'type',
        'related_table',
        'foreign_key',
        'local_key',
        'display_column',
    ];

    // Define the relationship back to CrudEntity
    public function crudEntity()
    {
        return $this->belongsTo(CrudEntity::class);
    }
}
