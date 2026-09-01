@php
    $sampleImage = Theme::asset()->url('images/event-modal-ai.png');
    $models = collect($models ?? []);
@endphp

<div class="motion-workspace">
    <section class="motion-models">
        <div class="motion-section-heading">
            <h2>1. Chọn model chuyển động</h2>
        </div>
        <div class="motion-models__carousel" data-motion-model-carousel>
            <button class="motion-models__arrow motion-models__arrow--previous" type="button" aria-label="Model trước" data-motion-carousel-previous hidden>‹</button>
            <div class="motion-models__viewport">
                <div class="motion-models__grid" data-motion-carousel-track>
                    @foreach ($models as $index => $model)
                        <button class="motion-model {{ $index === 0 ? 'is-selected' : '' }}" type="button" data-motion-model data-motion-model-name="{{ $model->name }}" data-motion-model-description="{{ $model->description }}" data-motion-model-price="{{ $model->price }}">
                            <div class="motion-model__image">
                                <img src="{{ $model->image ? \Botble\Media\Facades\RvMedia::getImageUrl($model->image) : $sampleImage }}" alt="{{ $model->name }}">
                                @if ($model->tag)
                                    <span class="motion-model__tag">{{ $model->tag->label() }}</span>
                                @endif
                            </div>
                            <strong>{{ $model->name }} @if ($index === 0)<em>★</em>@endif</strong>
                            <p>{{ $model->description }}</p>
                            <footer><b>{{ number_format((float) $model->price, 0, ',', '.') }} điểm</b></footer>
                        </button>
                    @endforeach
                </div>
            </div>
            <button class="motion-models__arrow motion-models__arrow--next" type="button" aria-label="Model tiếp theo" data-motion-carousel-next hidden>›</button>
        </div>
    </section>

    <div class="motion-workspace__columns">
        <section class="motion-card motion-inputs">
            <div class="motion-card__heading"><h2>2. Tải dữ liệu đầu vào</h2><span data-motion-pair-limit>(Tối đa 5 ảnh &amp; 5 video)</span><button type="button">Thêm cặp ảnh &amp; video</button></div>
            <p class="motion-note" data-motion-pair-count>Bạn đã thêm 5/5 cặp</p>
            <div class="motion-table">
                <div class="motion-table__header"><span>#</span><span>Ảnh tham chiếu</span><span>Video tham chiếu</span><span>Thao tác</span></div>
                @for ($i = 1; $i <= 5; $i++)
                    <div class="motion-table__row">
                        <span class="motion-row-number">⠿ {{ $i }}</span>
                        <label class="motion-upload" for="motion-image-{{ $i }}">
                            <input id="motion-image-{{ $i }}" type="file" accept="image/*" aria-label="Tải ảnh tham chiếu lên">
                            <span class="motion-upload__add" aria-hidden="true">+</span>
                        </label>
                        <label class="motion-upload motion-upload--video" for="motion-video-{{ $i }}">
                            <input id="motion-video-{{ $i }}" type="file" accept="video/*" aria-label="Tải video tham chiếu lên">
                            <span class="motion-upload__add" aria-hidden="true">+</span>
                        </label>
                        <button class="motion-delete" type="button" aria-label="Xóa">♙</button>
                    </div>
                @endfor
                <template data-motion-extra-rows>
                    @for ($i = 6; $i <= 10; $i++)
                        <div class="motion-table__row" data-motion-generated-row>
                            <span class="motion-row-number">⠿ {{ $i }}</span>
                            <label class="motion-upload" for="motion-image-{{ $i }}">
                                <input id="motion-image-{{ $i }}" type="file" accept="image/*" aria-label="Tải ảnh tham chiếu lên">
                                <span class="motion-upload__add" aria-hidden="true">+</span>
                            </label>
                            <label class="motion-upload motion-upload--video" for="motion-video-{{ $i }}">
                                <input id="motion-video-{{ $i }}" type="file" accept="video/*" aria-label="Tải video tham chiếu lên">
                                <span class="motion-upload__add" aria-hidden="true">+</span>
                            </label>
                            <button class="motion-delete" type="button" aria-label="Xóa">♙</button>
                        </div>
                    @endfor
                </template>
            </div>
            <p class="motion-tip">💡 Mỗi cặp ảnh - video sẽ tạo ra một video kết quả tương ứng</p>
        </section>

        <section class="motion-card motion-settings">
            <div class="motion-card__heading"><h2>3. Thiết lập video</h2></div>
            <div class="motion-selects">
                {{-- <label>Tỷ lệ<select><option>9:16 (TikTok)</option></select></label> --}}
                <label>Thời lượng<select><option value="5">5 giây</option><option value="10">10 giây</option><option value="15">15 giây</option></select></label>
                <label>Chất lượng<select><option value="basic">Cơ bản</option><option value="high">Chất lượng cao</option></select></label>
            </div>
            <div class="motion-schedule">
                <input id="motion-schedule-toggle" type="checkbox">
                <label for="motion-schedule-toggle" class="motion-schedule__toggle">
                    <span>Chỉ định thời gian chạy</span>
                    <i aria-hidden="true"></i>
                </label>
                <div class="motion-schedule__options">
                    <label>Ngày chạy<input type="date" value="2026-08-07"></label>
                    <label>Giờ chạy
                        <select aria-label="Giờ chạy">
                            @for ($hour = 0; $hour < 24; $hour++)
                                <option value="{{ sprintf('%02d:00', $hour) }}" @selected($hour === 9)>{{ $hour === 0 ? '12 AM' : ($hour < 12 ? $hour . ' AM' : ($hour === 12 ? '12 PM' : ($hour - 12) . ' PM')) }}</option>
                            @endfor
                        </select>
                    </label>
                </div>
            </div>
        </section>

        <section class="motion-preview">
            <div class="motion-card__heading"><h2>4. Xem trước &amp; Thông tin</h2></div>
            <dl>
                <div><dt>Model đã chọn</dt><dd data-motion-preview-model>Chưa chọn</dd></div>
                <div class="motion-preview__description"><dt>Thông tin chi tiết</dt><dd data-motion-preview-description>Chưa có thông tin chi tiết.</dd></div>
                <div><dt>Số cặp ảnh - video</dt><dd data-motion-preview-pair-count>0 cặp</dd></div>
                <div><dt>Chi phí dự kiến</dt><dd data-motion-preview-cost>0 điểm</dd></div>
                <div><dt>Thời gian xử lý ước tính</dt><dd>~ 8 phút</dd></div>
            </dl>
            <button class="motion-create" type="button">✧　Tạo video ngay</button>
        </section>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-motion-model-carousel]').forEach(function (carousel) {
            const track = carousel.querySelector('[data-motion-carousel-track]');
            const previous = carousel.querySelector('[data-motion-carousel-previous]');
            const next = carousel.querySelector('[data-motion-carousel-next]');
            const cards = Array.from(track.children);
            let position = 0;

            const visibleCards = function () {
                return window.matchMedia('(max-width: 640px)').matches ? 2 : 5;
            };

            const update = function () {
                const visible = visibleCards();
                const maxPosition = Math.max(0, cards.length - visible);
                position = Math.min(position, maxPosition);
                const needsNavigation = cards.length > visible;

                previous.hidden = !needsNavigation;
                next.hidden = !needsNavigation;
                previous.disabled = position === 0;
                next.disabled = position === maxPosition;
                const cardWidth = cards[0] ? cards[0].getBoundingClientRect().width : 0;
                track.style.transform = 'translateX(-' + (position * (cardWidth + 12)) + 'px)';
            };

            previous.addEventListener('click', function () { position--; update(); });
            next.addEventListener('click', function () { position++; update(); });
            window.addEventListener('resize', update);
            update();
        });

        const modelCards = document.querySelectorAll('[data-motion-model]');
        const previewModel = document.querySelector('[data-motion-preview-model]');
        const previewDescription = document.querySelector('[data-motion-preview-description]');
        const previewCost = document.querySelector('[data-motion-preview-cost]');
        let selectedModelPrice = 0;

        const updateSelectedModel = function (card) {
            modelCards.forEach(function (modelCard) { modelCard.classList.toggle('is-selected', modelCard === card); });
            selectedModelPrice = Number(card.dataset.motionModelPrice || 0);
            previewModel.textContent = card.dataset.motionModelName || 'Chưa chọn';
            previewDescription.textContent = card.dataset.motionModelDescription || 'Chưa có thông tin chi tiết.';
        };

        modelCards.forEach(function (card) {
            card.addEventListener('click', function () {
                updateSelectedModel(card);
                updatePairCount();
            });
        });

        const scheduleToggle = document.getElementById('motion-schedule-toggle');
        const pairLimit = document.querySelector('[data-motion-pair-limit]');
        const pairCount = document.querySelector('[data-motion-pair-count]');
        const motionTable = document.querySelector('.motion-table');
        const extraRowsTemplate = document.querySelector('[data-motion-extra-rows]');
        const previewPairCount = document.querySelector('[data-motion-preview-pair-count]');

        const updatePairCount = function () {
            if (!motionTable || !pairCount || !previewPairCount || !previewCost) {
                return;
            }

            const completedPairs = Array.from(motionTable.querySelectorAll('.motion-table__row')).filter(function (row) {
                const imageInput = row.querySelector('input[type="file"][accept="image/*"]');
                const videoInput = row.querySelector('input[type="file"][accept="video/*"]');

                return imageInput?.files.length && videoInput?.files.length;
            }).length;
            const maximumPairs = scheduleToggle?.checked ? 10 : 5;

            pairCount.textContent = 'Bạn đã thêm ' + completedPairs + '/' + maximumPairs + ' cặp';
            previewPairCount.textContent = completedPairs + ' cặp';
            previewCost.textContent = new Intl.NumberFormat('vi-VN').format(completedPairs * selectedModelPrice) + ' điểm';
        };

        const updateUploadPreview = function (input) {
            const upload = input.closest('.motion-upload');
            const addButton = upload?.querySelector('.motion-upload__add');
            const existingPreview = upload?.querySelector('.motion-upload__preview');

            if (!upload || !addButton) {
                return;
            }

            if (upload.dataset.motionPreviewUrl) {
                URL.revokeObjectURL(upload.dataset.motionPreviewUrl);
                delete upload.dataset.motionPreviewUrl;
            }

            existingPreview?.remove();

            const file = input.files[0];
            upload.classList.toggle('has-file', Boolean(file));
            addButton.hidden = Boolean(file);
            upload.title = file?.name || '';

            if (!file) {
                return;
            }

            const previewUrl = URL.createObjectURL(file);
            const preview = document.createElement(file.type.startsWith('image/') ? 'img' : 'video');

            preview.className = 'motion-upload__preview';
            preview.src = previewUrl;
            preview.alt = '';

            if (preview instanceof HTMLVideoElement) {
                preview.muted = true;
                preview.playsInline = true;
                preview.preload = 'metadata';
            }

            upload.dataset.motionPreviewUrl = previewUrl;
            upload.appendChild(preview);
        };

        if (scheduleToggle && pairLimit && pairCount && motionTable && extraRowsTemplate) {
            const updatePairLimit = function () {
                const maximumPairs = scheduleToggle.checked ? 10 : 5;

                pairLimit.textContent = '(Tối đa ' + maximumPairs + ' ảnh & ' + maximumPairs + ' video)';

                if (scheduleToggle.checked && !motionTable.querySelector('[data-motion-generated-row]')) {
                    motionTable.appendChild(extraRowsTemplate.content.cloneNode(true));
                }

                if (!scheduleToggle.checked) {
                    motionTable.querySelectorAll('[data-motion-generated-row]').forEach(function (row) { row.remove(); });
                }

                motionTable.classList.toggle('has-extended-rows', scheduleToggle.checked);
                motionTable.scrollTop = 0;
                updatePairCount();
            };

            scheduleToggle.addEventListener('change', updatePairLimit);
            motionTable.addEventListener('change', function (event) {
                if (event.target.matches('input[type="file"]')) {
                    updateUploadPreview(event.target);
                    updatePairCount();
                }
            });
            updatePairLimit();
        }

        const initiallySelectedModel = document.querySelector('[data-motion-model].is-selected');
        if (initiallySelectedModel) {
            updateSelectedModel(initiallySelectedModel);
            updatePairCount();
        }
    });
</script>
