@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:column fill class="bg-theme-background">
    <native:refreshable @refresh="refresh" class="w-full flex-1">
        <native:column class="w-full gap-4 p-4">
            {{-- Month switcher --}}
            <native:row class="w-full items-center">
                <native:button ref="previous-month" variant="ghost" :ios-icon="Ios::ChevronLeft" :android-icon="Android::ChevronLeft"
                               a11y-label="Previous month" @tap="previousMonth" />
                <native:text ref="month-label" class="flex-1 text-center text-lg font-bold text-theme-primary">{{ $this->monthLabel() }}</native:text>
                <native:button ref="next-month" variant="ghost" :ios-icon="Ios::ChevronRight" :android-icon="Android::ChevronRight"
                               a11y-label="Next month" :disabled="$this->isCurrentMonth()" @tap="nextMonth" />
            </native:row>

            {{-- Month summary, on the house blue --}}
            @if ($summary)
                <native:column ref="summary" class="w-full rounded-lg bg-theme-secondary p-5 gap-4">
                    <native:column class="gap-1">
                        <native:text class="text-sm text-theme-on-secondary opacity-80">Paid this month</native:text>
                        <native:text ref="month-total" class="text-3xl font-bold text-theme-on-secondary">{{ $this->money($summary['total_cents']) }}</native:text>
                        <native:text class="text-sm text-theme-on-secondary opacity-80">{{ $summary['count'] }} {{ Str::plural('bill', $summary['count']) }}</native:text>
                    </native:column>

                    @if ($summary['by_member'] !== [])
                        <native:column class="w-full gap-2">
                            <native:text class="text-xs font-bold text-theme-on-secondary opacity-80">WHO PAID</native:text>
                            @foreach ($summary['by_member'] as $row)
                                <native:row class="w-full">
                                    <native:text class="flex-1 text-theme-on-secondary">{{ $row['name'] }}</native:text>
                                    <native:text class="font-bold text-theme-on-secondary">{{ $this->money($row['total_cents']) }}</native:text>
                                </native:row>
                            @endforeach
                        </native:column>

                        <native:column class="w-full gap-2">
                            <native:text class="text-xs font-bold text-theme-on-secondary opacity-80">PAID WITH</native:text>
                            @foreach ($summary['by_payment_method'] as $row)
                                <native:row class="w-full">
                                    <native:text class="flex-1 text-theme-on-secondary">{{ $row['name'] }}</native:text>
                                    <native:text class="font-bold text-theme-on-secondary">{{ $this->money($row['total_cents']) }}</native:text>
                                </native:row>
                            @endforeach
                        </native:column>
                    @endif
                </native:column>
            @endif

            {{-- Who owes whom under the bill split. Private bills are left out, so "paid" can differ from WHO PAID above. --}}
            @if (($summary['settle_up'] ?? null) && $summary['count'] > 0)
                <native:column ref="settle-up" class="w-full rounded-lg bg-theme-surface border border-theme-outline-variant p-4 gap-3">
                    <native:row class="w-full items-center">
                        <native:text class="flex-1 font-bold text-theme-primary">Settle up</native:text>
                        <native:button ref="change-split" variant="ghost" size="sm" @tap="openBillSplit">Change split</native:button>
                    </native:row>

                    @forelse ($summary['settle_up']['transfers'] as $transfer)
                        <native:row ref="owes-{{ $transfer['from']['id'] }}-{{ $transfer['to']['id'] }}"
                                    a11y-label="{{ $transfer['from']['name'] }} owes {{ $transfer['to']['name'] }} {{ $this->money($transfer['amount_cents']) }}"
                                    class="w-full items-center gap-2 rounded-md bg-theme-surface-variant p-3">
                            <native:text class="font-bold text-theme-on-surface">{{ $transfer['from']['name'] }}</native:text>
                            <native:icon :ios="Ios::ArrowRight" :android="Android::ArrowForward" :size="14" class="text-theme-on-surface-variant" />
                            <native:text class="flex-1 font-bold text-theme-on-surface">{{ $transfer['to']['name'] }}</native:text>
                            <native:text class="text-lg font-bold text-theme-primary">{{ $this->money($transfer['amount_cents']) }}</native:text>
                        </native:row>
                    @empty
                        <native:text ref="all-square" class="text-theme-on-surface">You're all square this month.</native:text>
                    @endforelse

                    <native:button ref="split-details" variant="ghost" size="sm" class="self-start"
                                   :ios-icon="$showSplitDetails ? Ios::ChevronUp : Ios::ChevronDown"
                                   :android-icon="$showSplitDetails ? Android::ExpandLess : Android::ExpandMore"
                                   @tap="toggleSplitDetails">Details</native:button>

                    @if ($showSplitDetails)
                        <native:divider class="w-full border-theme-outline-variant" />
                        @foreach ($summary['settle_up']['members'] as $member)
                            <native:row class="w-full">
                                <native:text class="flex-1 text-sm text-theme-on-surface-variant">{{ $member['name'] }} · {{ $this->percent($member['share_percent']) }}%</native:text>
                                <native:text class="text-sm text-theme-on-surface">paid {{ $this->money($member['paid_cents']) }} of {{ $this->money($member['share_cents']) }}</native:text>
                            </native:row>
                        @endforeach
                    @endif
                </native:column>
            @endif

            @if ($error)
                <native:text ref="bills-error" class="text-sm text-theme-destructive">{{ $error }}</native:text>
            @endif

            {{-- The running list --}}
            @if ($loaded && $bills === [] && ! $error)
                <native:text class="w-full text-center py-8 text-theme-on-surface-variant">No bills recorded for {{ $this->monthLabel() }}.</native:text>
            @endif

            @foreach ($bills as $bill)
                <native:row ref="bill-{{ $bill['id'] }}" a11y-label="{{ $bill['payee'] }}, {{ $this->money($bill['amount_cents']) }}"
                            class="w-full items-center gap-3 rounded-lg bg-theme-surface border border-theme-outline-variant p-4"
                            @tap="openBill({{ $bill['id'] }})">
                    <native:column class="flex-1 gap-1">
                        <native:text class="font-bold text-theme-on-surface">{{ $bill['payee'] }}</native:text>
                        <native:text class="text-sm text-theme-on-surface-variant">
                            {{ $this->paidOnLabel($bill['paid_on']) }} · {{ $bill['paid_by']['name'] ?? '' }}@if ($bill['payment_method']) · {{ $bill['payment_method']['display_name'] }}@endif
                        </native:text>
                    </native:column>
                    @if ($bill['is_private'] ?? false)
                        <native:icon :ios="Ios::LockFill" :android="Android::Lock" :size="16" a11y-label="Private" class="text-theme-on-surface-variant" />
                    @endif
                    @if (($bill['attachment_count'] ?? 0) > 0)
                        <native:icon :ios="Ios::Paperclip" :android="Android::AttachFile" :size="16" a11y-label="Has photos" class="text-theme-on-surface-variant" />
                    @endif
                    <native:text class="font-bold text-theme-primary">{{ $this->money($bill['amount_cents']) }}</native:text>
                </native:row>
            @endforeach
        </native:column>
    </native:refreshable>

    <native:fab ref="add-bill" @tap="addBill" a11y-label="Add a bill" :ios="Ios::Plus" :android="Android::Add" />
</native:column>
