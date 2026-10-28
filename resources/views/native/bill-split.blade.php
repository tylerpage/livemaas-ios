{{-- Per-member fields spell out what native:model expands to: its value cannot contain a Blade echo, and the property path depends on the member id. --}}
<native:scroll-view fill class="bg-theme-background">
    <native:column class="w-full gap-4 p-4">
        <native:text class="text-sm text-theme-on-surface-variant">
            How the household's bills are shared. The Bills tab shows who owes whom each month. Private bills are never split.
        </native:text>

        <native:column class="w-full rounded-lg bg-theme-surface p-4 gap-3">
            @foreach ($members as $member)
                <native:outlined-text-input
                    ref="percent-{{ $member['id'] }}"
                    label="{{ $member['name'] }}"
                    keyboard="decimal"
                    suffix="%"
                    :value="$percents[$member['id']] ?? ''"
                    _change="__syncProperty('percents.{{ $member['id'] }}')"
                    sync-mode="live"
                />
            @endforeach

            <native:divider class="w-full border-theme-outline-variant" />
            <native:row class="w-full">
                <native:text class="flex-1 text-sm text-theme-on-surface-variant">Total</native:text>
                <native:text ref="total" :class="'text-sm font-bold '.($this->totalIsRight() ? 'text-theme-success' : 'text-theme-destructive')">{{ $this->format($this->total()) }}%</native:text>
            </native:row>
        </native:column>

        @if ($error)
            <native:text ref="split-error" class="text-sm text-theme-destructive">{{ $error }}</native:text>
        @endif

        <native:button ref="save" variant="primary" class="w-full" :loading="$saving" :disabled="! $this->totalIsRight()" @tap="save">
            {{ $this->totalIsRight() ? 'Save split' : 'Shares must add up to 100%' }}
        </native:button>

        @if ($enabled)
            <native:button ref="stop-splitting" variant="ghost" class="w-full" @tap="stopSplitting">Stop splitting bills</native:button>
        @endif
    </native:column>
</native:scroll-view>
