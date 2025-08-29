# Laravel XML Import with Progress Tracking

This project demonstrates how to import large XML files (10 lakh+ records) into a MySQL database using Laravel.  
It also supports **real-time progress tracking** without any external JavaScript plugins, only Laravel functionality.

---

## 🚀 Features
- Upload large `.xml` files (supports 20MB+).
- Import millions of records (tested with 10 lakh+).
- Tracks **Processed, Inserted, Updated, Skipped** records.
- Estimates **time remaining** to finish import.
- Uses **queue jobs** for non-blocking imports.
- Progress can be fetched via API or browser refresh.
- Configurable **memory & execution time limits**.

---

## 📂 Project Structure
