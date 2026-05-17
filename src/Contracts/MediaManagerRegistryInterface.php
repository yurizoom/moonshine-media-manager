<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Contracts;

interface MediaManagerRegistryInterface
{
    /**
     * Register a per-file action button.
     *
     * Appears in table view (inline) and grid view (dropdown) for each file.
     *
     * @param  string  $name  Unique identifier
     * @param  array{icon?: string, class?: string, label?: string, x-show?: string, click: string}  $definition
     */
    public function addFileAction(string $name, array $definition): self;

    /**
     * Register a toolbar action button.
     *
     * Appears alongside Refresh, Upload, New Folder, etc.
     *
     * @param  string  $name  Unique identifier
     * @param  array{icon?: string, class?: string, label?: string, showLabel?: bool, click: string}  $definition
     */
    public function addToolbarAction(string $name, array $definition): self;

    public function removeFileAction(string $name): self;

    public function removeToolbarAction(string $name): self;

    public function getFileActions(): array;

    public function getToolbarActions(): array;

    public function hasFileActions(): bool;

    public function hasToolbarActions(): bool;
}
