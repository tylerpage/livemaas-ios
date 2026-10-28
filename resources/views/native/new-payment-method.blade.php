<native:scroll-view fill class="bg-theme-background">
    <native:column class="w-full gap-4 p-4">
        <native:outlined-text-input
            ref="nickname"
            label="Nickname"
            placeholder="Chase Sapphire"
            autocapitalize="words"
            :error="isset($fieldErrors['nickname'])"
            :supporting="$fieldErrors['nickname'] ?? null"
            native:model.blur="nickname"
        />

        <native:select ref="type" label="Type" :options="$this->typeLabels()" native:model="type" />

        <native:outlined-text-input
            ref="last4"
            label="Last four digits (optional)"
            keyboard="number"
            max-length="4"
            :error="isset($fieldErrors['last4'])"
            :supporting="$fieldErrors['last4'] ?? 'Never enter the full card number.'"
            native:model.blur="last4"
        />

        <native:select ref="owner" label="Whose is it?" :options="$this->ownerNames()" native:model="owner" />

        @if ($error)
            <native:text class="text-sm text-theme-destructive">{{ $error }}</native:text>
        @endif

        <native:button ref="save" variant="primary" class="w-full" :loading="$saving" @tap="save">Save card</native:button>
    </native:column>
</native:scroll-view>
