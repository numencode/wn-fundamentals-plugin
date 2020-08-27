<?php

use NumenCode\Fundamentals\Classes\ImageResize;

Route::get('storage/app/media/{image}', function ($image) {
    return ImageResize::handleMedia($image);
})->where('image', '(.*)?');

Route::get('storage/app/uploads/{image}', function ($image) {
    return ImageResize::handleUpload($image);
})->where('image', '.*\.(jpg|png)');
