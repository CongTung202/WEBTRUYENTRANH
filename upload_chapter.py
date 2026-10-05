#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
GTSCHunder - CLI & Batch Chapter Uploader + WebP Converter
Converts image files into optimized WebP and uploads to Cloudinary & Database.
Runs independently from the web server.
"""

import os
import sys
import re
import time
import json
import base64
import socket
import hashlib
import tempfile
import subprocess
from pathlib import Path
import urllib.request
import urllib.parse
import urllib.error

# Import Pillow for high-speed local WebP conversion
try:
    from PIL import Image
    HAS_PILLOW = True
except ImportError:
    HAS_PILLOW = False


def natural_sort_key(s):
    """Sort strings with numbers naturally: 1, 2, ... 9, 10, 11 (instead of 1, 10, 2)."""
    return [int(text) if text.isdigit() else text.lower() for text in re.split(r'(\d+)', str(s))]


def load_env(filepath=".env"):
    """Parse .env file into dictionary."""
    env = {}
    path = Path(filepath)
    if not path.is_file():
        path = Path(__file__).resolve().parent / ".env"
    if path.is_file():
        with open(path, "r", encoding="utf-8", errors="ignore") as f:
            for line in f:
                line = line.strip()
                if not line or line.startswith("#"):
                    continue
                if "=" in line:
                    k, v = line.split("=", 1)
                    env[k.strip()] = v.strip().strip('"').strip("'")
    return env


class DatabaseHelper:
    """Execute MySQL queries safely via PHP CLI PDO without requiring external MySQL driver."""
    
    @staticmethod
    def get_comics_list(env):
        host = env.get("DB_HOST", "127.0.0.1")
        port = env.get("DB_PORT", "3306")
        dbname = env.get("DB_DATABASE", "webtruyentranh")
        user = env.get("DB_USERNAME", "root")
        password = env.get("DB_PASSWORD", "")

        php_code = f"""<?php
        try {{
            $pdo = new PDO('mysql:host={host};port={port};dbname={dbname};charset=utf8mb4', '{user}', '{password}', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 4
            ]);
            $stmt = $pdo->query("
                SELECT c.id, c.title, c.slug, 
                       COALESCE((SELECT MAX(chapter_number) FROM chapters WHERE comic_id = c.id), 0) AS latest_chap,
                       COALESCE((SELECT COUNT(*) FROM chapters WHERE comic_id = c.id), 0) AS total_chaps
                FROM comics c
                ORDER BY c.title ASC
            ");
            $comics = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'ok', 'comics' => $comics]);
        }} catch (Exception $e) {{
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }}
        """

        try:
            proc = subprocess.run(["php"], input=php_code, text=True, capture_output=True, timeout=8)
            res = json.loads(proc.stdout.strip())
            if res.get("status") == "ok":
                return res.get("comics", [])
        except Exception as e:
            print(f"Lỗi lấy danh sách truyện: {e}")
        return []

    @staticmethod
    def save_chapter(env, comic_id, chapter_number, title, pages_data):
        """Save or update chapter and all its page records in the database."""
        host = env.get("DB_HOST", "127.0.0.1")
        port = env.get("DB_PORT", "3306")
        dbname = env.get("DB_DATABASE", "webtruyentranh")
        user = env.get("DB_USERNAME", "root")
        password = env.get("DB_PASSWORD", "")

        payload = {
            "comic_id": comic_id,
            "chapter_number": chapter_number,
            "title": title,
            "slug": f"chap-{chapter_number}".replace(".", "-"),
            "pages": pages_data
        }
        json_payload = json.dumps(payload).replace('\\', '\\\\').replace("'", "\\'")

        php_code = f"""<?php
        try {{
            $pdo = new PDO('mysql:host={host};port={port};dbname={dbname};charset=utf8mb4', '{user}', '{password}', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10
            ]);
            $pdo->beginTransaction();

            $data = json_decode('{json_payload}', true);
            $comicId = (int)$data['comic_id'];
            $chapNum = (float)$data['chapter_number'];
            $title = $data['title'];
            $slug = $data['slug'];

            // 1. Find or create Chapter
            $stmt = $pdo->prepare("SELECT id FROM chapters WHERE comic_id = ? AND chapter_number = ? LIMIT 1");
            $stmt->execute([$comicId, $chapNum]);
            $chapId = $stmt->fetchColumn();

            if ($chapId) {{
                $upd = $pdo->prepare("UPDATE chapters SET title = ?, slug = ?, updated_at = NOW() WHERE id = ?");
                $upd->execute([$title, $slug, $chapId]);
                
                // Remove old pages if re-uploading
                $del = $pdo->prepare("DELETE FROM chapter_pages WHERE chapter_id = ?");
                $del->execute([$chapId]);
            }} else {{
                $ins = $pdo->prepare("INSERT INTO chapters (comic_id, chapter_number, title, slug, views, created_at, updated_at) VALUES (?, ?, ?, ?, 0, NOW(), NOW())");
                $ins->execute([$comicId, $chapNum, $title, $slug]);
                $chapId = $pdo->lastInsertId();
            }}

            // 2. Insert pages
            $insPage = $pdo->prepare("INSERT INTO chapter_pages (chapter_id, page_number, image_url, cloudinary_public_id, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
            foreach ($data['pages'] as $p) {{
                $insPage->execute([$chapId, (int)$p['page_number'], $p['image_url'], $p['cloudinary_public_id']]);
            }}

            // 3. Update comic updated_at
            $updComic = $pdo->prepare("UPDATE comics SET updated_at = NOW() WHERE id = ?");
            $updComic->execute([$comicId]);

            $pdo->commit();
            echo json_encode(['status' => 'ok', 'chapter_id' => $chapId]);
        }} catch (Exception $e) {{
            if (isset($pdo)) $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }}
        """

        try:
            proc = subprocess.run(["php"], input=php_code, text=True, capture_output=True, timeout=12)
            res = json.loads(proc.stdout.strip())
            return res
        except Exception as e:
            return {"status": "error", "message": str(e)}


class CloudinaryUploader:
    """Upload files to Cloudinary REST API."""

    @staticmethod
    def upload_image_file(file_path, folder, public_id, cloud_name, api_key, api_secret, timeout=30):
        """Upload a local image file to Cloudinary with SHA1 signature."""
        timestamp = str(int(time.time()))
        folder_clean = folder.strip('/')
        
        # Read file bytes & base64 encode
        with open(file_path, "rb") as f:
            file_bytes = f.read()
        b64_data = "data:image/webp;base64," + base64.b64encode(file_bytes).decode("utf-8")

        # Parameters to send & sign (All parameters except file, api_key, signature must be signed alphabetically)
        params = {
            "folder": folder_clean,
            "overwrite": "true",
            "public_id": public_id,
            "timestamp": timestamp
        }

        # Generate Signature by sorting keys alphabetically
        sorted_pairs = sorted(params.items())
        to_sign = "&".join([f"{k}={v}" for k, v in sorted_pairs]) + api_secret
        signature = hashlib.sha1(to_sign.encode("utf-8")).hexdigest()

        upload_url = f"https://api.cloudinary.com/v1_1/{cloud_name}/image/upload"
        form_data = urllib.parse.urlencode({
            "file": b64_data,
            "api_key": api_key,
            "signature": signature,
            **params
        }).encode("utf-8")

        req = urllib.request.Request(upload_url, data=form_data, headers={"User-Agent": "GTSCHunder-CLI/1.0"})
        try:
            with urllib.request.urlopen(req, timeout=timeout) as resp:
                data = json.loads(resp.read().decode("utf-8"))
                return {
                    "url": data.get("secure_url", data.get("url", "")),
                    "public_id": data.get("public_id", ""),
                    "width": data.get("width"),
                    "height": data.get("height"),
                    "bytes": data.get("bytes")
                }
        except urllib.error.HTTPError as err:
            err_body = ""
            try:
                err_body = err.read().decode("utf-8")
            except Exception:
                pass
            raise Exception(f"Cloudinary HTTP {err.code}: {err.reason} - {err_body}")


class ChapterUploadManager:
    """Coordinate WebP compression, sorting, uploading, and database saving."""

    ALLOWED_EXTENSIONS = {'.jpg', '.jpeg', '.png', '.webp', '.bmp', '.gif', '.avif', '.jfif'}

    @classmethod
    def scan_directory(cls, dir_path):
        """Scan folder and return naturally sorted list of image file paths."""
        folder = Path(dir_path)
        if not folder.is_dir():
            raise ValueError(f"Thư mục không tồn tại: {dir_path}")

        images = []
        for p in folder.iterdir():
            if p.is_file() and p.suffix.lower() in cls.ALLOWED_EXTENSIONS:
                images.append(p)

        images.sort(key=lambda x: natural_sort_key(x.name))
        return images

    @classmethod
    def convert_to_webp(cls, src_path, dest_path, quality=85):
        """Convert any image to WebP with Pillow."""
        if not HAS_PILLOW:
            # Fallback copy if Pillow not available
            import shutil
            shutil.copy2(src_path, dest_path)
            return

        with Image.open(src_path) as img:
            # Handle alpha channel
            if img.mode in ("RGBA", "LA", "P"):
                if "transparency" in img.info or img.mode == "RGBA":
                    img = img.convert("RGBA")
                else:
                    img = img.convert("RGB")
            else:
                img = img.convert("RGB")

            img.save(dest_path, "WEBP", quality=quality, method=4)


# ==============================================================================
# Interactive CLI Runner
# ==============================================================================
def render_progress_bar(current, total, width=32):
    """Draw a console progress bar."""
    percent = float(current) / total
    filled = int(width * percent)
    bar = '█' * filled + '░' * (width - filled)
    return f"[{bar}] {int(percent * 100)}% ({current}/{total})"


def run_interactive_cli():
    print("=" * 70)
    print("  GTSCHUNDER - TOOL ĐĂNG CHAPTER TỰ ĐỘNG & NÉN WEBP")
    print("=" * 70)

    env = load_env()
    cloud_name = env.get("CLOUDINARY_CLOUD_NAME", "")
    api_key = env.get("CLOUDINARY_API_KEY", "")
    api_secret = env.get("CLOUDINARY_API_SECRET", "")

    if not cloud_name or not api_key or not api_secret:
        print("\n❌ LỖI: Chưa cấu hình đầy đủ Cloudinary trong .env!")
        input("Nhấn Enter để thoát...")
        return

    # 1. Fetch Comics List
    print("\n🔍 Đang tải danh sách bộ truyện từ CSDL...")
    comics = DatabaseHelper.get_comics_list(env)
    if not comics:
        print("❌ Không lấy được danh sách truyện từ CSDL. Vui lòng kiểm tra lại kết nối MySQL!")
        input("Nhấn Enter để thoát...")
        return

    print("\n--- DANH SÁCH BỘ TRUYỆN HIỆN CÓ ---")
    for idx, c in enumerate(comics, 1):
        latest = f"Chap {c['latest_chap']}" if c['latest_chap'] else "Chưa có chapter"
        print(f"  [{idx:2d}] ID: {c['id']:<3} | {c['title']:<35} (Đang có: {latest})")

    # Select Comic
    selected_comic = None
    while not selected_comic:
        try:
            choice = input(f"\n👉 Chọn số thứ tự (1-{len(comics)}) hoặc nhập ID truyện: ").strip()
            if choice.isdigit():
                num = int(choice)
                if 1 <= num <= len(comics):
                    selected_comic = comics[num - 1]
                else:
                    matched = [c for c in comics if c['id'] == num]
                    if matched:
                        selected_comic = matched[0]
            if not selected_comic:
                print("⚠️ Lựa chọn không hợp lệ, vui lòng thử lại.")
        except KeyboardInterrupt:
            print("\nĐã huỷ thao tác.")
            return

    suggested_next = float(selected_comic['latest_chap']) + 1 if selected_comic['latest_chap'] else 1
    if suggested_next.is_integer():
        suggested_next = int(suggested_next)

    print(f"\n✅ Đã chọn bộ truyện: '{selected_comic['title']}' (Slug: {selected_comic['slug']})")

    # Input Chapter Number
    chap_num_str = input(f"👉 Nhập số Chapter cần đăng [Gợi ý: {suggested_next}]: ").strip()
    if not chap_num_str:
        chap_number = suggested_next
    else:
        try:
            chap_number = float(chap_num_str)
            if chap_number.is_integer():
                chap_number = int(chap_number)
        except ValueError:
            print("❌ Số Chapter không hợp lệ!")
            return

    # Input Chapter Title
    chap_title = input(f"👉 Nhập tiêu đề Chapter (tuỳ chọn, bấm Enter để để trống): ").strip()
    if not chap_title:
        chap_title = f"Chapter {chap_number}"

    # Input Local Folder
    images = []
    while not images:
        dir_path = input("👉 Kéo thả hoặc nhập đường dẫn thư mục ảnh trên máy: ").strip().strip('"').strip("'")
        if not dir_path:
            print("Đã huỷ thao tác.")
            return
        try:
            images = ChapterUploadManager.scan_directory(dir_path)
            if not images:
                print(f"⚠️ Không tìm thấy file ảnh nào trong '{dir_path}'")
        except Exception as e:
            print(f"❌ {e}")

    print(f"\n📸 Tìm thấy {len(images)} trang ảnh. Danh sách thứ tự trang:")
    for i, img in enumerate(images[:5], 1):
        print(f"   Trang {i:02d}: {img.name}")
    if len(images) > 5:
        print(f"   ... và {len(images) - 5} trang tiếp theo đến {images[-1].name}")

    # Confirm before starting
    confirm = input(f"\n🚀 Bắt đầu nén WebP và tải lên {len(images)} trang cho '{selected_comic['title']}' (Chap {chap_number})? (y/n): ").strip().lower()
    if confirm != 'y' and confirm != 'yes':
        print("Đã huỷ đăng chapter.")
        return

    # Process & Upload
    print("\n" + "=" * 70)
    print(f"  ĐANG XỬ LÝ & TẢI LÊN CLOUDINARY (Folder: webtruyentranh/{selected_comic['slug']}/chap{chap_number})")
    print("=" * 70 + "\n")

    pages_data = []
    total_pages = len(images)
    folder_dest = f"webtruyentranh/{selected_comic['slug']}/chap{chap_number}"

    start_time = time.time()
    with tempfile.TemporaryDirectory() as temp_dir:
        for idx, img_path in enumerate(images, 1):
            page_num_str = f"{idx:03d}"
            webp_temp_path = os.path.join(temp_dir, f"page_{page_num_str}.webp")

            # 1. Convert to WebP
            t_conv = time.time()
            ChapterUploadManager.convert_to_webp(str(img_path), webp_temp_path, quality=85)
            orig_size = img_path.stat().st_size
            webp_size = os.path.getsize(webp_temp_path)
            saved_pct = round((1 - webp_size / orig_size) * 100, 1) if orig_size > 0 else 0

            # 2. Upload to Cloudinary
            t_up = time.time()
            public_id = f"page_{page_num_str}"
            try:
                upload_res = CloudinaryUploader.upload_image_file(
                    webp_temp_path,
                    folder_dest,
                    public_id,
                    cloud_name,
                    api_key,
                    api_secret
                )
                pages_data.append({
                    "page_number": idx,
                    "image_url": upload_res["url"],
                    "cloudinary_public_id": upload_res["public_id"]
                })

                elapsed = round(time.time() - t_up, 2)
                pbar = render_progress_bar(idx, total_pages)
                print(f"{pbar} | Trang {idx:02d}: {img_path.name} -> WebP (-{saved_pct}%) -> OK ({elapsed}s)")
            except Exception as e:
                print(f"❌ Lỗi tải trang {idx} ({img_path.name}): {e}")

    # 3. Save to Database
    print("\n💾 Đang lưu dữ liệu Chapter vào CSDL MySQL...")
    db_res = DatabaseHelper.save_chapter(env, selected_comic["id"], chap_number, chap_title, pages_data)

    total_time = round(time.time() - start_time, 2)
    print("\n" + "=" * 70)
    if db_res.get("status") == "ok":
        print(f"  🎉 ĐĂNG CHAPTER THÀNH CÔNG RỰC RỠ TRONG {total_time} GIÂY!")
        print("=" * 70)
        print(f"  - Bộ truyện:      {selected_comic['title']}")
        print(f"  - Chapter:        Chap {chap_number} ({chap_title})")
        print(f"  - Số trang tải:   {len(pages_data)} / {total_pages} trang")
        print(f"  - Cloudinary Dir: {folder_dest}")
        print(f"  - Đọc trực tiếp:  http://127.0.0.1:8000/truyen/{selected_comic['slug']}/chap-{chap_number}")
    else:
        print(f"  ⚠️ ĐÃ TẢI LÊN ẢNH NHƯNG GẶP LỖI LƯU CSDL: {db_res.get('message')}")
    print("=" * 70 + "\n")

    input("Nhấn Enter để hoàn tất...")


if __name__ == "__main__":
    run_interactive_cli()
