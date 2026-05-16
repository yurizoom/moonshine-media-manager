<nav class="flex items-center gap-2 flex-wrap mb-4 text-sm">
    <template x-for="([url, label], index) in Object.entries(navigation)" :key="url">
        <div class="flex items-center gap-1">
            <a href="#" @click.prevent="loadFiles(extractPathFromUrl(url))" x-text="label"
               class="hover:text-primary transition-colors"></a>
            <span x-show="index < Object.entries(navigation).length - 1" class="text-gray-400">/</span>
        </div>
    </template>
</nav>
