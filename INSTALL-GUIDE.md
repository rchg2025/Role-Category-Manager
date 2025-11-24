# ✅ HƯỚNG DẪN SỬA LỖI VÀ CÀI ĐẶT

## Vấn đề đã được sửa

**Lỗi HTTP 500** khi kích hoạt plugin do:
1. ❌ Tên thư mục có khoảng trắng "Manager User" 
2. ❌ WordPress không thể load plugin từ thư mục có khoảng trắng

## Giải pháp

✅ Đã tạo plugin mới trong thư mục: `role-category-manager` (không có khoảng trắng)

## Cách cài đặt

### Bước 1: Xóa plugin cũ (nếu có)
1. Vào WordPress Admin → Plugins
2. Tìm "Role Category Manager" cũ (thư mục có khoảng trắng)
3. Deactivate và Delete nếu đã cài

### Bước 2: Kích hoạt plugin mới
1. Vào WordPress Admin → Plugins
2. Tìm "Role Category Manager" trong danh sách
3. Click **Activate**

### Bước 3: Sử dụng
1. Vào menu **Phân Quyền** trong Admin Panel
2. Chọn vai trò người dùng
3. Đánh dấu các chuyên mục được phép xem
4. Click **Lưu Phân Quyền**

## Vị trí plugin

```
wp-content/plugins/role-category-manager/
├── role-category-manager.php      ← File chính
├── includes/
│   ├── class-rchg-admin.php
│   ├── class-rchg-permissions.php
│   └── class-rchg-frontend.php
├── assets/
│   ├── css/
│   │   ├── admin-style.css
│   │   └── frontend-style.css
│   └── js/
│       └── admin-script.js
├── languages/
├── index.php
└── README.md
```

## Kiểm tra plugin đã cài đúng

Chạy lệnh này trong terminal:

```bash
php -l "c:\Users\nvluy\Studio\nsg\wp-content\plugins\role-category-manager\role-category-manager.php"
```

Nếu hiển thị: `No syntax errors detected` → Plugin OK! ✅

## Xóa thư mục cũ (tùy chọn)

Sau khi plugin mới hoạt động tốt, bạn có thể xóa thư mục cũ:

```
wp-content/plugins/Manager User/  ← Xóa thư mục này
```

## Lưu ý quan trọng

- ⚠️ Không đặt tên thư mục plugin có khoảng trắng
- ⚠️ Tên thư mục nên là: chữ thường, dấu gạch ngang (-), không dấu
- ✅ Tên tốt: `role-category-manager`, `my-plugin`, `user-manager`
- ❌ Tên tránh: `Manager User`, `My Plugin`, `User Manager`

## Hỗ trợ

Nếu vẫn gặp lỗi, kiểm tra:
1. PHP version ≥ 7.0
2. WordPress version ≥ 5.0
3. File permissions của thư mục plugin
4. Error log tại: `wp-content/debug.log` (nếu WP_DEBUG bật)

---

**Chúc bạn sử dụng plugin thành công!** 🎉
