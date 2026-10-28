<?php

declare(strict_types=1);

use App\NativeComponents\BillDetail;
use App\NativeComponents\Bills;
use App\NativeComponents\BillSplit;
use App\NativeComponents\DocumentDetail;
use App\NativeComponents\DogDetail;
use App\NativeComponents\Dogs;
use App\NativeComponents\EditBill;
use App\NativeComponents\EditDogFood;
use App\NativeComponents\FeedDog;
use App\NativeComponents\FeedDogs;
use App\NativeComponents\FeedingDetail;
use App\NativeComponents\Files;
use App\NativeComponents\Layouts\HouseStack;
use App\NativeComponents\Layouts\HouseTabs;
use App\NativeComponents\Login;
use App\NativeComponents\More;
use App\NativeComponents\NewBill;
use App\NativeComponents\NewDocument;
use App\NativeComponents\NewDog;
use App\NativeComponents\NewDogFood;
use App\NativeComponents\NewPaymentMethod;
use App\NativeComponents\NewPost;
use App\NativeComponents\PaymentMethods;
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

Route::nativeGroup(HouseStack::class, function (): void {
    Route::native('/posts/new', NewPost::class);
    Route::native('/bills/new', NewBill::class);
    Route::native('/bills/{id}', BillDetail::class);
    Route::native('/bills/{id}/edit', EditBill::class);
    Route::native('/documents/new', NewDocument::class);
    Route::native('/documents/{id}', DocumentDetail::class);
    Route::native('/dogs/new', NewDog::class);
    Route::native('/dogs/feed-all', FeedDogs::class);
    Route::native('/dogs/{id}', DogDetail::class);
    Route::native('/dogs/{id}/feed', FeedDog::class);
    Route::native('/feedings/{id}', FeedingDetail::class);
    Route::native('/dog-foods/new', NewDogFood::class);
    Route::native('/dog-foods/{id}/edit', EditDogFood::class);
    Route::native('/bill-split', BillSplit::class);
    Route::native('/payment-methods', PaymentMethods::class);
    Route::native('/payment-methods/new', NewPaymentMethod::class);
});
