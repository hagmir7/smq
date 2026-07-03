<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {

    dd(Config::get('auth.passwords'));
    return view('welcome');
});
