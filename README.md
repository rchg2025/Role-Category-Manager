# Role Category Manager

Plugin WordPress phân quyền user để quản lý quyền xem các chuyên mục bài viết.

## Mô tả

Role Category Manager cho phép quản trị viên WordPress kiểm soát quyền xem các chuyên mục (categories) dựa trên vai trò (role) của người dùng. Plugin này giúp bạn:

- Quản lý quyền truy cập vào các chuyên mục theo từng vai trò
- Ẩn các bài viết không thuộc chuyên mục được phép
- Ngăn chặn truy cập trực tiếp vào bài viết không có quyền
- Giao diện quản trị đẹp mắt với tone màu xanh dương và trắng

## Tính năng

✅ Phân quyền xem chuyên mục theo vai trò người dùng
✅ Giao diện admin thân thiện, dễ sử dụng
✅ Tự động lọc bài viết trên frontend
✅ Kiểm tra quyền truy cập bài viết đơn lẻ
✅ Shortcode hiển thị danh sách chuyên mục có quyền xem
✅ Responsive, tương thích mobile
✅ Sử dụng AJAX để tải và lưu dữ liệu nhanh chóng

## Cài đặt

1. Upload thư mục `Manager User` vào `/wp-content/plugins/`
2. Kích hoạt plugin qua menu 'Plugins' trong WordPress
3. Truy cập menu "Phân Quyền" trong Admin Panel để cấu hình

## Sử dụng

### Cấu hình phân quyền

1. Vào **Phân Quyền** trong menu WordPress Admin
2. Chọn vai trò người dùng từ dropdown
3. Đánh dấu các chuyên mục mà vai trò đó được phép xem
4. Nhấn **Lưu Phân Quyền**

### Shortcode

Sử dụng shortcode sau để hiển thị danh sách chuyên mục mà người dùng có quyền xem:

```
[rchg_mu_user_categories]
```

## Cấu trúc thư mục

```
Manager User/
├── role-category-manager.php       # File plugin chính
├── includes/                       # Các class PHP
│   ├── class-rchg-admin.php       # Xử lý admin panel
│   ├── class-rchg-permissions.php # Xử lý phân quyền
│   └── class-rchg-frontend.php    # Xử lý frontend
├── assets/                         # CSS và JavaScript
│   ├── css/
│   │   ├── admin-style.css        # Styles cho admin
│   │   └── frontend-style.css     # Styles cho frontend
│   └── js/
│       └── admin-script.js        # JavaScript cho admin
└── README.md                       # File này
```

## Quy ước đặt tên

Tất cả các ID và class trong plugin đều bắt đầu với prefix `rchg_mu_` (Role Category Manager Unique) để tránh xung đột với các plugin khác.

## Yêu cầu hệ thống

- WordPress 5.0 trở lên
- PHP 7.0 trở lên
- MySQL 5.6 trở lên

## Tác giả

Phát triển bởi Your Name

## Phiên bản

1.0.0 - Phiên bản đầu tiên

## Giấy phép

GPL v2 or later

## Hỗ trợ

Nếu bạn gặp vấn đề hoặc có câu hỏi, vui lòng liên hệ qua email hoặc tạo issue trên repository.

## Changelog

### 1.0.0
- Phiên bản đầu tiên
- Tính năng phân quyền theo vai trò
- Giao diện admin với tone màu xanh dương và trắng
- Shortcode hiển thị chuyên mục
- Tự động lọc bài viết theo phân quyền
