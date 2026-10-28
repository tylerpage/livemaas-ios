<native:column ref="login-screen" fill class="safe-area bg-theme-background">
    {{-- Blue band behind the house, like sky behind the roofline. --}}
    <native:column class="w-full items-center bg-theme-secondary pt-12 pb-8 px-6 gap-4">
        <native:image ref="login-logo" src="images/livemaas-logo.png" alt="Livemaas" class="w-full h-48 object-contain" />
        <native:text class="text-3xl font-bold text-theme-on-secondary">Livemaas</native:text>
        <native:text class="text-center text-theme-on-secondary opacity-80">Everything about the house, in one place.</native:text>
    </native:column>

    <native:column class="w-full gap-4 px-6 pt-8">
        <native:outlined-text-input
            label="Email"
            keyboard="email"
            autocapitalize="none"
            native:model.blur="email"
        />
        <native:outlined-text-input
            label="Password"
            secure
            revealable
            native:model.blur="password"
            @submit="signIn"
        />

        @if ($error)
            <native:text ref="login-error" class="text-sm text-theme-destructive">{{ $error }}</native:text>
        @endif

        <native:button ref="sign-in" variant="primary" :loading="$signingIn" class="w-full mt-2" @tap="signIn">
            Sign in
        </native:button>
    </native:column>
</native:column>
