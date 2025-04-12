<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrudField extends Model
{
    protected $fillable = ['name', 'type', 'label', 'visible_to_roles', 'computed', 'formula'];

    protected $casts = [
        'computed' => 'boolean',
    ];

    public function entity()
    {
        return $this->belongsTo(CrudEntity::class, 'crud_entity_id');
    }

    public function validations()
    {
        return $this->hasMany(CrudValidation::class, 'crud_field_id');
    }

    public function isVisibleTo($role)
    {
        $roles = explode(',', $this->visible_to_roles);
        return in_array($role, $roles);
    }
}
