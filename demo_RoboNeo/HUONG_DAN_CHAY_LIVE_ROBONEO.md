# Hướng dẫn chạy đầy đủ và bật RoboNeo Live API

Tài liệu này áp dụng cho project:

```text
/Users/apple/Desktop/src_duong/demo_RoboNeo
```

Ứng dụng gọi trực tiếp backend RoboNeo, **không dùng RoboNeo CLI**. Live mode được triển khai theo giao thức quan sát từ VibeForge 26.7.3, không phải public API có cam kết ổn định của RoboNeo.

## 1. Điều kiện bắt buộc

Cần có:

- PHP 8.3+, Composer và Node.js 20+.
- Tài khoản RoboNeo/Meitu của chính bạn, có quyền dùng Kling Motion Control và đủ credit.
- Personal Access Token của từng tài khoản RoboNeo, nhập tại giao diện quản trị.
- `ROBONEO_APP_TOKEN`: application token được RoboNeo hoặc bên tích hợp cấp phép cho client.

| Biến | Mục đích |
| --- | --- |
| Personal Access Token | Xác định và xác thực tài khoản; được mã hóa trong database |
| `ROBONEO_APP_TOKEN` | Xác thực ứng dụng với AI Engine Gateway |

> Không lấy hoặc tái sử dụng app token nhúng trong VibeForge. Project này không chứa token của VibeForge. Nếu chưa có `ROBONEO_APP_TOKEN` hợp lệ thì chưa thể chạy live, dù access token tài khoản đúng.

## 2. Lấy credential hợp lệ

### Access token

Trong RoboNeo CLI, mở **Settings → Personal Access Token**, tạo token cho tài khoản của bạn rồi nhập tại `http://127.0.0.1:8000/roboneo-accounts`. App chỉ nhận token, không nhận email hoặc mật khẩu.

Nếu tài khoản chưa có Personal Access Token, có thể dùng token từ phiên web của chính bạn để thử nghiệm:

1. Mở RoboNeo và đăng nhập.
2. Mở Developer Tools → **Network**.
3. Thực hiện một thao tác trong RoboNeo.
4. Chọn request tới `webapi.roboneo.com` hoặc `ai-engine-gateway-roboneo.meitu.com`.
5. Kiểm tra request header `access-token`.

Không gửi token vào chat, commit Git, ảnh chụp màn hình hoặc log. Khi gặp `401`, `403` hoặc lỗi user info, access token có thể đã hết hạn.

### App token

App token phải đến từ RoboNeo/Meitu hoặc integration bạn được phép vận hành. Nó không thể thay bằng UID, refresh token hoặc cookie. Hiện chưa có quy trình tự cấp app token qua public API được xác minh.

### UID và GID

- `ROBONEO_UID` có thể để trống; app sẽ thử resolve UID từ access token.
- `ROBONEO_GID` nên được tạo một lần và giữ ổn định cho tài khoản.

Tạo GID:

```bash
cd /Users/apple/Desktop/src_duong/demo_RoboNeo
php artisan tinker --execute="echo App\\Services\\RoboNeo\\RoboNeoIdentity::gid(), PHP_EOL;"
```

Lưu kết quả vào `ROBONEO_GID`.

## 3. Cài project lần đầu

```bash
cd /Users/apple/Desktop/src_duong/demo_RoboNeo
composer install
npm install
test -f .env || cp .env.example .env
test -f database/database.sqlite || touch database/database.sqlite
php artisan migrate
npm run build
```

Chỉ chạy lệnh sau nếu `.env` chưa có `APP_KEY`:

```bash
php artisan key:generate
```

Không đổi `APP_KEY` trên bản cài đang dùng. Session của job được mã hóa bằng key này; đổi key sẽ làm job cũ không đọc được.

## 4. Cấu hình live mode

Mở `.env` và đặt:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Ho_Chi_Minh

QUEUE_CONNECTION=database

ROBONEO_LIVE_ENABLED=true
ROBONEO_APP_TOKEN="APP_TOKEN_DUOC_CAP_PHEP"
ROBONEO_UID=""
ROBONEO_GID="GID_DA_TAO_O_BUOC_TREN"
ROBONEO_AREA_CODE=VN
ROBONEO_LANGUAGE=en

ROBONEO_MAX_QUOTE_COST=100
ROBONEO_POLL_INTERVAL=5
ROBONEO_MAX_POLL_ATTEMPTS=240
```

`ROBONEO_MAX_QUOTE_COST=100` là giới hạn thận trọng cho lần thử đầu. Nếu báo giá lớn hơn, backend sẽ từ chối xác nhận. Chỉ tăng sau khi kiểm tra giá thực tế.

Áp dụng cấu hình:

```bash
chmod 600 .env
php artisan optimize:clear
php artisan queue:restart
```

Sau đó mở `http://127.0.0.1:8000/roboneo-accounts`, thêm một hoặc nhiều Personal Access Token và chọn một tài khoản mặc định. Khi tạo job live, chọn tài khoản trong form; job sẽ giữ nguyên tài khoản đó trong toàn bộ quá trình chạy.

## 5. Chạy web và queue worker

Terminal 1:

```bash
cd /Users/apple/Desktop/src_duong/demo_RoboNeo
php -d upload_max_filesize=120M -d post_max_size=130M artisan serve --host=127.0.0.1 --port=8000
```

Terminal 2:

```bash
cd /Users/apple/Desktop/src_duong/demo_RoboNeo
php artisan queue:work --sleep=1 --tries=1 --timeout=1200
```

Mở:

```text
http://127.0.0.1:8000/motion
```

Góc trên bên phải phải hiển thị **Live API**. Nếu vẫn là **Dry run**, chạy lại `php artisan optimize:clear` và khởi động lại cả web server lẫn queue worker.

## 6. Chạy task live đầu tiên

1. Dùng ảnh JPG/PNG/WEBP dưới 10 MB.
2. Dùng video MP4/MOV/WEBM dưới 100 MB; lần đầu nên dùng file ngắn, nhỏ.
3. Chọn `Standard`; code cố định `quality=std` và `tree_id=93`.
4. Đặt thời lượng 5 giây cho lần thử đầu.
5. Nhấn **Tải lên và báo giá**.
6. Chờ trang chi tiết hiển thị báo giá thật từ `countcost`.
7. Kiểm tra badge phải là **Live API**, không phải Dry run.
8. Kiểm tra số cà rốt và chỉ nhấn **Xác nhận chạy** đúng một lần.
9. Giữ queue worker hoạt động đến khi job `Hoàn tất` hoặc `Thất bại`.

Trước bước 8, app mới tạo room, upload media, đăng ký asset và lấy báo giá. Request tạo video trả phí chỉ gửi sau khi xác nhận.

Luồng đầy đủ:

```text
initconfig
→ createroom
→ canvas/init
→ uploadpolicy
→ STS Aliyun OSS upload
→ mediacheck
→ asset/create
→ canvas/save
→ countcost
→ người dùng xác nhận
→ nodeexecute
→ nodeexecutequery (poll)
→ historymodify/save_cover/roomsubmit
```

## 7. Theo dõi task

Xem 5 job mới nhất:

```bash
php artisan tinker --execute="dump(App\\Models\\MotionJob::query()->latest()->limit(5)->get(['id','status','dry_run','task_id','quoted_cost','poll_attempts','error_code','error_message','created_at'])->toArray());"
```

Theo dõi log:

```bash
tail -f storage/logs/laravel.log
```

| Trạng thái | Ý nghĩa |
| --- | --- |
| `awaiting_confirmation` | Đã lấy báo giá, chưa tạo task trả phí |
| `submitted` | Đã xác nhận, đang chờ worker gửi task |
| `processing` | RoboNeo đang render và app đang poll |
| `completed` | Đã tìm thấy MP4 và hoàn tất |
| `failed` | Backend, upload, token hoặc render bị lỗi |

Không tự chạy lại `nodeexecute` bằng curl khi job đang `submitted` hoặc `processing`. Việc này có thể tạo task thứ hai và tính credit hai lần.

## 8. Xử lý lỗi thường gặp

### `Please log in first` hoặc không resolve được UID

Personal Access Token đã hết hạn, bị thu hồi hoặc không tương thích với backend. Mở **Tài khoản RoboNeo**, thay token rồi bấm xác minh. Worker đọc token mới từ database; sau khi deploy code mới vẫn cần chạy `php artisan queue:restart`.

### `missing_app_token`

Chưa có `ROBONEO_APP_TOKEN`. Không thể chạy live nếu thiếu token ứng dụng được cấp phép.

### HTTP 401/403

Token hết hạn, sai tài khoản hoặc không có quyền với client. Thay Personal Access Token trong giao diện; UID được app tự xác định khi xác minh.

### `invalid_upload_strategy`

Policy thiếu STS credential, bucket hoặc key. Nguyên nhân thường là token không hợp lệ hoặc schema backend đã đổi. Không đặt AWS key cá nhân vào project để thay thế STS của RoboNeo.

### Job đứng ở `submitted`

Queue worker chưa chạy:

```bash
php artisan queue:work --sleep=1 --tries=1 --timeout=1200
```

### Job đứng ở `processing`

Kiểm tra `poll_attempts`, `error_message` và worker. Mặc định app poll mỗi 5 giây, tối đa 240 lần, tương đương khoảng 20 phút.

### Upload lỗi 413

Chạy PHP server với giới hạn upload:

```bash
php -d upload_max_filesize=120M -d post_max_size=130M artisan serve --host=127.0.0.1 --port=8000
```

## 9. Quay lại dry-run

Đổi `.env`:

```dotenv
ROBONEO_LIVE_ENABLED=false
```

Sau đó:

```bash
php artisan optimize:clear
php artisan queue:restart
```

Khởi động lại web server. Badge phải trở về **Dry run**. Không xác nhận job live đang dở khi chưa xác định trạng thái task phía RoboNeo.

## 10. Checklist trước khi xác nhận trả phí

- [ ] Badge hiển thị `Live API`.
- [ ] Access token thuộc tài khoản của bạn và chưa hết hạn.
- [ ] App token được cấp phép, không lấy từ binary của bên khác.
- [ ] Queue worker đang chạy.
- [ ] Báo giá không vượt `ROBONEO_MAX_QUOTE_COST`.
- [ ] Ảnh/video đúng nội dung.
- [ ] Chỉ nhấn xác nhận một lần.
- [ ] Không public cổng 8000 ra Internet; demo hiện chưa có đăng nhập web.
- [ ] Có phương án revoke/rotate token sau thử nghiệm.

## 11. Giới hạn hiện tại

- Dry-run đã được kiểm thử end-to-end.
- Payload và thứ tự endpoint được tái dựng từ VibeForge 26.7.3.
- Live mode chưa thể xác minh end-to-end nếu không có credential và credit hợp lệ.
- RoboNeo có thể thay đổi endpoint, response schema, chữ ký upload hoặc model mà không báo trước.
- Demo phù hợp chạy local cho một tài khoản. Trước khi production cần thêm authentication, secret manager, rate limiting, audit log và cơ chế refresh token chính thức.
