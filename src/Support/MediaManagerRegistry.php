<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Support;

use YuriZoom\MoonShineMediaManager\Contracts\MediaManagerRegistryInterface;

final class MediaManagerRegistry implements MediaManagerRegistryInterface
{
    /**
     * Registered per-file action buttons.
     *
     * @var array<string, array{icon?: string, class?: string, label?: string, x-show?: string, click: string}>
     */
    protected array $fileActions = [];

    /**
     * Registered toolbar action buttons.
     *
     * @var array<string, array{icon?: string, class?: string, label?: string, showLabel?: bool, click: string}>
     */
    protected array $toolbarActions = [];

    /**
     * Register a per-file action button.
     *
     * Appears in:
     * - Table view: inline action buttons per row
     * - Grid view: dropdown menu per file card
     *
     * @param  string  $name  Unique identifier for the action
     * @param  array{icon?: string, class?: string, label?: string, x-show?: string, click: string}  $definition
     *                                                                                                            - icon:  Heroicons icon name (e.g. 'sparkles', 'pencil-square')
     *                                                                                                            - class: CSS classes for the button (e.g. 'btn-sm btn-accent')
     *                                                                                                            - label: Button text shown in dropdown
     *                                                                                                            - x-show: Alpine.js condition to control visibility (has access to `file` object)
     *                                                                                                            - click: Alpine.js @click handler (has access to `file` object)
     */
    public function addFileAction(string $name, array $definition): self
    {
        $this->fileActions[$name] = $this->validateAction($definition, ['click']);

        return $this;
    }

    /**
     * Register a toolbar action button.
     *
     * Appears in the toolbar alongside Refresh, Upload, New Folder, etc.
     *
     * @param  string  $name  Unique identifier for the action
     * @param  array{icon?: string, class?: string, label?: string, showLabel?: bool, click: string}  $definition
     *                                                                                                             - icon:      Heroicons icon name
     *                                                                                                             - class:     CSS classes for the button
     *                                                                                                             - label:     Button text
     *                                                                                                             - showLabel: Whether to show the label text (default: false)
     *                                                                                                             - click:     Alpine.js @click handler
     */
    public function addToolbarAction(string $name, array $definition): self
    {
        $this->toolbarActions[$name] = $this->validateAction($definition, ['click']);

        return $this;
    }

    /**
     * Remove a previously registered file action.
     */
    public function removeFileAction(string $name): self
    {
        unset($this->fileActions[$name]);

        return $this;
    }

    /**
     * Remove a previously registered toolbar action.
     */
    public function removeToolbarAction(string $name): self
    {
        unset($this->toolbarActions[$name]);

        return $this;
    }

    /**
     * Get all registered per-file action buttons.
     *
     * @return array<string, array{icon?: string, class?: string, label?: string, x-show?: string, click: string}>
     */
    public function getFileActions(): array
    {
        return $this->fileActions;
    }

    /**
     * Get all registered toolbar action buttons.
     *
     * @return array<string, array{icon?: string, class?: string, label?: string, showLabel?: bool, click: string}>
     */
    public function getToolbarActions(): array
    {
        return $this->toolbarActions;
    }

    /**
     * Check if any file actions are registered.
     */
    public function hasFileActions(): bool
    {
        return $this->fileActions !== [];
    }

    /**
     * Check if any toolbar actions are registered.
     */
    public function hasToolbarActions(): bool
    {
        return $this->toolbarActions !== [];
    }

    private function validateAction(array $definition, array $requiredKeys): array
    {
        foreach ($requiredKeys as $key) {
            if (! isset($definition[$key]) || ! is_string($definition[$key])) {
                throw new \InvalidArgumentException(
                    "MediaManager action must have a string \"{$key}\" key."
                );
            }
        }

        if (isset($definition['click']) && preg_match('/<\s*script|javascript:|on\w+\s*=/i', $definition['click'])) {
            throw new \InvalidArgumentException('MediaManager action "click" contains disallowed content.');
        }

        if (isset($definition['x-show']) && preg_match('/<\s*script|javascript:|on\w+\s*=/i', $definition['x-show'])) {
            throw new \InvalidArgumentException('MediaManager action "x-show" contains disallowed content.');
        }

        return $definition;
    }
}
