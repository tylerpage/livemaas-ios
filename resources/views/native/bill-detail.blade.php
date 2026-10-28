@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:scroll-view fill class="bg-theme-background">
    <native:column class="w-full gap-4 p-4">
        @if ($bill)
            <native:column class="w-full items-center rounded-lg bg-theme-secondary p-6 gap-1">
                <native:text ref="bill-amount" class="text-4xl font-bold text-theme-on-secondary">{{ $this->money($bill['amount_cents']) }}</native:text>
                <native:text class="text-theme-on-secondary opacity-80">{{ $bill['payee'] }}</native:text>
            </native:column>

            <native:column class="w-full rounded-lg bg-theme-surface p-4 gap-3">
                @foreach ([
                    'Paid on' => $this->paidOnLabel(),
                    'Paid by' => $bill['paid_by']['name'] ?? '—',
                    'Paid with' => $bill['payment_method']['display_name'] ?? 'Not recorded',
                    'Category' => $bill['category_label'],
                    'Entered by' => $bill['created_by']['name'] ?? '—',
                    ...(($bill['is_private'] ?? false) ? ['Visibility' => 'Private — only you, not split'] : []),
                ] as $label => $value)
                    <native:row class="w-full">
                        <native:text class="flex-1 text-theme-on-surface-variant">{{ $label }}</native:text>
                        <native:text class="font-bold text-theme-on-surface">{{ $value }}</native:text>
                    </native:row>
                @endforeach

                @if ($bill['notes'])
                    <native:divider class="w-full border-theme-outline-variant" />
                    <native:text class="text-theme-on-surface">{{ $bill['notes'] }}</native:text>
                @endif
            </native:column>

            @foreach ($bill['attachments'] as $attachment)
                @if (str_starts_with($attachment['mime_type'], 'image/') && $attachment['url'])
                    <native:pressable ref="attachment-{{ $loop->index }}" a11y-label="Open photo" class="w-full" @tap="openAttachment({{ $loop->index }})">
                        <native:image src="{{ $attachment['url'] }}" alt="Photo of the {{ $bill['payee'] }} bill"
                                      class="w-full h-80 rounded-md object-contain bg-theme-surface" />
                    </native:pressable>
                @else
                    <native:row ref="attachment-{{ $loop->index }}" class="w-full items-center gap-3 rounded-md bg-theme-surface p-4"
                                @tap="openAttachment({{ $loop->index }})">
                        <native:icon :ios="Ios::DocFill" :android="Android::Description" :size="24" class="text-theme-primary" />
                        <native:text class="flex-1 text-theme-on-surface">{{ $attachment['original_name'] }}</native:text>
                    </native:row>
                @endif
            @endforeach

            <native:button ref="edit" variant="secondary" class="w-full" :ios-icon="Ios::Pencil" :android-icon="Android::Edit" @tap="edit">Edit bill</native:button>

            @if ($confirmingDelete)
                <native:row class="w-full gap-2">
                    <native:button ref="cancel-delete" variant="secondary" class="flex-1" @tap="cancelDelete">Keep</native:button>
                    <native:button ref="confirm-delete" variant="destructive" class="flex-1" @tap="delete">Delete bill</native:button>
                </native:row>
            @else
                <native:button ref="delete" variant="ghost" class="w-full" @tap="askToDelete">Delete this bill</native:button>
            @endif
        @endif

        @if ($error)
            <native:text ref="bill-error" class="text-sm text-theme-destructive">{{ $error }}</native:text>
        @endif
    </native:column>
</native:scroll-view>
