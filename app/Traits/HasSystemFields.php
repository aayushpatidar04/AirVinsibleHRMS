<?php

namespace App\Traits;

use App\Models\FormField;

trait HasSystemFields
{
    public static function getSystemFields(): array
    {
        return FormField::where('is_system', '1')->get()->toArray();
    }

    public static function getSystemFieldNames(): array
    {
        return array_column(self::getSystemFields(), 'field_name');
    }

    public static function getSystemFieldOrders(): array
    {
        return array_column(self::getSystemFields(), 'order', 'field_name');
    }
}