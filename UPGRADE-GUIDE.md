# HƯỚNG DẪN NÂNG CẤP PLUGIN

## Thay đổi chính:

### 1. Database Schema Mới
Bảng `wp_rchg_mu_permissions` đã được cập nhật:
- `permission_type`: 'role' hoặc 'user'
- `role_name`: Tên role (nếu type='role')
- `user_id`: ID user (nếu type='user')
- `can_view`: Quyền xem (1/0)
- `can_edit`: Quyền sửa bài (1/0)
- `can_create`: Quyền tạo bài mới (1/0)

### 2. Tính năng mới
✅ Phân quyền theo Role (giữ nguyên)
✅ Phân quyền theo User cụ thể (mới)
✅ Phân quyền Xem, Sửa, Tạo bài (mới)
✅ Search user theo tên/email (mới)

### 3. Cách sử dụng

#### Phân quyền theo Role:
1. Vào Phân Quyền → Tab "Theo Vai Trò"
2. Chọn Role (Administrator, Editor, etc.)
3. Chọn categories
4. Chọn quyền: Xem / Sửa / Tạo mới
5. Lưu

#### Phân quyền theo User:
1. Vào Phân Quyền → Tab "Theo User"  
2. Tìm user bằng tên hoặc email
3. Chọn user
4. Chọn categories
5. Chọn quyền: Xem / Sửa / Tạo mới
6. Lưu

### 4. Ưu tiên phân quyền
User permissions > Role permissions

Nếu user có phân quyền riêng → dùng phân quyền user
Nếu không → dùng phân quyền role

### 5. Migration Data
Nếu bạn đã có data cũ, chạy script sau trong MySQL/phpMyAdmin:

```sql
-- Thêm các cột mới vào bảng cũ
ALTER TABLE wp_rchg_mu_permissions 
ADD COLUMN permission_type varchar(20) NOT NULL DEFAULT 'role' AFTER id,
ADD COLUMN user_id bigint(20) unsigned DEFAULT NULL AFTER role_name,
ADD COLUMN can_edit tinyint(1) DEFAULT 0 AFTER can_view,
ADD COLUMN can_create tinyint(1) DEFAULT 0 AFTER can_edit;

-- Update data cũ
UPDATE wp_rchg_mu_permissions SET permission_type = 'role' WHERE permission_type IS NULL;
```

### 6. Deactivate/Activate
Để áp dụng schema mới:
1. Vào Plugins
2. Deactivate "Role Category Manager"
3. Activate lại

Database sẽ tự động cập nhật!

---
**Version: 2.0.0**
**Date: November 10, 2025**
