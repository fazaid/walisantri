<?php

namespace App\Observers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

trait ReplacesUploadedFile
{
    protected function deleteOldFileIfReplaced(Model $model, string $field, string $disk = 'public'): void
    {
        if ($model->isDirty($field) && $model->getOriginal($field)) {
            Storage::disk($disk)->delete($model->getOriginal($field));
        }
    }

    protected function deleteFile(Model $model, string $field, string $disk = 'public'): void
    {
        if ($model->{$field}) {
            Storage::disk($disk)->delete($model->{$field});
        }
    }
}
