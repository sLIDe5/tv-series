<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('shows.view', $episode->show) }}">
            <img src="{{ $episode->show->bannerImage() }}" alt="{{ $episode->show->title }}" />
        </a>
        <h2 class="font-bold text-3xl text-gray-800 leading-tight my-2">
            {{ $episode->show->title }} - {{ $episode->episodeNumber }} ({{ $episode->title }})
        </h2>
        <p class="font-semibold text-l text-gray-800 leading-tight">
            {{ $episode->description }}
        </p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <video class="w-full" id="video" src="{{ $episode->url() }}" controls ontimeupdate="updateEpisodeTime('{{ route('watch.time', $episode->id) }}', this);" onloadedmetadata="updatePlayerTime(this);" onended="updateEpisodeEnded('{{ route('watch.ended', $episode->id) }}', this);" poster="{{ $episode->thumbnailImage() }}"></video>
            <div class="md:flex py-4">
                <form action="{{ route('episodes.watched', $episode) }}" method="post" onsubmit="return confirm('{{ __('Do you really want to mark this episode as watched?') }}');">
                    {{ csrf_field() }}
                    <x-button>{{ __('Mark as watched') }}</x-button>
                </form>
                <form class="mt-1 md:mt-0 md:ml-2" action="{{ route('seasons.watched', [$episode->show, $episode->season]) }}" method="post" onsubmit="return confirm('{{ __('Do you really want to mark season ' . $episode->season . ' as watched?') }}');">
                    {{ csrf_field() }}
                    <x-button>Mark season {{ $episode->season }} as watched</x-button>
                </form>
                <form class="mt-1 md:mt-0 md:ml-2" action="{{ route('shows.watched', $episode->show) }}" method="post" onsubmit="return confirm('{{ __('Do you really want to mark ' . $episode->show->title . ' as watched?') }}');">
                    {{ csrf_field() }}
                    <x-button>Mark show as watched</x-button>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="scripts">
        <script>
            function updatePlayerTime(video){
                video.currentTime = Number({{ $episode->watched ? $episode->watched->time : 0}});
            }

            function updateEpisodeTime(episodeUrl, video){
                if( video._sendingTime || (video._lastSent && new Date().getTime() - video._lastSent < 10000) || video.currentTime === 0 || video.paused ){
                    return;
                }
                video._sendingTime = true;
                video._lastSent = new Date().getTime();
                let data = new FormData();
                data.append('_method', 'POST');
                data.append('_token', '{{ csrf_token() }}');
                data.append('timeWatched', video.currentTime);
                fetch(episodeUrl, {
                    method: 'POST',
                    body: data
                }).finally(() => {
                    video._sendingTime = false;
                });
                if( video.duration && video.duration - 120 < video.currentTime ){
                    updateEpisodeEnded(episodeUrl, video);
                }
            }

            function updateEpisodeEnded(episodeUrl, video){
                if( video._endedCalled ){
                    return;
                }
                video._endedCalled = true;
                let data = new FormData();
                data.append('_method', 'PUT');
                data.append('_token', '{{ csrf_token() }}');
                fetch(episodeUrl, {
                    method: 'POST',
                    body: data
                });
            }
        </script>
    </x-slot>
</x-app-layout>
