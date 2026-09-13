@php
    $caption = (string) ($data['caption'] ?? '');
    $images = [];

    if (isset($data['files']) && is_array($data['files'])) {
        foreach ($data['files'] as $file) {
            if (is_array($file) && isset($file['url'])) {
                $images[] = ['url' => $file['url'], 'path' => $file['path'] ?? null];
            }
        }
    }

    if ($images === [] && isset($data['file']['url'])) {
        $images[] = ['url' => $data['file']['url'], 'path' => $data['file']['path'] ?? null];
    }
@endphp
<figure class="image @if (count($images) > 1) mm-editorjs-gallery @endif">
    @foreach ($images as $image)
        <img src="{{ $image['url'] }}" alt="{{ $caption }}">
    @endforeach
    @if ($caption !== '')
        <figcaption class="image-caption">{{ $caption }}</figcaption>
    @endif
</figure>
