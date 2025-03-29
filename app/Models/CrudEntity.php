<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrudEntity extends Model
{
    protected $fillable = ['code', 'name', 'model_class', 'table_name'];

    public function fields()
    {
        return $this->hasMany(CrudField::class);
    }

    public function columns()
    {
        return $this->hasMany(CrudColumn::class);
    }

    public function relationships()
    {
        return $this->hasMany(CrudRelationship::class);
    }
}
