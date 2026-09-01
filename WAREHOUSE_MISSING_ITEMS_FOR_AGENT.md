# Checklist Cho Agent: Hoàn Thiện Logic Ô Chứa Và Pallet Trong Kho

## 1. Mục tiêu tài liệu

Tài liệu này dùng để hướng dẫn AI Agent / Developer hoàn thiện phần setup kho theo 2 mô hình lưu trữ chính:

```txt
1. Ô chứa / BIN:
   Hàng → Location

2. Pallet:
   Hàng → Pallet → Location / SLOT
```

DB hiện tại đã có nền khá tốt, nhưng cần bổ sung thêm một số logic, field, index, generator và UI để vận hành chuẩn hơn.

Mục tiêu sau khi làm xong:

- Phân biệt rõ `BIN` và `SLOT`
- Kho không pallet sinh ô chứa hàng trực tiếp
- Kho có pallet sinh slot chứa pallet
- Pallet có sức chứa tối đa
- Có rule trộn SKU / batch trên pallet
- Map editor kéo thả mượt
- Generator sinh location từ sơ đồ
- Stock ledger không bị duplicate balance
- UI tạo sơ đồ hỏi rõ mode lưu trữ

---

# 2. Logic nền bắt buộc phải hiểu

## 2.1. Direct mode / Không pallet

Công thức:

```txt
Hàng → Location / BIN
```

Ví dụ:

```txt
Sản phẩm A
→ BIN-01
```

Trong DB tồn kho:

```txt
warehouse_location_id = BIN-01
pallet_id = null
```

## 2.2. Pallet mode / Có pallet

Công thức:

```txt
Hàng → Pallet → Location / SLOT
```

Ví dụ:

```txt
Sản phẩm A
→ PLT0001
→ RACK-A-L01-S01
```

Trong DB tồn kho:

```txt
warehouse_location_id = SLOT-01
pallet_id = PLT0001
```

## 2.3. Chốt quan trọng

```txt
BIN  = ô chứa hàng trực tiếp
SLOT = ô chứa pallet
Pallet = vật chứa hàng
Location = vị trí thật trong kho
Map item = vùng vẽ trên sơ đồ
Stock balance = dữ liệu tồn thật
```

---

# 3. Những gì DB hiện tại đã có nền

DB hiện tại đã có các bảng quan trọng:

```txt
inv_warehouse_locations
inv_warehouse_maps
inv_warehouse_map_items
inv_pallets
inv_pallet_movements
inv_receipt_storage_items
inv_stock_transactions
inv_stock_balances
inv_warehouse_product_policies
```

Các bảng này đã đủ nền để triển khai tiếp:

```txt
Direct mode:
Hàng → BIN

Pallet mode:
Hàng → Pallet → SLOT
```

Nhưng cần bổ sung các phần bên dưới để chuẩn hơn.

---

# 4. Việc 1 — Chuẩn hóa BIN và SLOT trong location type

## Cần làm

Chuẩn hóa `type` trong `inv_warehouse_locations`.

Cần có tối thiểu các loại:

```txt
zone
rack
level
bin
slot
pallet_slot
aisle
receiving
waiting_putaway
qc_hold
damaged
rejected
dispatch
staging_area
```

## Ý nghĩa

### `bin`

Là ô chứa hàng trực tiếp.

```txt
Hàng → BIN
pallet_id = null
```

### `slot` hoặc `pallet_slot`

Là vị trí chứa pallet.

```txt
Hàng → Pallet → SLOT
pallet_id != null
```

### `aisle`

Là lối đi.

```txt
Không chứa hàng
Không chứa pallet
Không post tồn
```

## Rule bắt buộc

```txt
Direct mode:
leaf location phải là BIN

Pallet mode:
leaf location phải là SLOT / pallet_slot
```

## Vì sao cần làm

Nếu chỉ có `rack` hoặc `level` mà không có leaf location rõ ràng, hệ thống không biết hàng/pallet nằm chính xác ở đâu.

---

# 5. Việc 2 — Generator sinh vị trí từ map item

## Mục tiêu

User không cần tự tạo từng ô/từng slot.

Khi user tạo module trên sơ đồ, hệ thống tự sinh location tree.

---

## 5.1. Direct mode: 4 x 4 sinh 16 BIN

Input:

```txt
module_type = simple_shelf
storage_mode = direct
level_count = 4
bin_count_per_level = 4
```

Output:

```txt
SHELF-A
├── L01
│   ├── B01
│   ├── B02
│   ├── B03
│   └── B04
├── L02
│   ├── B01
│   ├── B02
│   ├── B03
│   └── B04
├── L03
│   ├── B01
│   ├── B02
│   ├── B03
│   └── B04
└── L04
    ├── B01
    ├── B02
    ├── B03
    └── B04
```

Leaf location:

```txt
type = bin
```

---

## 5.2. Pallet mode: 4 x 4 sinh 16 SLOT

Input:

```txt
module_type = pallet_rack
storage_mode = pallet
level_count = 4
slot_count_per_level = 4
pallets_per_slot = 1
```

Output:

```txt
RACK-A
├── L01
│   ├── S01
│   ├── S02
│   ├── S03
│   └── S04
├── L02
│   ├── S01
│   ├── S02
│   ├── S03
│   └── S04
├── L03
│   ├── S01
│   ├── S02
│   ├── S03
│   └── S04
└── L04
    ├── S01
    ├── S02
    ├── S03
    └── S04
```

Leaf location:

```txt
type = slot hoặc pallet_slot
```

## Rule quan trọng

```txt
Tạo rack 4 x 4 trong pallet mode chỉ sinh 16 SLOT.
Không tự sinh 16 pallet.
```

Pallet chỉ được tạo khi:

- user tạo pallet
- nhập hàng cần pallet
- quét mã pallet
- bulk create pallet

---

# 6. Việc 3 — Thêm / lưu capacity cho BIN, SLOT, map item

## Cần làm

Cần lưu sức chứa cho location hoặc map item.

Nếu chưa muốn thêm nhiều cột, có thể lưu trong `meta_json`.

## Gợi ý field

```txt
storage_mode
capacity_mode
max_qty
max_weight
max_volume
max_pallets
level_count
bin_count_per_level
slot_count_per_level
pallets_per_slot
```

## Ví dụ Direct mode

```json
{
  "storage_mode": "direct",
  "module_type": "simple_shelf",
  "level_count": 4,
  "bin_count_per_level": 4,
  "capacity_mode": "qty",
  "max_qty_per_bin": 100
}
```

## Ví dụ Pallet mode

```json
{
  "storage_mode": "pallet",
  "module_type": "pallet_rack",
  "level_count": 4,
  "slot_count_per_level": 4,
  "pallets_per_slot": 1,
  "capacity_mode": "pallet",
  "max_pallets": 16
}
```

## Vì sao cần làm

Để hệ thống biết:

- BIN đã đầy chưa
- SLOT đã có pallet chưa
- SLOT chứa được bao nhiêu pallet
- Có được đưa thêm hàng/pallet vào không

---

# 7. Việc 4 — Thêm pallet capacity hoặc pallet type

## Hiện trạng

`inv_pallets` đã có pallet, nhưng cần thêm sức chứa pallet.

## Cách nhanh

Thêm trực tiếp vào `inv_pallets`:

```txt
max_qty
max_weight
max_volume
max_sku_count
max_batch_count
```

## Cách chuẩn hơn

Tạo bảng mới:

```txt
inv_pallet_types
```

Cột gợi ý:

```txt
id
code
name
max_qty
max_weight
max_volume
max_height
max_sku_count
max_batch_count
is_active
created_at
updated_at
```

Sửa `inv_pallets` thêm:

```txt
pallet_type_id
```

## Vì sao cần làm

Pallet không thể chứa vô hạn hàng.

Cần biết pallet:

- chứa tối đa bao nhiêu sản phẩm
- chịu tối đa bao nhiêu kg
- chứa tối đa bao nhiêu SKU
- chứa tối đa bao nhiêu batch

---

# 8. Việc 5 — Thêm rule trộn SKU / batch trên pallet

## Hiện trạng

Đã có hoặc từng có:

```txt
allow_mixed_batch_on_pallet
```

Nhưng còn thiếu:

```txt
allow_mixed_sku_on_pallet
max_qty_per_pallet
```

## Cần thêm vào `inv_warehouse_product_policies`

```txt
allow_mixed_sku_on_pallet boolean default false
max_qty_per_pallet decimal nullable
```

## Ý nghĩa

### `allow_mixed_sku_on_pallet`

Cho phép pallet chứa nhiều sản phẩm khác nhau hay không.

Nếu false:

```txt
PLT0001 đang có Product A
Không cho thêm Product B
```

### `allow_mixed_batch_on_pallet`

Cho phép pallet chứa nhiều batch khác nhau của cùng sản phẩm hay không.

Nếu false:

```txt
PLT0001 đang có Product A / Batch B001
Không cho thêm Product A / Batch B002
```

### `max_qty_per_pallet`

Giới hạn số lượng sản phẩm đó trên một pallet.

Ví dụ:

```txt
Product A tối đa 80 thùng / pallet
```

## Vì sao cần làm

Kho lớn thường cần rule:

```txt
1 pallet = 1 SKU + 1 batch
```

Còn kho nhỏ hoặc staging có thể cho mixed pallet.

---

# 9. Việc 6 — Thêm index cho các cột vận hành mới

## Cần thêm index

### `inv_stock_transactions`

```txt
pallet_id
storage_item_id
goods_receipt_id
goods_receipt_item_id
goods_receipt_batch_id
```

### `inv_stock_balances`

```txt
pallet_id
goods_receipt_batch_id
warehouse_location_id
```

### `inv_warehouse_map_items`

```txt
location_id
warehouse_map_id
```

### `inv_pallets`

```txt
current_location_id
warehouse_id
```

### `inv_pallet_movements`

```txt
pallet_id
from_location_id
to_location_id
warehouse_id
```

## Vì sao cần làm

Để các thao tác sau chạy nhanh:

- tìm sản phẩm trên pallet
- xem tồn theo location
- xem pallet đang ở đâu
- xem lịch sử pallet
- click map item tìm location
- click location highlight map

---

# 10. Việc 7 — Siết StockLedgerService chống duplicate balance do NULL

## Vấn đề

`inv_stock_balances` có nhiều cột nullable:

```txt
product_variation_id
pallet_id
goods_receipt_batch_id
```

Trong MySQL, unique key có `NULL` có thể vẫn cho nhiều dòng trùng nhau.

Ví dụ có thể phát sinh nhiều dòng giống nhau:

```txt
product_id = 1
product_variation_id = null
warehouse_id = 1
warehouse_location_id = 10
pallet_id = null
goods_receipt_batch_id = null
```

## Cần làm trong `StockLedgerService`

Khi tìm balance, phải query rõ:

```txt
where product_id = ...
where warehouse_id = ...
where warehouse_location_id = ...
whereNull product_variation_id nếu không có variation
whereNull pallet_id nếu không có pallet
whereNull goods_receipt_batch_id nếu không có batch
lockForUpdate()
```

## Rule

Không được chỉ dựa vào unique DB.

Phải dùng service-level idempotency + DB transaction.

## Vì sao cần làm

Tránh tạo 2 dòng tồn cho cùng một sản phẩm/vị trí/pallet/batch.

---

# 11. Việc 8 — Chuẩn hóa batch field

## Hiện trạng

Có thể đang tồn tại cả:

```txt
batch_id
goods_receipt_batch_id
```

## Khuyến nghị hiện tại

Nếu batch thực tế hiện đang nằm ở `inv_goods_receipt_batches`, thì chốt chuẩn:

```txt
goods_receipt_batch_id
```

Còn `batch_id` coi như legacy hoặc để dành sau này nếu có bảng batch master riêng như:

```txt
inv_batches
```

## Cần làm

- Trong service, ưu tiên dùng `goods_receipt_batch_id`
- Không lẫn lúc dùng `batch_id`, lúc dùng `goods_receipt_batch_id`
- Nếu chưa bỏ được `batch_id`, comment rõ vai trò

## Vì sao cần làm

Tránh lệch tồn và transaction khi cùng một batch bị ghi bằng 2 field khác nhau.

---

# 12. Việc 9 — Hoàn thiện map editor drag/drop

## Cần làm

Map editor phải thao tác mượt.

Tối thiểu cần có:

```txt
kéo item đổi vị trí
resize item
save x/y/width/height
duplicate item
delete item nếu hợp lệ
link/unlink location
click tree highlight map
click map focus tree
```

## Rule

Nếu tính năng chưa chạy thật:

```txt
ẩn hoặc disable
không hiển thị như đã hoàn thiện
```

## Vì sao cần làm

Hiện user vào sơ đồ sẽ bị rối nếu thấy nhiều nút nhưng không kéo thả được.

---

# 13. Việc 10 — UI chọn storage_mode khi tạo sơ đồ

## Cần làm

Khi tạo sơ đồ, hỏi rõ:

```txt
Kho này lưu hàng theo cách nào?
```

## Card 1: Hàng để trực tiếp lên kệ / ô chứa

```txt
Phù hợp kho nhỏ, shop, hàng lẻ.
storage_mode = direct
```

Toolbox hiện:

```txt
Khu vực
Lối đi
Kệ đơn giản
Tầng / Ô chứa
Text / Label
```

## Card 2: Hàng đặt trên pallet

```txt
Phù hợp kho lớn, có rack, xe nâng, di chuyển nguyên pallet.
storage_mode = pallet
```

Toolbox hiện:

```txt
Khu nhận hàng
Lối đi
Rack pallet
Pallet slot
Khu pallet sàn
Staging area
Text / Label
```

## Card 3: Hỗn hợp

```txt
Một số khu lưu hàng trực tiếp, một số khu dùng pallet.
storage_mode = mixed
```

Giai đoạn đầu có thể disable card này.

## Vì sao cần làm

Nếu không chọn mode trước, user không biết nên tạo ô chứa hay pallet rack.

---

# 14. Thứ tự triển khai khuyên dùng

Agent nên làm theo thứ tự sau:

```txt
1. Chuẩn hóa BIN / SLOT trong location type
2. Làm generator:
   - direct 4 x 4 → 16 BIN
   - pallet 4 x 4 → 16 SLOT
3. Thêm/lưu capacity cho BIN/SLOT/map item
4. Thêm pallet capacity hoặc pallet type
5. Thêm allow_mixed_sku_on_pallet + max_qty_per_pallet
6. Thêm index cho pallet/storage/batch/location
7. Siết StockLedgerService chống duplicate balance do NULL
8. Chuẩn hóa batch field
9. Hoàn thiện drag/drop map editor
10. Làm UI chọn storage_mode khi tạo sơ đồ
```

---

# 15. Acceptance criteria

Sau khi làm xong cần đạt:

## Direct mode

- Tạo kệ 4 x 4 sinh đúng 16 BIN
- Hàng post vào BIN với `pallet_id = null`
- Click BIN trên map xem tồn trực tiếp theo location
- Generator tự tính `level/path`

## Pallet mode

- Tạo rack 4 x 4 sinh đúng 16 SLOT
- Không tự sinh pallet khi tạo rack
- Pallet được tạo riêng
- Pallet được đặt vào SLOT
- Hàng post theo `pallet_id + warehouse_location_id`
- Click SLOT trên map xem pallet đang nằm ở đó
- Move pallet kéo theo stock balance của pallet

## Capacity

- BIN có thể có `max_qty`
- SLOT có thể có `max_pallets`
- Pallet có thể có `max_qty`, `max_weight`, `max_volume`
- Nếu vượt capacity thì backend phải chặn

## Mixed rule

- Nếu `allow_mixed_sku_on_pallet = false`, không cho thêm SKU khác vào pallet
- Nếu `allow_mixed_batch_on_pallet = false`, không cho thêm batch khác vào pallet
- Nếu vượt `max_qty_per_pallet`, không cho thêm hàng

## StockLedgerService

- Không tạo duplicate stock balance do nullable columns
- Dùng `whereNull` đúng
- Dùng `lockForUpdate()` khi update balance
- Không post trùng cùng storage item

## Map editor

- Kéo thả được
- Resize được
- Duplicate được
- Save layout được
- Link/unlink location được
- Tree click highlight map
- Map click focus tree

---

# 16. Những điều không được làm sai

- Không dùng map item làm dữ liệu tồn thật
- Không để aisle/path chứa hàng
- Không tự sinh pallet khi tạo rack
- Không cho pallet nằm trong BIN
- Không cho hàng direct nằm trong SLOT nếu mode đang direct
- Không để duplicate map item copy `location_id` mặc định
- Không để pallet move mà stock balance vẫn ở location cũ
- Không để `batch_id` và `goods_receipt_batch_id` bị dùng lẫn không rõ
- Không chỉ dựa vào unique key khi update stock balance có nullable columns

---

# 17. Kết luận ngắn gọn cho Agent

DB hiện tại đã có nền tốt, nhưng còn thiếu các phần sau để chuẩn logic mới:

```txt
1. BIN/SLOT rõ ràng
2. Generator sinh BIN/SLOT từ map
3. Capacity cho BIN/SLOT/PALLET
4. Pallet type hoặc pallet max capacity
5. allow_mixed_sku_on_pallet
6. max_qty_per_pallet
7. Index cho pallet/storage/batch/location
8. StockLedgerService chống duplicate nullable balance
9. Chuẩn hóa goods_receipt_batch_id
10. Drag/drop map editor mượt
11. UI chọn direct/pallet mode rõ ràng
```

Chốt logic:

```txt
Direct mode:
Hàng → BIN
pallet_id = null

Pallet mode:
Hàng → Pallet → SLOT
pallet_id != null
```

Cùng là 4 x 4 nhưng:

```txt
Direct mode:
4 x 4 = 16 BIN chứa hàng

Pallet mode:
4 x 4 = 16 SLOT chứa pallet
```

Map chỉ là giao diện chọn và nhìn vị trí.  
Stock balance mới là dữ liệu tồn thật.
