<native:scroll-view fill class="bg-theme-background">
    <native:column class="w-full gap-4 p-4">
        <native:outlined-text-input
            ref="body"
            label="What's happening at the house?"
            multiline
            min-lines="4"
            max-length="5000"
            native:model.debounce.300ms="body"
        />

        @include('native.partials.media-picker')

        @if ($error)
            <native:text ref="post-error" class="text-sm text-theme-destructive">{{ $error }}</native:text>
        @endif

        <native:button ref="post" variant="primary" class="w-full" :loading="$posting" :disabled="! $this->canPost()" @tap="post">
            {{ $this->isUploading() ? 'Waiting for uploads…' : 'Post' }}
        </native:button>
    </native:column>
</native:scroll-view>
