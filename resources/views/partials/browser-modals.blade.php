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
            <label class="mm-upload-dropzone" for="{{ $modalPrefix }}upload-input">
                <x-moonshine::icon icon="cloud-arrow-up" class="mm-upload-dropzone-icon"/>
                <span class="mm-upload-dropzone-text">{{ __('moonshine-media-manager::media-manager.upload_choose') }}</span>
                <span class="mm-upload-dropzone-hint">{{ __('moonshine-media-manager::media-manager.upload_hint') }}</span>
                <input type="file"
                       id="{{ $modalPrefix }}upload-input"
                       class="mm-upload-input"
                       multiple
                       @change="addPendingFiles($event.target.files); $event.target.value = ''"
                />
            </label>

            <div x-show="pendingUploads.length" x-cloak class="mm-upload-list">
                <template x-for="item in pendingUploads" :key="item.id">
                    <div class="mm-upload-item">
                        <div class="mm-upload-thumb">
                            <template x-if="item.isImage && item.preview">
                                <img :src="item.preview" alt="" loading="lazy"/>
                            </template>
                            <template x-if="!item.isImage">
                                <x-moonshine::icon icon="document" class="mm-upload-thumb-icon"/>
                            </template>
                        </div>
                        <div class="mm-upload-info">
                            <span class="mm-upload-name" x-text="item.name"></span>
                            <span class="mm-upload-size" x-text="formatBytes(item.size)"></span>
                        </div>
                        <button type="button"
                                @click.prevent="removePendingUpload(item.id)"
                                class="mm-upload-remove"
                                title="{{ __('moonshine-media-manager::media-manager.remove') }}"
                        >×</button>
                    </div>
                </template>
            </div>

            <div x-show="formError" x-cloak class="mm-form-error" x-text="formError"></div>

            <x-moonshine::form.button type="submit">
                {{ __('moonshine-media-manager::media-manager.upload') }}
                (<span x-text="pendingUploads.length"></span>)
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

<x-moonshine::modal name="{{ $modalPrefix }}move" title="{{ __('moonshine-media-manager::media-manager.move_action') }}" :closeOutside="false">
    <div class="mm-modal-form">
        <div class="mm-replace-current">
            <span class="mm-replace-label">{{ __('moonshine-media-manager::media-manager.move_file_label') }}:</span>
            <span class="mm-replace-filename" x-text="basename(movePath)"></span>
        </div>

        <div class="mm-move-browser">
            <div class="mm-move-path">
                <button type="button"
                        x-show="moveBrowserPath !== '/'"
                        @click.prevent="moveBrowserUp()"
                        class="mm-move-up"
                        title="{{ __('moonshine-media-manager::media-manager.move_up') }}"
                >
                    <x-moonshine::icon icon="arrow-small-left"/>
                </button>
                <span class="mm-move-path-text" x-text="moveBrowserPath"></span>
            </div>

            <div class="mm-move-list">
                <template x-for="folder in moveBrowserFolders" :key="folder.path">
                    <button type="button"
                            class="mm-move-folder"
                            @click.prevent="loadMoveFolders(folder.path)"
                            :class="{ 'mm-move-folder--active': folder.path === moveBrowserPath }"
                    >
                        <x-moonshine::icon icon="folder"/>
                        <span x-text="basename(folder.path)"></span>
                    </button>
                </template>
                <div x-show="! moveBrowserFolders.length && ! moveBrowserLoading" class="mm-move-empty">
                    {{ __('moonshine-media-manager::media-manager.move_no_subfolders') }}
                </div>
                <div x-show="moveBrowserLoading" class="mm-move-empty">
                    <x-moonshine::loader/>
                </div>
            </div>
        </div>

        <div class="mm-replace-current">
            <span class="mm-replace-label">{{ __('moonshine-media-manager::media-manager.move_destination') }}:</span>
            <span class="mm-replace-filename" x-text="moveDestinationPath"></span>
        </div>

        <div x-show="formError" x-cloak class="mm-form-error" x-text="formError"></div>

        <div class="mm-modal-actions">
            <x-moonshine::form.button @click.stop.prevent="window.MoonShine?.ui?.toggleModal(modalPrefix + 'move')" class="btn-secondary">
                {{ __('moonshine-media-manager::media-manager.close') }}
            </x-moonshine::form.button>
            <x-moonshine::form.button @click.stop.prevent="submitMove()" class="btn-primary">
                {{ __('moonshine-media-manager::media-manager.move_here') }}
            </x-moonshine::form.button>
        </div>
    </div>
</x-moonshine::modal>

<x-moonshine::modal name="{{ $modalPrefix }}replace" title="{{ __('moonshine-media-manager::media-manager.replace_action') }}" :closeOutside="false">    <div class="mm-modal-form">
        <div class="mm-replace-current">
            <span class="mm-replace-label">{{ __('moonshine-media-manager::media-manager.replace_current') }}:</span>
            <span class="mm-replace-filename" x-text="replaceFileName"></span>
        </div>

        <template x-if="! pendingReplace">
            <label class="mm-upload-dropzone" for="{{ $modalPrefix }}replace-input">
                <x-moonshine::icon icon="cloud-arrow-up" class="mm-upload-dropzone-icon"/>
                <span class="mm-upload-dropzone-text">{{ __('moonshine-media-manager::media-manager.replace_choose') }}</span>
                <input type="file"
                       id="{{ $modalPrefix }}replace-input"
                       class="mm-upload-input"
                       @change="addReplaceFile($event.target.files); $event.target.value = ''"
                />
            </label>
        </template>

        <template x-if="pendingReplace">
            <div class="mm-upload-item">
                <div class="mm-upload-thumb">
                    <template x-if="pendingReplace.isImage && pendingReplace.preview">
                        <img :src="pendingReplace.preview" alt=""/>
                    </template>
                    <template x-if="! pendingReplace.isImage">
                        <x-moonshine::icon icon="document" class="mm-upload-thumb-icon"/>
                    </template>
                </div>
                <div class="mm-upload-info">
                    <span class="mm-upload-name" x-text="pendingReplace.name"></span>
                    <span class="mm-upload-size" x-text="formatBytes(pendingReplace.size)"></span>
                </div>
                <button type="button"
                        @click.prevent="clearReplaceFile()"
                        class="mm-upload-remove"
                        title="{{ __('moonshine-media-manager::media-manager.remove') }}"
                >×</button>
            </div>
        </template>

        <div x-show="formError" x-cloak class="mm-form-error" x-text="formError"></div>

        <div class="mm-modal-actions">
            <x-moonshine::form.button @click.stop.prevent="window.MoonShine?.ui?.toggleModal(modalPrefix + 'replace')" class="btn-secondary">
                {{ __('moonshine-media-manager::media-manager.close') }}
            </x-moonshine::form.button>
            <x-moonshine::form.button @click.stop.prevent="submitReplace()" class="btn-warning">
                {{ __('moonshine-media-manager::media-manager.replace_action') }}
            </x-moonshine::form.button>
        </div>
    </div>
</x-moonshine::modal>
