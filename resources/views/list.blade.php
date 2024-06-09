<style>
    .files {
        display: flex;
        gap: 10px;
        margin-top: 1rem;
    }

    .files > .file {
        width: 150px;
        border-radius: 1rem;
        border-width: 1px;
        --tw-border-opacity: 1;
        border-color: rgb(229 231 235 / var(--tw-border-opacity));
        margin-bottom: 10px;
        padding: .5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: space-between;
    }

    .files > .file > .file-select {
        #position: absolute;
        #top: -4px;
        #left: -1px;
    }

    .file-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        #padding: 10px;
        width: 100%;
        #background: #f4f4f4;
    }

    .file-name {
        font-weight: bold;
        color: #666;
        display: block;
        width: 100%;
        text-align: center;
        overflow: hidden !important;
        white-space: nowrap !important;
        text-overflow: ellipsis !important;
    }

    .file-size {
        color: #999;
        font-size: 12px;
        display: block;
    }

    :is(.dark) {
        .files > .file {
            border-color: rgba(var(--dark-100), var(--tw-bg-opacity));
        }
    }

</style>

<div class="files">
    @if (empty($items))
        <div style="height: 200px;border: none;"></div>
    @else
        @foreach($items as $item)
            {{ $item->render() }}
        @endforeach
    @endif
</div>
