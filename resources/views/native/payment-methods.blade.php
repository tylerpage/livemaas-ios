@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:column fill class="bg-theme-background">
    <native:scroll-view class="w-full flex-1">
        <native:column class="w-full gap-3 p-4">
            <native:text class="text-sm text-theme-on-surface-variant">
                Only a nickname and the last four digits are stored — never a full card number.
            </native:text>

            @if ($loaded && $paymentMethods === [])
                <native:text class="w-full text-center py-8 text-theme-on-surface-variant">No cards yet. Add the ones you pay bills with.</native:text>
            @endif

            @foreach ($paymentMethods as $method)
                <native:column class="w-full rounded-lg bg-theme-surface border border-theme-outline-variant p-4 gap-2">
                    <native:row class="w-full items-center gap-3">
                        <native:column center class="w-10 h-10 rounded-md bg-theme-secondary">
                            <native:icon :ios="Ios::CreditcardFill" :android="Android::CreditCard" :size="20" class="text-theme-on-secondary" />
                        </native:column>
                        <native:column class="flex-1">
                            <native:text class="font-bold text-theme-on-surface">{{ $method['display_name'] }}</native:text>
                            <native:text class="text-sm text-theme-on-surface-variant">
                                {{ $method['type_label'] }} · {{ $method['owner']['name'] ?? 'Shared' }}
                            </native:text>
                        </native:column>
                        <native:button ref="remove-{{ $method['id'] }}" variant="ghost" :ios-icon="Ios::Trash" :android-icon="Android::Delete"
                                       a11y-label="Remove {{ $method['nickname'] }}" @tap="askToRemove({{ $method['id'] }})" />
                    </native:row>

                    @if ($confirmingRemoval === $method['id'])
                        <native:row class="w-full gap-2">
                            <native:button ref="cancel-remove" variant="secondary" class="flex-1" @tap="cancelRemoval">Keep</native:button>
                            <native:button ref="confirm-remove" variant="destructive" class="flex-1" @tap="remove">Remove</native:button>
                        </native:row>
                    @endif
                </native:column>
            @endforeach

            @if ($error)
                <native:text class="text-sm text-theme-destructive">{{ $error }}</native:text>
            @endif
        </native:column>
    </native:scroll-view>

    <native:fab ref="add-card" @tap="add" a11y-label="Add a card" :ios="Ios::Plus" :android="Android::Add" />
</native:column>
