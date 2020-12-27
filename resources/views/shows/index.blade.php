<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('TV Shows') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @foreach($shows as $show)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                    <div class="p-2 bg-white border-b border-gray-200">
                        <div class="grid grid-cols-1 gap-4 py-2 md:grid-cols-3">
                            <div class="flex md:pl-5">
                                <h3 class="font-bold text-2xl leading-tight self-center">{{ $show->title }}</h3>
                            </div>
                            <div class="col-span-2">
                                <a href="{{ route('shows.view', $show) }}">
                                    <img src="{{ $show->bannerImage() }}" alt="{{ $show->title }}" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
