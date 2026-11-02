@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:scroll-view fill class="bg-theme-background">
    <native:column class="w-full gap-4 p-4">
        @if ($utility)
            <native:column class="w-full rounded-lg bg-theme-secondary p-5 gap-1">
                <native:text ref="utility-name" class="text-2xl font-bold text-theme-on-secondary">{{ $utility['name'] }}</native:text>
                <native:text class="text-theme-on-secondary opacity-80">{{ $utility['kind_label'] }}</native:text>
            </native:column>

            <native:column class="w-full rounded-lg bg-theme-surface p-4 gap-3 select-text">
                <native:column class="w-full gap-0.5">
                    <native:text class="text-sm text-theme-on-surface-variant">Paid with</native:text>
                    <native:text ref="paid-with" class="text-theme-on-surface">{{ $utility['payment_method']['display_name'] ?? 'Not set' }}</native:text>
                </native:column>
                <native:column class="w-full gap-0.5">
                    <native:text class="text-sm text-theme-on-surface-variant">Account number</native:text>
                    <native:text ref="account-number" class="text-theme-on-surface">{{ filled($utility['account_number']) ? $utility['account_number'] : 'Not set' }}</native:text>
                </native:column>
            </native:column>

            @if ($this->hasSignIn())
                <native:column class="w-full rounded-lg bg-theme-surface p-4 gap-3 select-text">
                    <native:text class="font-bold text-theme-on-surface">Sign-in</native:text>

                    @if (filled($utility['website']))
                        <native:row ref="website" a11y-label="Open {{ $this->websiteHost() }}" class="w-full items-center gap-2" @tap="openWebsite">
                            <native:column class="flex-1 gap-0.5">
                                <native:text class="text-sm text-theme-on-surface-variant">Website</native:text>
                                <native:text class="text-theme-primary">{{ $this->websiteHost() }}</native:text>
                            </native:column>
                            <native:icon :ios="Ios::ArrowUpRight" :android="Android::OpenInNew" :size="14" class="text-theme-primary" />
                        </native:row>
                    @endif

                    @if (filled($utility['username']))
                        <native:column class="w-full gap-0.5">
                            <native:text class="text-sm text-theme-on-surface-variant">Username or email</native:text>
                            <native:text ref="username" class="text-theme-on-surface">{{ $utility['username'] }}</native:text>
                        </native:column>
                    @endif

                    @if (filled($utility['password']))
                        <native:row class="w-full items-center gap-2">
                            <native:column class="flex-1 gap-0.5">
                                <native:text class="text-sm text-theme-on-surface-variant">Password</native:text>
                                <native:text ref="password" class="font-mono text-theme-on-surface">{{ $showingPassword ? $utility['password'] : '••••••••' }}</native:text>
                            </native:column>
                            <native:button ref="toggle-password" variant="ghost" size="sm"
                                           :ios-icon="$showingPassword ? Ios::EyeSlash : Ios::Eye" :android-icon="$showingPassword ? Android::VisibilityOff : Android::Visibility"
                                           @tap="togglePassword">{{ $showingPassword ? 'Hide' : 'Show' }}</native:button>
                        </native:row>
                    @endif

                    @if (filled($utility['login_notes']))
                        <native:column class="w-full gap-0.5">
                            <native:text class="text-sm text-theme-on-surface-variant">How to log in</native:text>
                            <native:text ref="login-notes" class="text-theme-on-surface leading-relaxed">{{ $utility['login_notes'] }}</native:text>
                        </native:column>
                    @endif
                </native:column>
            @endif

            <native:button ref="edit" variant="secondary" class="w-full" :ios-icon="Ios::Pencil" :android-icon="Android::Edit" @tap="edit">Edit utility</native:button>

            @if ($confirmingDelete)
                <native:row class="w-full gap-2">
                    <native:button ref="cancel-delete" variant="secondary" class="flex-1" @tap="cancelDelete">Keep</native:button>
                    <native:button ref="confirm-delete" variant="destructive" class="flex-1" @tap="delete">Delete utility</native:button>
                </native:row>
            @else
                <native:button ref="delete" variant="ghost" class="w-full" @tap="askToDelete">Delete this utility</native:button>
            @endif
        @endif

        @if ($error)
            <native:text ref="utility-error" class="text-sm text-theme-destructive">{{ $error }}</native:text>
        @endif
    </native:column>
</native:scroll-view>
