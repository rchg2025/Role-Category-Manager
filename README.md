=== RCHG Role Category Manager ===
Contributors: rongconh
Tags: permissions, categories, roles, access-control, user-management
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.0
Stable tag: 2.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin WordPress phân quyền user để quản lý quyền xem, sửa và tạo bài viết theo chuyên mục.

== Description ==

RCHG Role Category Manager cho phép quản trị viên WordPress kiểm soát chi tiết quyền truy cập vào các chuyên mục (categories) dựa trên vai trò (role) hoặc từng người dùng (user) cụ thể. Plugin này giúp bạn:

- Quản lý quyền truy cập theo vai trò (Role) hoặc người dùng (User) cụ thể
- Phân quyền 3 loại: **Xem** (View), **Sửa** (Edit), **Tạo mới** (Create)
- Ưu tiên phân quyền User > Role (user-specific override)
- Ẩn các bài viết và chuyên mục không được phép
- Chặn truy cập trực tiếp vào bài viết không có quyền
- **Import/Export** cấu hình JSON để sao lưu và di chuyển
- **Reset** phân quyền user về mặc định theo Role
- Giao diện quản trị hiện đại với tabs và search

== Tính năng v2.0 ==

=== 🎯 Phân quyền nâng cao ===
- ✅ **Theo Role**: Cấu hình cho toàn bộ vai trò (Administrator, Editor, Author, etc.)
- ✅ **Theo User**: Phân quyền riêng cho từng user, ghi đè lên cấu hình Role
- ✅ **3 loại quyền độc lập**:
  - 👁️ **Xem** (can_view): Xem bài viết trong chuyên mục
  - ✏️ **Sửa** (can_edit): Sửa/xóa bài viết đã có
  - ➕ **Tạo** (can_create): Tạo bài viết mới trong chuyên mục

=== 🔐 Kiểm soát sâu
- Chặn edit/delete post ngoài UI thông qua `map_meta_cap` hook
- Chặn "Add New" post nếu user không có quyền create
- Ẩn category trong editor/quick-edit dựa trên quyền create
- Tự động gán phân quyền mặc định khi tạo user mới (clone từ Role)

=== 💾 Import/Export
- **Export toàn bộ**: Tải về tất cả cấu hình dạng JSON
- **Export theo Role/User**: Lọc riêng từng loại
- **Import JSON**: Upload file để phục hồi/di chuyển cấu hình
- **Reset User**: Xóa cấu hình riêng, dùng lại theo Role

=== 🎨 Giao diện mới
- Tab navigation (Role / User)
- User search autocomplete (tìm theo tên/email)
- Permission checkboxes với icon trực quan
- Tools panel với nút Export/Import/Reset
- Responsive, tương thích mobile

== Cài đặt

1. Upload thư mục `role-category-manager` vào `/wp-content/plugins/`
2. Kích hoạt plugin qua menu 'Plugins' trong WordPress
3. Plugin tự động tạo bảng database `wp_rchg_mu_permissions`
4. Truy cập menu **"Phân Quyền"** để cấu hình

== Sử dụng

=== Tab "Theo Vai Trò"

1. Vào **Phân Quyền** → tab **Theo Vai Trò**
2. Chọn vai trò (Administrator, Editor, Author, etc.)
3. Đánh dấu các chuyên mục được phép
4. Chọn loại quyền: ☑️ Xem, ☑️ Sửa, ☑️ Tạo
5. Nhấn **Lưu Phân Quyền**

=== Tab "Theo User"

1. Vào **Phân Quyền** → tab **Theo User**
2. Nhập tên hoặc email user vào ô tìm kiếm
3. Chọn user từ dropdown
4. Đánh dấu các chuyên mục được phép
5. Chọn loại quyền: ☑️ Xem, ☑️ Sửa, ☑️ Tạo
6. Nhấn **Lưu Phân Quyền**

=== Import/Export

**Export:**
- Nút **"Export JSON"** ở tab User → xuất phân quyền user
- Nút **"Export toàn bộ"** ở panel dưới → xuất tất cả

**Import:**
- Click nút **"Import JSON"** / **"Import từ file"**
- Chọn file JSON đã export trước đó
- Dữ liệu sẽ ghi đè theo key (role/user + category)

**Reset User:**
- Chọn user trong tab "Theo User"
- Click **"Reset User"** để xóa cấu hình riêng
- User sẽ tự động dùng lại phân quyền theo Role

=== Shortcode

Hiển thị danh sách chuyên mục mà user có quyền xem:

```
[rchg_mu_user_categories]
```

== Cấu trúc thư mục

```
role-category-manager/
├── role-category-manager.php       # Main plugin file
├── README.md                        # Documentation
├── INSTALL-GUIDE.md                # Installation guide
├── includes/
│   ├── class-rchg-mu-admin.php     # Admin interface & AJAX
│   ├── class-rchg-mu-permissions.php # Permission logic & hooks
│   ├── class-rchg-mu-frontend.php  # Frontend filtering & shortcode
│   └── index.php                    # Security
├── assets/
│   ├── css/
│   │   ├── admin-style.css         # Admin UI styles
│   │   └── frontend-style.css      # Shortcode styles
│   └── js/
│       └── admin-script.js         # AJAX handlers & interactions
└── languages/                       # Translation files
```

== Database Schema

**Table:** `wp_rchg_mu_permissions`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint(20) | Primary key |
| permission_type | varchar(20) | 'role' hoặc 'user' |
| role_name | varchar(100) | Tên role (nếu type='role') |
| user_id | bigint(20) | ID user (nếu type='user') |
| category_id | bigint(20) | ID chuyên mục |
| can_view | tinyint(1) | Quyền xem (0/1) |
| can_edit | tinyint(1) | Quyền sửa (0/1) |
| can_create | tinyint(1) | Quyền tạo (0/1) |
| created_at | datetime | Ngày tạo |
| updated_at | datetime | Ngày cập nhật |

**Indexes:**
- PRIMARY KEY (`id`)
- KEY `permission_type` (`permission_type`)
- KEY `role_name` (`role_name`)
- KEY `user_id` (`user_id`)
- KEY `category_id` (`category_id`)

== Hooks & Filters

=== Actions
- `pre_get_posts` - Lọc bài viết theo permission
- `template_redirect` - Kiểm tra quyền xem single post
- `user_register` - Gán mặc định khi tạo user mới

=== Filters
- `get_terms` - Lọc categories (frontend & admin)
- `map_meta_cap` - Chặn edit/delete post không có quyền
- `user_has_cap` - Chặn create post nếu không có category

== AJAX Endpoints

| Action | Capability | Description |
|--------|------------|-------------|
| `rchg_mu_save_permissions` | manage_options | Lưu phân quyền |
| `rchg_mu_get_permissions` | manage_options | Lấy phân quyền hiện tại |
| `rchg_mu_search_users` | manage_options | Tìm user autocomplete |
| `rchg_mu_export_permissions` | manage_options | Export JSON |
| `rchg_mu_import_permissions` | manage_options | Import JSON |
| `rchg_mu_reset_user_permissions` | manage_options | Reset user về role |

== Yêu cầu hệ thống

- WordPress 5.0 trở lên
- PHP 7.0 trở lên
- MySQL 5.6 trở lên / MariaDB 10.0 trở lên

== Lưu ý kỹ thuật

=== Thứ tự ưu tiên
1. **User-specific permissions** (permission_type='user')
2. **Role-based permissions** (permission_type='role')
3. **Default behavior** (nếu không có cấu hình → cho phép tất cả view)

=== Security
- Tất cả AJAX actions yêu cầu `manage_options` capability
- Nonce verification cho mọi request
- Data sanitization/validation với `sanitize_text_field()`, `intval()`
- Prepared statements cho database queries

=== Performance
- AJAX loading không reload trang
- Database indexes cho query nhanh
- Cache-friendly (không dùng transients)
- Filter removal during get_categories() to prevent infinite loops
- Direct database queries in category filtering for speed

=== Compatibility
- ✅ **SQLite** - Full support via SQLite Database Integration plugin
- ✅ **MySQL** 5.6+ / MariaDB 10.0+
- ✅ **WordPress** 5.0 - 6.9
- ✅ **PHP** 7.0 - 8.3
- ✅ **Modern browsers** (Chrome, Firefox, Safari, Edge)

== Changelog

=== Version 2.0.1 (2025-12-03)
**🐛 Critical Bugfixes & UX Improvements**
- 🐛 **FIXED**: Database schema - SQLite compatibility (removed `ON UPDATE CURRENT_TIMESTAMP`)
- 🐛 **FIXED**: AJAX save permissions - Fixed array sanitization breaking multi-dimensional data
- 🐛 **FIXED**: Infinite loop causing memory exhausted (256MB) when filtering categories
- 🐛 **FIXED**: Parse error from duplicate closing brace in permissions class
- 🐛 **FIXED**: PreparedSQL warning when user has no roles (empty array check)
- 🐛 **FIXED**: Table name variables causing database query failures
- ✨ **NEW**: Modern notification system with color-coded alerts (success/error/warning/info)
- ✨ **NEW**: Button loading states during AJAX operations ("Đang lưu...", "Đang export...")
- ✨ **NEW**: Auto-dismiss notifications after 5 seconds with manual close option
- ✨ **NEW**: File type validation for JSON import
- ✨ **NEW**: Icons in notifications (✓ ✗ ⚠ ℹ) for better UX
- 🔧 **ENHANCED**: Error handling with detailed user feedback
- 🔧 **ENHANCED**: Filter removal/re-addition to prevent recursive loops
- 🔧 **ENHANCED**: Direct database queries in filter_categories() for performance
- 🔧 **ENHANCED**: NULL handling for role_name/user_id fields
- 📝 **DOCS**: Updated with bugfix details and technical notes

=== Version 2.0.0 (2025-12-03)
**🎉 Major Update**
- 🎨 **RENAMED**: Plugin name to "RCHG Role Category Manager" for uniqueness
- ✨ **NEW**: Phân quyền theo User (user-specific override)
- ✨ **NEW**: 3 loại quyền: View / Edit / Create
- ✨ **NEW**: Import/Export JSON
- ✨ **NEW**: Reset user permissions
- ✨ **NEW**: Tab navigation UI
- ✨ **NEW**: User search autocomplete
- 🔒 **ENHANCED**: Deep permission enforcement (map_meta_cap, user_has_cap)
- 🔒 **ENHANCED**: Hide categories in editor based on create permission
- 🔒 **ENHANCED**: Auto-assign defaults on user registration
- 🐛 **FIXED**: WordPress coding standards compliance (SQL, validation, escaping)
- 🐛 **FIXED**: WordPress i18n coding standards compliance
- 🐛 **FIXED**: Ordered placeholders in translations
- 📝 **DOCS**: Complete README update

=== Version 1.0.0 (2025-11-10)
- 🎉 Initial release
- ✅ Role-based category permissions
- ✅ View-only permissions
- ✅ Basic admin UI
- ✅ Frontend filtering
- ✅ Shortcode support

== Hỗ trợ

Nếu bạn gặp vấn đề hoặc có câu hỏi:
1. Kiểm tra file `INSTALL-GUIDE.md`
2. Bật `WP_DEBUG` để xem error logs
3. Kiểm tra Console (F12) trong trình duyệt

== License

GPL v2 or later

== Tác giả

Rồng Con HG - [https://rongcon.net](https://rongcon.net)
