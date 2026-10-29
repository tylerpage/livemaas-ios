<?php

declare(strict_types=1);

use App\NativeComponents\DocumentDetail;
use App\NativeComponents\EditDocument;
use App\NativeComponents\Files;
use App\NativeComponents\NewDocument;
use App\Services\Api\ApiSession;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livemaas\MobileDocumentPicker\Events\DocumentsPicked;
use Native\Mobile\AsyncTask;
use Native\Mobile\Testing\Native;

beforeEach(function (): void {
    app(ApiSession::class)->start('1|secret-token', 1, 'Tyler');
    AsyncTask::fake();
});

function documentCategories(): array
{
    return ['data' => ['categories' => [
        ['value' => 'insurance', 'label' => 'Insurance'],
        ['value' => 'warranties', 'label' => 'Warranties'],
    ]]];
}

function apiDocument(array $overrides = []): array
{
    return array_replace([
        'id' => 4,
        'title' => 'Home insurance 2026',
        'category_label' => 'Insurance',
        'notes' => 'Policy HO-123. Renews in March.',
        'attachment_count' => 1,
        'attachments' => [['id' => 'a1', 'mime_type' => 'application/pdf', 'url' => 'https://bucket.test/policy.pdf', 'original_name' => 'policy.pdf', 'size_bytes' => 2_400_000]],
        'created_by' => ['name' => 'Tyler'],
        'created_at' => '2026-10-01T10:00:00+00:00',
    ], $overrides);
}

it('lists files and filters by category and search', function (): void {
    Http::fake([
        'api.test/api/v1/documents/options' => Http::response(documentCategories()),
        'api.test/api/v1/documents*' => Http::response(['data' => [apiDocument()]]),
    ]);

    Native::test(Files::class)
        ->assertSee('Home insurance 2026')
        ->assertSee('Insurance · 1 file · Oct 1, 2026')
        ->set('category', 'Warranties')
        ->set('search', 'furnace')
        ->tap('document-4')
        ->assertNavigatedTo('/documents/4');

    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'category=warranties')
        && str_contains($request->url(), 'search=furnace'));
});

it('files a PDF picked from the Files app', function (): void {
    $pdf = tempnam(sys_get_temp_dir(), 'policy');
    file_put_contents($pdf, '%PDF-1.7 test');

    Http::fake([
        'api.test/api/v1/documents/options' => Http::response(documentCategories()),
        'api.test/api/v1/attachments/presign' => Http::response([
            'attachment' => ['id' => '01JPDF'],
            'upload' => ['method' => 'PUT', 'url' => 'https://bucket.test/01JPDF.pdf', 'headers' => []],
        ], 201),
        'bucket.test/*' => Http::response(),
        'api.test/api/v1/attachments/01JPDF/confirm' => Http::response(['data' => ['id' => '01JPDF']]),
        'api.test/api/v1/documents' => Http::response(['data' => apiDocument()], 201),
    ]);

    Native::test(NewDocument::class)
        ->set('title', 'Home insurance 2026')
        ->emitNative(DocumentsPicked::class, [
            'success' => true,
            'files' => [['path' => $pdf, 'name' => 'policy.pdf', 'mimeType' => 'application/pdf', 'size' => 13]],
            'count' => 1,
        ])
        ->assertSee('policy.pdf')
        ->assertSee('Ready')
        ->tap('save')
        ->assertWentBack();

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.test/api/v1/documents'
        && $request['category'] === 'insurance'
        && $request['attachment_ids'] === ['01JPDF']);
});

it('will not save a document without a file', function (): void {
    Http::fake(['api.test/api/v1/documents/options' => Http::response(documentCategories())]);

    Native::test(NewDocument::class)
        ->set('title', 'Empty')
        ->tap('save')
        ->assertSee('Add at least one file or photo.')
        ->assertNoNavigation();
});

it('shows a document and opens its PDF', function (): void {
    Http::fake(['api.test/api/v1/documents/4' => Http::response(['data' => apiDocument()])]);
    $bridge = Native::fakeBridge()->respondTo('Browser.OpenInApp', ['success' => true]);

    Native::test(DocumentDetail::class, ['id' => 4])
        ->assertSee('Home insurance 2026')
        ->assertSee('Policy HO-123. Renews in March.')
        ->assertSee('2.3 MB')
        ->tap('attachment-0');

    $bridge->assertCalled('Browser.OpenInApp', fn (array $params): bool => $params['url'] === 'https://bucket.test/policy.pdf');
});

it('edits a file\'s name, category and notes without adding files', function (): void {
    Http::fake([
        'api.test/api/v1/documents/options' => Http::response(documentCategories()),
        'api.test/api/v1/documents/4' => Http::sequence()
            ->push(['data' => apiDocument()])
            ->push(['data' => apiDocument(['title' => 'Home insurance 2027'])]),
    ]);

    Native::test(EditDocument::class, ['id' => 4])
        ->assertSet('title', 'Home insurance 2026')
        ->assertSet('category', 'Insurance')
        ->assertSee('1 file already saved with this one.')
        ->set('title', 'Home insurance 2027')
        ->set('category', 'Warranties')
        ->tap('save')
        ->assertWentBack();

    Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
        && str_ends_with($request->url(), '/documents/4')
        && $request['title'] === 'Home insurance 2027'
        && $request['category'] === 'warranties'
        && $request['attachment_ids'] === []);
});

it('opens the edit screen from a file', function (): void {
    Http::fake(['api.test/api/v1/documents/4' => Http::response(['data' => apiDocument()])]);

    Native::test(DocumentDetail::class, ['id' => 4])->tap('edit')->assertNavigatedTo('/documents/4/edit');
});
