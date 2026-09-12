{{-- Elevates the enclosing core modal-template to the dedicated mm dialog layer
     (--mm-z-dialog) so media-manager dialogs always stack above foreign overlays
     on the core modal layer. Renders nothing. --}}
<span hidden aria-hidden="true"
      x-init="$el.closest('.modal-template')?.style.setProperty('--z-modal', 'var(--mm-z-dialog)'); console.debug('[media-manager] modal elevated to var(--mm-z-dialog)')"></span>
