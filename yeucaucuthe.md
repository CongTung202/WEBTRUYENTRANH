1. khi upload ảnh sẽ được nén về dạng webp sau đó đẩy lên cloudinary và lưu đường dẫn tại database(phân chia thư mục truyện rõ ràng trên cloudinary (vd TruyenA/chap1/1.png))
2. sử dụng mô hình mvc,oop để tối ưu và dễ quản lý
3. sử dụng công nghệ php laravel với php phiên bản 12.
4. yêu cầu đủ chức năng như một web đọc truyện tranh thông thường(webtoon,dmm,mangadex,...)
5. chia rõ admin(admin quản lý truyện) và user(độc giả)
6. dùng lazy hoặc cache để hạn chế băng thông trangweb và hình ảnh
7. tạo giao diện giống webtoon với màu sắc chủ đạo là #506891
8. sử dụng default.png làm ảnh minh hoạ hoặc ảnh lỗi
9. tạo tools giúp tôi đăng các chapter truyện nhanh(qua id truyện chính) từ đường dẫn thư mục(ở máy local để giúp tối ưu thời gian đăng truyện)