<native:scroll-view fill class="bg-theme-background">
    <native:column class="w-full gap-4 p-4">
        <native:outlined-text-input
            ref="payee"
            label="Paid to"
            placeholder="Xcel Energy"
            autocapitalize="words"
            :error="isset($fieldErrors['payee'])"
            :supporting="$fieldErrors['payee'] ?? null"
            native:model.blur="payee"
        />

        <native:outlined-text-input
            ref="amount"
            label="Amount"
            prefix="$"
            keyboard="decimal"
            :error="isset($fieldErrors['amount'])"
            :supporting="$fieldErrors['amount'] ?? null"
            native:model.blur="amount"
        />

        <native:select ref="category" label="Category" :options="$this->categoryLabels()" native:model="category" />

        <native:date-picker ref="paid-on" label="Paid on" mode="date" :max="now()" native:model="paidOn" />

        <native:select ref="paid-by" label="Paid by" :options="$this->memberNames()" native:model="paidBy" />

        <native:column class="w-full gap-1">
            <native:select ref="payment-method" label="Paid with" :options="$this->cardNames()" native:model="paymentMethod" />
            <native:button ref="add-card" variant="ghost" size="sm" @tap="addCard">Add a card or account</native:button>
        </native:column>

        <native:outlined-text-input ref="notes" label="Notes" multiline min-lines="2" native:model.blur="notes" />

        <native:column class="w-full gap-1">
            <native:toggle ref="private" label="Private" native:model="isPrivate" />
            <native:text class="text-sm text-theme-on-surface-variant">Only you can see it, and it isn't part of the bill split.</native:text>
        </native:column>

        <native:text class="text-sm font-bold text-theme-on-surface-variant">{{ ($existingPhotos ?? 0) > 0 ? 'Add more photos of the bill' : 'Photos of the bill' }}</native:text>
        @if (($existingPhotos ?? 0) > 0)
            <native:text ref="existing-photos" class="text-sm text-theme-on-surface-variant">{{ $existingPhotos }} {{ Str::plural('photo', $existingPhotos) }} already saved with this bill.</native:text>
        @endif
        @include('native.partials.media-picker', ['allowVideo' => false])

        @if ($error)
            <native:text ref="bill-error" class="text-sm text-theme-destructive">{{ $error }}</native:text>
        @endif

        <native:button ref="save" variant="primary" class="w-full" :loading="$saving" :disabled="$this->isUploading()" @tap="save">
            {{ $this->isUploading() ? 'Waiting for uploads…' : 'Save bill' }}
        </native:button>
    </native:column>
</native:scroll-view>
