<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div>You're logged in!</div>
                    <x-nav-link :href="route('oauth2.auth','google')">
                        {{ __('Add Google account') }}
                    </x-nav-link>
                    <x-nav-link :href="route('user.syncGoogleTrello')">
                        {{ __('Sync google and trello') }}
                    </x-nav-link>
                    <form action="{{route('user.addTrelloUsername')}}" method="POST" class="mt-8">
                        @csrf
                        <x-input type="text" name="trelloUsername" placeholder="Username Trello" value="{{auth()->user()->trello_username}}"></x-input>
                        <x-button>
                            Update
                        </x-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
