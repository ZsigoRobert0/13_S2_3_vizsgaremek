<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Fiók törlése') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('A fiók törlése végleges. Add meg a jelenlegi jelszavadat a megerősítéshez.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.destroy') }}" class="space-y-4">
        @csrf
        @method('delete')

        <div>
            <x-input-label for="delete_password" :value="__('Jelenlegi jelszó')" />
            <x-text-input
                id="delete_password"
                name="password"
                type="password"
                class="mt-1 block w-full"
                placeholder="Jelszó"
                required
            />
            <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
        </div>

        <div>
            <x-danger-button>
                {{ __('Fiók törlése') }}
            </x-danger-button>
        </div>
    </form>
</section>