<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-3xl text-gray-800 leading-tight mb-2">
            {{ __('Settings') }}
        </h2>
        <p class="font-semibold text-l text-gray-800 leading-tight">
            {{ __('Select shows you want to see on the front page') }}
        </p>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="text-white px-6 py-4 border-0 rounded relative mb-4 bg-green-500">
                    <span class="inline-block align-middle mr-8">{{ session('success') }}</span>
                </div>
            @endif
            <form action="{{ route('settings.save') }}" method="post">
                {{ csrf_field() }}
                @foreach($shows as $show)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                        <div class="p-2 bg-white border-b border-gray-200">
                            <label class="inline-flex items-center w-full md:pl-5">
                                <input type="checkbox" class="form-checkbox h-5 w-5 text-gray-600 mr-5" {{ in_array($show->id, $userShows) ? 'checked' : '' }} name="shows[]" value="{{ $show->id }}">
                                <div class="grid grid-cols-1 md:gird-cols-3 gap-4 py-2">
                                    <div class="flex">
                                        <h3 class="font-bold text-2xl leading-tight self-center">{{ $show->title }}</h3>
                                    </div>
                                    <div class="col-span-2">
                                        <img src="{{ $show->bannerImage() }}" alt="{{ $show->title }}" />
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                @endforeach
                <div>
                    <x-button>{{ __('Save') }}</x-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
