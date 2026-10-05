#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
GTSCHunder - GUI & CLI Fast Chapter Uploader + WebP Converter
Converts image files into optimized WebP and uploads to Cloudinary & MySQL Database.
Supports standalone Desktop GUI (Tkinter) and Headless CLI mode.
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
import threading
import webbrowser
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

# Import Tkinter for GUI
try:
    import tkinter as tk
    from tkinter import ttk, messagebox, filedialog
    HAS_TKINTER = True
except ImportError:
    HAS_TKINTER = False


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
# Modern Desktop GUI (Tkinter)
# ==============================================================================
class ChapterUploaderGUI:
    def __init__(self, root):
        self.root = root
        self.root.title("GTSCHunder - Tool Đăng Chapter Tự Động & Nén WebP")
        self.root.geometry("900x740")
        self.root.minsize(820, 660)

        # Palette
        self.C_BG = "#0b0f17"
        self.C_CARD = "#161e2c"
        self.C_CARD_LIGHT = "#1c2536"
        self.C_BORDER = "#232f42"
        self.C_PRIMARY = "#506891"
        self.C_PRIMARY_HOVER = "#3d5173"
        self.C_TEXT = "#f8fafc"
        self.C_TEXT_MUTED = "#94a3b8"
        self.C_SUCCESS = "#10b981"
        self.C_DANGER = "#ef4444"
        self.C_WARNING = "#f59e0b"

        self.root.configure(bg=self.C_BG)

        self.env = load_env()
        self.comics_list = []
        self.scanned_images = []
        self.is_uploading = False
        self.cancel_requested = False
        self.success_url = None

        self._build_ui()
        self.refresh_comics()

    def _build_ui(self):
        # 1. Header Banner
        header = tk.Frame(self.root, bg=self.C_CARD, bd=0, highlightthickness=1, highlightbackground=self.C_BORDER)
        header.pack(fill=tk.X, padx=16, pady=(14, 10))

        h_inner = tk.Frame(header, bg=self.C_CARD)
        h_inner.pack(fill=tk.X, padx=16, pady=12)

        title_lbl = tk.Label(
            h_inner,
            text="⚡ GTSCHUNDER - ĐĂNG CHAPTER & NÉN WEBP",
            font=("Segoe UI", 13, "bold"),
            fg=self.C_TEXT,
            bg=self.C_CARD
        )
        title_lbl.pack(side=tk.LEFT)

        # Badges
        cname = self.env.get("CLOUDINARY_CLOUD_NAME", "Chưa cấu hình")
        badge_cloud = tk.Label(
            h_inner,
            text=f"☁ Cloud: {cname}",
            font=("Segoe UI", 8, "bold"),
            fg="#93c5fd",
            bg="#172554",
            padx=8,
            pady=3
        )
        badge_cloud.pack(side=tk.RIGHT, padx=4)

        webp_text = "✓ Pillow WebP OK" if HAS_PILLOW else "⚠ Chưa có Pillow"
        webp_color = self.C_SUCCESS if HAS_PILLOW else self.C_WARNING
        badge_webp = tk.Label(
            h_inner,
            text=webp_text,
            font=("Segoe UI", 8, "bold"),
            fg=webp_color,
            bg="#064e3b" if HAS_PILLOW else "#78350f",
            padx=8,
            pady=3
        )
        badge_webp.pack(side=tk.RIGHT, padx=4)

        # 2. Form Card
        form_card = tk.Frame(self.root, bg=self.C_CARD, highlightthickness=1, highlightbackground=self.C_BORDER)
        form_card.pack(fill=tk.X, padx=16, pady=6)

        f_inner = tk.Frame(form_card, bg=self.C_CARD)
        f_inner.pack(fill=tk.X, padx=16, pady=14)

        # Row 1: Chọn bộ truyện
        r1 = tk.Frame(f_inner, bg=self.C_CARD)
        r1.pack(fill=tk.X, pady=4)

        tk.Label(r1, text="Bộ truyện:", font=("Segoe UI", 9, "bold"), fg=self.C_TEXT, bg=self.C_CARD, width=14, anchor="w").pack(side=tk.LEFT)

        self.cb_comic = ttk.Combobox(r1, state="readonly", font=("Segoe UI", 9))
        self.cb_comic.pack(side=tk.LEFT, fill=tk.X, expand=True, padx=(0, 8))
        self.cb_comic.bind("<<ComboboxSelected>>", self._on_comic_selected)

        btn_refresh = tk.Button(
            r1,
            text="🔄 Làm mới",
            font=("Segoe UI", 8, "bold"),
            bg=self.C_CARD_LIGHT,
            fg=self.C_TEXT,
            activebackground=self.C_PRIMARY,
            bd=0,
            padx=10,
            pady=4,
            command=self.refresh_comics,
            cursor="hand2"
        )
        btn_refresh.pack(side=tk.RIGHT)

        # Row 2: Số chapter & Tiêu đề
        r2 = tk.Frame(f_inner, bg=self.C_CARD)
        r2.pack(fill=tk.X, pady=4)

        tk.Label(r2, text="Số Chapter:", font=("Segoe UI", 9, "bold"), fg=self.C_TEXT, bg=self.C_CARD, width=14, anchor="w").pack(side=tk.LEFT)
        self.ent_chap_num = tk.Entry(r2, font=("Segoe UI", 9), bg="#0d131c", fg=self.C_TEXT, insertbackground="white", bd=1, relief=tk.SOLID, width=12)
        self.ent_chap_num.pack(side=tk.LEFT, padx=(0, 16))

        tk.Label(r2, text="Tiêu đề chap:", font=("Segoe UI", 9, "bold"), fg=self.C_TEXT, bg=self.C_CARD, width=12, anchor="w").pack(side=tk.LEFT)
        self.ent_chap_title = tk.Entry(r2, font=("Segoe UI", 9), bg="#0d131c", fg=self.C_TEXT, insertbackground="white", bd=1, relief=tk.SOLID)
        self.ent_chap_title.pack(side=tk.LEFT, fill=tk.X, expand=True)

        # Row 3: Thư mục ảnh
        r3 = tk.Frame(f_inner, bg=self.C_CARD)
        r3.pack(fill=tk.X, pady=4)

        tk.Label(r3, text="Thư mục ảnh:", font=("Segoe UI", 9, "bold"), fg=self.C_TEXT, bg=self.C_CARD, width=14, anchor="w").pack(side=tk.LEFT)
        self.ent_folder = tk.Entry(r3, font=("Segoe UI", 9), bg="#0d131c", fg=self.C_TEXT, insertbackground="white", bd=1, relief=tk.SOLID)
        self.ent_folder.pack(side=tk.LEFT, fill=tk.X, expand=True, padx=(0, 8))
        self.ent_folder.bind("<KeyRelease>", lambda e: self._on_folder_change())

        btn_browse = tk.Button(
            r3,
            text="📁 Duyệt thư mục...",
            font=("Segoe UI", 8, "bold"),
            bg=self.C_PRIMARY,
            fg="white",
            activebackground=self.C_PRIMARY_HOVER,
            bd=0,
            padx=12,
            pady=4,
            command=self._browse_folder,
            cursor="hand2"
        )
        btn_browse.pack(side=tk.RIGHT)

        # Row 4: Tùy chọn & Thống kê ảnh
        r4 = tk.Frame(f_inner, bg=self.C_CARD)
        r4.pack(fill=tk.X, pady=(6, 0))

        self.lbl_scan_info = tk.Label(
            r4,
            text="ℹ Vui lòng chọn bộ truyện và thư mục chứa ảnh chapter.",
            font=("Segoe UI", 8, "italic"),
            fg=self.C_TEXT_MUTED,
            bg=self.C_CARD
        )
        self.lbl_scan_info.pack(side=tk.LEFT)

        tk.Label(r4, text="Chất lượng WebP:", font=("Segoe UI", 8), fg=self.C_TEXT_MUTED, bg=self.C_CARD).pack(side=tk.LEFT, padx=(20, 4))
        self.sp_quality = tk.Spinbox(r4, from_=50, to=100, increment=5, width=4, font=("Segoe UI", 8), bg="#0d131c", fg="white", bd=1)
        self.sp_quality.delete(0, "end")
        self.sp_quality.insert(0, "85")
        self.sp_quality.pack(side=tk.LEFT)
        tk.Label(r4, text="%", font=("Segoe UI", 8), fg=self.C_TEXT_MUTED, bg=self.C_CARD).pack(side=tk.LEFT)

        # Action Buttons
        btn_bar = tk.Frame(f_inner, bg=self.C_CARD)
        btn_bar.pack(fill=tk.X, pady=(12, 0))

        self.btn_upload = tk.Button(
            btn_bar,
            text="🚀 BẮT ĐẦU NÉN WEBP & ĐĂNG CHAPTER",
            font=("Segoe UI", 10, "bold"),
            bg="#059669",
            fg="white",
            activebackground="#047857",
            bd=0,
            padx=20,
            pady=8,
            command=self.start_upload,
            cursor="hand2"
        )
        self.btn_upload.pack(side=tk.LEFT, fill=tk.X, expand=True, padx=(0, 6))

        self.btn_cancel = tk.Button(
            btn_bar,
            text="🛑 Dừng lại",
            font=("Segoe UI", 9, "bold"),
            bg="#991b1b",
            fg="white",
            bd=0,
            padx=14,
            pady=8,
            state="disabled",
            command=self.cancel_upload,
            cursor="hand2"
        )
        self.btn_cancel.pack(side=tk.RIGHT)

        # 3. Progress Card
        prog_card = tk.Frame(self.root, bg=self.C_CARD, highlightthickness=1, highlightbackground=self.C_BORDER)
        prog_card.pack(fill=tk.X, padx=16, pady=6)

        p_inner = tk.Frame(prog_card, bg=self.C_CARD)
        p_inner.pack(fill=tk.X, padx=16, pady=10)

        # Progress bar
        p_head = tk.Frame(p_inner, bg=self.C_CARD)
        p_head.pack(fill=tk.X, pady=(0, 4))

        self.lbl_status = tk.Label(p_head, text="Sẵn sàng", font=("Segoe UI", 9, "bold"), fg=self.C_TEXT, bg=self.C_CARD)
        self.lbl_status.pack(side=tk.LEFT)

        self.lbl_pct = tk.Label(p_head, text="0%", font=("Segoe UI", 10, "bold"), fg="#34d399", bg=self.C_CARD)
        self.lbl_pct.pack(side=tk.RIGHT)

        style = ttk.Style()
        style.theme_use('clam')
        style.configure("Green.Horizontal.TProgressbar", foreground='#10b981', background='#10b981', troughcolor='#0d131c', bordercolor=self.C_BORDER)
        
        self.pbar = ttk.Progressbar(p_inner, style="Green.Horizontal.TProgressbar", mode='determinate')
        self.pbar.pack(fill=tk.X, pady=(0, 4))

        # 4. Log Console Card
        log_card = tk.Frame(self.root, bg=self.C_CARD, highlightthickness=1, highlightbackground=self.C_BORDER)
        log_card.pack(fill=tk.BOTH, expand=True, padx=16, pady=(6, 12))

        l_head = tk.Frame(log_card, bg=self.C_CARD)
        l_head.pack(fill=tk.X, padx=12, pady=(8, 4))

        tk.Label(l_head, text="📋 Nhật Ký Xử Lý Chi Tiết (Live Logs):", font=("Segoe UI", 9, "bold"), fg=self.C_TEXT_MUTED, bg=self.C_CARD).pack(side=tk.LEFT)

        self.btn_open_web = tk.Button(
            l_head,
            text="🌐 Xem trên Website",
            font=("Segoe UI", 8, "bold"),
            bg=self.C_PRIMARY,
            fg="white",
            bd=0,
            padx=10,
            pady=3,
            state="disabled",
            command=self._open_web_chapter,
            cursor="hand2"
        )
        self.btn_open_web.pack(side=tk.RIGHT)

        # Text Console with Scrollbar
        log_frame = tk.Frame(log_card, bg="#080c14")
        log_frame.pack(fill=tk.BOTH, expand=True, padx=12, pady=(0, 10))

        self.txt_log = tk.Text(
            log_frame,
            bg="#080c14",
            fg="#cbd5e1",
            insertbackground="white",
            font=("Consolas", 9),
            bd=0,
            wrap=tk.WORD
        )
        scrollbar = tk.Scrollbar(log_frame, command=self.txt_log.yview, bg=self.C_CARD_LIGHT)
        self.txt_log.configure(yscrollcommand=scrollbar.set)

        scrollbar.pack(side=tk.RIGHT, fill=tk.Y)
        self.txt_log.pack(side=tk.LEFT, fill=tk.BOTH, expand=True)

        # Log tags
        self.txt_log.tag_config("info", foreground="#93c5fd")
        self.txt_log.tag_config("ok", foreground="#34d399")
        self.txt_log.tag_config("warn", foreground="#fbbf24")
        self.txt_log.tag_config("err", foreground="#f87171")
        self.txt_log.tag_config("head", foreground="#f472b6", font=("Consolas", 9, "bold"))

        self.log("GTSCHunder Chapter Uploader đã sẵn sàng.", "info")

    def log(self, text, tag="info"):
        self.txt_log.insert(tk.END, text + "\n", tag)
        self.txt_log.see(tk.END)

    def refresh_comics(self):
        self.log("Đang tải danh sách bộ truyện từ MySQL...", "info")
        self.comics_list = DatabaseHelper.get_comics_list(self.env)
        if not self.comics_list:
            self.log("⚠️ Không lấy được danh sách truyện. Vui lòng kiểm tra MySQL và cấu hình .env!", "warn")
            self.cb_comic['values'] = ["(Không thể kết nối CSDL)"]
            return

        items = []
        for c in self.comics_list:
            latest = f"Chap {c['latest_chap']}" if c['latest_chap'] else "Chưa có chap"
            items.append(f"[{c['id']}] {c['title']} ({latest})")

        self.cb_comic['values'] = items
        if items:
            self.cb_comic.current(0)
            self._on_comic_selected(None)
        self.log(f"✓ Đã tải {len(self.comics_list)} bộ truyện từ CSDL.", "ok")

    def _on_comic_selected(self, event):
        idx = self.cb_comic.current()
        if 0 <= idx < len(self.comics_list):
            c = self.comics_list[idx]
            # Auto suggest next chapter number
            try:
                latest = float(c['latest_chap']) if c['latest_chap'] else 0
                next_chap = int(latest + 1) if (latest + 1).is_integer() else round(latest + 1, 1)
                self.ent_chap_num.delete(0, tk.END)
                self.ent_chap_num.insert(0, str(next_chap))
            except Exception:
                pass

    def _browse_folder(self):
        folder = filedialog.askdirectory(title="Chọn thư mục chứa ảnh Chapter")
        if folder:
            self.ent_folder.delete(0, tk.END)
            self.ent_folder.insert(0, folder)
            self._on_folder_change()

    def _on_folder_change(self):
        folder = self.ent_folder.get().strip().strip('"').strip("'")
        if not folder or not os.path.isdir(folder):
            self.lbl_scan_info.config(text="ℹ Thư mục không hợp lệ hoặc chưa chọn.", fg=self.C_TEXT_MUTED)
            self.scanned_images = []
            return

        try:
            self.scanned_images = ChapterUploadManager.scan_directory(folder)
            total = len(self.scanned_images)
            if total > 0:
                self.lbl_scan_info.config(
                    text=f"✓ Tìm thấy {total} trang ảnh hợp lệ (Sắp xếp tự nhiên từ {self.scanned_images[0].name} đến {self.scanned_images[-1].name})",
                    fg=self.C_SUCCESS
                )
            else:
                self.lbl_scan_info.config(text="⚠️ Thư mục không có file ảnh JPG/PNG/WEBP nào!", fg=self.C_WARNING)
        except Exception as e:
            self.lbl_scan_info.config(text=f"❌ {e}", fg=self.C_DANGER)

    def cancel_upload(self):
        if self.is_uploading:
            self.cancel_requested = True
            self.log("\n🛑 Đã gửi yêu cầu dừng quá trình tải lên...", "warn")
            self.btn_cancel.config(state="disabled")

    def _open_web_chapter(self):
        if self.success_url:
            webbrowser.open(self.success_url)

    def start_upload(self):
        if self.is_uploading:
            return

        # Validate
        c_idx = self.cb_comic.current()
        if c_idx < 0 or c_idx >= len(self.comics_list):
            messagebox.showerror("Lỗi", "Vui lòng chọn bộ truyện!")
            return

        comic = self.comics_list[c_idx]
        chap_num_str = self.ent_chap_num.get().strip()
        if not chap_num_str:
            messagebox.showerror("Lỗi", "Vui lòng nhập số chapter!")
            return

        try:
            chap_num = float(chap_num_str)
        except ValueError:
            messagebox.showerror("Lỗi", "Số chapter phải là số (VD: 10 hoặc 10.5)!")
            return

        chap_title = self.ent_chap_title.get().strip()
        folder = self.ent_folder.get().strip().strip('"').strip("'")

        if not folder or not os.path.isdir(folder):
            messagebox.showerror("Lỗi", "Thư mục ảnh không tồn tại!")
            return

        try:
            images = ChapterUploadManager.scan_directory(folder)
            if not images:
                messagebox.showerror("Lỗi", "Thư mục không chứa file ảnh nào!")
                return
        except Exception as e:
            messagebox.showerror("Lỗi", str(e))
            return

        try:
            quality = int(self.sp_quality.get())
        except ValueError:
            quality = 85

        cloud_name = self.env.get("CLOUDINARY_CLOUD_NAME", "")
        api_key = self.env.get("CLOUDINARY_API_KEY", "")
        api_secret = self.env.get("CLOUDINARY_API_SECRET", "")

        if not cloud_name or not api_key or not api_secret:
            messagebox.showerror("Lỗi", "Chưa cấu hình đầy đủ Cloudinary trong .env!")
            return

        # Start thread
        self.is_uploading = True
        self.cancel_requested = False
        self.btn_upload.config(state="disabled", bg="#374151")
        self.btn_cancel.config(state="normal")
        self.btn_open_web.config(state="disabled")
        self.pbar['value'] = 0
        self.lbl_pct.config(text="0%")

        t = threading.Thread(
            target=self._worker_upload,
            args=(comic, chap_num, chap_title, images, quality, cloud_name, api_key, api_secret),
            daemon=True
        )
        t.start()

    def _worker_upload(self, comic, chap_num, chap_title, images, quality, cloud_name, api_key, api_secret):
        total_pages = len(images)
        folder_dest = f"webtruyentranh/{comic['slug']}/chap{chap_num}"
        pages_data = []

        self.log(f"\n==================================================================", "head")
        self.log(f"🚀 BẮT ĐẦU XỬ LÝ: {comic['title']} (Chap {chap_num})", "head")
        self.log(f"   - Tổng số ảnh:  {total_pages} trang", "info")
        self.log(f"   - Thư mục Cloud: {folder_dest}", "info")
        self.log(f"   - Nén WebP Quality: {quality}%", "info")
        self.log(f"==================================================================", "head")

        start_time = time.time()

        try:
            with tempfile.TemporaryDirectory() as temp_dir:
                for idx, img_path in enumerate(images, 1):
                    if self.cancel_requested:
                        self.log("\n⚠️ Quá trình đã bị người dùng huỷ bỏ.", "warn")
                        break

                    pct = int(((idx - 1) / total_pages) * 100)
                    self.root.after(0, lambda p=pct, i=idx, t=total_pages: self._update_progress(p, f"Đang xử lý trang {i}/{t}..."))

                    page_num_str = f"{idx:03d}"
                    webp_temp_path = os.path.join(temp_dir, f"page_{page_num_str}.webp")

                    # 1. Convert to WebP
                    t_conv = time.time()
                    ChapterUploadManager.convert_to_webp(str(img_path), webp_temp_path, quality=quality)
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
                        self.log(f"  [Trang {idx:02d}/{total_pages:02d}] {img_path.name} -> WebP (-{saved_pct}%) -> Tải lên Cloud OK ({elapsed}s)", "ok")
                    except Exception as e:
                        self.log(f"  ❌ Lỗi tải trang {idx} ({img_path.name}): {e}", "err")

            if not self.cancel_requested and pages_data:
                # 3. Save to Database
                self.root.after(0, lambda: self._update_progress(95, "Đang lưu thông tin Chapter vào CSDL MySQL..."))
                self.log("\n💾 Đang lưu dữ liệu Chapter vào CSDL MySQL...", "info")
                db_res = DatabaseHelper.save_chapter(self.env, comic["id"], chap_num, chap_title, pages_data)

                total_time = round(time.time() - start_time, 2)
                if db_res.get("status") == "ok":
                    chap_slug = f"chap-{chap_num}".replace(".", "-")
                    self.success_url = f"http://127.0.0.1:8000/truyen/{comic['slug']}/{chap_slug}"
                    self.log(f"\n==================================================================", "ok")
                    self.log(f"🎉 ĐĂNG CHAPTER THÀNH CÔNG RỰC RỠ TRONG {total_time} GIÂY!", "ok")
                    self.log(f"  - Số trang: {len(pages_data)}/{total_pages}", "ok")
                    self.log(f"  - Xem ngay: {self.success_url}", "ok")
                    self.log(f"==================================================================", "ok")
                    self.root.after(0, lambda: self.btn_open_web.config(state="normal"))
                    self.root.after(0, lambda: messagebox.showinfo("Thành Công", f"Đăng Chapter {chap_num} cho truyện '{comic['title']}' thành công!"))
                else:
                    self.log(f"❌ Lỗi lưu CSDL: {db_res.get('message')}", "err")
                    self.root.after(0, lambda: messagebox.showwarning("Cảnh Báo", f"Ảnh đã tải lên Cloudinary nhưng lưu CSDL lỗi: {db_res.get('message')}"))

        except Exception as e:
            self.log(f"❌ Lỗi không xác định: {e}", "err")
        finally:
            self.root.after(0, self._upload_finished)

    def _update_progress(self, pct, status_text):
        self.pbar['value'] = pct
        self.lbl_pct.config(text=f"{pct}%")
        self.lbl_status.config(text=status_text)

    def _upload_finished(self):
        self.is_uploading = False
        self.btn_upload.config(state="normal", bg="#059669")
        self.btn_cancel.config(state="disabled")
        if not self.cancel_requested:
            self.pbar['value'] = 100
            self.lbl_pct.config(text="100%")
            self.lbl_status.config(text="Hoàn tất!")
        else:
            self.lbl_status.config(text="Đã huỷ thao tác.")


# ==============================================================================
# Interactive CLI Fallback Mode
# ==============================================================================
def render_progress_bar(current, total, width=32):
    """Draw a console progress bar."""
    percent = float(current) / total
    filled = int(width * percent)
    bar = '█' * filled + '░' * (width - filled)
    return f"[{bar}] {int(percent * 100)}% ({current}/{total})"


def run_interactive_cli():
    print("=" * 70)
    print("  GTSCHUNDER - TOOL ĐĂNG CHAPTER TỰ ĐỘNG & NÉN WEBP (CLI)")
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
                    for c in comics:
                        if c["id"] == num:
                            selected_comic = c
                            break
            if not selected_comic:
                print("Lựa chọn không hợp lệ, vui lòng thử lại!")
        except (KeyboardInterrupt, EOFError):
            print("\nĐã hủy.")
            return

    print(f"\n✓ Đã chọn bộ truyện: {selected_comic['title']} (ID: {selected_comic['id']})")

    # Chapter Number
    chap_number = None
    while chap_number is None:
        try:
            raw = input("👉 Nhập số Chapter cần đăng (VD: 1, 2, 10.5): ").strip()
            chap_number = float(raw)
            if chap_number.is_integer():
                chap_number = int(chap_number)
        except ValueError:
            print("Số chapter không hợp lệ. Vui lòng nhập số!")
        except (KeyboardInterrupt, EOFError):
            return

    chap_title = input("👉 Nhập tiêu đề Chapter (bỏ trống nếu không có): ").strip()

    # Image Folder
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

    # Confirm
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
        chap_slug = f"chap-{chap_number}".replace(".", "-")
        print(f"  🎉 ĐĂNG CHAPTER THÀNH CÔNG RỰC RỠ TRONG {total_time} GIÂY!")
        print("=" * 70)
        print(f"  - Bộ truyện:      {selected_comic['title']}")
        print(f"  - Chapter:        Chap {chap_number} ({chap_title})")
        print(f"  - Số trang tải:   {len(pages_data)} / {total_pages} trang")
        print(f"  - Cloudinary Dir: {folder_dest}")
        print(f"  - Đọc trực tiếp:  http://127.0.0.1:8000/truyen/{selected_comic['slug']}/{chap_slug}")
    else:
        print(f"  ⚠️ ĐÃ TẢI LÊN ẢNH NHƯNG GẶP LỖI LƯU CSDL: {db_res.get('message')}")
    print("=" * 70 + "\n")

    input("Nhấn Enter để hoàn tất...")


# ==============================================================================
# Main Entry Point
# ==============================================================================
def main():
    if "--cli" in sys.argv or not HAS_TKINTER:
        run_interactive_cli()
    else:
        root = tk.Tk()
        app = ChapterUploaderGUI(root)
        root.mainloop()


if __name__ == "__main__":
    main()
