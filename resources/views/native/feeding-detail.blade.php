<native:scroll-view fill class="bg-theme-background">
    <native:column class="w-full gap-4 p-4">
        @if ($feeding !== [])
            <native:column class="w-full rounded-lg bg-theme-secondary p-5 gap-1">
                <native:text class="text-2xl font-bold text-theme-on-secondary">{{ $dogName }} · {{ $this->cups($feeding['served_cups']) }}</native:text>
                <native:text class="text-theme-on-secondary opacity-80">{{ $this->fedAtLabel() }} by {{ $feeding['fed_by']['name'] ?? 'someone' }}</native:text>
                @if ($feeding['food'])
                    <native:text class="text-theme-on-secondary opacity-80">{{ $feeding['food']['display_name'] }}</native:text>
                @endif
                @if ($feeding['notes'])
                    <native:text class="text-theme-on-secondary">{{ $feeding['notes'] }}</native:text>
                @endif
            </native:column>

            <native:outlined-text-input
                ref="leftover"
                label="Left in the bowl"
                placeholder="0"
                keyboard="decimal"
                suffix="cups"
                native:model.blur="leftover"
            />

            @if ($error)
                <native:text ref="feeding-error" class="text-sm text-theme-destructive">{{ $error }}</native:text>
            @endif

            <native:button ref="save" variant="primary" class="w-full" :loading="$saving" @tap="save">Save leftovers</native:button>

            @if ($confirmingDelete)
                <native:row class="w-full gap-2">
                    <native:button ref="cancel-delete" variant="secondary" class="flex-1" @tap="cancelDelete">Keep</native:button>
                    <native:button ref="confirm-delete" variant="destructive" class="flex-1" @tap="delete">Delete</native:button>
                </native:row>
            @else
                <native:button ref="delete" variant="ghost" class="w-full" @tap="askToDelete">Delete this feeding</native:button>
            @endif
        @endif
    </native:column>
</native:scroll-view>
