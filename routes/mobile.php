<?php

declare(strict_types=1);

use App\NativeComponents\Bills;
use App\NativeComponents\Dogs;
use App\NativeComponents\Files;
use App\NativeComponents\Layouts\HouseTabs;
use App\NativeComponents\Login;
use App\NativeComponents\More;
use App\NativeComponents\Timeline;
use Illuminate\Support\Facades\Route;

Route::native('/login', Login::class);

Route::nativeGroup(HouseTabs::class, function (): void {
    Route::native('/', Timeline::class);
    Route::native('/bills', Bills::class);
    Route::native('/dogs', Dogs::class);
    Route::native('/files', Files::class);
    Route::native('/more', More::class);
});
