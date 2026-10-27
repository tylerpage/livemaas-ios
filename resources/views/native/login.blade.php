@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:column ref="login-screen" fill center class="safe-area bg-theme-background px-6">
    <native:column class="w-full items-center gap-2 mb-10">
        <native:icon :ios="Ios::HouseFill" :android="Android::Home" :size="48" class="text-theme-primary" />
        <native:text class="text-3xl font-bold text-theme-on-background">Livemaas</native:text>
        <native:text class="text-theme-on-surface-variant">Everything about the house, in one place.</native:text>
    </native:column>

    <native:column class="w-full gap-4">
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
