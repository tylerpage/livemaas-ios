<native:scroll-view fill class="bg-theme-background">
    <native:column class="w-full gap-4 p-4">
        <native:outlined-text-input
            ref="name"
            label="Name"
            autocapitalize="words"
            :error="isset($fieldErrors['name'])"
            :supporting="$fieldErrors['name'] ?? null"
            native:model.blur="name"
        />
        <native:outlined-text-input ref="breed" label="Breed (optional)" autocapitalize="words" native:model.blur="breed" />
        <native:date-picker ref="birthday" label="Birthday (optional)" placeholder="Not set" mode="date" :max="now()" native:model="birthday" />
        <native:outlined-text-input
            ref="daily-target"
            label="Daily food target (optional)"
            keyboard="decimal"
            suffix="cups"
            :error="isset($fieldErrors['dailyTarget'])"
            :supporting="$fieldErrors['dailyTarget'] ?? 'Shows how close they are each day.'"
            native:model.blur="dailyTarget"
        />

        <native:text class="text-sm font-bold text-theme-on-surface-variant">Photo</native:text>
        @if ($media === [])
            @include('native.partials.media-picker', ['allowVideo' => false])
        @else
            @include('native.partials.media-picker', ['allowVideo' => false, 'hideButtons' => true])
        @endif

        @if ($error)
            <native:text class="text-sm text-theme-destructive">{{ $error }}</native:text>
        @endif

        <native:button ref="save" variant="primary" class="w-full" :loading="$saving" :disabled="$this->isUploading()" @tap="save">Save</native:button>
    </native:column>
</native:scroll-view>
