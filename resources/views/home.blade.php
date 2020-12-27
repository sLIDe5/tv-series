<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Welcome to EXG TV Series') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h2 class="font-semibold text-2xl leading-tight mb-4">{{ __('Latest updates') }}</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($shows as $show)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-2 bg-white border-b border-gray-200">
                            <a href="{{ route('shows.view', $show) }}">
                                <img src="{{ $show->bannerImage() }}" alt="{{ $show->title }}" data-title="{{ $show->last_episode->diffForHumans() }}" />
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h2 class="font-semibold text-2xl leading-tight mb-4">{{ __('Latest episodes') }}</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                @foreach($episodes as $episode)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-2 bg-white border-b border-gray-200 relative">
                            <a href="{{ route('shows.view', $episode->show) }}">
                                <img src="{{ $episode->show->bannerImage() }}" alt="{{ $episode->show->title }}" />
                            </a>
                            <div class="relative">
                                @if(!$episode->watched)
                                    <div class="absolute right-2 top-1">
                                        <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full">NEW</span>
                                    </div>
                                @elseif(!$episode->watched->finished)
                                    <div class="absolute right-2 top-1">
                                        <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-yellow-100 bg-yellow-600 rounded-full">IN PROGRESS</span>
                                    </div>
                                @endif
                                <a href="{{ route('episodes.watch', $episode) }}">
                                    <img src="{{ $episode->thumbnailImage() }}" alt="{{ $episode->title }}" data-title="{{ $episode->created_at->diffForHumans() }}" />
                                </a>
                            </div>
                            <h4 class="font-semibold text-s leading-tight pt-2">{{ $episode->show->title }} - {{ $episode->episodeNumber }}</h4>
                            <h5 class="font-semibold text-xs leading-tight">{{ $episode->title }}</h5>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="pt-8">
                {{ $episodes->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
