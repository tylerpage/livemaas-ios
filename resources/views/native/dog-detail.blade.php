@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:scroll-view fill class="bg-theme-background">
    <native:column class="w-full gap-4 p-4">
        @if ($dog)
            <native:row class="w-full items-center gap-4 rounded-lg bg-theme-secondary p-5">
                @if ($dog['photo_url'])
                    <native:image src="{{ $dog['photo_url'] }}" alt="{{ $dog['name'] }}" class="w-20 h-20 rounded-full object-cover" />
                @else
                    <native:column center class="w-20 h-20 rounded-full bg-theme-primary">
                        <native:icon :ios="Ios::PawprintFill" :android="Android::Pets" :size="32" class="text-theme-on-primary" />
                    </native:column>
                @endif
                <native:column class="flex-1 gap-1">
                    <native:text class="text-2xl font-bold text-theme-on-secondary">{{ $dog['name'] }}</native:text>
                    @if ($dog['breed'])
                        <native:text class="text-theme-on-secondary opacity-80">{{ $dog['breed'] }}</native:text>
                    @endif
                    <native:text ref="today-total" class="text-theme-on-secondary">
                        @if ($dog['daily_target_cups'])
                            {{ $this->cups($dog['today']['eaten_cups'], withUnit: false) }} of {{ $this->cups($dog['daily_target_cups']) }} today
                        @else
                            {{ $this->cups($dog['today']['eaten_cups']) }} today
                        @endif
                    </native:text>
                </native:column>
            </native:row>

            <native:button ref="feed" variant="primary" class="w-full" @tap="feed">Feed {{ $dog['name'] }}</native:button>

            <native:column class="w-full rounded-lg bg-theme-surface p-4 gap-3">
                <native:text class="font-bold text-theme-primary">Last 7 days</native:text>
                @foreach ($dog['daily'] as $day)
                    <native:row class="w-full items-center gap-3">
                        <native:text class="w-12 text-sm text-theme-on-surface-variant">{{ $this->dayLabel($day['date']) }}</native:text>
                        <native:progress-bar :value="$this->dayProgress($day['eaten_cups'])" class="flex-1" />
                        <native:text class="w-16 text-sm text-right text-theme-on-surface">{{ $this->cups($day['eaten_cups'], withUnit: false) }}</native:text>
                    </native:row>
                @endforeach
            </native:column>

            <native:text class="font-bold text-theme-primary">Recent bowls</native:text>
            @forelse ($dog['recent_feedings'] as $feeding)
                <native:row ref="feeding-{{ $feeding['id'] }}" a11y-label="{{ $feeding['meal_label'] }}, {{ $this->cups($feeding['served_cups']) }}"
                            class="w-full items-center gap-3 rounded-lg bg-theme-surface border border-theme-outline-variant p-3"
                            @tap="openFeeding({{ $loop->index }})">
                    <native:column class="flex-1 gap-1">
                        <native:text class="font-bold text-theme-on-surface">{{ $feeding['meal_label'] }} · {{ $this->cups($feeding['served_cups']) }}</native:text>
                        <native:text class="text-sm text-theme-on-surface-variant">{{ $this->feedingLabel($feeding) }}</native:text>
                    </native:column>
                    @if ($feeding['leftover_cups'] > 0)
                        <native:text class="text-sm text-theme-on-surface-variant">{{ $this->cups($feeding['leftover_cups'], withUnit: false) }} left</native:text>
                    @endif
                    <native:icon :ios="Ios::ChevronRight" :android="Android::ChevronRight" :size="14" class="text-theme-on-surface-variant" />
                </native:row>
            @empty
                <native:text class="text-sm text-theme-on-surface-variant">No feedings logged yet.</native:text>
            @endforelse
        @endif

        @if ($error)
            <native:text class="text-sm text-theme-destructive">{{ $error }}</native:text>
        @endif
    </native:column>
</native:scroll-view>
