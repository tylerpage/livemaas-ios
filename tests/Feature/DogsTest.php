<?php

declare(strict_types=1);

use App\NativeComponents\DogDetail;
use App\NativeComponents\Dogs;
use App\NativeComponents\EditDogFood;
use App\NativeComponents\FeedDog;
use App\NativeComponents\FeedDogs;
use App\NativeComponents\FeedingDetail;
use App\NativeComponents\NewDogFood;
use App\Services\Api\ApiSession;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Native\Mobile\AsyncTask;
use Native\Mobile\Events\Camera\PhotoTaken;
use Native\Mobile\Testing\Native;

beforeEach(function (): void {
    app(ApiSession::class)->start('1|secret-token', 1, 'Tyler');
    $this->travelTo('2026-10-27 18:15:00');
});

function apiFeeding(array $overrides = []): array
{
    return array_replace([
        'id' => 30, 'dog_id' => 1, 'meal' => 'breakfast', 'meal_label' => 'Breakfast',
        'fed_at' => '2026-10-27T07:30:00-05:00', 'served_cups' => 1.5, 'leftover_cups' => 0.0, 'eaten_cups' => 1.5,
        'food' => ['id' => 5, 'display_name' => 'Kirkland Lamb & Rice'], 'fed_by' => ['name' => 'Sam'],
        'notes' => null, 'posted_to_timeline' => false,
    ], $overrides);
}

function apiDog(array $overrides = []): array
{
    return array_replace([
        'id' => 1, 'name' => 'Biscuit', 'breed' => 'Golden Retriever', 'birthday' => null,
        'daily_target_cups' => 3.0, 'photo_url' => null,
        'today' => ['eaten_cups' => 1.5, 'served_cups' => 1.5, 'feedings' => 1, 'last_feeding' => apiFeeding()],
        'daily' => [['date' => '2026-10-26', 'eaten_cups' => 3.0], ['date' => '2026-10-27', 'eaten_cups' => 1.5]],
        'recent_feedings' => [apiFeeding()],
    ], $overrides);
}

function dogOptions(): array
{
    return ['data' => [
        'meals' => [['value' => 'breakfast', 'label' => 'Breakfast'], ['value' => 'lunch', 'label' => 'Lunch'], ['value' => 'dinner', 'label' => 'Dinner'], ['value' => 'snack', 'label' => 'Snack']],
        'foods' => [['id' => 5, 'display_name' => 'Kirkland Lamb & Rice']],
    ]];
}

it('shows each dog against today\'s target and when the bag runs out', function (): void {
    Http::fake([
        'api.test/api/v1/dogs' => Http::response(['data' => [apiDog()]]),
        'api.test/api/v1/dog-foods' => Http::response(['data' => [[
            'id' => 5, 'display_name' => 'Kirkland Lamb & Rice', 'cups_per_bag' => 120.0, 'opened_on' => '2026-10-20',
            'cups_remaining' => 79.0, 'cups_per_day' => 3.0, 'runs_out_on' => '2026-11-22', 'photo_url' => 'https://bucket.test/bag.jpg',
        ]]]),
    ]);

    Native::test(Dogs::class)
        ->assertSee('Biscuit')
        ->assertSee('1½ of 3 cups')
        ->assertSee('Breakfast · 10 hours ago · Sam')
        ->assertSee('79 cups left · runs out Nov 22')
        ->assertElement('image', fn (array $node): bool => ($node['props']['src'] ?? null) === 'https://bucket.test/bag.jpg')
        ->assertElement('progress_bar', fn (array $node): bool => abs(($node['props']['value'] ?? 0) - 0.5) < 0.001)
        ->tap('feed-1')
        ->assertNavigatedTo('/dogs/1/feed');
});

it('logs dinner in two taps, defaulting to last time\'s amount and food', function (): void {
    Http::fake([
        'api.test/api/v1/dogs/1' => Http::response(['data' => apiDog()]),
        'api.test/api/v1/dogs/options' => Http::response(dogOptions()),
        'api.test/api/v1/dogs/1/feedings' => Http::response(['data' => apiFeeding(['id' => 31])], 201),
    ]);

    Native::test(FeedDog::class, ['id' => 1])
        ->assertSet('meal', 'Dinner')
        ->assertSet('served', '1½')
        ->assertSet('food', 'Kirkland Lamb & Rice')
        ->tap('save')
        ->assertWentBack();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request['meal'] === 'dinner'
        && $request['served_cups'] === 1.5
        && $request['leftover_cups'] === 0.0
        && $request['dog_food_id'] === 5
        && $request['share_to_timeline'] === true
        && str_starts_with($request['fed_at'], '2026-10-27T18:15:00'));
});

it('uses the scoop presets and rejects leftovers above what was served', function (): void {
    Http::fake([
        'api.test/api/v1/dogs/1' => Http::response(['data' => apiDog(['today' => ['eaten_cups' => 0, 'served_cups' => 0, 'feedings' => 0, 'last_feeding' => null]])]),
        'api.test/api/v1/dogs/options' => Http::response(dogOptions()),
    ]);

    Native::test(FeedDog::class, ['id' => 1])
        ->assertSet('served', '')
        ->tap('preset-1')
        ->assertSet('served', '1')
        ->assertSet('shareToTimeline', true)
        ->set('leftover', '2')
        ->tap('save')
        ->assertSee('Leftovers can\'t be more than what was served.')
        ->assertNoNavigation();
});

it('shows the last week and opens a bowl to record leftovers', function (): void {
    Http::fake(['api.test/api/v1/dogs/1' => Http::response(['data' => apiDog()])]);

    Native::test(DogDetail::class, ['id' => 1])
        ->assertSee('Last 7 days')
        ->assertSee('Today')
        ->assertSee('Mon')
        ->assertSee('Breakfast · 1½ cups')
        ->tap('feeding-30')
        ->assertNavigatedTo('/feedings/30');
});

it('records leftovers on a bowl', function (): void {
    Http::fake(['api.test/api/v1/feedings/30' => Http::response(['data' => apiFeeding(['leftover_cups' => 0.25])])]);

    Native::test(FeedingDetail::class, ['id' => 30], ['feeding' => apiFeeding(), 'dogName' => 'Biscuit'])
        ->assertSee('Biscuit · 1½ cups')
        ->set('leftover', '1/4')
        ->tap('save')
        ->assertWentBack();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH' && $request['leftover_cups'] === 0.25);
});

it('opens a new bag of food with a photo', function (): void {
    $photo = tempnam(sys_get_temp_dir(), 'bag');
    file_put_contents($photo, 'jpeg');
    AsyncTask::fake();

    Http::fake([
        'api.test/api/v1/attachments/presign' => Http::response(['attachment' => ['id' => '01JBAG'], 'upload' => ['method' => 'PUT', 'url' => 'https://bucket.test/bag.jpg', 'headers' => []]], 201),
        'bucket.test/*' => Http::response(),
        'api.test/api/v1/attachments/01JBAG/confirm' => Http::response(['data' => ['id' => '01JBAG']]),
        'api.test/api/v1/dog-foods' => Http::response(['data' => ['id' => 6]], 201),
    ]);

    Native::test(NewDogFood::class)
        ->emitNative(PhotoTaken::class, ['path' => $photo, 'mimeType' => 'image/jpeg'])
        ->assertSee('Ready')
        ->assertMissingElement('button', fn (array $node): bool => ($node['ref'] ?? null) === 'camera')
        ->set('brand', 'Kirkland')
        ->set('name', 'Lamb & Rice')
        ->set('cupsPerBag', '160')
        ->tap('save')
        ->assertWentBack();

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'dog-foods')
        && $request['brand'] === 'Kirkland' && $request['cups_per_bag'] === 160.0 && $request['attachment_ids'] === ['01JBAG']);
});

it('guesses breakfast at 8am household time', function (): void {
    $this->travelTo(Carbon::parse('2026-10-27 08:00', 'America/Chicago'));
    Http::fake([
        'api.test/api/v1/dogs/1' => Http::response(['data' => apiDog()]),
        'api.test/api/v1/dogs/options' => Http::response(dogOptions()),
    ]);

    Native::test(FeedDog::class, ['id' => 1])->assertSet('meal', 'Breakfast');
});

it('hides Feed both with a single dog', function (): void {
    Http::fake(['api.test/api/v1/dogs' => Http::response(['data' => [apiDog()]]), 'api.test/api/v1/dog-foods' => Http::response(['data' => []])]);

    Native::test(Dogs::class)->assertDontSee('Feed both');
});

it('offers Feed both with two dogs', function (): void {
    $foods = ['api.test/api/v1/dog-foods' => Http::response(['data' => []])];

    Http::fake(['api.test/api/v1/dogs' => Http::response(['data' => [apiDog(), apiDog(['id' => 2, 'name' => 'Maple'])]]), ...$foods]);
    Native::test(Dogs::class)
        ->assertSee('Feed both')
        ->tap('feed-all')
        ->assertNavigatedTo('/dogs/feed-all');
});

it('feeds both dogs with their own amounts in one request', function (): void {
    $maple = apiDog(['id' => 2, 'name' => 'Maple', 'today' => ['eaten_cups' => 0, 'served_cups' => 0, 'feedings' => 0, 'last_feeding' => apiFeeding(['served_cups' => 1.0])]]);

    Http::fake([
        'api.test/api/v1/dogs' => Http::response(['data' => [apiDog(), $maple]]),
        'api.test/api/v1/dogs/options' => Http::response(dogOptions()),
        'api.test/api/v1/feedings/batch' => Http::response(['data' => [apiFeeding(), apiFeeding(['id' => 31, 'dog_id' => 2])]]),
    ]);

    Native::test(FeedDogs::class)
        ->assertSet('meal', 'Dinner')
        ->assertSet('servings', [1 => '1½', 2 => '1'])
        ->assertSet('shareToTimeline', true)
        ->set('servings.2', '1 1/4')
        ->tap('save')
        ->assertWentBack();

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.test/api/v1/feedings/batch'
        && $request['meal'] === 'dinner'
        && $request['dog_food_id'] === 5
        && $request['share_to_timeline'] === true
        && $request['bowls'] === [
            ['dog_id' => 1, 'served_cups' => 1.5],
            ['dog_id' => 2, 'served_cups' => 1.25],
        ]);
});

it('leaves out a dog that is skipping the meal', function (): void {
    Http::fake([
        'api.test/api/v1/dogs' => Http::response(['data' => [apiDog(), apiDog(['id' => 2, 'name' => 'Maple'])]]),
        'api.test/api/v1/dogs/options' => Http::response(dogOptions()),
        'api.test/api/v1/feedings/batch' => Http::response(['data' => [apiFeeding()]]),
    ]);

    Native::test(FeedDogs::class)
        ->set('included.2', false)
        ->tap('save')
        ->assertWentBack();

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'feedings/batch')
        && $request['bowls'] === [['dog_id' => 1, 'served_cups' => 1.5]]);
});

it('asks for an amount for each dog being fed', function (): void {
    $maple = apiDog(['id' => 2, 'name' => 'Maple', 'today' => ['eaten_cups' => 0, 'served_cups' => 0, 'feedings' => 0, 'last_feeding' => null]]);

    Http::fake([
        'api.test/api/v1/dogs' => Http::response(['data' => [apiDog(), $maple]]),
        'api.test/api/v1/dogs/options' => Http::response(dogOptions()),
    ]);

    Native::test(FeedDogs::class)
        ->tap('save')
        ->assertSee('How much did Maple get?')
        ->assertNoNavigation();
});

it('sets each dog\'s amount with the scoop buttons on Feed both', function (): void {
    $noHistory = ['eaten_cups' => 0, 'served_cups' => 0, 'feedings' => 0, 'last_feeding' => null];

    Http::fake([
        'api.test/api/v1/dogs' => Http::response(['data' => [apiDog(['today' => $noHistory]), apiDog(['id' => 2, 'name' => 'Maple', 'today' => $noHistory])]]),
        'api.test/api/v1/dogs/options' => Http::response(dogOptions()),
        'api.test/api/v1/feedings/batch' => Http::response(['data' => []]),
    ]);

    Native::test(FeedDogs::class)
        ->assertSet('servings', [1 => '', 2 => ''])
        ->tap('preset-1-2')
        ->tap('preset-2-1')
        ->assertSet('servings', [1 => '1½', 2 => '1'])
        ->assertElement('button', fn (array $node): bool => ($node['ref'] ?? null) === 'preset-1-2'
            && ($node['props']['variant'] ?? null) === 'primary')
        ->tap('save')
        ->assertWentBack();

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), 'feedings/batch')
        && $request['bowls'] === [['dog_id' => 1, 'served_cups' => 1.5], ['dog_id' => 2, 'served_cups' => 1.0]]);
});

it('edits a dog food and replaces its photo', function (): void {
    Http::fake([
        'api.test/api/v1/dog-foods/6' => Http::sequence()
            ->push(['data' => ['id' => 6, 'brand' => 'Kirkland', 'name' => 'Lamb & Rice', 'cups_per_bag' => 160.0, 'photo_url' => 'https://bucket.test/old.jpg']])
            ->push(['data' => ['id' => 6]]),
    ]);

    Native::test(EditDogFood::class, ['id' => 6])
        ->assertSet('brand', 'Kirkland')
        ->assertSet('cupsPerBag', '160')
        ->assertSee('Add a new photo to replace this one.')
        ->set('name', 'Salmon & Sweet Potato')
        ->tap('save')
        ->assertWentBack();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'PATCH'
        && str_ends_with($request->url(), '/dog-foods/6')
        && $request['name'] === 'Salmon & Sweet Potato'
        && $request['cups_per_bag'] === 160.0);
});

it('calls it dog food, and opens the edit screen from a food', function (): void {
    Http::fake([
        'api.test/api/v1/dogs' => Http::response(['data' => [apiDog()]]),
        'api.test/api/v1/dog-foods' => Http::response(['data' => [[
            'id' => 6, 'brand' => 'Kirkland', 'name' => 'Lamb & Rice', 'display_name' => 'Kirkland Lamb & Rice',
            'cups_per_bag' => null, 'opened_on' => '2026-10-01', 'photo_url' => null, 'cups_remaining' => null, 'runs_out_on' => null,
        ]]]),
    ]);

    Native::test(Dogs::class)
        ->assertSee('Dog food')
        ->assertSee('Add dog food')
        ->assertDontSee('bag')
        ->tap('edit-food-6')
        ->assertNavigatedTo('/dog-foods/6/edit');
});
