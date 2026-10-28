@use('App\Icons\Ios')
@use('App\Icons\Android')
@use('Native\Mobile\Edge\SharedValue')

<native:column class="w-full rounded-lg bg-theme-surface border border-theme-outline-variant p-4 gap-3">
    <native:row class="w-full items-center gap-3">
        <native:column center class="w-10 h-10 rounded-full bg-theme-secondary">
            <native:text class="font-bold text-theme-on-secondary">{{ mb_substr($post['author']['name'] ?? '?', 0, 1) }}</native:text>
        </native:column>

        <native:column class="flex-1">
            <native:text class="font-bold text-theme-on-surface">{{ $post['author']['name'] ?? 'Someone' }}</native:text>
            <native:text class="text-sm text-theme-on-surface-variant">{{ $this->postedAgo() }}</native:text>
        </native:column>

        @if ($post['can_edit'])
            <native:button ref="delete-{{ $post['id'] }}" variant="ghost" :ios-icon="Ios::Trash" :android-icon="Android::Delete"
                           a11y-label="Delete this log" @tap="askToDelete" />
        @endif
    </native:row>

    @if ($post['body'])
        <native:text class="text-theme-on-surface leading-relaxed">{{ $post['body'] }}</native:text>
    @endif

    @foreach ($post['attachments'] as $attachment)
        @if (str_starts_with($attachment['mime_type'], 'image/') && $attachment['url'])
            <native:pressable ref="photo-{{ $attachment['id'] }}" a11y-label="View photo full screen" press-opacity="0.8"
                              class="w-full" @tap="viewPhoto({{ $loop->index }})">
                <native:image src="{{ $attachment['url'] }}" alt="Photo from {{ $post['author']['name'] ?? 'the house' }}"
                              class="w-full h-64 rounded-md object-cover" />
            </native:pressable>
        @elseif (str_starts_with($attachment['mime_type'], 'video/'))
            <native:row ref="video-{{ $attachment['id'] }}" a11y-label="Play video"
                        class="w-full h-48 rounded-md bg-theme-secondary items-center justify-center gap-2"
                        @tap="openVideo({{ $loop->index }})">
                <native:icon :ios="Ios::PlayCircleFill" :android="Android::PlayCircle" :size="40" class="text-theme-on-secondary" />
                <native:text class="text-theme-on-secondary">Play video</native:text>
            </native:row>
        @endif
    @endforeach

    @if ($viewingPhoto !== null && isset($post['attachments'][$viewingPhoto]['url']))
        {{-- A fresh SharedValue each render, so reopening a photo starts unzoomed. --}}
        @php $zoom = SharedValue::make(1.0); @endphp

        <native:modal ref="photo-viewer" :visible="true" a11y-label="Photo" @dismiss="closePhoto">
            <native:gesture-area :pinch="$zoom" pinch-min="1" pinch-max="4" class="w-full h-full">
                <native:column fill center class="bg-black">
                    <native:image src="{{ $post['attachments'][$viewingPhoto]['url'] }}"
                                  alt="Photo from {{ $post['author']['name'] ?? 'the house' }}"
                                  class="w-full h-full" :fit="1" :scale="$zoom" />
                </native:column>
            </native:gesture-area>
        </native:modal>
    @endif

    @if ($confirmingDelete)
        <native:row class="w-full gap-2">
            <native:button ref="cancel-delete" variant="secondary" class="flex-1" @tap="cancelDelete">Keep</native:button>
            <native:button ref="confirm-delete" variant="destructive" class="flex-1" @tap="delete">Delete</native:button>
        </native:row>
    @endif

    @if ($error)
        <native:text class="text-sm text-theme-destructive">{{ $error }}</native:text>
    @endif
</native:column>
