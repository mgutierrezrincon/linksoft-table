<?php

namespace App\Observers;

use App\Models\File;
use Illuminate\Support\Str;

class WithFileObserver
{
    public function generalActions($model)
    {
        if (request()->has('evidence_file')) {
            foreach ($model->files as $file) {
                if (file_exists(public_path($file->url))) {
                    unlink(public_path($file->url));
                }
                $file->delete();
            }

            if (!is_dir(public_path('storage/files/'.$model->getTable().'/'.$model->id))) {
                mkdir(public_path('storage/files/'.$model->getTable().'/'.$model->id), 0777, true);
            }

            $name = Str::uuid().'.'.request()->file('evidence_file')->extension();

            request()->file('evidence_file')->move(public_path('storage/files/'.$model->getTable().'/'.$model->id), $name);

            File::create([
                'url' => 'storage/files/'.$model->getTable().'/'.$model->id.'/'.$name,
                'file_type' => request()->file('evidence_file')->getClientMimeType(),
                'parentable_id' => $model->id,
                'parentable_type' => get_class($model),
            ]);
        }
    }

    public function created($model)
    {
        $this->generalActions($model);
    }

    public function updated($model)
    {
        $this->generalActions($model);
    }
}
