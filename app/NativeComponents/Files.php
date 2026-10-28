<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\NativeComponents\Concerns\RequiresSignIn;
use App\Services\Api\ApiException;
use App\Services\Api\LivemaasApi;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

/**
 * The household's paperwork: insurance, warranties, manuals and the rest,
 * filterable by category and searchable by title or notes.
 */
class Files extends NativeComponent
{
    use RequiresSignIn;

    private const string AllCategories = 'All categories';

    /** @var list<array<string, mixed>> */
    public array $documents = [];

    /** @var list<array{value: string, label: string}> */
    public array $categories = [];

    public string $category = self::AllCategories;

    public string $search = '';

    public bool $loaded = false;

    public ?string $error = null;

    public function mount(LivemaasApi $api): void
    {
        if ($this->redirectIfSignedOut()) {
            return;
        }

        try {
            $this->categories = $api->documentCategories();
        } catch (ApiException $exception) {
            $this->error = $exception->getMessage();
        }

        $this->refresh($api);
    }

    public function onResume(): void
    {
        $this->refresh(app(LivemaasApi::class));
    }

    public function updatedCategory(): void
    {
        $this->refresh(app(LivemaasApi::class));
    }

    public function updatedSearch(): void
    {
        $this->refresh(app(LivemaasApi::class));
    }

    public function refresh(LivemaasApi $api): void
    {
        $this->error = null;

        try {
            $this->documents = $api->documents(
                collect($this->categories)->firstWhere('label', $this->category)['value'] ?? null,
                trim($this->search) === '' ? null : trim($this->search),
            );
        } catch (ApiException $exception) {
            if ($exception->isUnauthenticated()) {
                $this->replace('/login');

                return;
            }

            $this->error = $exception->getMessage();
        } finally {
            $this->loaded = true;
        }
    }

    /**
     * @return list<string>
     */
    public function categoryOptions(): array
    {
        return [self::AllCategories, ...array_column($this->categories, 'label')];
    }

    public function isFiltered(): bool
    {
        return $this->category !== self::AllCategories || trim($this->search) !== '';
    }

    public function addedOn(string $timestamp): string
    {
        return Carbon::parse($timestamp)->format('M j, Y');
    }

    public function add(): void
    {
        $this->navigate('/documents/new');
    }

    public function open(int $documentId): void
    {
        $this->navigate("/documents/{$documentId}");
    }

    public function navTitle(): string
    {
        return 'Files';
    }

    public function render(): View
    {
        return view('native.files');
    }
}
