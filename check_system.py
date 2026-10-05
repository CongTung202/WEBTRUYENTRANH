#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
GTSCHUNDER - Unified System Checker & Fast Chapter Uploader Tool
Supports both Desktop GUI (Tkinter) and Headless CLI mode.
Works 100% independently from the Laravel web server.
"""

import os
import sys
import time
import socket
import json
import base64
import re
import hashlib
import tempfile
import urllib.request
import urllib.error
import urllib.parse
import subprocess
import threading
from pathlib import Path

# Optional Pillow for WebP
try:
    from PIL import Image
    HAS_PILLOW = True
except ImportError:
    HAS_PILLOW = False

# Optional Tkinter for GUI
try:
    import tkinter as tk
    from tkinter import ttk, messagebox, filedialog
    HAS_TKINTER = True
except ImportError:
    HAS_TKINTER = False


def natural_sort_key(s):
    """Sort strings with numbers naturally: 1, 2, ... 9, 10, 11."""
    return [int(text) if text.isdigit() else text.lower() for text in re.split(r'(\d+)', str(s))]


def load_env_file(filepath=".env"):
    """Parse standard .env file into a dictionary without external packages."""
    env_vars = {}
    path = Path(filepath)
    if not path.is_file():
        script_dir = Path(__file__).resolve().parent
        candidate = script_dir / ".env"
        if candidate.is_file():
            path = candidate
        else:
            return env_vars

    try:
        with open(path, "r", encoding="utf-8", errors="ignore") as f:
            for line in f:
                line = line.strip()
                if not line or line.startswith("#"):
                    continue
                if "=" in line:
                    key, val = line.split("=", 1)
                    key = key.strip()
                    val = val.strip().strip('"').strip("'")
                    env_vars[key] = val
    except Exception as e:
        print(f"Lỗi đọc .env: {e}")
    return env_vars


class ConnectionChecker:
    """Core logic for testing Database and Cloudinary connections."""

    @staticmethod
    def test_database(host, port, database, username, password, timeout=5):
        results = {
            "success": False,
            "latency_ms": 0,
            "version": "",
            "database": database,
            "tables_count": 0,
            "tables": [],
            "message": "",
            "details": []
        }

        # 1. TCP Socket Ping
        t0 = time.time()
        try:
            sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            sock.settimeout(timeout)
            port_int = int(port) if str(port).isdigit() else 3306
            sock.connect((host, port_int))
            sock.close()
            latency = (time.time() - t0) * 1000
            results["latency_ms"] = round(latency, 2)
            results["details"].append(f"✓ TCP Socket tới {host}:{port_int} thành công ({results['latency_ms']} ms)")
        except Exception as e:
            results["message"] = f"Không thể kết nối cổng TCP {host}:{port}: {e}"
            results["details"].append(f"✗ TCP Socket thất bại: {e}")
            return results

        # 2. Query Database via PHP CLI (PDO)
        php_script = f"""<?php
        try {{
            $dsn = 'mysql:host={host};port={port};dbname={database};charset=utf8mb4';
            $pdo = new PDO($dsn, '{username}', '{password}', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => {timeout}
            ]);
            $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
            $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
            $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            $charset = $pdo->query('SELECT @@character_set_database')->fetchColumn();
            
            echo json_encode([
                'status' => 'ok',
                'version' => $ver,
                'database' => $db,
                'charset' => $charset,
                'tables_count' => count($tables),
                'tables' => $tables
            ]);
        }} catch (Exception $e) {{
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }}
        """

        try:
            proc = subprocess.run(
                ["php"],
                input=php_script,
                capture_output=True,
                text=True,
                timeout=timeout + 3
            )
            raw_out = proc.stdout.strip()
            if not raw_out and proc.stderr:
                results["message"] = f"PHP Lỗi: {proc.stderr.strip()}"
                results["details"].append(f"✗ Thực thi PHP thất bại: {proc.stderr.strip()}")
                return results

            data = json.loads(raw_out)
            if data.get("status") == "ok":
                results["success"] = True
                results["version"] = data.get("version", "")
                results["tables_count"] = data.get("tables_count", 0)
                results["tables"] = data.get("tables", [])
                results["charset"] = data.get("charset", "utf8mb4")
                results["message"] = f"Kết nối Database MySQL thành công! (Phiên bản: {results['version']})"
                results["details"].append(f"✓ Xác thực tài khoản '{username}' thành công")
                results["details"].append(f"✓ MySQL Server: {results['version']} | Bảng mã: {results['charset']}")
                results["details"].append(f"✓ Tìm thấy {results['tables_count']} bảng dữ liệu trong CSDL '{database}'")
            else:
                results["message"] = f"Lỗi xác thực CSDL: {data.get('message')}"
                results["details"].append(f"✗ Lỗi xác thực: {data.get('message')}")
        except FileNotFoundError:
            results["success"] = True
            results["message"] = f"Cổng MySQL {host}:{port} đang mở và phản hồi ({results['latency_ms']} ms)"
            results["details"].append("! Không tìm thấy PHP CLI để kiểm tra bảng & user")
        except Exception as e:
            results["message"] = f"Lỗi kiểm tra Database: {e}"
            results["details"].append(f"✗ Lỗi: {e}")

        return results

    @staticmethod
    def test_cloudinary(cloud_name, api_key, api_secret, timeout=10):
        results = {
            "success": False,
            "latency_ms": 0,
            "cloud_name": cloud_name,
            "plan": "",
            "storage_used": "",
            "credits_used": "",
            "upload_test": False,
            "message": "",
            "details": []
        }

        if not cloud_name or not api_key or not api_secret:
            results["message"] = "Vui lòng nhập đầy đủ Cloud Name, API Key và API Secret!"
            results["details"].append("✗ Thiếu thông tin cấu hình Cloudinary")
            return results

        auth_str = f"{api_key}:{api_secret}"
        b64_auth = base64.b64encode(auth_str.encode()).decode()
        headers = {
            "Authorization": f"Basic {b64_auth}",
            "User-Agent": "GTSCHunder-Checker/1.0"
        }

        # 1. Ping
        ping_url = f"https://api.cloudinary.com/v1_1/{cloud_name}/ping"
        t0 = time.time()
        try:
            req = urllib.request.Request(ping_url, headers=headers)
            with urllib.request.urlopen(req, timeout=timeout) as resp:
                latency = (time.time() - t0) * 1000
                results["latency_ms"] = round(latency, 2)
                raw_json = json.loads(resp.read().decode())
                if raw_json.get("status") == "ok":
                    results["details"].append(f"✓ Cloudinary API Endpoint phản hồi OK ({results['latency_ms']} ms)")
        except Exception as e:
            results["message"] = f"Không thể kết nối máy chủ Cloudinary: {e}"
            results["details"].append(f"✗ Kết nối Cloudinary thất bại: {e}")
            return results

        # 2. Usage info
        usage_url = f"https://api.cloudinary.com/v1_1/{cloud_name}/usage"
        try:
            req = urllib.request.Request(usage_url, headers=headers)
            with urllib.request.urlopen(req, timeout=timeout) as resp:
                usage_data = json.loads(resp.read().decode())
                results["plan"] = usage_data.get("plan", "Free")
                
                storage_bytes = usage_data.get("storage", {}).get("usage", 0)
                if storage_bytes >= 1024 * 1024 * 1024:
                    storage_str = f"{round(storage_bytes / (1024*1024*1024), 2)} GB"
                elif storage_bytes >= 1024 * 1024:
                    storage_str = f"{round(storage_bytes / (1024*1024), 2)} MB"
                else:
                    storage_str = f"{round(storage_bytes / 1024, 2)} KB"
                results["storage_used"] = storage_str

                credits_used = usage_data.get("credits", {}).get("usage", 0)
                results["credits_used"] = str(credits_used)

                results["details"].append(f"✓ Gói dịch vụ Cloudinary: Gói {results['plan']}")
                results["details"].append(f"✓ Dung lượng lưu trữ đã dùng: {storage_str}")
                results["details"].append(f"✓ Credits đã sử dụng: {credits_used} credits")
        except Exception as e:
            results["details"].append(f"! Không lấy được thông tin usage chi tiết: {e}")

        # 3. Test Direct Upload & Auto Delete (1x1 PNG)
        tiny_png_base64 = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=="
        timestamp = int(time.time())
        public_id = f"system_test_{timestamp}"

        to_sign = f"public_id={public_id}&timestamp={timestamp}{api_secret}"
        signature = hashlib.sha1(to_sign.encode("utf-8")).hexdigest()

        upload_url = f"https://api.cloudinary.com/v1_1/{cloud_name}/image/upload"
        form_data = urllib.parse.urlencode({
            "file": tiny_png_base64,
            "public_id": public_id,
            "timestamp": timestamp,
            "api_key": api_key,
            "signature": signature
        }).encode("utf-8")

        try:
            req = urllib.request.Request(upload_url, data=form_data)
            with urllib.request.urlopen(req, timeout=timeout) as resp:
                upload_res = json.loads(resp.read().decode())
                if "secure_url" in upload_res:
                    results["upload_test"] = True
                    results["details"].append("✓ Kiểm tra tải ảnh mẫu (Write Permission): Thành công")

                    to_sign_del = f"public_id={public_id}&timestamp={timestamp}{api_secret}"
                    sig_del = hashlib.sha1(to_sign_del.encode("utf-8")).hexdigest()
                    del_url = f"https://api.cloudinary.com/v1_1/{cloud_name}/image/destroy"
                    del_data = urllib.parse.urlencode({
                        "public_id": public_id,
                        "timestamp": timestamp,
                        "api_key": api_key,
                        "signature": sig_del
                    }).encode("utf-8")

                    try:
                        req_del = urllib.request.Request(del_url, data=del_data)
                        with urllib.request.urlopen(req_del, timeout=5):
                            results["details"].append("✓ Dọn dẹp ảnh mẫu (Delete Permission): Thành công")
                    except Exception:
                        pass
        except Exception as e:
            results["details"].append(f"! Kiểm tra tải ảnh thử nghiệm: {e}")

        results["success"] = True
        results["message"] = f"Kết nối Cloudinary '{cloud_name}' thành công! (Phản hồi: {results['latency_ms']} ms)"
        return results


# Import Chapter Uploader Helper
try:
    from upload_chapter import DatabaseHelper, ChapterUploadManager, CloudinaryUploader
except ImportError:
    pass


# ==============================================================================
# Modern Desktop GUI (Tkinter) with Tabs
# ==============================================================================
class ModernGuiApp:
    def __init__(self, root):
        self.root = root
        self.root.title("GTSCHunder - Hệ Thống Kiểm Tra Kết Nối & Đăng Chapter Nhanh")
        self.root.geometry("920x760")
        self.root.minsize(840, 680)

        # Dark Theme Color Palette
        self.C_BG = "#0b0f17"
        self.C_CARD = "#151b26"
        self.C_CARD_LIGHT = "#1c2433"
        self.C_BORDER = "#243042"
        self.C_PRIMARY = "#506891"
        self.C_PRIMARY_HOVER = "#3d5173"
        self.C_TEXT = "#f8fafc"
        self.C_TEXT_MUTED = "#94a3b8"
        self.C_SUCCESS = "#10b981"
        self.C_DANGER = "#ef4444"
        self.C_WARNING = "#f59e0b"

        self.root.configure(bg=self.C_BG)

        # Style configuration
        self.style = ttk.Style()
        self.style.theme_use("clam")
        self.style.configure(".", background=self.C_BG, foreground=self.C_TEXT, font=("Segoe UI", 9))
        self.style.configure("TNotebook", background=self.C_BG, borderwidth=0)
        self.style.configure("TNotebook.Tab", background=self.C_CARD, foreground=self.C_TEXT_MUTED, font=("Segoe UI", 10, "bold"), padding=[16, 8])
        self.style.map("TNotebook.Tab", background=[("selected", self.C_PRIMARY)], foreground=[("selected", "#ffffff")])

        self.env_data = load_env_file()
        self.comics_cache = []
        self._build_ui()
        self._load_values_from_env()

    def _build_ui(self):
        # Header banner
        header_frame = tk.Frame(self.root, bg=self.C_CARD, bd=0, highlightthickness=1, highlightbackground=self.C_BORDER)
        header_frame.pack(fill=tk.X, padx=16, pady=(14, 10))

        header_inner = tk.Frame(header_frame, bg=self.C_CARD, padx=16, pady=10)
        header_inner.pack(fill=tk.X)

        title_lbl = tk.Label(header_inner, text="⚡ GTSCHUNDER - CÔNG CỤ QUẢN TRỊ & HỖ TRỢ HỆ THỐNG", font=("Segoe UI", 12, "bold"), fg="#ffffff", bg=self.C_CARD)
        title_lbl.pack(side=tk.LEFT)

        btn_box = tk.Frame(header_inner, bg=self.C_CARD)
        btn_box.pack(side=tk.RIGHT)

        load_env_btn = tk.Button(btn_box, text="📂 Nạp lại .env", font=("Segoe UI", 9, "bold"), bg=self.C_CARD_LIGHT, fg="#ffffff", activebackground=self.C_PRIMARY, activeforeground="#fff", bd=0, padx=12, pady=5, cursor="hand2", command=self.reload_env)
        load_env_btn.pack(side=tk.LEFT, padx=6)

        # Tab Notebook
        self.notebook = ttk.Notebook(self.root)
        self.notebook.pack(fill=tk.BOTH, expand=False, padx=16, pady=0)

        self.tab_check = tk.Frame(self.notebook, bg=self.C_BG)
        self.tab_upload = tk.Frame(self.notebook, bg=self.C_BG)

        self.notebook.add(self.tab_check, text="🔍 Kiểm Tra Kết Nối (Diagnostics)")
        self.notebook.add(self.tab_upload, text="🚀 Đăng Chapter Nhanh (WebP Converter)")

        self._build_tab_check()
        self._build_tab_upload()

        # Bottom: Diagnostic Log Console
        log_frame = tk.Frame(self.root, bg=self.C_CARD, highlightthickness=1, highlightbackground=self.C_BORDER, padx=16, pady=10)
        log_frame.pack(fill=tk.BOTH, expand=True, padx=16, pady=(10, 14))

        log_header = tk.Frame(log_frame, bg=self.C_CARD)
        log_header.pack(fill=tk.X, pady=(0, 6))
        tk.Label(log_header, text="📋 Nhật Ký Hoạt Động (Live Terminal Log)", font=("Segoe UI", 10, "bold"), fg="#ffffff", bg=self.C_CARD).pack(side=tk.LEFT)

        clear_log_btn = tk.Button(log_header, text="Xóa Log", font=("Segoe UI", 8), bg=self.C_CARD_LIGHT, fg=self.C_TEXT_MUTED, bd=0, padx=8, pady=2, cursor="hand2", command=self.clear_log)
        clear_log_btn.pack(side=tk.RIGHT, padx=(6, 0))

        copy_log_btn = tk.Button(log_header, text="Sao Chép Log", font=("Segoe UI", 8), bg=self.C_CARD_LIGHT, fg=self.C_TEXT_MUTED, bd=0, padx=8, pady=2, cursor="hand2", command=self.copy_log)
        copy_log_btn.pack(side=tk.RIGHT)

        self.log_text = tk.Text(log_frame, bg="#080c14", fg="#cbd5e1", font=("Consolas", 9), bd=0, padx=10, pady=8, insertbackground="#fff", wrap=tk.WORD, height=8)
        self.log_text.pack(fill=tk.BOTH, expand=True)

        self.log_text.tag_config("INFO", foreground="#38bdf8")
        self.log_text.tag_config("SUCCESS", foreground="#34d399")
        self.log_text.tag_config("WARNING", foreground="#fbbf24")
        self.log_text.tag_config("ERROR", foreground="#f87171")
        self.log_text.tag_config("TIME", foreground="#64748b")

    def _build_tab_check(self):
        cards_frame = tk.Frame(self.tab_check, bg=self.C_BG, pady=8)
        cards_frame.pack(fill=tk.BOTH, expand=True)

        # Cloudinary Card
        self.cloud_card = tk.Frame(cards_frame, bg=self.C_CARD, highlightthickness=1, highlightbackground=self.C_BORDER, padx=14, pady=12)
        self.cloud_card.pack(side=tk.LEFT, fill=tk.BOTH, expand=True, padx=(0, 6))

        cloud_header = tk.Frame(self.cloud_card, bg=self.C_CARD)
        cloud_header.pack(fill=tk.X, pady=(0, 8))
        tk.Label(cloud_header, text="☁️ Cloudinary Storage", font=("Segoe UI", 11, "bold"), fg="#38bdf8", bg=self.C_CARD).pack(side=tk.LEFT)
        self.cloud_badge = tk.Label(cloud_header, text="Chưa kiểm tra", font=("Segoe UI", 8, "bold"), fg=self.C_TEXT_MUTED, bg="#1e293b", padx=8, pady=2)
        self.cloud_badge.pack(side=tk.RIGHT)

        self.entry_cloud_name = self._create_input_row(self.cloud_card, "Cloud Name:")
        self.entry_cloud_key = self._create_input_row(self.cloud_card, "API Key:")
        self.entry_cloud_secret = self._create_input_row(self.cloud_card, "API Secret:", show="*")

        self.btn_test_cloud = tk.Button(self.cloud_card, text="🔍 Kiểm Tra Cloudinary", font=("Segoe UI", 9, "bold"), bg=self.C_PRIMARY, fg="#ffffff", bd=0, pady=6, cursor="hand2", command=self.test_cloudinary)
        self.btn_test_cloud.pack(fill=tk.X, pady=(10, 0))

        # Database Card
        self.db_card = tk.Frame(cards_frame, bg=self.C_CARD, highlightthickness=1, highlightbackground=self.C_BORDER, padx=14, pady=12)
        self.db_card.pack(side=tk.RIGHT, fill=tk.BOTH, expand=True, padx=(6, 0))

        db_header = tk.Frame(self.db_card, bg=self.C_CARD)
        db_header.pack(fill=tk.X, pady=(0, 8))
        tk.Label(db_header, text="🗄️ Database MySQL", font=("Segoe UI", 11, "bold"), fg="#34d399", bg=self.C_CARD).pack(side=tk.LEFT)
        self.db_badge = tk.Label(db_header, text="Chưa kiểm tra", font=("Segoe UI", 8, "bold"), fg=self.C_TEXT_MUTED, bg="#1e293b", padx=8, pady=2)
        self.db_badge.pack(side=tk.RIGHT)

        self.entry_db_host = self._create_input_row(self.db_card, "Host:")
        self.entry_db_port = self._create_input_row(self.db_card, "Port:")
        self.entry_db_name = self._create_input_row(self.db_card, "Database:")
        self.entry_db_user = self._create_input_row(self.db_card, "Username:")
        self.entry_db_pass = self._create_input_row(self.db_card, "Password:", show="*")

        self.btn_test_db = tk.Button(self.db_card, text="🔍 Kiểm Tra Database", font=("Segoe UI", 9, "bold"), bg=self.C_PRIMARY, fg="#ffffff", bd=0, pady=6, cursor="hand2", command=self.test_database)
        self.btn_test_db.pack(fill=tk.X, pady=(10, 0))

    def _build_tab_upload(self):
        upload_box = tk.Frame(self.tab_upload, bg=self.C_CARD, highlightthickness=1, highlightbackground=self.C_BORDER, padx=18, pady=14)
        upload_box.pack(fill=tk.BOTH, expand=True, pady=8)

        # Row 1: Comic Selector
        row1 = tk.Frame(upload_box, bg=self.C_CARD)
        row1.pack(fill=tk.X, pady=(0, 8))
        tk.Label(row1, text="1. Chọn Bộ Truyện:", font=("Segoe UI", 9, "bold"), fg=self.C_TEXT, bg=self.C_CARD, width=18, anchor="w").pack(side=tk.LEFT)

        self.comic_combo = ttk.Combobox(row1, state="readonly", font=("Segoe UI", 9))
        self.comic_combo.pack(side=tk.LEFT, fill=tk.X, expand=True, padx=(4, 8))
        self.comic_combo.bind("<<ComboboxSelected>>", self._on_comic_selected)

        refresh_comics_btn = tk.Button(row1, text="🔄 Tải Danh Sách", font=("Segoe UI", 8, "bold"), bg=self.C_CARD_LIGHT, fg=self.C_TEXT, bd=0, padx=10, pady=4, cursor="hand2", command=self.fetch_comics_list)
        refresh_comics_btn.pack(side=tk.RIGHT)

        # Row 2: Chapter Number & Title
        row2 = tk.Frame(upload_box, bg=self.C_CARD)
        row2.pack(fill=tk.X, pady=6)

        tk.Label(row2, text="2. Số Chapter:", font=("Segoe UI", 9, "bold"), fg=self.C_TEXT, bg=self.C_CARD, width=18, anchor="w").pack(side=tk.LEFT)
        self.entry_upload_chap_num = tk.Entry(row2, bg="#0d131c", fg="#ffffff", font=("Segoe UI", 9), bd=0, highlightthickness=1, highlightbackground=self.C_BORDER, insertbackground="#fff", width=12)
        self.entry_upload_chap_num.pack(side=tk.LEFT, ipady=3, padx=(4, 16))

        tk.Label(row2, text="Tiêu đề Chapter (tuỳ chọn):", font=("Segoe UI", 9), fg=self.C_TEXT_MUTED, bg=self.C_CARD).pack(side=tk.LEFT)
        self.entry_upload_chap_title = tk.Entry(row2, bg="#0d131c", fg="#ffffff", font=("Segoe UI", 9), bd=0, highlightthickness=1, highlightbackground=self.C_BORDER, insertbackground="#fff")
        self.entry_upload_chap_title.pack(side=tk.LEFT, fill=tk.X, expand=True, ipady=3, padx=(6, 0))

        # Row 3: Folder Picker
        row3 = tk.Frame(upload_box, bg=self.C_CARD)
        row3.pack(fill=tk.X, pady=6)

        tk.Label(row3, text="3. Thư Mục Ảnh Local:", font=("Segoe UI", 9, "bold"), fg=self.C_TEXT, bg=self.C_CARD, width=18, anchor="w").pack(side=tk.LEFT)
        self.entry_upload_dir = tk.Entry(row3, bg="#0d131c", fg="#ffffff", font=("Segoe UI", 9), bd=0, highlightthickness=1, highlightbackground=self.C_BORDER, insertbackground="#fff")
        self.entry_upload_dir.pack(side=tk.LEFT, fill=tk.X, expand=True, ipady=3, padx=(4, 8))

        browse_btn = tk.Button(row3, text="📁 Chọn Thư Mục...", font=("Segoe UI", 8, "bold"), bg=self.C_CARD_LIGHT, fg=self.C_TEXT, bd=0, padx=10, pady=4, cursor="hand2", command=self._browse_folder)
        browse_btn.pack(side=tk.RIGHT)

        # Progress bar
        self.upload_progress = ttk.Progressbar(upload_box, orient="horizontal", mode="determinate")
        self.upload_progress.pack(fill=tk.X, pady=(12, 6))

        self.lbl_upload_status = tk.Label(upload_box, text="Sẵn sàng đăng chapter...", font=("Segoe UI", 8), fg=self.C_TEXT_MUTED, bg=self.C_CARD)
        self.lbl_upload_status.pack(anchor="w")

        # Start Upload Button
        self.btn_start_upload = tk.Button(upload_box, text="🚀 BẮT ĐẦU NÉN WEBP & TẢI LÊN CLOUDINARY", font=("Segoe UI", 10, "bold"), bg=self.C_PRIMARY, fg="#ffffff", activebackground=self.C_PRIMARY_HOVER, activeforeground="#fff", bd=0, pady=8, cursor="hand2", command=self.start_upload_chapter)
        self.btn_start_upload.pack(fill=tk.X, pady=(8, 0))

    def _create_input_row(self, parent, label_text, show=None):
        row = tk.Frame(parent, bg=self.C_CARD)
        row.pack(fill=tk.X, pady=3)

        lbl = tk.Label(row, text=label_text, width=11, anchor="w", font=("Segoe UI", 8, "bold"), fg=self.C_TEXT_MUTED, bg=self.C_CARD)
        lbl.pack(side=tk.LEFT)

        entry = tk.Entry(row, bg="#0d131c", fg="#ffffff", font=("Segoe UI", 9), bd=0, highlightthickness=1, highlightbackground=self.C_BORDER, insertbackground="#fff", show=show)
        entry.pack(side=tk.LEFT, fill=tk.X, expand=True, ipady=3, padx=(4, 0))
        return entry

    def log(self, message, level="INFO"):
        timestamp = time.strftime("[%H:%M:%S] ")
        self.log_text.insert(tk.END, timestamp, "TIME")
        self.log_text.insert(tk.END, f"{message}\n", level)
        self.log_text.see(tk.END)

    def clear_log(self):
        self.log_text.delete("1.0", tk.END)

    def copy_log(self):
        text = self.log_text.get("1.0", tk.END).strip()
        if text:
            self.root.clipboard_clear()
            self.root.clipboard_append(text)
            messagebox.showinfo("Thông báo", "Đã sao chép toàn bộ nhật ký vào Clipboard!")

    def _load_values_from_env(self):
        self.entry_cloud_name.delete(0, tk.END)
        self.entry_cloud_name.insert(0, self.env_data.get("CLOUDINARY_CLOUD_NAME", ""))

        self.entry_cloud_key.delete(0, tk.END)
        self.entry_cloud_key.insert(0, self.env_data.get("CLOUDINARY_API_KEY", ""))

        self.entry_cloud_secret.delete(0, tk.END)
        self.entry_cloud_secret.insert(0, self.env_data.get("CLOUDINARY_API_SECRET", ""))

        self.entry_db_host.delete(0, tk.END)
        self.entry_db_host.insert(0, self.env_data.get("DB_HOST", "127.0.0.1"))

        self.entry_db_port.delete(0, tk.END)
        self.entry_db_port.insert(0, self.env_data.get("DB_PORT", "3306"))

        self.entry_db_name.delete(0, tk.END)
        self.entry_db_name.insert(0, self.env_data.get("DB_DATABASE", "webtruyentranh"))

        self.entry_db_user.delete(0, tk.END)
        self.entry_db_user.insert(0, self.env_data.get("DB_USERNAME", "root"))

        self.entry_db_pass.delete(0, tk.END)
        self.entry_db_pass.insert(0, self.env_data.get("DB_PASSWORD", ""))

        self.log("Đã nạp cấu hình thành công từ tệp .env", "INFO")
        self.fetch_comics_list()

    def reload_env(self):
        self.env_data = load_env_file()
        self._load_values_from_env()

    def fetch_comics_list(self):
        def task():
            comics = DatabaseHelper.get_comics_list(self.env_data)
            self.comics_cache = comics
            if comics:
                titles = [f"#{c['id']} {c['title']} (Đang có: Chap {c['latest_chap'] or 0})" for c in comics]
                self.comic_combo["values"] = titles
                if not self.comic_combo.get() and titles:
                    self.comic_combo.current(0)
                    self._on_comic_selected(None)
                self.log(f"Đã tải {len(comics)} bộ truyện từ CSDL", "INFO")
        threading.Thread(target=task, daemon=True).start()

    def _on_comic_selected(self, event):
        idx = self.comic_combo.current()
        if 0 <= idx < len(self.comics_cache):
            comic = self.comics_cache[idx]
            latest = float(comic["latest_chap"]) if comic["latest_chap"] else 0
            suggested = int(latest + 1) if (latest + 1).is_integer() else (latest + 1)
            self.entry_upload_chap_num.delete(0, tk.END)
            self.entry_upload_chap_num.insert(0, str(suggested))
            self.entry_upload_chap_title.delete(0, tk.END)
            self.entry_upload_chap_title.insert(0, f"Chapter {suggested}")

    def _browse_folder(self):
        folder = filedialog.askdirectory(title="Chọn thư mục chứa ảnh chapter")
        if folder:
            self.entry_upload_dir.delete(0, tk.END)
            self.entry_upload_dir.insert(0, folder)
            try:
                images = ChapterUploadManager.scan_directory(folder)
                self.lbl_upload_status.config(text=f"✓ Đã tìm thấy {len(images)} tệp ảnh trong thư mục", fg="#34d399")
            except Exception as e:
                self.lbl_upload_status.config(text=f"Lỗi: {e}", fg="#f87171")

    def test_cloudinary(self):
        cloud_name = self.entry_cloud_name.get().strip()
        api_key = self.entry_cloud_key.get().strip()
        api_secret = self.entry_cloud_secret.get().strip()

        self.cloud_badge.config(text="Đang kiểm tra...", fg="#fbbf24", bg="#451a03")
        self.log(f"--- BẮT ĐẦU KIỂM TRA CLOUDINARY ({cloud_name}) ---", "INFO")

        def task():
            res = ConnectionChecker.test_cloudinary(cloud_name, api_key, api_secret)
            for d in res["details"]:
                self.log(d, "SUCCESS" if d.startswith("✓") else ("WARNING" if d.startswith("!") else "ERROR"))

            if res["success"]:
                self.cloud_badge.config(text=f"🟢 ĐÃ KẾT NỐI ({res['latency_ms']}ms)", fg="#34d399", bg="#064e3b")
                self.log(f"==> KẾT LUẬN: {res['message']}", "SUCCESS")
            else:
                self.cloud_badge.config(text="🔴 THẤT BẠI", fg="#f87171", bg="#7f1d1d")
                self.log(f"==> KẾT LUẬN: {res['message']}", "ERROR")

        threading.Thread(target=task, daemon=True).start()

    def test_database(self):
        host = self.entry_db_host.get().strip()
        port = self.entry_db_port.get().strip()
        database = self.entry_db_name.get().strip()
        username = self.entry_db_user.get().strip()
        password = self.entry_db_pass.get().strip()

        self.db_badge.config(text="Đang kiểm tra...", fg="#fbbf24", bg="#451a03")
        self.log(f"--- BẮT ĐẦU KIỂM TRA DATABASE ({host}:{port}/{database}) ---", "INFO")

        def task():
            res = ConnectionChecker.test_database(host, port, database, username, password)
            for d in res["details"]:
                self.log(d, "SUCCESS" if d.startswith("✓") else ("WARNING" if d.startswith("!") else "ERROR"))

            if res["success"]:
                self.db_badge.config(text=f"🟢 ĐÃ KẾT NỐI ({res['latency_ms']}ms)", fg="#34d399", bg="#064e3b")
                self.log(f"==> KẾT LUẬN: {res['message']}", "SUCCESS")
            else:
                self.db_badge.config(text="🔴 THẤT BẠI", fg="#f87171", bg="#7f1d1d")
                self.log(f"==> KẾT LUẬN: {res['message']}", "ERROR")

        threading.Thread(target=task, daemon=True).start()

    def start_upload_chapter(self):
        idx = self.comic_combo.current()
        if idx < 0 or idx >= len(self.comics_cache):
            messagebox.showwarning("Cảnh báo", "Vui lòng chọn một bộ truyện trước!")
            return

        selected_comic = self.comics_cache[idx]
        chap_num_str = self.entry_upload_chap_num.get().strip()
        chap_title = self.entry_upload_chap_title.get().strip()
        folder_dir = self.entry_upload_dir.get().strip()

        if not chap_num_str:
            messagebox.showwarning("Cảnh báo", "Vui lòng nhập số Chapter!")
            return
        if not folder_dir or not os.path.isdir(folder_dir):
            messagebox.showwarning("Cảnh báo", "Thư mục ảnh không hợp lệ hoặc không tồn tại!")
            return

        try:
            images = ChapterUploadManager.scan_directory(folder_dir)
        except Exception as e:
            messagebox.showerror("Lỗi", str(e))
            return

        if not images:
            messagebox.showwarning("Cảnh báo", "Không tìm thấy file ảnh nào trong thư mục!")
            return

        cloud_name = self.entry_cloud_name.get().strip()
        api_key = self.entry_cloud_key.get().strip()
        api_secret = self.entry_cloud_secret.get().strip()

        self.btn_start_upload.config(state="disabled", bg=self.C_CARD_LIGHT)
        self.upload_progress["value"] = 0
        self.upload_progress["maximum"] = len(images)

        self.log(f"🚀 BẮT ĐẦU ĐĂNG CHAP {chap_num_str} CHO TRUYỆN '{selected_comic['title']}' ({len(images)} trang)...", "INFO")

        def task():
            total = len(images)
            pages_data = []
            folder_dest = f"webtruyentranh/{selected_comic['slug']}/chap{chap_num_str}"
            t0 = time.time()

            with tempfile.TemporaryDirectory() as temp_dir:
                for i, img_path in enumerate(images, 1):
                    pnum = f"{i:03d}"
                    webp_path = os.path.join(temp_dir, f"page_{pnum}.webp")
                    
                    # Convert to WebP
                    ChapterUploadManager.convert_to_webp(str(img_path), webp_path, quality=85)
                    orig_kb = round(img_path.stat().st_size / 1024, 1)
                    webp_kb = round(os.path.getsize(webp_path) / 1024, 1)

                    # Upload to Cloudinary
                    try:
                        res = CloudinaryUploader.upload_image_file(webp_path, folder_dest, f"page_{pnum}", cloud_name, api_key, api_secret)
                        pages_data.append({
                            "page_number": i,
                            "image_url": res["url"],
                            "cloudinary_public_id": res["public_id"]
                        })
                        self.log(f"✓ Trang {i:02d}/{total:02d}: {img_path.name} ({orig_kb}KB -> {webp_kb}KB) -> Cloudinary OK", "SUCCESS")
                    except Exception as e:
                        self.log(f"✗ Trang {i:02d} Lỗi: {e}", "ERROR")

                    self.upload_progress["value"] = i
                    self.lbl_upload_status.config(text=f"Đang tải lên: {i}/{total} trang ({int(i/total*100)}%)")

            # Save to Database
            self.lbl_upload_status.config(text="Đang lưu dữ liệu vào CSDL MySQL...")
            db_res = DatabaseHelper.save_chapter(self.env_data, selected_comic["id"], float(chap_num_str), chap_title or f"Chapter {chap_num_str}", pages_data)
            
            elapsed = round(time.time() - t0, 2)
            self.btn_start_upload.config(state="normal", bg=self.C_PRIMARY)

            if db_res.get("status") == "ok":
                self.lbl_upload_status.config(text=f"🎉 Đã đăng thành công Chap {chap_num_str} ({total} trang) trong {elapsed}s!", fg="#34d399")
                self.log(f"🎉 HOÀN THÀNH XUẤT SẮC: Đã đăng Chap {chap_num_str} cho '{selected_comic['title']}' ({total} trang, {elapsed}s)", "SUCCESS")
                messagebox.showinfo("Thành công", f"Đã đăng thành công Chapter {chap_num_str} cho truyện '{selected_comic['title']}' ({total} trang) trong {elapsed} giây!")
            else:
                self.lbl_upload_status.config(text=f"Lỗi CSDL: {db_res.get('message')}", fg="#f87171")
                self.log(f"⚠️ Lỗi CSDL: {db_res.get('message')}", "ERROR")

        threading.Thread(target=task, daemon=True).start()


if __name__ == "__main__":
    if "--upload" in sys.argv:
        from upload_chapter import run_interactive_cli
        run_interactive_cli()
    elif "--cli" in sys.argv or not HAS_TKINTER:
        from check_system import run_cli_tests
        run_cli_tests()
    else:
        root = tk.Tk()
        app = ModernGuiApp(root)
        root.mainloop()
