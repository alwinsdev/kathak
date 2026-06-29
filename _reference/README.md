# 🪔 Siddha Mudra Therapy — PHP App

A mini-system where doctors prescribe Siddha mudras as therapy and patients practice them with live AI pose detection.

## 📁 Structure
```
kathak/
├── config.php          # DB + Roboflow settings
├── schema.sql          # MySQL schema + seed data
├── index.php           # Landing page
├── register.php        # Patient signup
├── login.php           # Patient/Doctor login
├── logout.php
├── predict.php         # Roboflow API proxy
├── includes/
│   ├── header.php
│   └── footer.php
├── doctor/
│   ├── dashboard.php   # View patient list
│   └── assign.php      # Assign mudras + timings
└── patient/
    ├── dashboard.php   # See schedule, alerts, mark EOD complete
    └── practice.php    # Live AI camera detection
```

## ⚙️ Setup

1. **Install** XAMPP / LAMP / WAMP. Place the `kathak/` folder in `htdocs`.

2. **Create the database**:
   ```bash
   mysql -u root -p < schema.sql
   ```
   Or open phpMyAdmin → Import → `schema.sql`.

3. **Edit `config.php`**:
   - Set your MySQL credentials.
   - Paste your Roboflow API key.

4. **Run** the server. Open `http://localhost/kathak/`.

## 👥 Demo Logins

| Role | Email | Password |
|------|-------|----------|
| Doctor | `doctor@kathak.com` | `doctor123` |
| Patient | *register your own* | — |

## 🔁 Flow

1. Patient registers → suggested mudras shown.
2. Doctor logs in → sees patient records → assigns mudras with daily times.
3. Patient sees schedule → browser sends notification at the scheduled time.
4. Patient clicks **📷 Live AI** → camera opens → Roboflow detects mudra in real time.
5. Patient clicks **Mark Done** at end of day.

## 🔧 Notes
- Browser notifications require HTTPS (or `localhost`).
- Camera access needs HTTPS in production.
- The detection rate is adjustable from the practice page.
