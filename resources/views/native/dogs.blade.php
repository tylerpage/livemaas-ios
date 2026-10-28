@use('App\Icons\Ios')
@use('App\Icons\Android')

<native:column fill class="bg-theme-background">
    @if ($loaded && $dogs === [] && ! $error)
        <native:column fill center class="gap-4 px-8">
            <native:empty-state title="No dogs yet" message="Add your dogs to start logging how much they eat." />
            <native:button ref="add-first-dog" variant="primary" @tap="addDog">Add a dog</native:button>
        </native:column>
    @else
        <native:refreshable @refresh="refresh" class="w-full flex-1">
            <native:column class="w-full gap-4 p-4">
                @if (count($dogs) > 1)
                    <native:button ref="feed-all" variant="primary" size="lg" class="w-full"
                                   :ios-icon="Ios::PawprintFill" :android-icon="Android::Pets" @tap="feedAll">
                        {{ count($dogs) === 2 ? 'Feed both' : 'Feed all '.count($dogs) }}
                    </native:button>
                @endif

                @foreach ($dogs as $dog)
                    <native:column class="w-full rounded-lg bg-theme-surface border border-theme-outline-variant p-4 gap-3">
                        <native:row ref="dog-{{ $dog['id'] }}" a11y-label="{{ $dog['name'] }}" class="w-full items-center gap-3" @tap="openDog({{ $dog['id'] }})">
                            @if ($dog['photo_url'])
                                <native:image src="{{ $dog['photo_url'] }}" alt="{{ $dog['name'] }}" class="w-14 h-14 rounded-full object-cover" />
                            @else
                                <native:column center class="w-14 h-14 rounded-full bg-theme-secondary">
                                    <native:icon :ios="Ios::PawprintFill" :android="Android::Pets" :size="24" class="text-theme-on-secondary" />
                                </native:column>
                            @endif
                            <native:column class="flex-1 gap-1">
                                <native:text class="text-lg font-bold text-theme-on-surface">{{ $dog['name'] }}</native:text>
                                <native:text class="text-sm text-theme-on-surface-variant">{{ $this->lastFedLabel($dog['today']['last_feeding']) }}</native:text>
                            </native:column>
                            <native:icon :ios="Ios::ChevronRight" :android="Android::ChevronRight" :size="14" class="text-theme-on-surface-variant" />
                        </native:row>

                        <native:column class="w-full gap-1">
                            <native:row class="w-full">
                                <native:text class="flex-1 text-sm text-theme-on-surface-variant">Eaten today</native:text>
                                <native:text ref="today-{{ $dog['id'] }}" class="text-sm font-bold text-theme-primary">
                                    @if ($dog['daily_target_cups'])
                                        {{ $this->cups($dog['today']['eaten_cups'], withUnit: false) }} of {{ $this->cups($dog['daily_target_cups']) }}
                                    @else
                                        {{ $this->cups($dog['today']['eaten_cups']) }}
                                    @endif
                                </native:text>
                            </native:row>
                            @if ($dog['daily_target_cups'])
                                <native:progress-bar :value="$this->progress($dog)" class="w-full" />
                            @endif
                        </native:column>

                        <native:button ref="feed-{{ $dog['id'] }}" variant="primary" class="w-full"
                                       :ios-icon="Ios::PawprintFill" :android-icon="Android::Pets" @tap="feed({{ $dog['id'] }})">
                            Feed {{ $dog['name'] }}
                        </native:button>
                    </native:column>
                @endforeach

                {{-- Food bags --}}
                <native:row class="w-full items-center pt-2">
                    <native:text class="flex-1 text-lg font-bold text-theme-primary">Food</native:text>
                    <native:button ref="add-food" variant="ghost" size="sm" @tap="addFood">Open a new bag</native:button>
                </native:row>

                @forelse ($foods as $food)
                    <native:column class="w-full rounded-lg bg-theme-surface p-4 gap-2">
                        <native:row class="w-full items-center gap-3">
                            @if ($food['photo_url'] ?? null)
                                <native:image ref="bag-photo-{{ $food['id'] }}" src="{{ $food['photo_url'] }}" alt="Bag of {{ $food['display_name'] }}"
                                              class="w-12 h-12 rounded-md object-cover" />
                            @endif
                            <native:text class="flex-1 font-bold text-theme-on-surface">{{ $food['display_name'] }}</native:text>
                            <native:button ref="finish-{{ $food['id'] }}" variant="ghost" size="sm" @tap="askToFinish({{ $food['id'] }})">Finished</native:button>
                        </native:row>
                        <native:text ref="bag-{{ $food['id'] }}" class="text-sm text-theme-on-surface-variant">{{ $this->bagLabel($food) }}</native:text>
                        @if ($food['cups_per_bag'])
                            <native:progress-bar :value="$this->bagProgress($food)" class="w-full" />
                        @endif

                        @if ($confirmingFinish === $food['id'])
                            <native:row class="w-full gap-2">
                                <native:button ref="cancel-finish" variant="secondary" class="flex-1" @tap="cancelFinish">Not yet</native:button>
                                <native:button ref="confirm-finish" variant="primary" class="flex-1" @tap="finishFood">Bag is empty</native:button>
                            </native:row>
                        @endif
                    </native:column>
                @empty
                    <native:text class="text-sm text-theme-on-surface-variant">Add the bag you are feeding from to see when it will run out.</native:text>
                @endforelse

                @if ($error)
                    <native:text ref="dogs-error" class="text-sm text-theme-destructive">{{ $error }}</native:text>
                @endif
            </native:column>
        </native:refreshable>

        <native:fab ref="add-dog" @tap="addDog" a11y-label="Add a dog" :ios="Ios::Plus" :android="Android::Add" />
    @endif
</native:column>
