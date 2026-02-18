<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Settings
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="mb-4 p-3 rounded bg-green-100 text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('settings.update') }}" class="space-y-6">
                    @csrf

                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="AutoLogin" id="AutoLogin" value="1"
                            @checked((int)($data['AutoLogin'] ?? 0) === 1)>
                        <label for="AutoLogin" class="text-sm">Auto login</label>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="ReceiveNotifications" id="ReceiveNotifications" value="1"
                            @checked((int)($data['ReceiveNotifications'] ?? 0) === 1)>
                        <label for="ReceiveNotifications" class="text-sm">Receive notifications</label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Preferred chart theme</label>
                        @php $theme = $data['PreferredChartTheme'] ?? 'dark'; @endphp
                        <select name="PreferredChartTheme" class="border rounded p-2 w-full">
                            <option value="dark" @selected($theme === 'dark')>dark</option>
                            <option value="light" @selected($theme === 'light')>light</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Preferred chart interval</label>
                        <input type="text" name="PreferredChartInterval"
                               class="border rounded p-2 w-full"
                               value="{{ old('PreferredChartInterval', $data['PreferredChartInterval'] ?? '') }}"
                               placeholder="e.g. 15m">
                        @error('PreferredChartInterval')
                            <div class="text-red-600 text-sm mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">News limit</label>
                            <input type="number" name="NewsLimit" class="border rounded p-2 w-full"
                                   value="{{ old('NewsLimit', $data['NewsLimit'] ?? 8) }}">
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">News per symbol limit</label>
                            <input type="number" name="NewsPerSymbolLimit" class="border rounded p-2 w-full"
                                   value="{{ old('NewsPerSymbolLimit', $data['NewsPerSymbolLimit'] ?? 3) }}">
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">News portfolio total limit</label>
                            <input type="number" name="NewsPortfolioTotalLimit" class="border rounded p-2 w-full"
                                   value="{{ old('NewsPortfolioTotalLimit', $data['NewsPortfolioTotalLimit'] ?? 20) }}">
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Calendar limit</label>
                            <input type="number" name="CalendarLimit" class="border rounded p-2 w-full"
                                   value="{{ old('CalendarLimit', $data['CalendarLimit'] ?? 8) }}">
                        </div>
                    </div>

                    <div class="pt-2">
                        <button class="px-4 py-2 bg-black text-white rounded">
                            Save
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</x-app-layout>
