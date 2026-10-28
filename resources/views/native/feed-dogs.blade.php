{{-- Per-dog fields spell out what native:model expands to: its value cannot contain a Blade echo, and the property path here depends on the dog id. --}}
@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:scroll-view fill class="bg-theme-background">
    <native:column class="w-full gap-4 p-4">
        <native:select ref="meal" label="Meal" :options="$this->mealLabels()" native:model="meal" />

        @foreach ($dogs as $dog)
            <native:column class="w-full rounded-lg bg-theme-surface border border-theme-outline-variant p-4 gap-3">
                <native:row class="w-full items-center gap-3">
                    @if ($dog['photo_url'])
                        <native:image src="{{ $dog['photo_url'] }}" alt="" class="w-10 h-10 rounded-full object-cover" />
                    @else
                        <native:column center class="w-10 h-10 rounded-full bg-theme-secondary">
                            <native:icon :ios="Ios::PawprintFill" :android="Android::Pets" :size="18" class="text-theme-on-secondary" />
                        </native:column>
                    @endif
                    <native:text class="flex-1 text-lg font-bold text-theme-on-surface">{{ $dog['name'] }}</native:text>
                    <native:toggle ref="include-{{ $dog['id'] }}" a11y-label="Feed {{ $dog['name'] }}" :value="$included[$dog['id']] ?? false" _change="__syncProperty('included.{{ $dog['id'] }}')" sync-mode="live" />
                </native:row>

                @if ($included[$dog['id']] ?? false)
                    <native:outlined-text-input
                        ref="serving-{{ $dog['id'] }}"
                        label="Cups for {{ $dog['name'] }}"
                        keyboard="decimal"
                        suffix="cups"
                        :error="isset($fieldErrors[$dog['id']])"
                        :supporting="$fieldErrors[$dog['id']] ?? null"
                        :value="$servings[$dog['id']] ?? ''"
                        _change="__syncProperty('servings.{{ $dog['id'] }}')"
                        sync-mode="blur"
                    />
                    <native:row class="w-full gap-2">
                        @foreach (\App\NativeComponents\FeedDog::Presets as $preset)
                            <native:button ref="preset-{{ $dog['id'] }}-{{ $loop->index }}" size="sm" class="flex-1"
                                           :variant="$this->isPresetSelected($dog['id'], $preset) ? 'primary' : 'secondary'"
                                           a11y-label="{{ $this->presetLabel($preset) }} cups for {{ $dog['name'] }}"
                                           @tap="usePreset({{ $dog['id'] }}, {{ $preset }})">
                                {{ $this->presetLabel($preset) }}
                            </native:button>
                        @endforeach
                    </native:row>
                @endif
            </native:column>
        @endforeach

        <native:select ref="food" label="Food" :options="$this->foodNames()" native:model="food" />

        <native:outlined-text-input ref="notes" label="Notes" placeholder="Added pumpkin, both ate fast…" native:model.blur="notes" />

        <native:toggle ref="share" label="Post to the house timeline" native:model="shareToTimeline" />

        @if ($error)
            <native:text ref="feed-error" class="text-sm text-theme-destructive">{{ $error }}</native:text>
        @endif

        <native:button ref="save" variant="primary" class="w-full" :loading="$saving" @tap="save">
            {{ count($dogs) === 2 ? 'Feed both' : 'Feed all' }}
        </native:button>
    </native:column>
</native:scroll-view>
