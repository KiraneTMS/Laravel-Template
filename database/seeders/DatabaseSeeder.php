<?php

namespace Database\Seeders;

use App\Models\CrudEntity;
use App\Models\CrudRelationship;
use App\Models\User;
use App\Models\Role;
use App\Models\WebProperty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. WebProperty
        $webPropertyEntity = CrudEntity::create([
            'code' => '0.0',
            'name' => 'web_properties',
            'model_class' => 'App\\Models\\WebProperty',
            'table_name' => 'web_properties',
        ]);
        $webPropertyFields = [
            ['name' => 'webname', 'type' => 'string', 'label' => 'Web Name', 'visible_to_roles' => 'admin,editor'],
            ['name' => 'style', 'type' => 'string', 'label' => 'Style', 'visible_to_roles' => 'admin,editor'],
            ['name' => 'icon', 'type' => 'string', 'label' => 'Icon', 'visible_to_roles' => 'admin,editor'],
            ['name' => 'welcome_msg', 'type' => 'string', 'label' => 'Welcome Message', 'visible_to_roles' => 'admin,editor'],
            ['name' => 'color_scheme', 'type' => 'json', 'label' => 'Color Scheme', 'visible_to_roles' => 'admin,editor'],
            ['name' => 'tagline', 'type' => 'string', 'label' => 'Tagline', 'visible_to_roles' => 'admin,editor'],
            ['name' => 'description', 'type' => 'text', 'label' => 'Description', 'visible_to_roles' => 'admin,editor'],
            ['name' => 'status', 'type' => 'string', 'label' => 'Status', 'visible_to_roles' => 'admin,editor'],
            ['name' => 'packages', 'type' => 'json', 'label' => 'Composer Packages', 'visible_to_roles' => 'admin,editor'],
        ];
        foreach ($webPropertyFields as $field) {
            $crudField = $webPropertyEntity->fields()->create($field);
            $crudField->validations()->createMany([
                ['rule' => 'nullable'],
                ['rule' => 'string'],
                ['rule' => 'max:255'],
            ]);
            if ($field['name'] === 'webname') {
                $crudField->validations()->create(['rule' => 'required']);
            }
            if ($field['name'] === 'status') {
                $crudField->validations()->create(['rule' => 'in:active,inactive,pending']);
            }
            if ($field['name'] === 'color_scheme' || $field['name'] === 'packages') {
                $crudField->validations()->create(['rule' => 'json']);
            }
        }
        $webPropertyEntity->columns()->createMany([
            ['field_name' => 'webname'],
            ['field_name' => 'style'],
            ['field_name' => 'status'],
            ['field_name' => 'packages'],
        ]);

        WebProperty::create([
            'webname' => 'Content Management System',
            'style' => 'modern',
            'icon' => 'https://i.imgur.com/NmiMYcQ.png',
            'welcome_msg' => 'Build and manage with ease, {user}!',
            'color_scheme' => [
                "#fd7e14",
                "#343a40",
                "#f8f1e4",
                "#d65a10",
                "#b71c1c",
                "#ffea00",
                "#1b5e20",
                "#0097a7"
            ],
            'tagline' => 'Effortless CRUD generation',
            'description' => 'A dynamic tool for creating and managing CRUD interfaces.',
            'status' => 'active',
            'packages' => ['barryvdh/laravel-dompdf', 'laravel/telescope'],
        ]);

        // 2. CrudEntity
        $crudEntity = CrudEntity::create([
            'code' => '0.1',
            'name' => 'crud_entities',
            'model_class' => 'App\\Models\\CrudEntity',
            'table_name' => 'crud_entities',
        ]);
        $crudEntityFields = [
            ['name' => 'code', 'type' => 'text', 'label' => 'Code'],
            ['name' => 'name', 'type' => 'text', 'label' => 'Entity Name'],
            ['name' => 'model_class', 'type' => 'text', 'label' => 'Model Class'],
            ['name' => 'table_name', 'type' => 'text', 'label' => 'Table Name'],
        ];
        foreach ($crudEntityFields as $field) {
            $crudField = $crudEntity->fields()->create($field);
            $crudField->validations()->createMany([
                ['rule' => 'required'],
                ['rule' => 'string'],
                ['rule' => 'max:255'],
            ]);
            if ($field['name'] === 'name') {
                $crudField->validations()->create(['rule' => 'unique:crud_entities,name']);
            }
            if ($field['name'] === 'code') {
                $crudField->validations()->create(['rule' => 'unique:crud_entities,code']);
            }
        }
        $crudEntity->columns()->createMany([
            ['field_name' => 'code'],
            ['field_name' => 'name'],
            ['field_name' => 'model_class'],
            ['field_name' => 'table_name'],
        ]);
        // Relationships for CrudEntity with display_column
        $crudEntity->relationships()->createMany([
            [
                'type' => 'hasMany',
                'related_table' => 'crud_fields',
                'foreign_key' => 'crud_entity_id',
                'local_key' => 'id','display_columns' => ['name'],
            ],
            [
                'type' => 'hasMany',
                'related_table' => 'crud_columns',
                'foreign_key' => 'crud_entity_id',
                'local_key' => 'id',
                'display_columns' => ['field_name'],
            ],
            [
                'type' => 'hasMany',
                'related_table' => 'crud_relationships',
                'foreign_key' => 'crud_entity_id',
                'local_key' => 'id',
                'display_columns' => ['related_table'],
            ],
        ]);

        // 3. CrudFields
        $crudFieldEntity = CrudEntity::create([
            'code' => '0.2',
            'name' => 'crud_fields',
            'model_class' => 'App\\Models\\CrudField',
            'table_name' => 'crud_fields',
        ]);
        $crudFieldFields = [
            ['name' => 'crud_entity_id', 'type' => 'integer', 'label' => 'Entity ID', 'visible_to_roles' => 'admin'],
            ['name' => 'name', 'type' => 'string', 'label' => 'Field Name', 'visible_to_roles' => 'admin,editor'],
            ['name' => 'type', 'type' => 'string', 'label' => 'Field Type', 'visible_to_roles' => 'admin,editor'],
            ['name' => 'label', 'type' => 'string', 'label' => 'Label', 'visible_to_roles' => 'admin,editor'],
            ['name' => 'visible_to_roles', 'type' => 'string', 'label' => 'Visible To Roles', 'visible_to_roles' => 'admin'],
        ];
        foreach ($crudFieldFields as $field) {
            $crudField = $crudFieldEntity->fields()->create($field);
            if ($field['name'] === 'crud_entity_id') {
                $crudField->validations()->createMany([
                    ['rule' => 'required'],
                    ['rule' => 'numeric'],
                    ['rule' => 'exists:crud_entities,id'],
                ]);
            } else {
                $crudField->validations()->createMany([
                    ['rule' => 'required'],
                    ['rule' => 'string'],
                    ['rule' => 'max:255'],
                ]);
            }
        }
        $crudFieldEntity->columns()->createMany([
            ['field_name' => 'crud_entity_id'],
            ['field_name' => 'name'],
            ['field_name' => 'type'],
            ['field_name' => 'label'],
        ]);
        // Relationships for CrudFields with display_column
        $crudFieldEntity->relationships()->createMany([
            [
                'type' => 'belongsTo',
                'related_table' => 'crud_entities',
                'foreign_key' => 'crud_entity_id',
                'local_key' => 'id',
                'display_columns' => ['name'],
            ],
            [
                'type' => 'hasMany',
                'related_table' => 'crud_validations',
                'foreign_key' => 'crud_field_id',
                'local_key' => 'id',
                'display_columns' => ['rule'],
            ],
        ]);

        // 4. CrudColumns
        $crudColumnEntity = CrudEntity::create([
            'code' => '0.3',
            'name' => 'crud_columns',
            'model_class' => 'App\\Models\\CrudColumn',
            'table_name' => 'crud_columns',
        ]);
        $crudColumnFields = [
            ['name' => 'crud_entity_id', 'type' => 'number', 'label' => 'Entity ID'],
            ['name' => 'field_name', 'type' => 'text', 'label' => 'Field Name'],
        ];
        foreach ($crudColumnFields as $field) {
            $crudField = $crudColumnEntity->fields()->create($field);
            if ($field['name'] === 'crud_entity_id') {
                $crudField->validations()->createMany([
                    ['rule' => 'required'],
                    ['rule' => 'numeric'],
                    ['rule' => 'exists:crud_entities,id'],
                ]);
            } else {
                $crudField->validations()->createMany([
                    ['rule' => 'required'],
                    ['rule' => 'string'],
                    ['rule' => 'max:255'],
                ]);
            }
        }
        $crudColumnEntity->columns()->createMany([
            ['field_name' => 'crud_entity_id'],
            ['field_name' => 'field_name'],
        ]);
        // Relationships for CrudColumns with display_column
        $crudColumnEntity->relationships()->create([
            'type' => 'belongsTo',
            'related_table' => 'crud_entities',
            'foreign_key' => 'crud_entity_id',
            'local_key' => 'id',
            'display_columns' => ['name'],
        ]);

        // 5. CrudValidations
        $crudValidationEntity = CrudEntity::create([
            'code' => '0.4',
            'name' => 'crud_validations',
            'model_class' => 'App\\Models\\CrudValidation',
            'table_name' => 'crud_validations',
        ]);
        $crudValidationFields = [
            ['name' => 'crud_field_id', 'type' => 'number', 'label' => 'Field ID'],
            ['name' => 'rule', 'type' => 'text', 'label' => 'Validation Rule'],
        ];
        foreach ($crudValidationFields as $field) {
            $crudField = $crudValidationEntity->fields()->create($field);
            if ($field['name'] === 'crud_field_id') {
                $crudField->validations()->createMany([
                    ['rule' => 'required'],
                    ['rule' => 'numeric'],
                    ['rule' => 'exists:crud_fields,id'],
                ]);
            } else {
                $crudField->validations()->createMany([
                    ['rule' => 'required'],
                    ['rule' => 'string'],
                    ['rule' => 'max:255'],
                ]);
            }
        }
        $crudValidationEntity->columns()->createMany([
            ['field_name' => 'crud_field_id'],
            ['field_name' => 'rule'],
        ]);
        // Relationships for CrudValidations with display_column
        $crudValidationEntity->relationships()->create([
            'type' => 'belongsTo',
            'related_table' => 'crud_fields',
            'foreign_key' => 'crud_field_id',
            'local_key' => 'id',
            'display_columns' => ['name'],
        ]);

        // 6. Roles
        $roleEntity = CrudEntity::create([
            'code' => '0.5',
            'name' => 'role',
            'model_class' => 'App\\Models\\Role',
            'table_name' => 'roles',
        ]);
        $roleFields = [
            ['name' => 'name', 'type' => 'text', 'label' => 'Name'],
            ['name' => 'priority', 'type' => 'number', 'label' => 'Priority'],
        ];
        foreach ($roleFields as $field) {
            $crudField = $roleEntity->fields()->create($field);
            if ($field['name'] === 'name') {
                $crudField->validations()->createMany([
                    ['rule' => 'required'],
                    ['rule' => 'string'],
                    ['rule' => 'max:255'],
                    ['rule' => 'unique:roles,name'],
                ]);
            } elseif ($field['name'] === 'priority') {
                $crudField->validations()->createMany([
                    ['rule' => 'required'],
                    ['rule' => 'integer'],
                    ['rule' => 'min:0'],
                ]);
            }
        }
        $roleEntity->columns()->createMany([
            ['field_name' => 'name'],
            ['field_name' => 'priority'],
        ]);
        // Relationships for Roles with display_column
        $roleEntity->relationships()->create([
            'type' => 'belongsToMany',
            'related_table' => 'users',
            'foreign_key' => 'role_id',  // pivot table column referencing roles
            'local_key' => 'id',         // roles table primary key
            'display_columns' => ['name'],
        ]);

        // Seed roles
        $adminRole = Role::create(['name' => 'admin', 'priority' => 1]);
        $editorRole = Role::create(['name' => 'editor', 'priority' => 2]);
        $userRole = Role::create(['name' => 'user', 'priority' => 3]);

        // 7. Users
        $userEntity = CrudEntity::create([
            'code' => '0.6',
            'name' => 'user',
            'model_class' => 'App\\Models\\User',
            'table_name' => 'users',
        ]);
        $userFields = [
            ['name' => 'name', 'type' => 'text', 'label' => 'Name'],
            ['name' => 'email', 'type' => 'text', 'label' => 'Email'],
            ['name' => 'password', 'type' => 'password', 'label' => 'Password'],
        ];
        foreach ($userFields as $field) {
            $crudField = $userEntity->fields()->create($field);
            if ($field['name'] === 'name') {
                $crudField->validations()->createMany([
                    ['rule' => 'required'],
                    ['rule' => 'string'],
                    ['rule' => 'max:255'],
                ]);
            } elseif ($field['name'] === 'email') {
                $crudField->validations()->createMany([
                    ['rule' => 'required'],
                    ['rule' => 'email'],
                    ['rule' => 'unique:users,email'],
                    ['rule' => 'max:255'],
                ]);
            } elseif ($field['name'] === 'password') {
                $crudField->validations()->createMany([
                    ['rule' => 'required'],
                    ['rule' => 'string'],
                    ['rule' => 'min:8'],
                ]);
            }
        }
        $userEntity->columns()->createMany([
            ['field_name' => 'name'],
            ['field_name' => 'email'],
        ]);
        // Relationships for Users with display_column
        $userEntity->relationships()->create([
            'type' => 'belongsToMany',
            'related_table' => 'roles',
            'foreign_key' => 'user_id',  // pivot table column referencing users
            'local_key' => 'id',         // users table primary key
            'display_columns' => ['name'],
        ]);

        // Seed users and assign roles
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);
        $admin->roles()->attach($adminRole);

        $editor = User::create([
            'name' => 'Editor User',
            'email' => 'editor@example.com',
            'password' => Hash::make('password123'),
        ]);
        $editor->roles()->attach($editorRole);

        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->roles()->attach($userRole);
    }
}
