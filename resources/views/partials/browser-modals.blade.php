@props([
    'modalPrefix' => 'mm-',
    'showUrlModal' => false,
])

<x-moonshine::modal name="{{ $modalPrefix }}upload" title="{{ __('moonshine-media-manager::media-manager.upload') }}" :closeOutside="true">
    <form @submit.prevent="submitUpload()">
        <div class="flex flex-col gap-4">
            <input type="file" id="{{ $modalPrefix }}upload-input" name="files[]" multiple required class="file-input" />
            <x-moonshine::form.button type="submit">
                {{ __('moonshine-media-manager::media-manager.submit') }}
            </x-moonshine::form.button>
        </div>
    </form>
</x-moonshine::modal>

<x-moonshine::modal name="{{ $modalPrefix }}rename" title="{{ __('moonshine-media-manager::media-manager.rename') }}" :closeOutside="true">
    <form @submit.prevent="submitRename()">
        <div class="flex flex-col gap-4">
            <x-moonshine::form.input x-model="renameNew" placeholder="{{ __('moonshine-media-manager::media-manager.new_path') }}" />
            <x-moonshine::form.button type="submit">
                {{ __('moonshine-media-manager::media-manager.submit') }}
            </x-moonshine::form.button>
        </div>
    </form>
</x-moonshine::modal>

<x-moonshine::modal name="{{ $modalPrefix }}new-folder" title="{{ __('moonshine-media-manager::media-manager.new_folder') }}" :closeOutside="true">
    <form @submit.prevent="submitNewFolder()">
        <div class="flex flex-col gap-4">
            <x-moonshine::form.input x-model="newFolderName" placeholder="{{ __('moonshine-media-manager::media-manager.name') }}" />
            <x-moonshine::form.button type="submit">
                {{ __('moonshine-media-manager::media-manager.submit') }}
            </x-moonshine::form.button>
        </div>
    </form>
</x-moonshine::modal>

<x-moonshine::modal name="{{ $modalPrefix }}delete" title="{{ __('moonshine-media-manager::media-manager.delete') }}" :closeOutside="true">
    <div class="flex flex-col gap-4">
        <p>{{ __('moonshine-media-manager::media-manager.confirm_message') }}</p>
        <div class="flex gap-2 justify-end">
            <x-moonshine::form.button @click.prevent="window.MoonShine?.ui?.toggleModal(modalPrefix + 'delete')" class="btn-secondary">
                {{ __('moonshine-media-manager::media-manager.close') }}
            </x-moonshine::form.button>
            <x-moonshine::form.button @click.prevent="submitDelete()" class="btn-error">
                {{ __('moonshine-media-manager::media-manager.delete') }}
            </x-moonshine::form.button>
        </div>
    </div>
</x-moonshine::modal>

@if($showUrlModal)
    <x-moonshine::modal name="{{ $modalPrefix }}url" title="{{ __('moonshine-media-manager::media-manager.url') }}" :closeOutside="true">
        <div class="flex flex-col gap-4">
            <div class="break-all select-all text-sm" x-text="urlToShow"></div>
            <div class="flex justify-end">
                <x-moonshine::form.button @click.prevent="window.MoonShine?.ui?.toggleModal(modalPrefix + 'url')">
                    {{ __('moonshine-media-manager::media-manager.close') }}
                </x-moonshine::form.button>
            </div>
        </div>
    </x-moonshine::modal>
@endif
