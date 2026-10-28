{{-- Expects $media from a component using UploadsMedia. Optional $allowVideo (default true), $allowFiles (default false). --}}
@use('App\Icons\Ios')
@use('App\Icons\Android')

@unless ($hideButtons ?? false)
<native:row class="w-full gap-2">
    <native:button ref="library" variant="secondary" class="flex-1" size="sm"
                   :ios-icon="Ios::PhotoOnRectangle" :android-icon="Android::PhotoLibrary" @tap="pickFromLibrary">
        Library
    </native:button>
    <native:button ref="camera" variant="secondary" class="flex-1" size="sm"
                   :ios-icon="Ios::CameraFill" :android-icon="Android::PhotoCamera" @tap="takePhoto">
        Photo
    </native:button>
    @if ($allowFiles ?? false)
        <native:button ref="files" variant="secondary" class="flex-1" size="sm"
                       :ios-icon="Ios::FolderFill" :android-icon="Android::Folder" @tap="pickFiles">
            Files
        </native:button>
    @endif
    @if ($allowVideo ?? true)
        <native:button ref="video" variant="secondary" class="flex-1" size="sm"
                       :ios-icon="Ios::VideoFill" :android-icon="Android::Videocam" @tap="recordVideo">
            Video
        </native:button>
    @endif
</native:row>
@endunless

@foreach ($media as $item)
    <native:row class="w-full items-center gap-3 rounded-md bg-theme-surface p-2">
        @if (str_starts_with($item['mime_type'], 'image/'))
            <native:image src="{{ $item['path'] }}" alt="" class="w-16 h-16 rounded-sm object-cover" />
        @else
            <native:column center class="w-16 h-16 rounded-sm bg-theme-secondary">
                @if (str_starts_with($item['mime_type'], 'video/'))
                    <native:icon :ios="Ios::VideoFill" :android="Android::Videocam" :size="24" class="text-theme-on-secondary" />
                @else
                    <native:icon :ios="Ios::DocFill" :android="Android::Description" :size="24" class="text-theme-on-secondary" />
                @endif
            </native:column>
        @endif

        <native:column class="flex-1 gap-1">
            @unless (str_starts_with($item['mime_type'], 'image/'))
                <native:text class="text-sm text-theme-on-surface" max-lines="1">{{ $item['name'] ?? 'File' }}</native:text>
            @endunless
            @if ($item['status'] === 'uploading')
                <native:row class="items-center gap-2">
                    <native:activity-indicator size="small" />
                    <native:text class="text-sm text-theme-on-surface-variant">Uploading…</native:text>
                </native:row>
            @elseif ($item['status'] === 'ready')
                <native:text class="text-sm text-theme-success">Ready</native:text>
            @else
                <native:text class="text-sm text-theme-destructive">Upload failed</native:text>
                <native:button ref="retry-{{ $item['key'] }}" variant="ghost" size="sm" @tap="retryUpload('{{ $item['key'] }}')">Try again</native:button>
            @endif
        </native:column>

        <native:button ref="remove-{{ $item['key'] }}" variant="ghost" :ios-icon="Ios::XmarkCircleFill" :android-icon="Android::Close"
                       a11y-label="Remove" @tap="removeMedia('{{ $item['key'] }}')" />
    </native:row>
@endforeach
