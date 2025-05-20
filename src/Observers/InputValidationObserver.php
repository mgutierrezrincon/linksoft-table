<?php

namespace App\Observers;

class InputValidationObserver
{
    public function generalActions($model)
    {
        foreach ($model->getFieldTypes() as $field => $data) {
            if ('price' == $data['type']) {
                if (empty($model->{$field})) {
                    continue;
                }
                $model->{$field} = str_replace([',', '$'], '', $model->{$field});
            }
        }
    }

    public function creating($model)
    {
        $this->generalActions($model);
    }

    public function updating($model)
    {
        $this->generalActions($model);
    }
}
