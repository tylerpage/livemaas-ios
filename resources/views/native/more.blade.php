@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:column fill class="bg-theme-background p-4 gap-6">
    <native:column class="w-full items-center rounded-lg bg-theme-secondary p-6 gap-3">
        <native:image src="images/livemaas-logo.png" alt="Livemaas" class="w-full h-32 object-contain" />
        <native:column class="items-center gap-1">
            <native:text class="text-sm text-theme-on-secondary opacity-80">Signed in as</native:text>
            <native:text ref="user-name" class="text-lg font-bold text-theme-on-secondary">{{ $userName }}</native:text>
        </native:column>
    </native:column>

    <native:row ref="cards" a11y-label="Cards and accounts"
                class="w-full items-center gap-3 rounded-lg bg-theme-surface p-4" @tap="openCards">
        <native:icon :ios="Ios::CreditcardFill" :android="Android::CreditCard" :size="20" class="text-theme-primary" />
        <native:text class="flex-1 text-theme-on-surface">Cards & accounts</native:text>
        <native:icon :ios="Ios::ChevronRight" :android="Android::ChevronRight" :size="14" class="text-theme-on-surface-variant" />
    </native:row>

    <native:row ref="bill-split" a11y-label="Bill split"
                class="w-full items-center gap-3 rounded-lg bg-theme-surface p-4" @tap="openBillSplit">
        <native:icon :ios="Ios::ChartPieFill" :android="Android::PieChart" :size="20" class="text-theme-primary" />
        <native:text class="flex-1 text-theme-on-surface">Bill split</native:text>
        <native:icon :ios="Ios::ChevronRight" :android="Android::ChevronRight" :size="14" class="text-theme-on-surface-variant" />
    </native:row>

    <native:button ref="sign-out" variant="destructive" class="w-full" @tap="signOut">
        Sign out
    </native:button>
</native:column>
