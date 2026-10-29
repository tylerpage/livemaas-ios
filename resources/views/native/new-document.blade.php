<native:scroll-view fill class="bg-theme-background">
    <native:column class="w-full gap-4 p-4">
        <native:outlined-text-input
            ref="title"
            label="Name"
            placeholder="Home insurance 2026"
            autocapitalize="words"
            :error="isset($fieldErrors['title'])"
            :supporting="$fieldErrors['title'] ?? null"
            native:model.blur="title"
        />

        <native:select ref="category" label="Category" :options="$this->categoryLabels()" native:model="category" />

        <native:outlined-text-input ref="notes" label="Notes" placeholder="Policy number, renewal date…" multiline min-lines="2" native:model.blur="notes" />

        <native:text class="text-sm font-bold text-theme-on-surface-variant">{{ ($existingFiles ?? 0) > 0 ? 'Add more files' : 'Files' }}</native:text>
        @if (($existingFiles ?? 0) > 0)
            <native:text ref="existing-files" class="text-sm text-theme-on-surface-variant">{{ $existingFiles }} {{ Str::plural('file', $existingFiles) }} already saved with this one.</native:text>
        @endif
        @include('native.partials.media-picker', ['allowVideo' => false, 'allowFiles' => true])

        @if ($error)
            <native:text ref="document-error" class="text-sm text-theme-destructive">{{ $error }}</native:text>
        @endif

        <native:button ref="save" variant="primary" class="w-full" :loading="$saving" :disabled="$this->isUploading()" @tap="save">
            {{ $this->isUploading() ? 'Waiting for uploads…' : 'Save' }}
        </native:button>
    </native:column>
</native:scroll-view>
