<?php

namespace App\Observers;

use App\Models\File;
use App\Models\Image;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image as ImageFacade;

class WithImagesObserver
{
    public function generalActions($model)
    {
        if (request()->has('images')) {
            // foreach ($model->files as $file) {
            //     if (file_exists(public_path($file->url))) {
            //         unlink(public_path($file->url));
            //     }
            //     $file->delete();
            // }

            if (!is_dir(public_path('storage/images/'.$model->getTable().'/'.$model->id))) {
                mkdir(public_path('storage/images/'.$model->getTable().'/'.$model->id), 0777, true);
            }

            foreach (request()->all()['images'] ?? [] as $image) {
                $name = Str::uuid().'.jpg';

                ImageFacade::read($image)->cover(500, 500)->toJpeg()->save(public_path('storage/images/'.$model->getTable().'/'.$model->id).'/'.$name);

                Image::create([
                    'url' => 'storage/images/'.$model->getTable().'/'.$model->id.'/'.$name,
                    'image_type' => 'image/jpeg',
                    'parentable_id' => $model->id,
                    'parentable_type' => get_class($model),
                ]);
            }
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
