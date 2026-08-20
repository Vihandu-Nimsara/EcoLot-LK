<?php
declare(strict_types=1);

if (!defined('ECOLOT_COLLECTOR_REQUEST_MODAL_RENDERED')):
    define('ECOLOT_COLLECTOR_REQUEST_MODAL_RENDERED', true);
?>
<div id="collector-record-modal" class="collector-modal" hidden>
    <div class="collector-modal__backdrop" data-collector-modal-close></div>

    <section
        class="collector-modal__dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="collector-record-modal-title"
        aria-describedby="collector-record-modal-description"
    >
        <header class="collector-modal__header">
            <div>
                <span class="collector-eyebrow">Collection record</span>
                <h2 id="collector-record-modal-title">Request Details</h2>
                <p id="collector-record-modal-description">Review the original request and record the actual collection outcome.</p>
            </div>

            <button
                type="button"
                class="collector-modal__close"
                aria-label="Close collection record"
                data-collector-modal-close
            >×</button>
        </header>

        <div id="collector-record-modal-body" class="collector-modal__body"></div>
    </section>
</div>
<?php endif; ?>