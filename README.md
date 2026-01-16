# 🎓 CampusAuth - Advanced College Attendance & Management System

CampusAuth is a robust, role-based web application designed to streamline college administrative tasks, attendance tracking, and communication between stakeholders (Admins, Teachers, Students, and Parents). Built with a focus on security, usability, and modern aesthetics (Glassmorphism), it ensures data integrity and operational efficiency.

## 🚀 Key Features

### 🔐 Multi-Role Access Control
*   **Super Admin**: Full control over users, classes, settings, and system transitions.
*   **Sub-Admin**: Time-limited access with granular permissions (e.g., "Only manage attendance").
*   **Teacher**: Class-specific management (Attendance, Leaves, Announcements).
*   **Temporary Teacher**: Restricted, time-bound access to take attendance for specific classes.
*   **Student**: View-only access to their academic stats, apply for leave/medical, and view announcements.
*   **Parent**: Monitoring access to their child's attendance and notices.

### 📊 Attendance & Academic Management
*   **Smart Attendance**: Teachers mark present/absent/late/half-day. System auto-calculates percentages.
*   **Edit Requests**: Teachers can request attendance corrections; Admins approve/reject.
*   **Leave Management System**: Students apply for leave; auto-reflects in attendance upon approval.
*   **Medical Certificates**: Upload & review workflow. Approved certificates grant a +5% attendance bonus.
*   **Semester Transition**: 
    *   **End Semester**: Auto-calculates final eligibility, locks system, and archives data.
    *   **Start New Semester**: Secure data cleanup (wipes daily logs, keeps records) for a fresh start.

### 📢 Communication Hub
*   **Media-Rich Announcements**: Post updates with images/videos/links.
*   **Targeted Audience**: Send to "All", "Teachers Only", or specific "Classes".
*   **Smart Notifications**: Real-time unread indicators (Bell badge, Sidebar dot) for all users.

### 🛡️ Security & Policy
*   **Desktop-Only Enforcement**: Blocks mobile/tablet access to ensure data layout integrity.
*   **Secure Authentication**: BCrypt password hashing, session hardening, and login history logging (IP/Device).
*   **Audit Logging**: Tracks critical actions (Semester changes, User bans).

---

## 🛠️ Technology Stack

*   **Frontend**: HTML5, CSS3 (Custom Glassmorphism Design System), JavaScript (ES6+), FontAwesome.
*   **Backend**: PHP (Vanilla, Object-Oriented style).
*   **Database**: MySQL / MariaDB.
*   **Server**: Apache (XAMPP/WAMP/LAMP).

---

## 📂 Project Structure

```
CampusAuth/
├── admin/                 # Admin Dashboard & Management Pages
├── teacher/               # Teacher Dashboard & Tools
├── student/               # Student Portal
├── parent/                # Parent Portal
├── includes/              # Shared Components (Sidebar, Header, Config)
├── assets/
├── css/               # Main Style System (style.css)
├── uploads/               # Stored Medical Certs & Announcement Media
├── config.php             # Database Connection & Helper Functions
├── index.php              # Login Page (with Mobile Block)
├── mobile_restriction.php # Mobile Block Landing Page
└── update_db_v*.php       # Database Migration Scripts
```

---

## ⚙️ Installation & Setup

1.  **Clone the Repository**
    ```bash
    git clone https://github.com/nadeemmhdm/CampusAuth2.0.git
    cd campusauth
    ```

2.  **Database Setup**
    *   Create a MySQL database (e.g., `campus_db`).
    *   Import the SQL schema file (or run the `update_db_vX.php` scripts in sequence v1 -> v6).

3.  **Configure Connection**
    *   Open `config.php`.
    *   Update database credentials:
        ```php
        $db_host = 'localhost';
        $db_user = 'root';
        $db_pass = '';
        $db_name = 'campus_db';
        ```

4.  **Run Application**
    *   Host the files on a PHP-enabled server (e.g., put in `htdocs` for XAMPP).
    *   Access via browser: `http://localhost/campusauth/`

---

## 🔐 Default Login Credentials (Demo)

*   **Admin**: `admin` / (Your configured password)
*   **Teacher**: `teacher_demo` / `password`
*   **Student**: `student_demo` / `password`

---

## 📸 Screenshots

**Login Page**
 <img width="1755" height="840" alt="image" src="https://github.com/user-attachments/assets/fa49ee96-113e-4cf6-9124-c0fccc6a9d2a" />
**Dashboard**
  <img width="1755" height="1447" alt="image" src="https://github.com/user-attachments/assets/c1c2acb4-9464-4add-ac57-d5e9db127143" />
**Announcements**
  <img width="1755" height="1575" alt="image" src="https://github.com/user-attachments/assets/0ea90677-d939-4567-9dfd-39d23d5b68ee" />
 

---

## ⚠️ Important Notes

*   **Mobile Restriction**: Attempting to view the site on a screen width < 1024px will trigger the "Desktop Only" lock screen.
*   **Semester End**: This is a destructive action for daily logs. Ensure you have exported reports before clicking "Start New Semester".

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
