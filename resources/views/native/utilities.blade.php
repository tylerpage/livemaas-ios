@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:column fill class="bg-theme-background">
    @if ($loaded && $utilities === [] && ! $error)
        <native:empty-state title="No utilities yet" message="Tap + to add the electric, gas, water and internet accounts, and how to sign in to each." />
    @else
        <native:refreshable @refresh="refresh" class="w-full flex-1">
            <native:column class="w-full gap-3 p-4">
                @foreach ($utilities as $utility)
                    <native:row ref="utility-{{ $utility['id'] }}" a11y-label="{{ $utility['name'] }}"
                                class="w-full items-center gap-3 rounded-lg bg-theme-surface border border-theme-outline-variant p-4"
                                @tap="open({{ $utility['id'] }})">
                        <native:column center class="w-10 h-10 rounded-md bg-theme-secondary">
                            <native:icon :ios="Ios::BoltFill" :android="Android::ElectricBolt" :size="20" class="text-theme-on-secondary" />
                        </native:column>
                        <native:column class="flex-1 gap-1">
                            <native:text class="font-bold text-theme-on-surface">{{ $utility['name'] }}</native:text>
                            <native:text class="text-sm text-theme-on-surface-variant">
                                {{ $utility['kind_label'] }} · {{ $utility['payment_method']['display_name'] ?? 'No card set' }}
                            </native:text>
                        </native:column>
                        <native:icon :ios="Ios::ChevronRight" :android="Android::ChevronRight" :size="14" class="text-theme-on-surface-variant" />
                    </native:row>
                @endforeach
            </native:column>
        </native:refreshable>
    @endif

    @if ($error)
        <native:text ref="utilities-error" class="w-full px-4 text-sm text-theme-destructive">{{ $error }}</native:text>
    @endif

    <native:fab ref="add-utility" @tap="add" a11y-label="Add a utility" :ios="Ios::Plus" :android="Android::Add" />
</native:column>
