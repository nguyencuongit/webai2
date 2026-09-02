@php
    Theme::set('pageTitle', 'Video của tôi');
@endphp

<section class="my-videos-dashboard" data-my-videos data-tasks-url="{{ route('public.my-videos.tasks') }}">
    <div class="my-videos-dashboard__top">
        <div>
            <h1><span aria-hidden="true">▪</span> Bảng điều khiển</h1>
            <p>Theo dõi tiến độ tạo video, xem trước và tải xuống khi video hoàn tất.</p>
            <div class="my-videos-dashboard__note"><strong>Lưu ý:</strong> Bạn có thể thoát trình duyệt trong lúc AI tạo video.<br>Dữ liệu video sẽ tự động xóa sau 3 ngày để bảo mật.</div>
        </div>
        <div class="my-videos-dashboard__controls">
            <label class="my-videos-search"><input type="search" placeholder="Tìm theo mã task..." data-my-videos-search></label>
            <div class="my-videos-filters">
                <button class="is-active" type="button" data-filter="all">✦ Tất cả</button><button type="button" data-filter="queue">⌛ Hàng đợi</button><button type="button" data-filter="processing">◌ Đang xử lý</button><button type="button" data-filter="completed">◉ Đã hoàn thành</button><button type="button" data-filter="failed">⊗ Chưa thành công</button>
            </div>
        </div>
    </div>
    <div class="my-videos-dashboard__meta"><span data-my-videos-count>Đang tải video...</span><span data-my-videos-page></span></div>
    <div class="my-videos-grid" data-my-videos-list></div>
    <div class="my-videos-empty" data-my-videos-empty hidden><strong>☻ Bạn chưa có video nào.</strong><span>Hãy tạo video đầu tiên ngay!</span><a href="{{ route('public.video-lab') }}">Tạo video</a></div>
    <div class="my-videos-pagination" data-my-videos-pagination hidden><button type="button" data-previous>← Trước</button><button type="button" data-next>Sau →</button></div>
</section>

<style>
    .my-videos-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:16px; margin-top:28px; } .my-videos-card { overflow:hidden; border:1px solid rgba(198,198,210,.65); border-radius:16px; background:#fff; } .my-videos-card__media { display:grid; min-height:150px; place-items:center; background:#f5f6fa; color:#717180; } .my-videos-card__media video { width:100%; aspect-ratio:16/9; background:#111; object-fit:cover; } .my-videos-card__body { padding:14px; } .my-videos-card__title { overflow:hidden; margin:0 0 8px; font-weight:700; text-overflow:ellipsis; white-space:nowrap; } .my-videos-card__status { display:inline-block; padding:4px 8px; border-radius:12px; background:#fff0e8; color:var(--webai-accent); font-size:12px; font-weight:700; } .my-videos-card__status.completed { background:#ecf9f0; color:#208a4a; } .my-videos-card__status.failed { background:#fff0f0; color:#d63939; } .my-videos-card__error,.my-videos-card__date { margin:10px 0 0; color:var(--webai-muted); font-size:12px; } .my-videos-card__error { color:#d63939; } .my-videos-card__download { display:inline-block; margin-top:12px; color:var(--webai-accent); font-size:13px; font-weight:700; text-decoration:none; } .my-videos-pagination { display:flex; justify-content:center; gap:10px; margin-top:24px; } .my-videos-pagination button { padding:8px 14px; border:1px solid rgba(198,198,210,.7); border-radius:9px; background:#fff; cursor:pointer; } .my-videos-pagination button:disabled { opacity:.45; }
</style>

<script>
(() => {
    const root = document.querySelector('[data-my-videos]'); if (!root) return;
    const list = root.querySelector('[data-my-videos-list]'), empty = root.querySelector('[data-my-videos-empty]'), count = root.querySelector('[data-my-videos-count]'), pageText = root.querySelector('[data-my-videos-page]'), pager = root.querySelector('[data-my-videos-pagination]'), previous = root.querySelector('[data-previous]'), next = root.querySelector('[data-next]'), search = root.querySelector('[data-my-videos-search]');
    let filter = 'all', page = 1, searchTimer;
    const labels = {PENDING:'Hàng đợi',QUEUED:'Hàng đợi',PROCESSING:'Đang xử lý',RUNNING:'Đang xử lý',COMPLETED:'Đã hoàn thành',FAILED:'Chưa thành công',CANCELLED:'Đã hủy',ERROR:'Chưa thành công'};
    const escapeHtml = value => String(value || '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    const formatDate = value => value ? new Intl.DateTimeFormat('vi-VN',{dateStyle:'short',timeStyle:'short'}).format(new Date(value)) : '';
    function render(tasks, meta) {
        list.innerHTML = tasks.map(task => { const done = task.status === 'COMPLETED', failed = ['FAILED','CANCELLED','ERROR'].includes(task.status), media = done && task.media_url ? `<video controls playsinline preload="metadata" ${task.thumbnail_url ? `poster="${escapeHtml(task.thumbnail_url)}"` : ''}><source src="${escapeHtml(task.media_url)}"></video>` : `<span>${failed ? 'Video tạo thất bại' : 'Video đang được tạo...'}</span>`, download = done && task.media_url ? `<a class="my-videos-card__download" href="${escapeHtml(task.media_url)}" target="_blank" rel="noopener">Tải video</a>` : ''; return `<article class="my-videos-card"><div class="my-videos-card__media">${media}</div><div class="my-videos-card__body"><p class="my-videos-card__title" title="${escapeHtml(task.task_id)}">${escapeHtml(task.task_id)}</p><span class="my-videos-card__status ${done ? 'completed' : ''} ${failed ? 'failed' : ''}">${labels[task.status] || task.status}</span><p class="my-videos-card__date">${formatDate(task.created_at)}</p>${download}</div></article>`; }).join('');
        empty.hidden = tasks.length > 0; count.textContent = `Hiển thị ${tasks.length}/${meta.total} video`; pageText.textContent = `Trang: ${meta.current_page}/${meta.last_page}`; pager.hidden = meta.last_page <= 1; previous.disabled = meta.current_page <= 1; next.disabled = meta.current_page >= meta.last_page;
    }
    function load() { const url = new URL(root.dataset.tasksUrl, window.location.origin); url.searchParams.set('filter',filter); url.searchParams.set('page',page); if (search.value.trim()) url.searchParams.set('search',search.value.trim()); count.textContent = 'Đang tải video...'; fetch(url,{headers:{Accept:'application/json'}}).then(r=>r.json()).then(response=>{if(!response.success) throw Error(); render(response.data.tasks||[],response.data.meta);}).catch(()=>{list.innerHTML='';empty.hidden=false;count.textContent='Không thể tải danh sách video.';pageText.textContent='';pager.hidden=true;}); }
    root.querySelectorAll('[data-filter]').forEach(button => button.addEventListener('click',()=>{filter=button.dataset.filter;page=1;root.querySelectorAll('[data-filter]').forEach(item=>item.classList.toggle('is-active',item===button));load();})); previous.addEventListener('click',()=>{if(page>1){page--;load();}}); next.addEventListener('click',()=>{page++;load();}); search.addEventListener('input',()=>{clearTimeout(searchTimer);searchTimer=setTimeout(()=>{page=1;load();},300);}); load();
})();
</script>
