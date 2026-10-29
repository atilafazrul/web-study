# SmartStudy - AI Academic Study Planner

A clean, modern frontend prototype for **SmartStudy** built with **Laravel 12/13**, **Tailwind CSS**, and **Alpine.js**, structured with dummy data for frontend presentation.

---

## 🚀 Fitur yang Tersedia (Sesuai Desain Asli)

1. **Dashboard (`/`)**
   - Header ramah: *"Good evening, Student! 👋 - Plan smarter. Study better."*
   - 4 Kartu Metrik: **Total Tasks (5)**, **Due This Week (3)**, **Completed (1)**, **Study Time This Week (12h)**.
   - **My Tasks Widget**: Daftar tugas dengan badge prioritas (*High, Medium, Low*), subjek, estimasi waktu, dan checklist interaktif.
   - **AI Analysis Card**:
     - Klasifikasi otomatis subjek (*Programming*).
     - Breakdown 5 subtask yang disarankan oleh AI.
     - Gauge skor prioritas (*92% High Priority*).
     - Tab navigasi (*Task Breakdown, Priority Insights, Study Suggestions*).
   - **Weekly Study Plan**: Jadwal mingguan blok waktu sore (19:00–21:00) dan *Flexible Time*.
   - **Study Tips**: Rekomendasi pintar berbasis tugas aktif.
   - **Mascot Box**: Ilustrasi kucing belajar di sidebar (*"You've got this! ♡"*).

2. **My Tasks (`/tasks`)**
   - Header pencarian tugas interaktif.
   - 3 Kartu Ringkasan tugas (*Total Tasks, High Priority, Total Estimated Time*).
   - Tombol **+ Add New Task** untuk memicu modal Continuous AI Pipeline.
   - Filter status: *All (5), Pending (4), Completed (1)* & sorting by priority.
   - Kartu tugas dengan strip warna kategori (*Programming, Research, Presentation, Exam Prep, Reading*).
   - Panel kanan **AI Insights**:
     - *Top Priority Task* (OOP Java Assignment).
     - *Task Category Overview* (Donut Chart SVG visualisasi proporsi kategori).
     - *Study Tip Card*.

3. **Study Plan (`/study-plan`)**
   - Header dengan pemilih rentang tanggal (*Oct 27 - Nov 2, 2026*) dan tombol *✨ Regenerate Plan*.
   - Kalender matriks slot jam belajar (7 PM, 8 PM, 9 PM) untuk Senin s/d Minggu.
   - Slot tugas terstruktur sesuai warna kategori.
   - Panel samping: *Today's Plan (Mon, Oct 27)* dengan fokus subtask.
   - *Study Plan Summary*: 5 Total Tasks, 12h Total Study Time, 71% On Track (Progress bar).

4. **Continuous AI Modal (Pop-up)**
   - Dapat dibuka dari tombol **+ Add New Task** di halaman manapun.
   - Mensimulasikan pipeline AI berkelanjutan:
     - Deteksi kategori otomatis oleh AI Studio (Gemini).
     - Perhitungan skor prioritas backend (92%).
     - Penjadwalan otomatis subtask ke slot waktu belajar terbaik.

5. **Settings (`/settings`)**
   - Pengaturan profil mahasiswa.
   - Saklar preferensi integrasi Google AI Studio (Gemini) dan OpenAI GPT.

---

## 💻 Cara Menjalankan Website

Buka terminal di folder proyek ini (`c:\Users\Atila\Downloads\sofwer &toolkits ide\smartstudy`), lalu jalankan:

```bash
php artisan serve
```

Buka browser dan akses:
👉 **[http://127.0.0.1:8000](http://127.0.0.1:8000)**

Semua data telah terhubung langsung ke **MySQL** (Database: `smartstudy` pada `127.0.0.1:3306`). Data tugas baru dan checklist akan tersimpan langsung ke MySQL.
