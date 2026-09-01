@php
    Theme::set('pageTitle', 'Phòng lab video');
@endphp

<h1 class="webai-title">Phòng lab video</h1>

<p class="webai-subtitle">
    Biến ý tưởng của bạn thành những thước phim chuyển động chân thực và sống động bằng công nghệ AI tiên tiến nhất.
</p>

<h2 class="webai-section-title"><span>✧</span> Video gần đây</h2>

<section class="webai-empty" aria-label="Video gần đây">
    Chưa có video nào. Hãy thử tạo video đầu tiên!
</section>

<form class="webai-composer" action="#" method="post">
    @csrf

    <textarea class="webai-prompt" name="prompt" placeholder="Mô tả chi tiết video bạn muốn tạo..."></textarea>

    <div class="webai-toolbar">
        <div class="webai-pill">VIDEO <strong>Seedance 2.0 - Mini</strong></div>

        <select class="webai-select" name="ratio" aria-label="Tỷ lệ">
            <option>16:9</option>
            <option>9:16</option>
            <option>1:1</option>
        </select>

        <select class="webai-select" name="quality" aria-label="Chất lượng">
            <option>480p</option>
            <option>720p</option>
            <option>1080p</option>
        </select>

        <select class="webai-select" name="duration" aria-label="Thời lượng">
            <option>4s</option>
            <option>8s</option>
            <option>12s</option>
        </select>

        <select class="webai-select" name="mode" aria-label="Chế độ">
            <option>Mini</option>
            <option>Pro</option>
        </select>

        <select class="webai-select" name="count" aria-label="Số kết quả">
            <option>1 kết quả</option>
            <option>2 kết quả</option>
            <option>4 kết quả</option>
        </select>

        <span class="webai-spacer"></span>
        <span class="webai-cost">23</span>
        <button class="webai-send" type="submit" aria-label="Tạo video">↑</button>
    </div>
</form>
