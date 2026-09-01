<div class="col">
    <div class="bg-white border rounded h-100 p-3 p-xxl-4" style="box-shadow: 0 0.5rem 1.25rem rgba(15, 23, 42, .14); min-height: 164px;">
        <div class="d-flex align-items-center gap-3">
            <span
                class="d-inline-flex align-items-center justify-content-center flex-shrink-0"
                style="width: 2.8rem; height: 2.8rem; border-radius: .7rem; color: {{ $color }}; background: color-mix(in srgb, {{ $color }} 14%, white);"
            >
                <x-core::icon :name="$icon" style="--bb-icon-size: 1.4rem;" />
            </span>
            <div class="fw-semibold text-body-secondary lh-sm" style="font-size: .95rem;">{{ $label }}</div>
        </div>
        <div class="fw-bolder text-dark mt-4 text-nowrap" style="font-size: {{ $isMoney ?? false ? 'clamp(1.3rem, 1.45vw, 1.55rem)' : 'clamp(2.3rem, 3vw, 3.25rem)' }}; line-height: 1; letter-spacing: -.04em; overflow: hidden; text-overflow: ellipsis;">
            {{ $value }}@if (! empty($suffix))<small class="ms-1" style="font-size: .48em; letter-spacing: 0;">{{ $suffix }}</small>@endif
        </div>
    </div>
</div>

