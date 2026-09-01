# RoboNeo Motion Desk

Laravel demo tu dong hoa **Kling 2.6 Motion Control Standard** bang cach goi truc tiep cac backend ma VibeForge 26.7.3 dang su dung. Du an khong goi RoboNeo CLI, khong nhung token lay tu VibeForge va mac dinh chay o che do `dry-run`.

## Chuc nang

- Nhan anh tham chieu, video motion, prompt va thoi luong 5/10 giay.
- Tao room/canvas, upload media qua STS Aliyun OSS, dang ky asset, `countcost`, xac nhan chi phi, `nodeexecute` va poll ket qua.
- Model co dinh: `video_bonbon_motioncontrol_v26`, `tree_id=93`, `quality=std`.
- Queue database cho cac buoc lau; trang chi tiet tu dong cap nhat trang thai.
- Dry-run tao manifest JSON de kiem tra toan bo workflow ma khong ton credit.

Phan tich giao thuc va nhung diem chua chac chan nam tai [docs/vibeforge-roboneo-protocol.md](docs/vibeforge-roboneo-protocol.md).

## Chay local

Yeu cau PHP 8.3+, Composer va Node.js 20+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm run build
```

Chay web va worker o hai terminal:

```bash
php -d upload_max_filesize=120M -d post_max_size=130M artisan serve --host=127.0.0.1 --port=8000
php artisan queue:work --sleep=1 --tries=1 --timeout=1200
```

Mo `http://127.0.0.1:8000/motion`. Dry-run la cau hinh an toan mac dinh.

## Bat live mode

Chi dung credential cua tai khoan ban duoc phep su dung. Dat cac bien sau trong `.env`:

```dotenv
ROBONEO_LIVE_ENABLED=true
ROBONEO_APP_TOKEN=...
ROBONEO_UID=...
ROBONEO_GID=...
ROBONEO_MAX_QUOTE_COST=250
```

Nhap Personal Access Token tai `/roboneo-accounts`; token duoc ma hoa trong database va tung job khoa vao tai khoan da chon. `ROBONEO_UID` co the bo trong de app tu resolve. `ROBONEO_GID` cung co the bo trong va se duoc tao ngau nhien. `ROBONEO_APP_TOKEN` phai la app token hop le do chu tai khoan/nen tang cap; demo khong trich xuat hoac phat hanh token nay.

Sau khi doi `.env`, chay:

```bash
php artisan optimize:clear
php artisan queue:restart
```

Day la giao thuc backend noi bo quan sat tu VibeForge, khong phai public API co cam ket on dinh. Endpoint, chu ky va schema co the thay doi; live mode chua the xac minh end-to-end neu khong co credential/credit hop le. Khong public app nay ra Internet neu chua them authentication, rate limiting va secret manager.

## Kiem tra

```bash
vendor/bin/pint --test
php artisan test
npm run build
```
