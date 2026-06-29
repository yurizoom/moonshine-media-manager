@props([
    'modalPrefix' => 'mm-',
    'showUrlModal' => false,
])

@php
    // closeOutside=false: prevents click-cascade where closing this modal also closes the parent offcanvas.
@endphp

<x-moonshine::modal name="{{ $modalPrefix }}upload" title="{{ __('moonshine-media-manager::media-manager.upload') }}" :closeOutside="false">
    <form @submit.prevent="submitUpload()">
        <div class="mm-modal-form">
            <input type="file" id="{{ $modalPrefix }}upload-input" name="files[]" multiple class="file-input" @change="formError = ''"/>
            <div x-show="formError" x-cloak class="mm-form-error" x-text="formError"></div>
            <x-moonshine::form.button type="submit">
                {{ __('moonshine-media-manager::media-manager.submit') }}
            </x-moonshine::form.button>
        </div>
    </form>
</x-moonshine::modal>

<x-moonshine::modal name="{{ $modalPrefix }}rename" title="{{ __('moonshine-media-manager::media-manager.rename') }}" :closeOutside="false">
    <form @submit.prevent="submitRename()">
        <div class="mm-modal-form">
            <x-moonshine::form.input x-model="renameNew" @input="formError = ''" placeholder="{{ __('moonshine-media-manager::media-manager.new_path') }}" />
            <div x-show="formError" x-cloak class="mm-form-error" x-text="formError"></div>
            <x-moonshine::form.button type="submit">
                {{ __('moonshine-media-manager::media-manager.submit') }}
            </x-moonshine::form.button>
        </div>
    </form>
</x-moonshine::modal>

<x-moonshine::modal name="{{ $modalPrefix }}new-folder" title="{{ __('moonshine-media-manager::media-manager.new_folder') }}" :closeOutside="false">
    <form @submit.prevent="submitNewFolder()">
        <div class="mm-modal-form">
            <x-moonshine::form.input x-model="newFolderName" @input="formError = ''" placeholder="{{ __('moonshine-media-manager::media-manager.name') }}" />
            <div x-show="formError" x-cloak class="mm-form-error" x-text="formError"></div>
            <x-moonshine::form.button type="submit">
                {{ __('moonshine-media-manager::media-manager.submit') }}
            </x-moonshine::form.button>
        </div>
    </form>
</x-moonshine::modal>

<x-moonshine::modal name="{{ $modalPrefix }}delete" title="{{ __('moonshine-media-manager::media-manager.delete') }}" :closeOutside="false">
    <div class="mm-modal-form">
        <p>{{ __('moonshine-media-manager::media-manager.confirm_message') }}</p>
        <div x-show="formError" x-cloak class="mm-form-error" x-text="formError"></div>
        <div class="mm-modal-actions">
            <x-moonshine::form.button @click.stop.prevent="window.MoonShine?.ui?.toggleModal(modalPrefix + 'delete')" class="btn-secondary">
                {{ __('moonshine-media-manager::media-manager.close') }}
            </x-moonshine::form.button>
            <x-moonshine::form.button @click.stop.prevent="submitDelete()" class="btn-error">
                {{ __('moonshine-media-manager::media-manager.delete') }}
            </x-moonshine::form.button>
        </div>
    </div>
</x-moonshine::modal>

@if($showUrlModal)
    <x-moonshine::modal name="{{ $modalPrefix }}url" title="{{ __('moonshine-media-manager::media-manager.url') }}" :closeOutside="false">
        <div class="mm-modal-form">
            <div class="mm-modal-url" x-text="urlToShow"></div>
            <div class="mm-modal-actions">
                <x-moonshine::form.button @click.stop.prevent="window.MoonShine?.ui?.toggleModal(modalPrefix + 'url')">
                    {{ __('moonshine-media-manager::media-manager.close') }}
                </x-moonshine::form.button>
            </div>
        </div>
    </x-moonshine::modal>
@endif

<x-moonshine::modal name="{{ $modalPrefix }}image-preview" title="{{ __('moonshine-media-manager::media-manager.view_image') }}" :closeOutside="false" :wide="true">
    <div class="mm-modal-preview">
        <img :src="imagePreviewSrc" alt=""/>
    </div>
    <div class="mm-modal-preview-actions">
        <x-moonshine::form.button @click.stop.prevent="window.MoonShine?.ui?.toggleModal(modalPrefix + 'image-preview')">
            {{ __('moonshine-media-manager::media-manager.close') }}
        </x-moonshine::form.button>
    </div>
</x-moonshine::modal>
