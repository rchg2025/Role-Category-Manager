# Role Category Manager

Plugin WordPress phân quyền user để quản lý quyền xem các chuyên mục bài viết.

## Mô tả

Role Category Manager cho phép quản trị viên WordPress kiểm soát quyền xem các chuyên mục (categories) dựa trên vai trò (role) của người dùng. Plugin này giúp bạn:

- ✅ Quản lý quyền truy cập vào các chuyên mục theo từng vai trò
- ✅ Ẩn các bài viết không thuộc chuyên mục được phép
- ✅ Ngăn chặn truy cập trực tiếp vào bài viết không có quyền
- ✅ Giao diện quản trị đẹp mắt với tone màu xanh dương và trắng

## Tính năng chính

### 1. Quản lý quyền theo vai trò
- Cấu hình quyền xem chuyên mục cho từng vai trò người dùng
- Giao diện trực quan, dễ sử dụng
- Administrator luôn có quyền xem tất cả

### 2. Lọc bài viết tự động
- Tự động ẩn các bài viết không thuộc chuyên mục được phép
- Áp dụng cho trang chủ, archive, và tìm kiếm
- Không ảnh hưởng đến admin và editor

### 3. Bảo vệ truy cập trực tiếp
- Ngăn chặn người dùng truy cập trực tiếp vào bài viết không có quyền
- Tự động chuyển hướng về trang chủ khi không có quyền

### 4. Giao diện đẹp mắt
- Thiết kế hiện đại với tone màu xanh dương và trắng
- Responsive, tương thích với mọi thiết bị
- Tích hợp hoàn hảo với WordPress admin

## Yêu cầu hệ thống

- WordPress 5.0 hoặc cao hơn
- PHP 7.2 hoặc cao hơn
- MySQL 5.6 hoặc cao hơn

## Cài đặt

### Cách 1: Upload trực tiếp

1. Tải về plugin (file ZIP)
2. Đăng nhập vào WordPress Admin
3. Vào **Plugins** → **Add New** → **Upload Plugin**
4. Chọn file ZIP và click **Install Now**
5. Click **Activate** để kích hoạt plugin

### Cách 2: Upload qua FTP

1. Giải nén file ZIP
2. Upload thư mục `role-category-manager` vào `/wp-content/plugins/`
3. Đăng nhập WordPress Admin
4. Vào **Plugins** và kích hoạt **Role Category Manager**

## Sử dụng

### Bước 1: Truy cập trang quản lý

Sau khi kích hoạt plugin, vào menu **Category Permissions** trong WordPress Admin.

### Bước 2: Chọn vai trò

1. Chọn một vai trò từ danh sách bên trái
2. Administrator mặc định có quyền xem tất cả và không thể chỉnh sửa

### Bước 3: Cấu hình quyền

1. Chọn các chuyên mục mà vai trò đó được phép xem
2. Sử dụng "Chọn Tất Cả" để chọn/bỏ chọn nhanh
3. Click **Lưu Thay Đổi** để lưu cấu hình

### Bước 4: Kiểm tra

Đăng xuất và đăng nhập với tài khoản có vai trò đã cấu hình để kiểm tra.

## Cách hoạt động

### Lọc danh sách bài viết

Plugin tự động lọc các bài viết trong:
- Trang chủ (home)
- Trang archive (category, tag, date, author)
- Trang tìm kiếm (search)

Chỉ hiển thị các bài viết thuộc chuyên mục được phép xem.

### Kiểm tra truy cập đơn lẻ

Khi người dùng truy cập trực tiếp vào một bài viết:
1. Plugin kiểm tra xem bài viết có thuộc chuyên mục được phép không
2. Nếu không có quyền, tự động chuyển hướng về trang chủ
3. Administrator và Editor không bị giới hạn

### Quyền mặc định

- **Administrator**: Luôn xem được tất cả
- **Editor**: Luôn xem được tất cả
- **Các vai trò khác**: Phụ thuộc vào cấu hình

## Cấu trúc thư mục

```
role-category-manager/
├── role-category-manager.php    # File plugin chính
├── templates/
│   └── admin-page.php           # Template trang admin
├── assets/
│   ├── css/
│   │   └── admin-style.css      # CSS cho admin
│   └── js/
│       └── admin-script.js      # JavaScript cho admin
├── README.md                     # Tài liệu này
└── .gitignore                   # Git ignore file
```

## Cơ sở dữ liệu

Plugin tạo bảng `wp_role_category_permissions` với cấu trúc:

```sql
CREATE TABLE wp_role_category_permissions (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    role_name varchar(50) NOT NULL,
    category_id bigint(20) NOT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY role_category (role_name, category_id)
);
```

## API và Hooks

### Filters

```php
// Kiểm tra xem user có quyền xem category không
$has_permission = apply_filters('rcm_has_category_permission', $has_permission, $category_id, $user_id);

// Cho phép tất cả categories với guest users (mặc định: true)
$allow_all = apply_filters('rcm_allow_all_categories_for_guests', true);

// Tùy chỉnh URL chuyển hướng khi không có quyền truy cập (mặc định: home_url())
$redirect_url = apply_filters('rcm_access_denied_redirect', home_url(), $post_id);
```

### Actions

```php
// Khi user bị chặn truy cập
do_action('rcm_access_denied', $post_id, $user_id);
```

### Ví dụ sử dụng

```php
// Ẩn tất cả posts với guest users
add_filter('rcm_allow_all_categories_for_guests', '__return_false');

// Chuyển hướng đến trang tùy chỉnh khi không có quyền
add_filter('rcm_access_denied_redirect', function($url, $post_id) {
    return get_permalink(123); // ID của trang "Access Denied"
}, 10, 2);

// Ghi log khi user bị chặn
add_action('rcm_access_denied', function($post_id, $user_id) {
    error_log("User {$user_id} denied access to post {$post_id}");
}, 10, 2);
```

## Bảo mật

Plugin đã được thiết kế với các biện pháp bảo mật:

- ✅ Kiểm tra quyền truy cập (capability check)
- ✅ Xác thực nonce cho AJAX requests
- ✅ Sanitization cho tất cả input
- ✅ Escape output để tránh XSS
- ✅ Prepared statements cho database queries

## Tương thích

Plugin tương thích với:
- ✅ WordPress Multisite
- ✅ Các theme phổ biến
- ✅ Plugins quản lý role khác
- ✅ WooCommerce
- ✅ BuddyPress

## Gỡ lỗi

Nếu gặp vấn đề, bạn có thể:

1. **Kiểm tra quyền**: Đảm bảo bạn đã cấu hình đúng quyền cho vai trò
2. **Clear cache**: Xóa cache của WordPress và trình duyệt
3. **Kiểm tra conflict**: Tạm thời vô hiệu hóa các plugin khác để kiểm tra xung đột
4. **Enable debug**: Bật WP_DEBUG trong wp-config.php

## Câu hỏi thường gặp

### Q: Plugin có hoạt động với custom post types không?

A: Hiện tại plugin chỉ hỗ trợ posts mặc định. Hỗ trợ custom post types sẽ được thêm trong phiên bản sau.

### Q: Người dùng chưa đăng nhập (guest) có thể xem posts không?

A: Mặc định, guest users có thể xem tất cả posts. Bạn có thể thay đổi hành vi này bằng filter:
```php
add_filter('rcm_allow_all_categories_for_guests', '__return_false');
```

### Q: Làm sao để cho phép một user xem tất cả categories?

A: Chọn tất cả categories cho vai trò đó trong trang cấu hình.

### Q: Plugin có làm chậm website không?

A: Không, plugin được tối ưu hóa và chỉ chạy các query cần thiết.

### Q: Có thể cấu hình cho từng user cụ thể không?

A: Hiện tại plugin chỉ hỗ trợ cấu hình theo vai trò. Cấu hình theo user sẽ được thêm trong phiên bản sau.

## Changelog

### Version 1.0.0 (2024)
- 🎉 Phiên bản đầu tiên
- ✨ Quản lý quyền xem category theo role
- ✨ Lọc posts tự động
- ✨ Ngăn chặn truy cập trực tiếp
- ✨ Giao diện admin đẹp mắt

## Đóng góp

Mọi đóng góp đều được chào đón! Vui lòng:

1. Fork repository
2. Tạo branch mới (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Mở Pull Request

## License

Plugin này được phát hành dưới GPL v2 hoặc cao hơn.

## Tác giả

- **rchg2025**
- GitHub: [rchg2025](https://github.com/rchg2025)

## Hỗ trợ

Nếu bạn thích plugin này, hãy:
- ⭐ Star repository trên GitHub
- 🐛 Báo cáo bugs qua Issues
- 💡 Đề xuất tính năng mới
- 📖 Cải thiện tài liệu

---

Made with ❤️ by rchg2025
