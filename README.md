# GTSCHunder - Nền Tảng Web Đọc Truyện Tranh & Quản Trị Tự Động

Dự án Web Truyện Tranh xây dựng trên nền tảng **Laravel 12**, **TailwindCSS & Vite**, tích hợp lưu trữ đám mây **Cloudinary**, cơ sở dữ liệu **MySQL**, và bộ công cụ độc lập viết bằng **Python** hỗ trợ nén ảnh WebP & đăng chapter tự động.

---

## Hướng Dẫn Cài Đặt Cho Máy Mới (Clone Mới)

thực hiện:

1. Chạy file [`setup_env.bat`]
2. File sẽ tự động thực hiện từ A-Z:
   - ✅ Kiểm tra cài đặt của `PHP`, `Composer`, `Node.js`, `Python`, `Pip`.
   - ✅ Tự động tạo file `.env` và tạo mã bảo mật `APP_KEY`.
   - ✅ Chạy `composer install` để cài đặt thư viện PHP Backend.
   - ✅ Chạy `npm install` & `npm run build` để biên dịch giao diện Frontend.
   - ✅ Cài đặt thư viện Python `Pillow` (cho tool nén WebP).
   - ✅ Liên kết thư mục `public/storage`.
   - ✅ Hỗ trợ khởi tạo CSDL & nạp sẵn dữ liệu mẫu (`php artisan migrate:fresh --seed`).

---

## Các File Chạy Nhanh Trong Thư Mục Gốc

| File Launcher | Chức Năng |
| --- | --- |
| [`setup_env.bat`] | Cài đặt toàn bộ môi trường và dependencies khi clone sang máy mới. |
| [`run.bat`] | Khởi động máy chủ Web Server và tự động mở trình duyệt (`http://127.0.0.1:8000`). |
| [`check_system.bat`] | Mở giao diện Desktop GUI kiểm tra Cloudinary, Database & Đăng Chapter nhanh. |
| [`upload_chapter.bat`] | Mở giao diện dòng lệnh CLI tự động nén ảnh WebP và đăng Chapter lên Cloudinary/DB. |

---

## 🔑 Tài Khoản Mặc Định

- **Trang chủ**: [http://127.0.0.1:8000](http://127.0.0.1:8000)
- **Trang Quản trị**: [http://127.0.0.1:8000/admin](http://127.0.0.1:8000/admin)
- **Tài khoản Quản trị (Admin)**: `admin@gmail.com` / Mật khẩu: `123456`
- **Tài khoản Độc giả (User)**: `user@gmail.com` / Mật khẩu: `123456`
