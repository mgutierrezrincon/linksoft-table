<?php

namespace App\Observers;

use App\Models\File;
use Illuminate\Support\Str;

class WithFilesObserver
{
    public function generalActions($model)
    {
        foreach (request()->all()['file'] ?? [] as $file) {
            // foreach ($model->files as $file) {
            //     if (file_exists(public_path($file->url))) {
            //         unlink(public_path($file->url));
            //     }
            //     $file->delete();
            // }

            if (!is_dir(public_path('storage/files/'.$model->getTable().'/'.$model->id))) {
                mkdir(public_path('storage/files/'.$model->getTable().'/'.$model->id), 0777, true);
            }

            $name = Str::uuid().'.'.$file->extension();

            $file->move(public_path('storage/files/'.$model->getTable().'/'.$model->id), $name);

            File::create([
                'url' => 'storage/files/'.$model->getTable().'/'.$model->id.'/'.$name,
                'file_type' => $file->getClientMimeType(),
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
