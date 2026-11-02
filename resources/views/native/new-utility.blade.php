<native:scroll-view fill class="bg-theme-background">
    <native:column class="w-full gap-4 p-4">
        <native:outlined-text-input
            ref="name"
            label="Utility"
            placeholder="Xcel Energy"
            autocapitalize="words"
            :error="isset($fieldErrors['name'])"
            :supporting="$fieldErrors['name'] ?? null"
            native:model.blur="name"
        />

        <native:select ref="kind" label="Type" :options="$this->kindLabels()" native:model="kind" />

        <native:select ref="payment-method" label="Paid with" :options="$this->cardNames()" native:model="paymentMethod" />

        <native:outlined-text-input ref="account-number" label="Account number" autocapitalize="none" native:model.blur="accountNumber" />

        <native:text class="text-sm font-bold text-theme-on-surface-variant">Sign-in</native:text>

        <native:outlined-text-input ref="website" label="Website" placeholder="xcelenergy.com" keyboard="url" native:model.blur="website" />

        <native:outlined-text-input ref="username" label="Username or email" keyboard="email" native:model.blur="username" />

        <native:outlined-text-input ref="password" label="Password" secure revealable native:model.blur="password" />

        <native:outlined-text-input ref="login-notes" label="How to log in" placeholder="Code is texted to Tyler’s phone. Security question: first dog’s name."
                                    multiline min-lines="3" native:model.blur="loginNotes" />

        <native:text class="text-sm text-theme-on-surface-variant">The account number and sign-in are stored encrypted. Everyone in the house can see them.</native:text>

        @if ($error)
            <native:text ref="utility-error" class="text-sm text-theme-destructive">{{ $error }}</native:text>
        @endif

        <native:button ref="save" variant="primary" class="w-full" :loading="$saving" @tap="save">Save utility</native:button>
    </native:column>
</native:scroll-view>
