# VibeForge 26.7.3 -> RoboNeo protocol research

Ngay nghien cuu: 2026-08-30

## Pham vi

Tai lieu nay duoc tai dung tu phan tich tinh installer VibeForge 26.7.3. Installer
khong duoc chay va khong co tai khoan RoboNeo nao duoc su dung. Vi vay:

- Cac endpoint, payload va thu tu request da duoc xac nhan tu client code.
- Hinh dang response duoc suy ra tu parser cua VibeForge, chua duoc doi chieu bang
  mot job live.
- Day la giao thuc noi bo, khong phai public API contract cua RoboNeo.
- Khong dua account rotation, proxy rotation hoac cach vuot gioi han vao thiet ke.

Muc tieu Laravel demo la tu dong hoa mot tai khoan do chinh nguoi dung so huu,
co dry-run, gioi han chi phi va khong luu mat khau dang ro.

## Ket luan kien truc

VibeForge khong goi RoboNeo CLI. No la mot Electron client goi truc tiep ba
nhom dich vu:

| Nhom | Base URL | Vai tro |
| --- | --- | --- |
| AI engine | `https://ai-engine-gateway-roboneo.meitu.com` | Room, cost, execute, poll, media check |
| Web API | `https://webapi.roboneo.com` | Canvas va asset library |
| Meitu account | `https://account.meitu.com`, `https://api.account.meitu.com` | Login, refresh, user info |
| Upload strategy | `https://strategy.app.meitudata.com` | Cap temporary OSS credentials |

Motion Control khong phai mot HTTP request don. Luong day du la:

1. Co access token hop le va khoi tao AI engine.
2. Tao room va khoi tao canvas.
3. Xin upload policy, upload anh/video len Meitu OSS.
4. Kiem tra media va dang ky hai asset vao room.
5. Tao graph canvas gom text, image, video va motion node.
6. Goi `countcost` de lay gia live.
7. Goi `nodeexecute` voi Kling 2.6 Standard.
8. Poll `nodeexecutequery` den khi co MP4 hoac task loi.
9. Luu cover/history va submit room.

## Client identity va HTTP session

Client code su dung cac gia tri sau:

- `client_id`: `1189857647`
- `app_scene`: `roboneo`
- `area_code`: `VN`
- `lang`: `en`
- Web account version: `4.9.0`
- Web zip version: `4.76000`
- User-Agent mo phong Chrome tren Windows.
- Header xac thuc sau login: `access-token: <token>`.
- Cookie tu `Set-Cookie` duoc giu lai va gui cho cac request sau.

AI engine con yeu cau mot application token co dinh nam trong binary VibeForge.
Khong nen tai su dung token cua nha phat hanh trong ung dung moi. Laravel demo
phai doc credential tich hop duoc phep tu `.env` va fail closed neu thieu.

Moi AI engine request co envelope:

```json
{
  "parameter": {
    "token": "${ROBONEO_APP_TOKEN}",
    "gid": "${GID}",
    "uid": "${UID}",
    "trace_id": "${UUID_V4}",
    "client_id": "1189857647",
    "app_scene": "roboneo",
    "area_code": "VN",
    "lang": "en",
    "extra": {
      "big_data_patch": {
        "position_type": "/ai_flow"
      }
    },
    "path_scene": "${OPERATION}",
    "...": "operation-specific fields"
  }
}
```

AI engine coi response thanh cong khi `error_code === 0` va lay payload trong
`data`. Web API coi response thanh cong khi `code === 0` va cung lay `data`.

### Identity generators

- `gid`: nam doan random hex/decimal ghep bang dau `-`; nen tao mot lan cho moi
  RoboNeo account va giu on dinh.
- `mt_g`: 16 random bytes -> 32 ky tu hex.
- `sid`: 16 random bytes -> 32 ky tu hex.
- `trace_id`: UUID v4 moi request.
- Motion `random`: `<unix_ms>-<8_random_digits>`.

## Authentication ma VibeForge su dung

Login bang email/password:

```http
POST https://account.meitu.com/oauth/access_token
Content-Type: application/x-www-form-urlencoded
```

Form fields:

```text
client_id=1189857647
client_language=en
overseas=1
client_type=2
web_version=4.9.0
zip_version=4.76000
is_web=1
client_accept_cookies=1
country_code=VN
mt_g=<32 hex chars>
sid=<32 hex chars>
email=<email>
password=<lowercase MD5 of password>
captcha=
grant_type=email
```

Neu response yeu cau agreement, VibeForge gui lai cung form voi `agree=1`.

Token parser chap nhan cac bien the:

```json
{
  "meta": { "code": 0 },
  "response": {
    "access_token": "...",
    "refresh_token": "...",
    "uid": "...",
    "expires_in": 3600
  }
}
```

`response` co the duoc tra duoi ten `data`. Access token duoc refresh bang:

```http
GET https://api.account.meitu.com/oauth/get_token_info?...&refresh_token=<token>
access-token: <old-access-token>
```

Khuyen nghi Laravel demo:

- Uu tien access token do nguoi dung cap thay vi luu password.
- Neu bat buoc login email/password, chi dung trong request khoi tao; khong luu
  password, ma hoa token bang Laravel encrypted cast va cho phep revoke.
- MD5 o day chi la dinh dang protocol, khong phai bao ve password.

## Khoi tao session va room

### Init config

```http
POST /roboneo/sync/request/initconfig
```

`path_scene=initconfig`, `position_type=/`.

### Doc credit

```http
POST /roboneo/sync/request/meiyequery
```

So credit/carrots nam o `data.amount`.

### Tao room

```http
POST /roboneo/sync/request/createroom
```

Operation fields:

```json
{
  "room_type": 2
}
```

VibeForge lay `data.room_id`, sau do:

```http
POST https://webapi.roboneo.com/workflow/canvas/init.json
```

```json
{
  "gnum": "${GID}",
  "client_id": "1189857647",
  "client_language": "en",
  "country_code": "VN",
  "room_id": "${ROOM_ID}"
}
```

## Upload va dang ky asset

Quy trinh nay lap lai mot lan cho anh va mot lan cho video.

### 1. Lay AI upload policy

```http
POST /roboneo/sync/request/uploadpolicy
```

Operation fields:

```json
{
  "upload_version": "2",
  "app": "RoboNeo",
  "type": "roboneo_private_web",
  "count": 1,
  "suffix": "jpg",
  "sig": "",
  "sigTime": "${UNIX_SECONDS}",
  "sigVersion": "1.3",
  "version": "2"
}
```

Response can cap `sig`, `sigTime` va `sigVersion` cho buoc strategy.

### 2. Lay temporary OSS credentials

```http
GET https://strategy.app.meitudata.com/upload/policy
```

Query:

```text
app=RoboNeo
count=1
sig=<sig>
sigTime=<sigTime>
sigVersion=<sigVersion>
suffix=<file extension>
type=roboneo_private_web
version=2
```

VibeForge doc provider dau tien trong `order` (thuong la `oss`) va lay:

- `credentials.access_key`
- `credentials.secret_key`
- `credentials.session_token`
- `bucket`
- `url`/upload host
- `region`, mac dinh `oss-cn-beijing`
- `key`
- `data_url` va `access_url`

### 3. Upload OSS

- File <= 5 MiB: multipart/form-data POST voi AWS4-HMAC-SHA256 POST policy.
- File > 5 MiB: Aliyun OSS multipart upload, part size 4 MiB, auth header
  `Authorization: OSS <access-key>:<HMAC-SHA1>` va
  `x-oss-security-token`.

Laravel khong nen viet lai signer neu co the tranh. Su dung
`aliyuncs/oss-sdk-php` voi STS access key, secret va security token tra ve tu
strategy service. Gioi han demo chi nhan file hop le va khong log temporary
credentials.

### 4. Media check

```http
POST /roboneo/sync/request/mediacheck
```

Anh:

```json
{
  "image_urls": ["${DATA_URL}"],
  "video_urls": []
}
```

Video doi vi tri hai mang tren.

### 5. Dang ky asset

```http
POST https://webapi.roboneo.com/asset_library/asset/create.json
```

```json
{
  "gnum": "${GID}",
  "client_id": "1189857647",
  "client_language": "en",
  "country_code": "VN",
  "room_id": "${ROOM_ID}",
  "task_type": "workflow",
  "material_type": "image",
  "url": "${DATA_URL}",
  "watermark_url": "${DATA_URL}",
  "thumbnail_url": "${OPTIONAL_THUMBNAIL}",
  "ext": "jpeg",
  "width": 720,
  "height": 1280,
  "name": "character"
}
```

Asset id duoc doc tu `data.asset_id`. Voi video, thumbnail ma VibeForge dung
la `${DATA_URL}&vframe/jpg/offset/0`.

## Canvas graph cho Motion Control

Graph gom bon node:

1. `TEXT_NODE`: prompt.
2. `IMAGE_NODE`: URL va asset id cua anh.
3. `VIDEO_NODE`: URL va asset id cua video.
4. `VIDEO_EDIT_NODE`: Kling Motion Control.

Motion node Kling 2.6:

```json
{
  "type": "VIDEO_EDIT_NODE",
  "data": {
    "mcpCategoriesId": "93",
    "apiName": "video_bonbon_motioncontrol_v26",
    "parameters": {},
    "unfinishTaskList": [],
    "childrenNodeList": []
  }
}
```

Ba edge noi text/image/video vao motion node bang port:

```text
port-input-<motion-node-id>-TEXT-0-0
port-input-<motion-node-id>-IMAGE-1-0
port-input-<motion-node-id>-VIDEO-2-0
```

Node id la NanoID-style 21 ky tu URL-safe. Graph duoc luu bang:

```http
POST https://webapi.roboneo.com/workflow/canvas/save.json
```

Trong body, `data` la JSON string cua object `{nodes: [...], edges: [...]}`.

## Tinh chi phi live

```http
POST /roboneo/sync/request/countcost
```

Operation fields:

```json
{
  "room_id": "${ROOM_ID}",
  "items": [
    {
      "id": "${MOTION_NODE_ID}",
      "tool_name": "video_bonbon_motioncontrol_v26",
      "video_duration": 10,
      "size": "",
      "resolution": "",
      "params": {
        "prompt": "...",
        "quality": "std",
        "image_url": "${IMAGE_URL}",
        "video_url": "${VIDEO_URL}",
        "video_duration": 10
      }
    }
  ]
}
```

Chi phi nam o `data.items[0].cost` (VibeForge cung chap nhan mot alias cu).
Demo phai hien cost va yeu cau xac nhan truoc `nodeexecute`.

## Trigger Kling 2.6 Standard

```http
POST /roboneo/sync/request/nodeexecute
```

Operation fields:

```json
{
  "room_id": "${ROOM_ID}",
  "node_id": "${MOTION_NODE_ID}",
  "node_list_array": [
    [
      {
        "name": "video_bonbon_motioncontrol_v26",
        "tree_id": "93",
        "tool_abstract_name": {
          "en": "Motion Control",
          "cn": "Motion Control"
        },
        "node_id": "${MOTION_NODE_ID}",
        "parameters": {
          "prompt": "...",
          "quality": "std",
          "image_url": "${IMAGE_URL}",
          "video_url": "${VIDEO_URL}",
          "random": "${UNIX_MS}-${RANDOM_8_DIGITS}"
        }
      }
    ]
  ]
}
```

Task id co the nam tai `data.task_id`, phan tu dau cua `task_ids`, hoac trong
`tasks`. Parser Laravel can tim de quy cac key `task_id`, `taskId`, `task_ids`.

VibeForge khong gui `character_orientation`. Backend dang tu chon default.
Demo khong nen tu them field nay cho den khi response cua backend xac nhan ho
tro, vi countcost va execute phai dung cung mot bo parameter.

## Poll ket qua

Moi 3 giay:

```http
POST /roboneo/sync/request/nodeexecutequery
```

Operation fields:

```json
{
  "task_ids": ["${TASK_ID}"],
  "room_id": "${ROOM_ID}"
}
```

`data.tasks` co the la array hoac object map theo task id. VibeForge coi cac
status sau la thanh cong:

```text
SUCCESS, SUCCEED, FINISHED, COMPLETED
```

Pending:

```text
PENDING, QUEUED, WAITING, RUNNING, PROCESSING
```

That bai:

```text
CANCEL, FAILED, ERROR
```

Result URL co the xuat hien trong `steps[].output` (JSON string/object), cac
media arrays, `video_url`, `video_urls` hoac result URL top-level. Parser an
toan nhat cho demo la duyet response co gioi han do sau va lay HTTPS URL co
duoi `.mp4`, dong thoi luu raw response da redact de debug.

Khong poll trong web request. Dung Laravel queue job voi backoff 3-10 giay,
timeout 20 phut va toi da nam network failure lien tiep.

## Finalize room

Sau khi co ket qua, VibeForge best-effort goi:

1. `/roboneo/sync/request/historymodify` voi `room_id`.
2. `/workflow/canvas/save_cover.json` voi room va cover URL neu co.
3. `/roboneo/sync/request/roomsubmit` voi `room_id`, `room_type=2`.

Loi finalize khong duoc lam mat video da tao thanh cong.

## De xuat cau truc Laravel demo

```text
app/Services/RoboNeo/RoboNeoHttpClient.php
app/Services/RoboNeo/RoboNeoIdentity.php
app/Services/RoboNeo/RoboNeoAuth.php
app/Services/RoboNeo/RoboNeoAiEngine.php
app/Services/RoboNeo/RoboNeoWebApi.php
app/Services/RoboNeo/RoboNeoUploader.php
app/Services/RoboNeo/MotionGraphBuilder.php
app/Services/RoboNeo/MotionControlService.php
app/Jobs/UploadRoboNeoAssets.php
app/Jobs/SubmitMotionControl.php
app/Jobs/PollRoboNeoTask.php
```

Bang `roboneo_jobs` toi thieu:

```text
id, status, room_id, task_id, motion_node_id
prompt, quality, duration_seconds, quoted_cost
image_path, video_path, image_asset_json, video_asset_json
result_url, error_code, error_message
raw_status_json, submitted_at, completed_at
```

State machine:

```text
draft -> uploading -> quoted -> awaiting_confirmation -> submitted
      -> processing -> completed | failed | cancelled
```

Endpoints demo:

```text
GET  /motion
POST /motion/quote
POST /motion/{job}/confirm
GET  /motion/{job}
GET  /motion/{job}/status
```

`POST /quote` chi upload, save canvas va count cost. Chi `/confirm` moi duoc
phep tao task tinh credit.

## Security va van hanh

- Khong hardcode app token, access token hoac temporary OSS credential.
- Laravel secrets nam trong `.env`; user token dung encrypted cast.
- Tuyet doi khong log password, token, cookie, upload signature hoac raw header.
- Bat CSRF, authentication, rate limit va per-user job ownership.
- Validate MIME bang noi dung, khong chi extension.
- Gioi han anh/video, thoi luong va kich thuoc truoc khi upload.
- Chi download result tu HTTPS host allowlist duoc quan sat tu response hop le.
- Khong tu dong retry `nodeexecute`; retry nham co the tru credit hai lan.
- Gan idempotency o database: mot job chi co mot `task_id` sau khi submit.
- Hien thi quote live va yeu cau explicit confirmation.
- Them kill switch `ROBONEO_LIVE_ENABLED=false` mac dinh.
- API noi bo co the thay doi ma khong bao truoc; can integration test hang ngay
  voi mot tai khoan test credit thap.

## Phan chua duoc xac minh live

Can mot tai khoan test do nguoi dung so huu va hai media test de xac nhan:

1. Application credential duoc phep cho client Laravel.
2. Response thuc cua login/initconfig/createroom.
3. Response policy cho image va video.
4. Upload bang OSS PHP SDK co tuong thich temporary credentials hay khong.
5. Exact response cua `countcost`, `nodeexecute`, `nodeexecutequery`.
6. Backend default cho `character_orientation`.
7. Output resolution cua `quality=std` va chi phi thuc te.
8. Cookie nao la bat buoc ben canh `access-token`.

Khong nen viet production integration truoc khi hoan thanh cac smoke test nay.
