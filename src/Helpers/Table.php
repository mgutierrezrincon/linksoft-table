<?php

namespace App\Helpers;

class Table
{
    public static function getInputType($field, $model)
    {
        if (array_key_exists($field, $model::getFieldTypes())) {
            switch ($model::getFieldTypes()[$field]['type']) {
                case 'price':
                    return 'text';

                    break;
                default:
                    return $model::getFieldTypes()[$field]['type'];
            }
        }

        switch ($field) {
            case 'email':
                return 'email';
            case 'password':
                return 'password';
            default:
                return 'text';
        }
    }

    public static function getInputClass($field, $model)
    {
        if (array_key_exists($field, $model::getFieldTypes())) {
            switch ($model::getFieldTypes()[$field]['type']) {
                case 'price':
                    return 'input-format-price';

                    break;
                default:
                    return '';
            }
        }

        return '';
    }

    public static function getAlign($field, $model)
    {
        if (!array_key_exists($field, $model::getFieldTypes())) {
            return 'left';
        }

        switch ($model::getFieldTypes()[$field]['type']) {
            case 'price':
                return 'right';

                break;
            case 'number':
                return 'center';

                break;
            default:
                return 'left';
        }
    }

    public static function getReadOnly($field, $model)
    {
        if (in_array($field, $model::getReadOnlyFields())) {
            return 'readonly';
        }

        return '';
    }

    public static function getRequired($field, $model)
    {
        if (!in_array($field, $model::getNoRequiredFields())) {
            return 'required';
        }

        return '';
    }
}
