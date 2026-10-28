<native:scroll-view fill class="bg-theme-background">
    <native:column class="w-full gap-4 p-4">
        <native:outlined-text-input ref="brand" label="Brand" placeholder="Purina Pro Plan" autocapitalize="words"
                                    :error="isset($fieldErrors['brand'])" :supporting="$fieldErrors['brand'] ?? null" native:model.blur="brand" />
        <native:outlined-text-input ref="name" label="Formula" placeholder="Adult Chicken & Rice" autocapitalize="words"
                                    :error="isset($fieldErrors['name'])" :supporting="$fieldErrors['name'] ?? null" native:model.blur="name" />
        <native:outlined-text-input ref="cups-per-bag" label="Total cups (optional)" keyboard="decimal" suffix="cups"
                                    :error="isset($fieldErrors['cupsPerBag'])"
                                    :supporting="$fieldErrors['cupsPerBag'] ?? 'Usually on the label, e.g. 30 lb is about 120 cups.'"
                                    native:model.blur="cupsPerBag" />

        <native:text class="text-sm font-bold text-theme-on-surface-variant">Photo (optional)</native:text>
        @if (($currentPhotoUrl ?? null) && $media === [])
            <native:row class="w-full items-center gap-3">
                <native:image ref="current-photo" src="{{ $currentPhotoUrl }}" alt="Current photo" class="w-16 h-16 rounded-md object-cover" />
                <native:text class="flex-1 text-sm text-theme-on-surface-variant">Add a new photo to replace this one.</native:text>
            </native:row>
        @endif
        @include('native.partials.media-picker', ['allowVideo' => false, 'hideButtons' => $media !== []])

        @if ($error)
            <native:text class="text-sm text-theme-destructive">{{ $error }}</native:text>
        @endif

        <native:button ref="save" variant="primary" class="w-full" :loading="$saving" :disabled="$this->isUploading()" @tap="save">
            {{ $this->isUploading() ? 'Waiting for the photo…' : 'Save' }}
        </native:button>
    </native:column>
</native:scroll-view>
