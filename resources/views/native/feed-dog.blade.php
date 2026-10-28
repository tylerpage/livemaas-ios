<native:scroll-view fill class="bg-theme-background">
    <native:column class="w-full gap-4 p-4">
        <native:select ref="meal" label="Meal" :options="$this->mealLabels()" native:model="meal" />

        <native:column class="w-full gap-2">
            <native:outlined-text-input
                ref="served"
                label="Cups served"
                keyboard="decimal"
                suffix="cups"
                :error="isset($fieldErrors['served'])"
                :supporting="$fieldErrors['served'] ?? null"
                native:model.blur="served"
            />
            <native:row class="w-full gap-2">
                @foreach (\App\NativeComponents\FeedDog::Presets as $preset)
                    <native:button ref="preset-{{ $loop->index }}" size="sm" class="flex-1"
                                   :variant="$this->isPresetSelected($preset) ? 'primary' : 'secondary'"
                                   a11y-label="{{ $this->presetLabel($preset) }} cups" @tap="usePreset({{ $preset }})">
                        {{ $this->presetLabel($preset) }}
                    </native:button>
                @endforeach
            </native:row>
        </native:column>

        <native:select ref="food" label="Food" :options="$this->foodNames()" native:model="food" />

        <native:outlined-text-input
            ref="leftover"
            label="Left in the bowl (optional)"
            placeholder="You can add this later"
            keyboard="decimal"
            suffix="cups"
            :error="isset($fieldErrors['leftover'])"
            :supporting="$fieldErrors['leftover'] ?? null"
            native:model.blur="leftover"
        />

        <native:outlined-text-input ref="notes" label="Notes" placeholder="Added pumpkin, ate slowly…" native:model.blur="notes" />

        <native:toggle ref="share" label="Post to the house timeline" native:model="shareToTimeline" />

        @if ($error)
            <native:text ref="feed-error" class="text-sm text-theme-destructive">{{ $error }}</native:text>
        @endif

        <native:button ref="save" variant="primary" class="w-full" :loading="$saving" @tap="save">Log feeding</native:button>
    </native:column>
</native:scroll-view>
